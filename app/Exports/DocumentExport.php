<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Document;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DocumentExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{status?: string, document_type_id?: string, q?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<Document> */
    public function query(): Builder
    {
        return Document::query()
            ->with(['type', 'allocation', 'dsrtSample'])
            ->when(isset($this->filters['status']) && in_array($this->filters['status'], Document::STATUSES, true), fn ($q) => $q->where('status', $this->filters['status']))
            ->when(isset($this->filters['document_type_id']) && is_numeric($this->filters['document_type_id']), fn ($q) => $q->where('document_type_id', (int) $this->filters['document_type_id']))
            ->when(! empty($this->filters['q'] ?? null), function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('document_number', 'like', $keyword)->orWhere('title', 'like', $keyword);
                });
            })
            ->orderBy('document_number');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['document_number', 'title', 'type_code', 'allocation_nks', 'dsrt_nurt', 'quantity', 'status', 'created_at'];
    }

    /** @param Document $row */
    public function map($row): array
    {
        return [
            (string) ($row->document_number ?? ''),
            (string) $row->title,
            (string) ($row->type->code ?? ''),
            (string) ($row->allocation->nks ?? ''),
            (string) ($row->dsrtSample->nurt ?? ''),
            (int) $row->quantity,
            (string) $row->status,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
