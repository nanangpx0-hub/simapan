<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Allocation;
use App\Models\ProcessingEntryReport;
use App\Models\SurveyPeriod;
use Illuminate\Support\Collection;

/**
 * Mesin verifikasi silang (reconciliation engine) untuk 5 laporan pelaporan
 * SUSENAS-SERUTI. Membandingkan fisik diterima vs hasil entri dan menandai
 * selisih sebagai DISCREPANCY (dengan audit log).
 */
final class DocumentReconciliationService
{
    private const RECONCILED = 'RECONCILED';

    private const DISCREPANCY = 'DISCREPANCY';

    /** Status entri yang menandakan entri telah selesai untuk NKS tsb. */
    private const ENTRY_DONE = ['COMPLETED', 'RECONCILED'];

    /**
     * Jalankan rekonsiliasi seluruh alokasi pada satu periode survei.
     *
     * @return array{reconciled: int, discrepancy: int}
     */
    public function reconcileSubmissionVsEntry(SurveyPeriod $period): array
    {
        $counts = ['reconciled' => 0, 'discrepancy' => 0];

        $reports = ProcessingEntryReport::query()
            ->where('survey_period_id', $period->getKey())
            ->get()
            ->groupBy('allocation_id');

        $allocations = Allocation::query()->where('survey_period_id', $period->getKey())->get();

        foreach ($allocations as $allocation) {
            $result = $this->reconcileAllocation($allocation, $reports->get($allocation->getKey(), collect()));

            if ($result === self::RECONCILED) {
                $counts['reconciled']++;
            } elseif ($result === self::DISCREPANCY) {
                $counts['discrepancy']++;
            }
        }

        return $counts;
    }

    /**
     * Rekonsiliasi satu alokasi (NKS); mengembalikan status akhir atau null
     * bila belum ada data entri yang memadai.
     *
     * @param  Collection<int, ProcessingEntryReport>  $reports
     */
    private function reconcileAllocation(Allocation $allocation, Collection $reports): ?string
    {
        $notes = [];

        $this->reconcileLap1VsLap3($allocation, $reports, $notes);
        $this->reconcileLap2VsLap4($allocation, $reports, $notes);
        $this->reconcileLap4VsLap5($allocation, $reports, $notes);

        $byType = $reports->keyBy('report_type');
        $markers = [
            $byType->get('PEMUTAKHIRAN_SUSENAS'),
            $byType->get('SAMPEL_SUSENAS'),
            $byType->get('SAMPEL_SERUTI'),
        ];

        return $this->applyResult($markers, $notes);
    }

    /**
     * Laporan 1 (manifest P_SUSENAS diterima) vs Laporan 3 (hasil entri
     * pemutakhiran). Mendeteksi NKS belum dientri.
     *
     * @param  Collection<int, ProcessingEntryReport>  $reports
     * @param  list<string>  $notes
     */
    private function reconcileLap1VsLap3(Allocation $allocation, Collection $reports, array &$notes): void
    {
        $lap3 = $reports->firstWhere('report_type', 'PEMUTAKHIRAN_SUSENAS');

        if (! $lap3 instanceof ProcessingEntryReport) {
            $notes[] = "NKS {$allocation->nks}: Belum ada laporan entri pemutakhiran (L3).";

            return;
        }

        if ($lap3->processed_qty < $lap3->target_qty) {
            $notes[] = "NKS {$allocation->nks}: Entri pemutakhiran (L3) belum selesai "
                ."({$lap3->processed_qty}/{$lap3->target_qty}).";
        }
    }

    /**
     * Laporan 2 (manifest sampel diterima) vs Laporan 4 (entri sampel SUSENAS).
     * Gap antara kuesioner fisik diterima vs terselesai dientri (10 ruta/NKS).
     *
     * @param  Collection<int, ProcessingEntryReport>  $reports
     * @param  list<string>  $notes
     */
    private function reconcileLap2VsLap4(Allocation $allocation, Collection $reports, array &$notes): void
    {
        $lap4 = $reports->firstWhere('report_type', 'SAMPEL_SUSENAS');

        if (! $lap4 instanceof ProcessingEntryReport) {
            $notes[] = "NKS {$allocation->nks}: Laporan entri sampel SUSENAS (L4) belum ada.";

            return;
        }

        if ($lap4->processed_qty < $lap4->target_qty) {
            $notes[] = "NKS {$allocation->nks}: Kurang entri sampel "
                .($lap4->target_qty - $lap4->processed_qty)." ruta dari target {$lap4->target_qty}.";
        }
    }

    /**
     * Laporan 4 (SUSENAS) vs Laporan 5 (SERUTI). Setiap sampel SERUTI wajib
     * tertaut pada sampel SUSENAS yang sudah dientri & bersih.
     *
     * @param  Collection<int, ProcessingEntryReport>  $reports
     * @param  list<string>  $notes
     */
    private function reconcileLap4VsLap5(Allocation $allocation, Collection $reports, array &$notes): void
    {
        $sampelSusenas = $reports->firstWhere('report_type', 'SAMPEL_SUSENAS');
        $sampelSeruti = $reports->firstWhere('report_type', 'SAMPEL_SERUTI');

        if (! $sampelSeruti instanceof ProcessingEntryReport) {
            return;
        }

        if (! $sampelSusenas instanceof ProcessingEntryReport) {
            $notes[] = "NKS {$allocation->nks}: L4 belum ada sehingga entri SERUTI (L5) tidak dapat ditautkan.";

            return;
        }

        if (! in_array($sampelSusenas->entry_status, self::ENTRY_DONE, true)) {
            $notes[] = "NKS {$allocation->nks}: L4 belum bersih (status {$sampelSusenas->entry_status}) sehingga tautan SERUTI (L5) tidak valid.";
        }
    }

    /**
     * Terapkan hasil rekonsiliasi ke laporan pada NKS.
     *
     * @param  array<int, ProcessingEntryReport|null>  $markers
     * @param  list<string>  $notes
     */
    private function applyResult(array $markers, array $notes): ?string
    {
        $any = collect($markers)->filter();

        if ($any->isEmpty()) {
            return null;
        }

        $hasDiscrepancy = $notes !== [];
        $target = $hasDiscrepancy ? self::DISCREPANCY : self::RECONCILED;

        foreach ($markers as $report) {
            if (! $report instanceof ProcessingEntryReport) {
                continue;
            }

            if ($report->entry_status !== $target) {
                $report->update([
                    'entry_status' => $target,
                    'reconciliation_note' => $hasDiscrepancy ? implode('; ', $notes) : null,
                ]);
            }
        }

        if ($hasDiscrepancy) {
            AuditLogger::log('recon_discrepancy', $markers[0], [], [], [
                'note' => implode('; ', $notes),
            ]);
        }

        return $target;
    }
}
