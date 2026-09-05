<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ProcessingEntryReport extends Model
{
    use Auditable;

    public const REPORT_TYPES = [
        'PEMUTAKHIRAN_SUSENAS',
        'SAMPEL_SUSENAS',
        'SAMPEL_SERUTI',
    ];

    public const ENTRY_STATUSES = [
        'PENDING',
        'IN_PROGRESS',
        'COMPLETED',
        'RECONCILED',
        'DISCREPANCY',
    ];

    /** Status entry report yang dianggap bersih/valid sebagai induk Seruti. */
    public const CLEAN_STATUSES = ['COMPLETED', 'RECONCILED'];

    public const DOCUMENT_TYPE_CODES = [
        'PEMUTAKHIRAN_SUSENAS' => 'P_SUSENAS',
        'SAMPEL_SUSENAS' => 'VSEN_SUSENAS',
        'SAMPEL_SERUTI' => 'VSERUTI',
    ];

    /** Jumlah ruta sampel per NKS untuk dokumen sampel SUSENAS. */
    public const SAMPEL_RUTA_PER_NKS = 10;

    protected $fillable = [
        'survey_period_id',
        'allocation_id',
        'document_id',
        'document_type_id',
        'report_type',
        'batch_number',
        'officer_id',
        'assigned_at',
        'started_at',
        'completed_at',
        'target_qty',
        'processed_qty',
        'clean_qty',
        'error_qty',
        'entry_status',
        'reconciliation_note',
        'parent_entry_report_id',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'target_qty' => 'integer',
            'processed_qty' => 'integer',
            'clean_qty' => 'integer',
            'error_qty' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'survey_period_id',
            'allocation_id',
            'document_id',
            'document_type_id',
            'report_type',
            'batch_number',
            'officer_id',
            'started_at',
            'completed_at',
            'target_qty',
            'processed_qty',
            'clean_qty',
            'error_qty',
            'entry_status',
        ];
    }

    /**
     * @return BelongsTo<SurveyPeriod, $this>
     */
    public function surveyPeriod(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class);
    }

    /**
     * @return BelongsTo<Allocation, $this>
     */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * @return BelongsTo<ProcessingEntryReport, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_entry_report_id');
    }

    /**
     * Deadline SLA untuk report_type ini (dari survey_periods).
     */
    public function deadline(): ?Carbon
    {
        $period = $this->surveyPeriod;

        if ($period === null) {
            return null;
        }

        $column = $this->report_type === 'PEMUTAKHIRAN_SUSENAS'
            ? 'pemutakhiran_entry_deadline'
            : 'sampel_entry_deadline';

        /** @var Carbon|null */
        return $period->{$column};
    }

    public function isOverdue(): bool
    {
        $deadline = $this->deadline();

        if ($deadline === null) {
            return false;
        }

        $reference = $this->completed_at ?? now();

        return $reference->startOfDay()->greaterThan($deadline->copy()->startOfDay());
    }

    public function daysRemaining(): int
    {
        $deadline = $this->deadline();

        if ($deadline === null) {
            return PHP_INT_MAX;
        }

        $reference = $this->completed_at ?? now();

        return (int) $reference->copy()->startOfDay()->diffInDays($deadline->copy()->startOfDay(), false);
    }

    /**
     * Status SLA: ON_TRACK (>2 hari), WARNING (0-2 hari), OVERDUE (<0).
     */
    public function slaStatus(): string
    {
        if ($this->isOverdue()) {
            return 'OVERDUE';
        }

        return $this->daysRemaining() <= 2 ? 'WARNING' : 'ON_TRACK';
    }

    /**
     * @param  Builder<ProcessingEntryReport>  $query
     */
    public function scopeOfType(Builder $query, string $reportType): Builder
    {
        return $query->where('report_type', $reportType);
    }

    /**
     * @param  Builder<ProcessingEntryReport>  $query
     */
    public function scopeForPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('survey_period_id', $periodId);
    }

    /**
     * @param  Builder<ProcessingEntryReport>  $query
     */
    public function scopeWithEntryStatus(Builder $query, array $statuses): Builder
    {
        return $query->whereIn('entry_status', $statuses);
    }
}
