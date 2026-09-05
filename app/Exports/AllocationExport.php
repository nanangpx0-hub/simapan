<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\Allocation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AllocationExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{q?: string, survey_period_id?: string, status?: string, village_region_id?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<Allocation> */
    public function query(): Builder
    {
        return Allocation::query()
            ->with(['period.surveyType', 'village'])
            ->when(isset($this->filters['survey_period_id']) && is_numeric($this->filters['survey_period_id']), fn ($query) => $query->forPeriod((int) $this->filters['survey_period_id']))
            ->when(isset($this->filters['status']) && in_array($this->filters['status'], Allocation::STATUSES, true), fn ($query) => $query->byStatus((string) $this->filters['status']))
            ->when(isset($this->filters['village_region_id']) && is_numeric($this->filters['village_region_id']), fn ($query) => $query->forVillage((int) $this->filters['village_region_id']))
            ->when(! empty($this->filters['q'] ?? null), function ($query): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nks', 'like', $keyword)
                        ->orWhere('sls_code', 'like', $keyword)
                        ->orWhere('sls_name', 'like', $keyword);
                });
            })
            ->orderBy('nks');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['nks', 'period_code', 'type_code', 'village_full_code', 'sls', 'status', 'created_at'];
    }

    /** @param Allocation $row */
    public function map($row): array
    {
        $sls = (string) ($row->sls_code ?? '');
        if ($row->sub_sls_code !== null && $row->sub_sls_code !== '') {
            $sls .= '/'.(string) $row->sub_sls_code;
        }

        return [
            (string) $row->nks,
            (string) ($row->period->code ?? ''),
            (string) ($row->period->surveyType->code ?? ''),
            (string) ($row->village->full_code ?? ''),
            $sls,
            (string) $row->status,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
