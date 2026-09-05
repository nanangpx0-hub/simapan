<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class RegionExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{level?: string, parent_id?: string, status?: string, q?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<Region> */
    public function query(): Builder
    {
        return Region::query()
            ->with(['parent'])
            ->when(isset($this->filters['level']) && in_array($this->filters['level'], Region::LEVELS, true), fn ($q) => $q->where('level', $this->filters['level']))
            ->when(isset($this->filters['parent_id']) && is_numeric($this->filters['parent_id']), fn ($q) => $q->where('parent_id', (int) $this->filters['parent_id']))
            ->when(($this->filters['status'] ?? null) === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when(($this->filters['status'] ?? null) === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when(! empty($this->filters['q'] ?? null), function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)
                        ->orWhere('full_code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy('full_code');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['full_code', 'code', 'name', 'level', 'parent_full_code', 'status', 'created_at'];
    }

    /** @param Region $row */
    public function map($row): array
    {
        return [
            (string) $row->full_code,
            (string) $row->code,
            (string) $row->name,
            (string) $row->level,
            (string) ($row->parent->full_code ?? ''),
            $row->is_active ? 'aktif' : 'nonaktif',
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
