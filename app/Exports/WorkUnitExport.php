<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class WorkUnitExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{q?: string, status?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<WorkUnit> */
    public function query(): Builder
    {
        return WorkUnit::query()
            ->with(['parent'])
            ->when(($this->filters['status'] ?? null) === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when(($this->filters['status'] ?? null) === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when(! empty($this->filters['q'] ?? null), function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy('code');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['code', 'name', 'parent_code', 'status', 'created_at'];
    }

    /** @param WorkUnit $row */
    public function map($row): array
    {
        return [
            (string) $row->code,
            (string) $row->name,
            (string) ($row->parent->code ?? ''),
            $row->is_active ? 'aktif' : 'nonaktif',
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
