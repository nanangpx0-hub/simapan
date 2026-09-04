<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use App\Support\NameNormalizer;
use App\Support\XlsxReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ImportAlokasi extends Command
{
    /**
     * Posisi kolom pada sheet "Daftar alokasi" (berbasis 1) sesuai kontrak berkas.
     */
    private const COL_NO = 1;

    private const COL_KEC_CODE = 2;

    private const COL_KEC_NAME = 3;

    private const COL_DESA_CODE = 4;

    private const COL_DESA_NAME = 5;

    private const COL_NKS = 6;

    private const COL_RT = 8;

    private const COL_PCL = 9;

    private const COL_PML = 10;

    private const COL_PENGOLAH = 11;

    private const SHEET_ALOKASI = 'Daftar alokasi';

    private const SHEET_RINCIAN = 'Rincian';

    protected $signature = 'simapan:import-alokasi
        {--file=data/Alokasi.xlsx : Path file Excel relatif ke root proyek}
        {--period= : Kode survey period tujuan, mis. SUSENAS-S1-2026 (wajib)}
        {--ensure-period : Buat period berstatus DRAFT bila kode belum ada}
        {--actor-email=admin@simapan.test : Email user sebagai created_by/assigned_by}
        {--provinsi-code=35 : Kode provinsi untuk rantai wilayah}
        {--provinsi-name= : Nama provinsi (wajib bila provinsi belum ada di master wilayah)}
        {--kabkota-code=09 : Kode kabupaten/kota untuk rantai wilayah}
        {--kabkota-name= : Nama kabupaten/kota (wajib bila belum ada)}
        {--dry-run : Validasi dan laporan tanpa menulis ke database}
        {--skip-assignments : Impor alokasi saja tanpa penugasan PCL/PML/Pengolah}
        {--force : Wajib untuk menjalankan impor di lingkungan produksi}';

    protected $description = 'Impor alokasi kegiatan dari Excel dengan validasi struktur, pembersihan data, transaksi, dan logging';

    private int $rowsTotal = 0;

    private int $rowsValid = 0;

    private int $rowsSkippedEmpty = 0;

    private int $rowsDuplicate = 0;

    private int $rowsError = 0;

    private int $allocationsCreated = 0;

    private int $allocationsExisting = 0;

    private int $assignmentsCreated = 0;

    private int $assignmentsExisting = 0;

    private int $assignmentsConflict = 0;

    private int $officersCreated = 0;

    private int $regionsCreated = 0;

    /** @var list<string> */
    private array $errors = [];

    /** @var array<string, Region> */
    private array $regionCache = [];

    /** @var array<string, Officer> */
    private array $officerCache = [];

    /** @var array<string, int> kode prefix -> nomor urut terakhir */
    private array $officerSequence = [];

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Impor di produksi wajib memakai --force setelah staging tervalidasi.');

            return self::FAILURE;
        }

        $periodCode = (string) $this->option('period');

        if ($periodCode === '') {
            $this->error('Opsi --period wajib diisi, mis. --period=SUSENAS-S1-2026');

            return self::FAILURE;
        }

        try {
            $path = base_path((string) $this->option('file'));
            $reader = new XlsxReader($path);

            $this->reportStructure($reader);
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            Log::error('import.alokasi: gagal membuka berkas', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        // === Langkah 1-2: validasi struktur & pembersihan baris ===
        $records = $this->parseRows($reader);

        $this->info('');
        $this->table(
            ['Ukuran', 'Nilai'],
            [
                ['Baris dibaca', $this->rowsTotal],
                ['Baris kosong dibuang', $this->rowsSkippedEmpty],
                ['Baris valid', $this->rowsValid],
                ['Duplikat NKS dalam berkas', $this->rowsDuplicate],
                ['Baris error', $this->rowsError],
                ['Sheet Rincian (kredensial)', 'DILEWATI — kolom email/password adalah Data Terlarang'],
            ],
        );

        if ($this->rowsError > 0) {
            $this->error("Impor dibatalkan: {$this->rowsError} baris tidak valid (lihat daftar di atas dan storage/logs).");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            $this->info('DRY-RUN: tidak ada perubahan database.');

            return self::SUCCESS;
        }

        // === Langkah 3-4: impor dalam satu transaksi ===
        try {
            DB::transaction(function () use ($records, $periodCode): void {
                $actor = $this->resolveActor();
                $period = $this->resolvePeriod($periodCode);
                $this->importRecords($records, $period, $actor);
            });
        } catch (\Throwable $e) {
            $this->error('IMPORT GAGAL — transaksi di-ROLLBACK: '.$e->getMessage());
            Log::error('import.alokasi: rollback', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        // === Langkah 5: verifikasi pasca-impor ===
        return $this->verify($periodCode);
    }

    /**
     * Langkah 1: validasi struktur berkas (sheet, header, sheet kredensial).
     */
    private function reportStructure(XlsxReader $reader): void
    {
        $names = $reader->sheetNames();
        $this->info('Sheet ditemukan: '.implode(', ', $names));

        $alokasiIndex = $this->findSheet($names, self::SHEET_ALOKASI);

        if ($alokasiIndex === null) {
            throw new RuntimeException('Sheet "'.self::SHEET_ALOKASI.'" tidak ditemukan. Sheet tersedia: '.implode(', ', $names));
        }

        if ($this->findSheet($names, self::SHEET_RINCIAN) !== null) {
            $this->warn('Sheet "'.self::SHEET_RINCIAN.'" terdeteksi dan DIKECUALIKAN dari impor: kolom email/password adalah Data Terlarang (docs/data-classification.md).');
            Log::warning('import.alokasi: sheet Rincian dikecualikan karena memuat kolom kredensial');
        }

        $header = $reader->rows($alokasiIndex)[0] ?? [];

        $expectations = [
            self::COL_NO => 'No',
            self::COL_KEC_CODE => 'Kecamatan',
            self::COL_DESA_CODE => 'Desa',
            self::COL_NKS => 'NKS',
            self::COL_RT => 'Rumah Tangga',
            self::COL_PCL => 'PCL',
            self::COL_PML => 'PML',
            self::COL_PENGOLAH => 'Pengolah',
        ];

        foreach ($expectations as $column => $keyword) {
            $actual = trim((string) ($header[$column] ?? ''));

            if (mb_stripos($actual, $keyword) === false) {
                throw new RuntimeException(
                    "Header tidak sesuai skema: kolom {$column} diharapkan mengandung \"{$keyword}\", ditemukan \"{$actual}\"."
                );
            }
        }

        $this->info('Header sheet "'.self::SHEET_ALOKASI.'" sesuai kontrak (kolom nama kecamatan/desa pada posisi 3 dan 5).');
    }

    /**
     * Langkah 2: bersihkan dan validasi tiap baris data.
     *
     * @return list<array<string, string|null>>
     */
    private function parseRows(XlsxReader $reader): array
    {
        $alokasiIndex = $this->findSheet($reader->sheetNames(), self::SHEET_ALOKASI);
        $rows = $reader->rows($alokasiIndex);
        array_shift($rows); // buang header

        $records = [];
        $seenNks = [];

        foreach ($rows as $offset => $row) {
            $rowNumber = $offset + 2; // +1 header, +1 basis 1
            $no = $this->cleanText($row[self::COL_NO] ?? null);

            if ($no === null && $this->rowIsEmpty($row)) {
                $this->rowsSkippedEmpty++;

                continue;
            }

            $this->rowsTotal++;

            $nks = $this->cleanNumericCode($row[self::COL_NKS] ?? null);
            $kecCode = $this->cleanNumericCode($row[self::COL_KEC_CODE] ?? null);
            $kecName = $this->cleanText($row[self::COL_KEC_NAME] ?? null);
            $desaCode = $this->cleanNumericCode($row[self::COL_DESA_CODE] ?? null);
            $desaName = $this->cleanText($row[self::COL_DESA_NAME] ?? null);
            $rt = $this->cleanNumber($row[self::COL_RT] ?? null);
            $pcl = $this->cleanText($row[self::COL_PCL] ?? null);
            $pml = $this->cleanText($row[self::COL_PML] ?? null);
            $pengolah = $this->cleanText($row[self::COL_PENGOLAH] ?? null);

            $rowErrors = [];

            if ($nks === null) {
                $rowErrors[] = 'NKS kosong/tidak valid';
            } elseif (strlen($nks) > 32 || ! preg_match('/^[A-Za-z0-9._-]+$/', $nks)) {
                $rowErrors[] = "NKS \"{$nks}\" tidak memenuhi pola kode (maks 32, alfanumerik/._-)";
            }

            if ($kecCode === null || $kecName === null) {
                $rowErrors[] = 'Kode/nama kecamatan kosong';
            }

            if ($desaCode === null || $desaName === null) {
                $rowErrors[] = 'Kode/nama desa kosong';
            }

            if ($nks !== null && isset($seenNks[$nks])) {
                $this->rowsDuplicate++;
                $this->logRowError($rowNumber, $nks, "NKS duplikat dengan baris {$seenNks[$nks]} (dilewati)");
                $this->rowsTotal--;
                $this->rowsError--;

                continue;
            }

            if ($rowErrors !== []) {
                $this->rowsError++;
                $message = implode('; ', $rowErrors);
                $this->errors[] = "Baris {$rowNumber}: {$message}";
                $this->logRowError($rowNumber, $nks ?? '?', $message);

                continue;
            }

            $seenNks[$nks] = $rowNumber;
            $this->rowsValid++;

            $records[] = [
                'row' => $rowNumber,
                'nks' => $nks,
                'kec_code' => $kecCode,
                'kec_name' => $kecName,
                'desa_code' => $desaCode,
                'desa_name' => $desaName,
                'rt' => $rt,
                'pcl' => $pcl,
                'pml' => $pml,
                'pengolah' => $pengolah,
            ];
        }

        return $records;
    }

    /**
     * Langkah 3a: actor (created_by/assigned_by) harus user yang ada.
     */
    private function resolveActor(): User
    {
        $email = (string) $this->option('actor-email');
        $actor = User::query()->where('email', $email)->first();

        if ($actor === null) {
            throw new RuntimeException("User actor dengan email {$email} tidak ditemukan.");
        }

        return $actor;
    }

    /**
     * Langkah 3b: periode tujuan; opsional dibuat DRAFT dari pola kode TIPE-Sx/Tx-YYYY.
     */
    private function resolvePeriod(string $periodCode): SurveyPeriod
    {
        $period = SurveyPeriod::query()->where('code', $periodCode)->first();

        if ($period instanceof SurveyPeriod) {
            return $period;
        }

        if (! $this->option('ensure-period')) {
            throw new RuntimeException(
                "Period {$periodCode} tidak ada. Buat dulu via UI/Seeder atau pakai --ensure-period."
            );
        }

        if (! preg_match('/^([A-Z]+)-(S|T)(\d)-(\d{4})$/', $periodCode, $m)) {
            throw new RuntimeException("Kode period {$periodCode} tidak sesuai pola TIPE-S1/T1-YYYY untuk --ensure-period.");
        }

        $type = SurveyType::query()->where('code', $m[1])->first();

        if ($type === null) {
            throw new RuntimeException("Survey type {$m[1]} tidak ditemukan (jalankan SurveyTypeSeeder).");
        }

        $periodType = $m[2] === 'S' ? 'SEMESTER' : 'TRIWULAN';
        $number = (int) $m[3];
        $year = (int) $m[4];
        $months = $periodType === 'SEMESTER'
            ? [1 => [1, 6], 2 => [7, 12]]
            : [1 => [1, 3], 2 => [4, 6], 3 => [7, 9], 4 => [10, 12]];

        if (! isset($months[$number])) {
            throw new RuntimeException("Nomor period {$number} tidak valid untuk {$periodType}.");
        }

        [$startMonth, $endMonth] = $months[$number];
        $startDate = sprintf('%04d-%02d-01', $year, $startMonth);
        $endDate = sprintf('%04d-%02d-%02d', $year, $endMonth, (int) date('t', mktime(0, 0, 0, $endMonth, 1, $year)));

        $period = SurveyPeriod::create([
            'code' => $periodCode,
            'survey_type_id' => $type->getKey(),
            'name' => $type->name.' '.$periodType.' '.$number.' '.$year,
            'period_type' => $periodType,
            'period_number' => $number,
            'year' => $year,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'DRAFT',
            'created_by' => (int) User::query()->where('email', (string) $this->option('actor-email'))->value('id'),
        ]);

        $this->info("Period DRAFT dibuat: {$periodCode} ({$startDate} s.d. {$endDate}).");
        Log::info('import.alokasi: period dibuat', ['code' => $periodCode, 'status' => 'DRAFT']);

        return $period;
    }

    /**
     * Langkah 3c: impor seluruh rekaman bersih (region -> officer -> alokasi -> penugasan).
     *
     * @param  list<array<string, string|null>>  $records
     */
    private function importRecords(array $records, SurveyPeriod $period, User $actor): void
    {
        $provinsi = $this->resolveRegion('PROVINSI', (string) $this->option('provinsi-code'), null, (string) $this->option('provinsi-name'));
        $kabkota = $this->resolveRegion('KAB_KOTA', (string) $this->option('kabkota-code'), $provinsi->getKey(), (string) $this->option('kabkota-name'));
        $units = [
            'FIELD_OFFICER' => WorkUnit::query()->where('code', 'SOSIAL')->firstOrFail(),
            'FIELD_SUPERVISOR' => WorkUnit::query()->where('code', 'SOSIAL')->firstOrFail(),
            'PROCESSING_OFFICER' => WorkUnit::query()->where('code', 'PENGOLAHAN_LS')->firstOrFail(),
        ];

        foreach ($records as $record) {
            $kecamatan = $this->resolveRegion('KECAMATAN', $record['kec_code'], $kabkota->getKey(), $record['kec_name']);
            $desa = $this->resolveRegion('DESA_KELURAHAN_NAGARI', $record['desa_code'], $kecamatan->getKey(), $record['desa_name']);

            $notes = 'Jumlah RT: '.($record['rt'] ?? '—').'. Impor Alokasi.xlsx baris '.$record['row'];

            $allocation = Allocation::query()
                ->where('survey_period_id', $period->getKey())
                ->where('nks', $record['nks'])
                ->first();

            if ($allocation === null) {
                $allocation = Allocation::create([
                    'survey_period_id' => $period->getKey(),
                    'village_region_id' => $desa->getKey(),
                    'nks' => $record['nks'],
                    'sls_code' => null,
                    'sub_sls_code' => null,
                    'sls_name' => $record['desa_name'],
                    'status' => 'DRAFT',
                    'notes' => $notes,
                    'created_by' => $actor->getKey(),
                ]);
                $this->allocationsCreated++;
            } else {
                $this->allocationsExisting++;
            }

            if ($this->option('skip-assignments')) {
                continue;
            }

            $this->importAssignments($record, $allocation, $units, $actor);
        }

        Log::info('import.alokasi: impor selesai dalam transaksi', [
            'period' => $period->code,
            'allocations_created' => $this->allocationsCreated,
            'allocations_existing' => $this->allocationsExisting,
            'assignments_created' => $this->assignmentsCreated,
            'assignments_existing' => $this->assignmentsExisting,
            'assignments_conflict' => $this->assignmentsConflict,
            'officers_created' => $this->officersCreated,
            'regions_created' => $this->regionsCreated,
        ]);
    }

    /**
     * Penugasan PCL/PML/Pengolah per alokasi; konflik dengan penugasan aktif
     * berbeda petugas tidak ditimpa (non-destruktif), hanya dilaporkan.
     *
     * @param  array<string, string|null>  $record
     * @param  array<string, WorkUnit>  $units
     */
    private function importAssignments(array $record, Allocation $allocation, array $units, User $actor): void
    {
        $roles = [
            'FIELD_OFFICER' => $record['pcl'],
            'FIELD_SUPERVISOR' => $record['pml'],
            'PROCESSING_OFFICER' => $record['pengolah'],
        ];

        foreach ($roles as $role => $name) {
            if ($name === null || $name === '') {
                continue;
            }

            $officer = $this->resolveOfficer($name, $units[$role]);

            $existing = Assignment::query()
                ->where('allocation_id', $allocation->getKey())
                ->where('assignment_role', $role)
                ->where('is_active', true)
                ->first();

            if ($existing !== null) {
                if ((int) $existing->officer_id === (int) $officer->getKey()) {
                    $this->assignmentsExisting++;

                    continue;
                }

                $this->assignmentsConflict++;
                Log::warning('import.alokasi: penugasan aktif berbeda petugas, dilewati', [
                    'nks' => $record['nks'],
                    'assignment_role' => $role,
                    'allocation_id' => $allocation->getKey(),
                ]);

                continue;
            }

            Assignment::create([
                'allocation_id' => $allocation->getKey(),
                'officer_id' => $officer->getKey(),
                'assignment_role' => $role,
                'employment_category' => null,
                'is_active' => true,
                'started_at' => now(),
                'assigned_by' => $actor->getKey(),
            ]);
            $this->assignmentsCreated++;
        }
    }

    /**
     * Cari atau buat region pada rantai provinsi -> kab/kota -> kecamatan -> desa.
     */
    private function resolveRegion(string $level, string $code, ?int $parentId, ?string $name): Region
    {
        $cacheKey = $level.'|'.($parentId ?? 0).'|'.$code;

        if (isset($this->regionCache[$cacheKey])) {
            return $this->regionCache[$cacheKey];
        }

        $region = Region::query()
            ->where('level', $level)
            ->where('code', $code)
            ->where(fn ($query) => $parentId === null
                ? $query->whereNull('parent_id')
                : $query->where('parent_id', $parentId))
            ->first();

        if ($region === null) {
            if ($name === null || trim($name) === '') {
                throw new RuntimeException(
                    "Region {$level} kode {$code} belum ada di master wilayah; sediakan nama via opsi terkait."
                );
            }

            $region = Region::create([
                'parent_id' => $parentId,
                'level' => $level,
                'code' => $code,
                'name' => trim($name),
                'is_active' => true,
            ]);
            $this->regionsCreated++;
        }

        $this->regionCache[$cacheKey] = $region;

        return $region;
    }

    /**
     * Cari petugas berdasar nama ternormalisasi + unit kerja; buat bila belum ada
     * dengan kode tergenerasi (PPL-/PML-/PGO- + 4 digit). Tidak menyimpan kontak.
     */
    private function resolveOfficer(string $name, WorkUnit $unit): Officer
    {
        $normalized = NameNormalizer::normalize($name);
        $cacheKey = $unit->getKey().'|'.$normalized;

        if (isset($this->officerCache[$cacheKey])) {
            return $this->officerCache[$cacheKey];
        }

        $officer = Officer::withTrashed()
            ->where('normalized_name', $normalized)
            ->where('work_unit_id', $unit->getKey())
            ->first();

        if ($officer === null) {
            $officer = Officer::create([
                'code' => $this->nextOfficerCode($unit->code),
                'name' => trim($name),
                'work_unit_id' => $unit->getKey(),
                'status' => 'ACTIVE',
            ]);
            $this->officersCreated++;
        } elseif ($officer->trashed()) {
            $officer->restore();
        }

        $this->officerCache[$cacheKey] = $officer;

        return $officer;
    }

    private function nextOfficerCode(string $unitCode): string
    {
        $prefix = match ($unitCode) {
            'SOSIAL' => 'PPL',
            'PENGOLAHAN_LS' => 'PGO',
            'IPDS' => 'IPDS',
            default => 'OFF',
        };
        $current = $this->officerSequence[$prefix] ?? 0;

        if ($current === 0) {
            $last = Officer::withTrashed()->where('code', 'like', $prefix.'-%')->orderByDesc('code')->value('code');
            $current = $last !== null ? (int) substr((string) $last, strlen($prefix) + 1) : 0;
        }

        $current++;
        $this->officerSequence[$prefix] = $current;

        return $prefix.'-'.str_pad((string) $current, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Langkah 5: verifikasi pasca-impor — jumlah baris dan sampel data.
     */
    private function verify(string $periodCode): int
    {
        $period = SurveyPeriod::query()->where('code', $periodCode)->firstOrFail();
        $total = Allocation::query()->where('survey_period_id', $period->getKey())->count();

        $this->info('');
        $this->info('=== VERIFIKASI PASCA-IMPOR ===');
        $this->info("Alokasi pada period {$periodCode}: {$total} (baris valid berkas: {$this->rowsValid}, sudah ada sebelumnya: {$this->allocationsExisting})");
        $this->info("Alokasi dibuat: {$this->allocationsCreated} | Region dibuat: {$this->regionsCreated} | Petugas dibuat: {$this->officersCreated}");
        $this->info("Penugasan dibuat: {$this->assignmentsCreated} | sudah ada: {$this->assignmentsExisting} | konflik dilewati: {$this->assignmentsConflict}");

        $samples = Allocation::query()
            ->where('survey_period_id', $period->getKey())
            ->with(['village.parent', 'activeAssignments.officer'])
            ->orderBy('nks')
            ->limit(5)
            ->get();

        foreach ($samples as $sample) {
            $desa = $sample->village?->name ?? '?';
            $kec = $sample->village?->parent?->name ?? '?';
            $assignees = $sample->activeAssignments
                ->map(fn (Assignment $a): string => $a->assignment_role.'='.$a->officer?->code)
                ->implode(', ');

            $this->line("  NKS {$sample->nks} | {$kec} / {$desa} | {$assignees}");
        }

        if ($total < $this->rowsValid) {
            $this->error('VERIFIKASI GAGAL: jumlah alokasi di DB lebih kecil dari baris valid berkas.');

            return self::FAILURE;
        }

        Log::info('import.alokasi: verifikasi lulus', [
            'period' => $periodCode,
            'db_total' => $total,
            'file_valid' => $this->rowsValid,
        ]);

        $this->info('VERIFIKASI LULUS.');

        return self::SUCCESS;
    }

    private function findSheet(array $names, string $target): ?int
    {
        foreach ($names as $index => $name) {
            if (mb_strtolower(trim($name)) === mb_strtolower(trim($target))) {
                return (int) $index;
            }
        }

        return null;
    }

    private function rowIsEmpty(array $row): bool
    {
        return count(array_filter($row, fn ($value): bool => $value !== null && trim((string) $value) !== '')) === 0;
    }

    /** Bersihkan teks: trim, rapatkan spasi, hilangkan nilai kosong. */
    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $clean = trim(preg_replace('/\s+/u', ' ', $value) ?? '');

        return $clean === '' ? null : $clean;
    }

    /** Kode numerik: buang ".0" hasil format float Excel, pertahankan leading zero string. */
    private function cleanNumericCode(?string $value): ?string
    {
        $clean = $this->cleanText($value);

        if ($clean === null) {
            return null;
        }

        if (preg_match('/^(\d+)\.0+$/', $clean, $m)) {
            $clean = $m[1];
        }

        return $clean;
    }

    /** Angka: "83.0" -> "83"; nilai non-numerik dicatat di notes. */
    private function cleanNumber(?string $value): ?string
    {
        return $this->cleanNumericCode($value);
    }

    private function logRowError(int $rowNumber, string $nks, string $message): void
    {
        Log::error('import.alokasi: baris tidak valid', [
            'row' => $rowNumber,
            'nks' => $nks,
            'error' => $message,
        ]);
    }
}
