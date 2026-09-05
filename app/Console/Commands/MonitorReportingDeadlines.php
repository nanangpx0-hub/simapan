<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Allocation;
use App\Models\ProcessingEntryReport;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Notifications\ReportingDeadlineNotification;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

/**
 * Memantau SLA/deadline pelaporan dan mengirim notifikasi keterlambatan ke
 * role terkait (Tim Sosial, Tim Pengolahan/IPDS, dan Pimpinan).
 */
class MonitorReportingDeadlines extends Command
{
    protected $signature = 'reporting:monitor-deadlines';

    protected $description = 'Kirim notifikasi keterlambatan pelaporan berdasarkan SLA periode survei.';

    public function handle(): int
    {
        foreach (SurveyPeriod::query()->active()->get() as $period) {
            $this->monitorSubmissions($period);
            $this->monitorEntries($period);
            $this->notifyLeadership($period);
        }

        return self::SUCCESS;
    }

    private function monitorSubmissions(SurveyPeriod $period): void
    {
        $deadline = $period->pemutakhiran_submission_deadline;

        if ($deadline === null) {
            return;
        }

        $days = (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        // H-3 dan H+0
        if (! in_array($days, [3, 0], true)) {
            return;
        }

        $incomplete = $this->incompleteSubmissionNks($period);

        if ($incomplete === [] || $incomplete === 0) {
            $this->info('Deadline pemutakhiran periode '.$period->code.': semua NKS sudah diserahkan.');

            return;
        }

        Notification::send(
            $this->recipients(['social_operator']),
            new ReportingDeadlineNotification(
                $days === 0 ? 'danger' : 'warning',
                'Keterlambatan Serah Pemutakhiran',
                "Periode {$period->name}: {$incomplete} NKS belum diserahkan "
                    .($days === 0 ? 'melewati' : 'mendekati (H-'.$days.')')." batas deadline {$deadline->format('d-m-Y')}."
            )
        );
    }

    private function monitorEntries(SurveyPeriod $period): void
    {
        $deadline = $period->sampel_entry_deadline;

        if ($deadline === null) {
            return;
        }

        $days = (int) now()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);

        if (! in_array($days, [2, 0], true)) {
            return;
        }

        $pending = ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->whereIn('entry_status', ['PENDING', 'IN_PROGRESS', 'DISCREPANCY'])
            ->count();

        if ($pending === 0) {
            return;
        }

        Notification::send(
            $this->recipients(['ipds_operator', 'processing_supervisor']),
            new ReportingDeadlineNotification(
                $days === 0 ? 'danger' : 'warning',
                'Penumpukan Entri Melampaui SLA',
                "Periode {$period->name}: {$pending} laporan entri belum selesai "
                    .($days === 0 ? 'melewati' : 'mendekati (H-'.$days.')')." deadline entri {$deadline->format('d-m-Y')}."
            )
        );
    }

    private function notifyLeadership(SurveyPeriod $period): void
    {
        $overdue = $this->overdueCount($period);

        Notification::send(
            $this->recipients(['viewer', 'administrator']),
            new ReportingDeadlineNotification(
                $overdue > 0 ? 'danger' : 'info',
                'Rekapitulasi SLA Pelaporan',
                "Periode {$period->name}: {$overdue} laporan berstatus overdue"
                    .($overdue > 0 ? ' — segera lakukan pemantauan dan percepatan.' : ' — semua dalam batas SLA.')
            )
        );
    }

    /**
     * @param  list<string>  $roleSlugs
     * @return Collection<int, User>
     */
    private function recipients(array $roleSlugs)
    {
        return User::query()->where('is_active', true)->role($roleSlugs)->get();
    }

    private function incompleteSubmissionNks(SurveyPeriod $period): int
    {
        $reportNks = ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->where('report_type', 'PEMUTAKHIRAN_SUSENAS')
            ->pluck('allocation_id')
            ->all();

        return Allocation::query()
            ->where('survey_period_id', $period->getKey())
            ->whereNotIn('id', $reportNks)
            ->count();
    }

    private function overdueCount(SurveyPeriod $period): int
    {
        $deadline = $period->getAttribute('sampel_entry_deadline');

        if ($deadline === null) {
            return 0;
        }

        return ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->get()
            ->filter(fn (ProcessingEntryReport $report) => $report->isOverdue())
            ->count();
    }
}
