<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
    use Auditable;

    public const ROLES = [
        'FIELD_OFFICER',
        'FIELD_SUPERVISOR',
        'PROCESSING_OFFICER',
        'PROCESSING_SUPERVISOR',
    ];

    public const EMPLOYMENT_CATEGORIES = [
        'ORGANIK',
        'MITRA',
    ];

    protected $fillable = [
        'allocation_id',
        'officer_id',
        'assignment_role',
        'employment_category',
        'is_active',
        'started_at',
        'ended_at',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'allocation_id',
            'officer_id',
            'assignment_role',
            'employment_category',
            'is_active',
            'started_at',
            'ended_at',
        ];
    }

    /**
     * @return BelongsTo<Allocation, $this>
     */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * @param  Builder<Assignment>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<Assignment>  $query
     */
    public function scopeForRole(Builder $query, string $role): Builder
    {
        return $query->where('assignment_role', $role);
    }
}
