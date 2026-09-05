<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\DsrtSample;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DsrtSampleImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function __construct(private int $allocationId, private int $userId) {}

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $nus = trim((string) ($row['nus'] ?? ''));
            $nurt = trim((string) ($row['nurt'] ?? ''));
            $krt = trim((string) ($row['krt_name'] ?? $row['krt'] ?? ''));

            if ($nus === '' || $nurt === '' || $krt === '') {
                continue;
            }

            $duplicate = DsrtSample::query()
                ->where('allocation_id', $this->allocationId)
                ->where(function ($q) use ($nus, $nurt): void {
                    $q->where('nus', $nus)->orWhere('nurt', $nurt);
                })
                ->exists();

            if ($duplicate) {
                continue;
            }

            DsrtSample::create([
                'allocation_id' => $this->allocationId,
                'nus' => $nus,
                'nurt' => $nurt,
                'krt_name' => $krt,
                'enumeration_status' => 'PENDING',
                'record_status' => 'DRAFT',
                'created_by' => $this->userId,
            ]);

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'nus' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'nurt' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'krt_name' => ['nullable', 'string', 'max:255'],
            'krt' => ['nullable', 'string', 'max:255'],
        ];
    }
}
