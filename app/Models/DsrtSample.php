<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DsrtSample extends Model
{
    use Auditable;

    public const RECORD_STATUSES = [
        'DRAFT',
        'VERIFIED',
        'ARCHIVED',
    ];

    public const RECORD_TRANSITIONS = [
        'DRAFT' => ['VERIFIED', 'ARCHIVED'],
        'VERIFIED' => ['ARCHIVED'],
        'ARCHIVED' => [],
    ];

    public const ENUMERATION_STATUSES = [
        'PENDING',
        'IN_PROGRESS',
        'COMPLETED',
        'REVISIT_REQUIRED',
        'NEEDS_CLARIFICATION',
        'NON_RESPONSE',
        'REFUSED',
        'MOVED',
        'DECEASED',
        'NOT_FOUND',
        'NOT_ELIGIBLE',
        'REPLACED',
    ];

    public const NOTE_REQUIRED_STATUSES = [
        'MOVED',
        'DECEASED',
        'NOT_FOUND',
        'NOT_ELIGIBLE',
        'REPLACED',
        'REFUSED',
        'NON_RESPONSE',
    ];

    protected $fillable = [
        'allocation_id',
        'nus',
        'nurt',
        'family_number',
        'building_number',
        'household_number',
        'krt_name',
        'address',
        'krt_education_code',
        'enumeration_status',
        'contact_person',
        'contact_phone',
        'notes',
        'record_status',
        'created_by',
        'verified_by',
        'verified_at',
        'archived_by',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'allocation_id',
            'nus',
            'nurt',
            'family_number',
            'building_number',
            'household_number',
            'krt_name',
            'krt_education_code',
            'enumeration_status',
            'record_status',
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
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function archiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * @param  Builder<DsrtSample>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('record_status', '!=', 'ARCHIVED');
    }

    /**
     * @param  Builder<DsrtSample>  $query
     */
    public function scopeForAllocation(Builder $query, int $allocationId): Builder
    {
        return $query->where('allocation_id', $allocationId);
    }

    /**
     * @param  Builder<DsrtSample>  $query
     */
    public function scopeSearch(Builder $query, string $keyword): Builder
    {
        $like = '%'.str_replace(['%', '_'], '', $keyword).'%';

        return $query->where(function ($query) use ($like): void {
            $query->where('nus', 'like', $like)
                ->orWhere('nurt', 'like', $like)
                ->orWhere('krt_name', 'like', $like);
        });
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::RECORD_TRANSITIONS[$this->record_status] ?? [], true);
    }

    public function isEditable(): bool
    {
        return $this->record_status === 'DRAFT';
    }

    public function maskedContactPerson(): string
    {
        $name = trim((string) ($this->contact_person ?? ''));

        if ($name === '') {
            return '—';
        }

        $first = mb_substr($name, 0, 1);

        return $first.'•••';
    }

    public function maskedContactPhone(): string
    {
        $phone = (string) ($this->contact_phone ?? '');

        if ($phone === '') {
            return '—';
        }

        return '••••'.mb_substr($phone, -4);
    }

    public function maskedAddress(): string
    {
        $address = trim((string) ($this->address ?? ''));

        if ($address === '') {
            return '—';
        }

        return mb_substr($address, 0, 12).'•••';
    }
}
