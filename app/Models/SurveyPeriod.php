<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyPeriod extends Model
{
    use Auditable;

    public const PERIOD_TYPES = [
        'SEMESTER',
        'TRIWULAN',
        'TAHUNAN',
    ];

    public const PERIOD_NUMBERS = [
        'SEMESTER' => [1, 2],
        'TRIWULAN' => [1, 2, 3, 4],
        'TAHUNAN' => [],
    ];

    public const STATUSES = [
        'DRAFT',
        'ACTIVE',
        'CLOSED',
        'ARCHIVED',
    ];

    public const TRANSITIONS = [
        'DRAFT' => ['ACTIVE', 'ARCHIVED'],
        'ACTIVE' => ['CLOSED'],
        'CLOSED' => ['ARCHIVED'],
        'ARCHIVED' => [],
    ];

    protected $fillable = [
        'code',
        'survey_type_id',
        'name',
        'period_type',
        'period_number',
        'year',
        'start_date',
        'end_date',
        'pemutakhiran_submission_deadline',
        'pemutakhiran_entry_deadline',
        'sampel_submission_deadline',
        'sampel_entry_deadline',
        'status',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_number' => 'integer',
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'pemutakhiran_submission_deadline' => 'date',
            'pemutakhiran_entry_deadline' => 'date',
            'sampel_submission_deadline' => 'date',
            'sampel_entry_deadline' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'code',
            'survey_type_id',
            'name',
            'period_type',
            'period_number',
            'year',
            'start_date',
            'end_date',
            'status',
        ];
    }

    /**
     * @return BelongsTo<SurveyType, $this>
     */
    public function surveyType(): BelongsTo
    {
        return $this->belongsTo(SurveyType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * @param  Builder<SurveyPeriod>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }

    /**
     * @param  Builder<SurveyPeriod>  $query
     */
    public function scopeForSlot(Builder $query, int $surveyTypeId, int $year, string $periodType, ?int $periodNumber): Builder
    {
        return $query->where('survey_type_id', $surveyTypeId)
            ->where('year', $year)
            ->where('period_type', $periodType)
            ->where('period_number', $periodNumber);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }
}
