<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Officer;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class OfficerImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        $units = WorkUnit::query()->pluck('id', 'code');

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $unitCode = trim((string) ($row['unit_code'] ?? ''));

            if ($code === '' || ! $units->has($unitCode)) {
                continue;
            }

            if (Officer::where('code', $code)->exists()) {
                continue;
            }

            Officer::create([
                'code' => $code,
                'name' => trim((string) ($row['name'] ?? '')),
                'work_unit_id' => (int) $units->get($unitCode),
                'status' => strtoupper(trim((string) ($row['status'] ?? 'ACTIVE'))),
            ]);

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32'],
            'name' => ['required', 'string', 'max:150'],
            'unit_code' => ['required', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:ACTIVE,INACTIVE,active,inactive'],
        ];
    }
}
