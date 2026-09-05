<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SurveyPeriodImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        $types = SurveyType::query()->pluck('id', 'code');
        $creatorId = auth()->id();

        if ($creatorId === null) {
            return;
        }

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $typeCode = trim((string) ($row['survey_type_code'] ?? ''));
            $periodType = strtoupper(trim((string) ($row['period_type'] ?? '')));

            if ($code === '' || ! $types->has($typeCode)) {
                continue;
            }

            if (SurveyPeriod::where('code', $code)->exists()) {
                continue;
            }

            if (! in_array($periodType, SurveyPeriod::PERIOD_TYPES, true)) {
                continue;
            }

            $periodNumber = $row['period_number'] !== null && trim((string) $row['period_number']) !== ''
                ? (int) $row['period_number']
                : null;

            if ($periodType === 'TAHUNAN') {
                if ($periodNumber !== null) {
                    continue;
                }
            } elseif (! in_array($periodNumber, SurveyPeriod::PERIOD_NUMBERS[$periodType] ?? [], true)) {
                continue;
            }

            $status = strtoupper(trim((string) ($row['status'] ?? 'DRAFT')));
            if (! in_array($status, ['DRAFT', 'ARCHIVED'], true)) {
                continue;
            }

            SurveyPeriod::create([
                'code' => $code,
                'survey_type_id' => (int) $types->get($typeCode),
                'name' => trim((string) ($row['name'] ?? '')),
                'period_type' => $periodType,
                'period_number' => $periodNumber,
                'year' => (int) $row['year'],
                'start_date' => trim((string) ($row['start_date'] ?? '')),
                'end_date' => trim((string) ($row['end_date'] ?? '')),
                'status' => $status,
                'created_by' => $creatorId,
            ]);

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]{1,32}$/'],
            'survey_type_code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:150'],
            'period_type' => ['required', 'string', Rule::in(SurveyPeriod::PERIOD_TYPES)],
            'period_number' => ['nullable', 'integer'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:DRAFT,ARCHIVED,draft,archived'],
        ];
    }
}
