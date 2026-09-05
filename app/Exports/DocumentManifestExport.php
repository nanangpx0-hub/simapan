<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DocumentManifest;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentManifestExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /** @param  array{status?: string, q?: string}  $filters */
    public function __construct(private array $filters = []) {}

    /** @return Builder<DocumentManifest> */
    public function query(): Builder
    {
        return DocumentManifest::query()
            ->with(['fromUnit', 'toUnit'])
            ->withCount('items')
            ->when(isset($this->filters['status']) && in_array($this->filters['status'], DocumentManifest::STATUSES, true), fn ($q) => $q->where('status', $this->filters['status']))
            ->when(! empty($this->filters['q'] ?? null), function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $q->where('manifest_number', 'like', $keyword);
            })
            ->orderBy('manifest_number');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['manifest_number', 'from_unit_code', 'to_unit_code', 'items_count', 'status', 'created_at'];
    }

    /** @param DocumentManifest $row */
    public function map($row): array
    {
        return [
            (string) $row->manifest_number,
            (string) ($row->fromUnit->code ?? ''),
            (string) ($row->toUnit->code ?? ''),
            (int) $row->items_count,
            (string) $row->status,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
