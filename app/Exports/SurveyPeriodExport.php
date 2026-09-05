<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\SurveyPeriod;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SurveyPeriodExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping
{
    use Exportable;

    /**
     * @param  array{survey_type_id?: string, status?: string, year?: string, q?: string}  $filters
     */
    public function __construct(private array $filters = []) {}

    /** @return Builder<SurveyPeriod> */
    public function query(): Builder
    {
        return SurveyPeriod::query()
            ->with(['surveyType'])
            ->when(isset($this->filters['survey_type_id']) && is_numeric($this->filters['survey_type_id']), fn ($q) => $q->where('survey_type_id', (int) $this->filters['survey_type_id']))
            ->when(isset($this->filters['status']) && in_array($this->filters['status'], SurveyPeriod::STATUSES, true), fn ($q) => $q->where('status', $this->filters['status']))
            ->when(isset($this->filters['year']) && is_numeric($this->filters['year']), fn ($q) => $q->where('year', (int) $this->filters['year']))
            ->when(! empty($this->filters['q'] ?? null), function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $this->filters['q']).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)->orWhere('name', 'like', $keyword);
                });
            })
            ->orderByDesc('year')
            ->orderBy('survey_type_id');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['code', 'survey_type_code', 'name', 'period_type', 'period_number', 'year', 'status', 'created_at'];
    }

    /** @param SurveyPeriod $row */
    public function map($row): array
    {
        return [
            (string) $row->code,
            (string) ($row->surveyType->code ?? ''),
            (string) $row->name,
            (string) $row->period_type,
            $row->period_number !== null ? (int) $row->period_number : '',
            (int) $row->year,
            (string) $row->status,
            (string) ($row->created_at?->format('Y-m-d H:i:s') ?? ''),
        ];
    }
}
