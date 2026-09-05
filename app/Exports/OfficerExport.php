<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Officer;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class OfficerExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{search?: string, status?: string, work_unit_id?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<Officer> */
    public function query(): Builder
    {
        return Officer::query()
            ->with(['workUnit'])
            ->withCount('aliases')
            ->when(isset($this->filters['work_unit_id']) && is_numeric($this->filters['work_unit_id']), fn ($q) => $q->where('work_unit_id', (int) $this->filters['work_unit_id']))
            ->when(isset($this->filters['status']) && in_array($this->filters['status'], Officer::STATUSES, true), fn ($q) => $q->where('status', $this->filters['status']))
            ->when(! empty($this->filters['search'] ?? null), fn ($q) => $q->search((string) $this->filters['search']))
            ->orderBy('code');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['code', 'name', 'unit_code', 'status', 'aliases_count', 'created_at'];
    }

    /** @param Officer $row */
    public function map($row): array
    {
        return [
            (string) $row->code,
            (string) $row->name,
            (string) ($row->workUnit->code ?? ''),
            (string) $row->status,
            (int) $row->aliases_count,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
