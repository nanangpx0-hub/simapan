<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Allocation extends Model
{
    use Auditable;

    public const STATUSES = [
        'DRAFT',
        'ACTIVE',
        'SUSPENDED',
        'COMPLETED',
        'ARCHIVED',
    ];

    public const TRANSITIONS = [
        'DRAFT' => ['ACTIVE', 'ARCHIVED'],
        'ACTIVE' => ['SUSPENDED', 'COMPLETED'],
        'SUSPENDED' => ['ACTIVE', 'ARCHIVED'],
        'COMPLETED' => ['ARCHIVED'],
        'ARCHIVED' => [],
    ];

    public const ASSIGNMENT_ROLES = [
        'FIELD_OFFICER',
        'FIELD_SUPERVISOR',
        'PROCESSING_OFFICER',
        'PROCESSING_SUPERVISOR',
    ];

    protected $fillable = [
        'survey_period_id',
        'village_region_id',
        'nks',
        'sls_code',
        'sub_sls_code',
        'sls_name',
        'status',
        'notes',
        'created_by',
    ];

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'survey_period_id',
            'village_region_id',
            'nks',
            'sls_code',
            'sub_sls_code',
            'sls_name',
            'status',
            'notes',
        ];
    }

    /**
     * @return BelongsTo<SurveyPeriod, $this>
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(SurveyPeriod::class, 'survey_period_id');
    }

    /**
     * @return BelongsTo<Region, $this>
     */
    public function village(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'village_region_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<Assignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * @return HasMany<Assignment, $this>
     */
    public function activeAssignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->where('is_active', true);
    }

    /**
     * @param  Builder<Allocation>  $query
     */
    public function scopeForPeriod(Builder $query, int $periodId): Builder
    {
        return $query->where('survey_period_id', $periodId);
    }

    /**
     * @param  Builder<Allocation>  $query
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * @param  Builder<Allocation>  $query
     */
    public function scopeForVillage(Builder $query, int $villageId): Builder
    {
        return $query->where('village_region_id', $villageId);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::TRANSITIONS[$this->status] ?? [], true);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['DRAFT', 'ACTIVE'], true);
    }

    public function isAssignmentEditable(): bool
    {
        return in_array($this->status, ['DRAFT', 'ACTIVE', 'SUSPENDED'], true);
    }

    /**
     * @return list<string>
     */
    public function missingAssignmentRoles(): array
    {
        $filled = $this->activeAssignments()->pluck('assignment_role')->all();

        return array_values(array_diff(self::ASSIGNMENT_ROLES, $filled));
    }
}
