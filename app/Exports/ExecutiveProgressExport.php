<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Allocation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExecutiveProgressExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    use Exportable;

    public function collection(): Collection
    {
        $allocations = Allocation::query()->with(['village.parent'])->orderBy('nks')->get();

        /** @var array<int, array{code: string, name: string, total: int, done: int}> $grouped */
        $grouped = [];

        foreach ($allocations as $allocation) {
            $village = $allocation->village;
            $kecamatan = $village?->parent ?? $village;
            $key = $kecamatan?->getKey() ?? 0;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'code' => (string) ($kecamatan?->full_code ?? '—'),
                    'name' => (string) ($kecamatan?->name ?? 'Tanpa wilayah'),
                    'total' => 0,
                    'done' => 0,
                ];
            }

            $grouped[$key]['total']++;

            if ($allocation->status === 'COMPLETED') {
                $grouped[$key]['done']++;
            }
        }

        return collect(array_values(array_map(
            fn (array $group): array => [
                $group['code'],
                $group['name'],
                $group['total'],
                $group['done'],
                $group['total'] - $group['done'],
                $group['total'] > 0 ? round($group['done'] / $group['total'] * 100, 1) : 0.0,
            ],
            $grouped
        )));
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['kecamatan_code', 'kecamatan_name', 'nks_total', 'nks_selesai', 'nks_sisa', 'progres_persen'];
    }
}
