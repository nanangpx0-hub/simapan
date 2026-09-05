<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class AllocationImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function __construct(private ?int $creatorId = null) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $nks = trim((string) ($row['nks'] ?? ''));
            $periodCode = trim((string) ($row['period_code'] ?? ''));
            $villageCode = trim((string) ($row['village_full_code'] ?? ''));
            $slsCode = trim((string) ($row['sls_code'] ?? ''));
            $subSlsCode = trim((string) ($row['sub_sls_code'] ?? ''));

            if ($nks === '' || $periodCode === '' || $villageCode === '') {
                continue;
            }

            $period = SurveyPeriod::where('code', $periodCode)->first();
            $village = Region::where('full_code', $villageCode)
                ->where('level', 'DESA_KELURAHAN_NAGARI')
                ->first();

            if (! $period || ! $village) {
                continue;
            }

            if (Allocation::where('survey_period_id', $period->getKey())->where('nks', $nks)->exists()) {
                continue;
            }

            Allocation::create([
                'survey_period_id' => $period->getKey(),
                'village_region_id' => $village->getKey(),
                'nks' => $nks,
                'sls_code' => $slsCode !== '' ? $slsCode : null,
                'sub_sls_code' => $subSlsCode !== '' ? $subSlsCode : null,
                'sls_name' => $slsCode !== '' ? $slsCode : $nks,
                'status' => 'DRAFT',
                'created_by' => $this->creatorId,
            ]);

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'nks' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'period_code' => ['required', 'string', 'max:64'],
            'village_full_code' => ['required', 'string', 'max:64'],
            'sls_code' => ['nullable', 'string', 'max:32'],
            'sub_sls_code' => ['nullable', 'string', 'max:32'],
        ];
    }
}
