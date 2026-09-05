<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class WorkUnitImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        $parents = WorkUnit::query()->pluck('id', 'code');

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            $parentCode = trim((string) ($row['parent_code'] ?? ''));

            if ($code === '' || WorkUnit::where('code', $code)->exists()) {
                continue;
            }

            $parentId = null;
            if ($parentCode !== '') {
                if (! $parents->has($parentCode)) {
                    continue;
                }
                $parentId = (int) $parents->get($parentCode);
            }

            $unit = WorkUnit::create([
                'code' => $code,
                'name' => trim((string) ($row['name'] ?? '')),
                'parent_id' => $parentId,
                'is_active' => strtolower(trim((string) ($row['status'] ?? 'aktif'))) !== 'nonaktif',
            ]);

            $parents->put($code, $unit->getKey());

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]{1,32}$/'],
            'name' => ['required', 'string', 'max:150'],
            'parent_code' => ['nullable', 'string', 'max:32'],
            'status' => ['nullable', 'string', 'in:aktif,nonaktif,AKTIF,NONAKTIF'],
        ];
    }
}
