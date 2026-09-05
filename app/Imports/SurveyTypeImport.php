<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\SurveyType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SurveyTypeImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));

            if ($code === '' || SurveyType::where('code', $code)->exists()) {
                continue;
            }

            SurveyType::create([
                'code' => $code,
                'name' => trim((string) ($row['name'] ?? '')),
                'is_active' => strtolower(trim((string) ($row['status'] ?? 'aktif'))) !== 'nonaktif',
            ]);

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]{1,32}$/'],
            'name' => ['required', 'string', 'max:150'],
            'status' => ['nullable', 'string', 'in:aktif,nonaktif,AKTIF,NONAKTIF'],
        ];
    }
}
