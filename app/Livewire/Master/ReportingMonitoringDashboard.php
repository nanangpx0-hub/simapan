<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Exports\ReportingReconciliationExport;
use App\Models\Allocation;
use App\Models\ProcessingEntryReport;
use App\Models\SurveyPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportingMonitoringDashboard extends Component
{
    public const SLA_FILTERS = ['ALL', 'ON_TRACK', 'WARNING', 'OVERDUE'];

    public const RECON_FILTERS = ['ALL', 'MATCH', 'DISCREPANCY', 'PENDING'];

    public ?int $periodId = null;

    public string $slaFilter = 'ALL';

    public string $reconFilter = 'ALL';

    public function mount(): void
    {
        Gate::allowIf(auth()->user()->can('document.view'));

        $this->periodId = SurveyPeriod::query()->active()->value('id')
            ?? SurveyPeriod::query()->latest('id')->value('id');
    }

    public function updatedPeriodId(): void
    {
        $this->resetPage();
    }

    public function export(): BinaryFileResponse
    {
        Gate::authorize('document.view');

        $period = $this->period()?->load('surveyType');

        $rows = $period === null ? [] : $this->matrixRows();

        return (new ReportingReconciliationExport($rows, $period?->name ?? 'Semua'))->download('laporan-rekonsiliasi-5-dokumen.xlsx');
    }

    public function render(): View
    {
        return view('livewire.master.reporting-monitoring-dashboard', [
            'periods' => SurveyPeriod::query()->orderByDesc('year')->orderByDesc('id')->get(),
            'period' => $this->period(),
            'metrics' => $this->metrics(),
            'rows' => $this->matrixRows(),
        ]);
    }

    private function period(): ?SurveyPeriod
    {
        if ($this->periodId === null) {
            return null;
        }

        /** @var SurveyPeriod|null */
        return SurveyPeriod::query()->find($this->periodId);
    }

    /**
     * @return array<string, int>
     */
    private function metrics(): array
    {
        $period = $this->period();

        if ($period === null) {
            return $this->emptyMetrics();
        }

        $reportTypes = ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->get()
            ->groupBy('report_type');

        $targetNks = Allocation::query()->where('survey_period_id', $period->getKey())->count();

        /** @var Collection<int, ProcessingEntryReport>|EloquentCollection<int, ProcessingEntryReport> $pemutakhiran */
        $pemutakhiran = $reportTypes->get('PEMUTAKHIRAN_SUSENAS', collect())->first();
        /** @var Collection<int, ProcessingEntryReport>|EloquentCollection<int, ProcessingEntryReport> $sampel */
        $sampel = $reportTypes->get('SAMPEL_SUSENAS', collect())->first();
        /** @var Collection<int, ProcessingEntryReport>|EloquentCollection<int, ProcessingEntryReport> $seruti */
        $seruti = $reportTypes->get('SAMPEL_SERUTI', collect())->first();

        return [
            'target_nks' => $targetNks,
            'lap1_count' => $reportTypes->get('PEMUTAKHIRAN_SUSENAS', collect())->count(),
            'entri_pemutakhiran' => $pemutakhiran instanceof ProcessingEntryReport ? $this->progressPct($pemutakhiran) : 0,
            'entri_sampel' => $sampel instanceof ProcessingEntryReport ? $this->progressPct($sampel) : 0,
            'entri_seruti' => $seruti instanceof ProcessingEntryReport ? $this->progressPct($seruti) : 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function emptyMetrics(): array
    {
        return ['target_nks' => 0, 'lap1_count' => 0, 'entri_pemutakhiran' => 0, 'entri_sampel' => 0, 'entri_seruti' => 0];
    }

    private function progressPct(ProcessingEntryReport $report): int
    {
        if ($report->target_qty === 0) {
            return 0;
        }

        return (int) round(($report->processed_qty / $report->target_qty) * 100);
    }

    /**
     * Susun matriks rekonsiliasi per NKS.
     *
     * @return list<array<string, mixed>>
     */
    private function matrixRows(): array
    {
        $period = $this->period();

        if ($period === null) {
            return [];
        }

        $reports = ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->get()
            ->groupBy('allocation_id');

        $rows = [];

        foreach (Allocation::query()->where('survey_period_id', $period->getKey())->get() as $allocation) {
            /** @var Collection<int, ProcessingEntryReport> $byType */
            $byType = $reports->get($allocation->getKey(), collect())->keyBy('report_type');

            /** @var ProcessingEntryReport|null $pemutakhiran */
            $pemutakhiran = $byType->get('PEMUTAKHIRAN_SUSENAS');
            /** @var ProcessingEntryReport|null $sampel */
            $sampel = $byType->get('SAMPEL_SUSENAS');
            /** @var ProcessingEntryReport|null $seruti */
            $seruti = $byType->get('SAMPEL_SERUTI');

            $reconStatus = $this->deriveReconStatus([$pemutakhiran, $sampel, $seruti]);
            $sla = $this->deriveSla($sampel ?? $pemutakhiran);

            if ($this->reconFilter !== 'ALL' && $reconStatus !== $this->reconFilter) {
                continue;
            }

            if ($this->slaFilter !== 'ALL' && $sla !== $this->slaFilter) {
                continue;
            }

            $rows[] = [
                'nks' => $allocation->nks,
                'lap1' => $this->statusText($pemutakhiran, 'BELUM'),
                'lap3' => $pemutakhiran?->entry_status ?? 'PENDING',
                'lap2' => $sampel !== null ? 'DITERIMA' : 'BELUM',
                'lap4' => $sampel?->entry_status ?? 'PENDING',
                'lap5' => $seruti?->entry_status ?? 'PENDING',
                'recon' => $reconStatus,
                'sla' => $sla,
            ];
        }

        return array_values($rows);
    }

    /**
     * @param  array<int, ProcessingEntryReport|null>  $reports
     */
    private function deriveReconStatus(array $reports): string
    {
        $any = collect($reports)->filter();

        if ($any->isEmpty()) {
            return 'PENDING';
        }

        $hasDiscrepancy = $reports[0] instanceof ProcessingEntryReport && $reports[0]->entry_status === 'DISCREPANCY';

        if ($hasDiscrepancy) {
            return 'DISCREPANCY';
        }

        $allFinal = $any->every(fn (ProcessingEntryReport $r) => in_array($r->entry_status, ['COMPLETED', 'RECONCILED'], true));

        return $allFinal ? 'MATCH' : 'PENDING';
    }

    private function deriveSla(?ProcessingEntryReport $report): string
    {
        if (! $report instanceof ProcessingEntryReport) {
            return 'PENDING';
        }

        return $report->slaStatus();
    }

    private function statusText(?ProcessingEntryReport $report, string $fallback): string
    {
        if (! $report instanceof ProcessingEntryReport) {
            return $fallback;
        }

        return $report->processed_qty > 0
            ? "{$report->processed_qty}/{$report->target_qty}"
            : $report->entry_status;
    }
}
