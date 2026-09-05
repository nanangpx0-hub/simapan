<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Region;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class RegionImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $level = trim((string) ($row['level'] ?? ''));
            $code = trim((string) ($row['code'] ?? ''));
            $parentFullCode = trim((string) ($row['parent_full_code'] ?? ''));

            if ($code === '' || ! in_array($level, Region::LEVELS, true)) {
                continue;
            }

            $parent = null;
            if ($level === 'PROVINSI') {
                if ($parentFullCode !== '') {
                    continue;
                }
            } else {
                if ($parentFullCode === '') {
                    continue;
                }
                $parent = Region::query()->where('full_code', $parentFullCode)->first();
                if (! $parent instanceof Region) {
                    continue;
                }
                $expected = Region::PARENT_LEVELS[$level] ?? null;
                if ($expected === null || $parent->level !== $expected) {
                    continue;
                }
            }

            $fullCode = $parent instanceof Region ? $parent->full_code.$code : $code;

            if (Region::where('full_code', $fullCode)->exists()) {
                continue;
            }

            $region = new Region([
                'parent_id' => $parent?->getKey(),
                'level' => $level,
                'code' => $code,
                'name' => trim((string) ($row['name'] ?? '')),
                'is_active' => strtolower(trim((string) ($row['status'] ?? 'aktif'))) !== 'nonaktif',
            ]);
            $region->full_code = $fullCode;
            $region->save();

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'name' => ['required', 'string', 'max:150'],
            'level' => ['required', 'string', Rule::in(Region::LEVELS)],
            'parent_full_code' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:aktif,nonaktif,AKTIF,NONAKTIF'],
        ];
    }
}
