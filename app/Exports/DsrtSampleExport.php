<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\DsrtSample;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DsrtSampleExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /** @param  array{q?: string, record_status?: string, enumeration_status?: string}  $filters */
    public function __construct(private int $allocationId, private array $filters = []) {}

    /** @return Builder<DsrtSample> */
    public function query(): Builder
    {
        return DsrtSample::query()
            ->forAllocation($this->allocationId)
            ->when(isset($this->filters['record_status']) && in_array($this->filters['record_status'], DsrtSample::RECORD_STATUSES, true), fn ($q) => $q->where('record_status', $this->filters['record_status']))
            ->when(isset($this->filters['enumeration_status']) && in_array($this->filters['enumeration_status'], DsrtSample::ENUMERATION_STATUSES, true), fn ($q) => $q->where('enumeration_status', $this->filters['enumeration_status']))
            ->when(! empty($this->filters['q'] ?? null), fn ($q) => $q->search((string) $this->filters['q']))
            ->orderBy('nurt');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['nus', 'nurt', 'krt_name', 'enumeration_status', 'record_status', 'created_at'];
    }

    /** @param DsrtSample $row */
    public function map($row): array
    {
        return [
            (string) $row->nus,
            (string) $row->nurt,
            (string) $row->krt_name,
            (string) $row->enumeration_status,
            (string) $row->record_status,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
