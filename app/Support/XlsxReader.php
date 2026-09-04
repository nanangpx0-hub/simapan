<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;
use ZipArchive;

/**
 * Pembaca XLSX minimal tanpa dependency tambahan.
 * Mendukung shared strings, inline strings, boolean, angka, dan
 * konversi serial date Excel berdasarkan numFmt pada styles.xml.
 */
class XlsxReader
{
    private ZipArchive $zip;

    /** @var list<string> */
    private array $sheetNames = [];

    /** @var list<string> path sheet dalam zip */
    private array $sheetPaths = [];

    /** @var list<string> */
    private array $sharedStrings = [];

    /** @var array<int, bool> index style -> apakah format tanggal */
    private array $dateStyles = [];

    public function __construct(string $path)
    {
        if (! is_file($path)) {
            throw new RuntimeException("File tidak ditemukan: {$path}");
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path, ZipArchive::RDONLY);

        if ($opened !== true) {
            throw new RuntimeException("Gagal membuka XLSX (kode {$opened}): {$path}");
        }

        $this->zip = $zip;
        $this->loadSharedStrings();
        $this->loadDateStyles();
        $this->loadSheets();
    }

    public function __destruct()
    {
        if (isset($this->zip)) {
            $this->zip->close();
        }
    }

    /**
     * @return list<string>
     */
    public function sheetNames(): array
    {
        return $this->sheetNames;
    }

    /**
     * Semua baris sebuah sheet, keyed nomor kolom (1-based).
     *
     * @return list<array<int, string|null>>
     */
    public function rows(int $sheetIndex): array
    {
        if (! isset($this->sheetPaths[$sheetIndex])) {
            throw new RuntimeException("Sheet index tidak valid: {$sheetIndex}");
        }

        $xml = $this->zip->getFromName($this->sheetPaths[$sheetIndex]);

        if ($xml === false) {
            throw new RuntimeException("Gagal membaca sheet: {$this->sheetPaths[$sheetIndex]}");
        }

        $sheet = new \SimpleXMLIterator($xml);
        $rows = [];

        foreach ($sheet->sheetData->row as $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $ref = (string) ($cell['r'] ?? '');

                if ($ref === '' || ! preg_match('/^([A-Z]+)/', $ref, $m)) {
                    continue;
                }

                $values[$this->columnNumber($m[1])] = $this->cellValue($cell);
            }

            $max = $values === [] ? 0 : max(array_keys($values));
            $filled = [];

            for ($i = 1; $i <= $max; $i++) {
                $filled[$i] = $values[$i] ?? null;
            }

            $rows[] = $filled;
        }

        return $rows;
    }

    private function cellValue(\SimpleXMLElement $cell): ?string
    {
        $type = (string) ($cell['t'] ?? '');
        $styleId = (int) ($cell['s'] ?? 0);

        if ($type === 'inlineStr') {
            $text = '';

            foreach ($cell->is->children() as $child) {
                $text .= (string) $child;
            }

            return $text !== '' ? $text : null;
        }

        $raw = isset($cell->v) ? (string) $cell->v : null;

        if ($raw === null || $raw === '') {
            return null;
        }

        return match ($type) {
            's' => $this->sharedStrings[(int) $raw] ?? null,
            'b' => $raw === '1' ? '1' : '0',
            'str' => $raw,
            default => $this->isDateFormat($styleId) ? $this->serialToDate($raw) : $raw,
        };
    }

    private function isDateFormat(int $styleId): bool
    {
        return $this->dateStyles[$styleId] ?? false;
    }

    private function serialToDate(string $serial): string
    {
        $days = (int) floor((float) $serial);
        $seconds = (int) round(((float) $serial - $days) * 86400);
        $epoch = \DateTimeImmutable::createFromFormat('!Y-m-d', '1899-12-30');

        if ($epoch === false) {
            return $serial;
        }

        $date = $epoch->modify("+{$days} days")->modify("+{$seconds} seconds");

        return $seconds === 0 ? $date->format('Y-m-d') : $date->format('Y-m-d H:i:s');
    }

    private function columnNumber(string $letters): int
    {
        $number = 0;

        for ($i = 0, $n = strlen($letters); $i < $n; $i++) {
            $number = $number * 26 + (ord($letters[$i]) - 64);
        }

        return $number;
    }

    private function loadSharedStrings(): void
    {
        $xml = $this->zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return;
        }

        $shared = new \SimpleXMLIterator($xml);

        foreach ($shared as $si) {
            $text = (string) ($si->t ?? '');

            if ($text === '') {
                foreach ($si->r as $run) {
                    $text .= (string) $run->t;
                }
            }

            $this->sharedStrings[] = $text;
        }
    }

    private function loadDateStyles(): void
    {
        $xml = $this->zip->getFromName('xl/styles.xml');

        if ($xml === false) {
            return;
        }

        $styles = new \SimpleXMLIterator($xml);

        // Format tanggal builtin Excel
        $builtinDate = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];
        $customDate = [];

        if (isset($styles->numFmts)) {
            foreach ($styles->numFmts->numFmt as $numFmt) {
                $code = strtolower((string) $numFmt['formatCode']);

                if (preg_match('/[dy]/', $code) && ! preg_match('/\[|general|0\.|#/', $code)) {
                    $customDate[(int) $numFmt['numFmtId']] = true;
                }
            }
        }

        if (isset($styles->cellXfs)) {
            foreach ($styles->cellXfs->xf as $index => $xf) {
                $numFmtId = (int) ($xf['numFmtId'] ?? 0);
                $this->dateStyles[(int) $index] = in_array($numFmtId, $builtinDate, true)
                    || isset($customDate[$numFmtId]);
            }
        }
    }

    private function loadSheets(): void
    {
        $workbook = new \SimpleXMLIterator((string) $this->zip->getFromName('xl/workbook.xml'));
        $rels = new \SimpleXMLIterator((string) $this->zip->getFromName('xl/_rels/workbook.xml.rels'));

        $targets = [];

        foreach ($rels as $rel) {
            $targets[(string) $rel['Id']] = (string) $rel['Target'];
        }

        foreach ($workbook->sheets->sheet as $sheet) {
            $this->sheetNames[] = (string) $sheet['name'];

            $rid = (string) ($sheet->attributes('r', true)['id'] ?? '');
            $target = $targets[$rid] ?? '';

            if ($target === '') {
                throw new RuntimeException("Relasi sheet tidak ditemukan untuk {$rid}");
            }

            if (! str_starts_with($target, 'xl/')) {
                $target = 'xl/'.ltrim($target, '/');
            }

            $this->sheetPaths[] = $target;
        }
    }
}
