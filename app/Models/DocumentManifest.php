<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentManifest extends Model
{
    use Auditable;

    public const STATUSES = [
        'DRAFT',
        'SUBMITTED',
        'RECEIVED_COMPLETE',
        'RECEIVED_PARTIAL',
        'RECEIVED_NOTED',
        'REJECTED',
    ];

    public const MAX_ITEMS = 200;

    protected $fillable = [
        'manifest_number',
        'from_work_unit_id',
        'to_work_unit_id',
        'status',
        'submitted_by',
        'submitted_at',
        'received_by',
        'received_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'received_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'manifest_number',
            'from_work_unit_id',
            'to_work_unit_id',
            'status',
        ];
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function fromUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'from_work_unit_id');
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function toUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class, 'to_work_unit_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<DocumentManifestItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentManifestItem::class);
    }

    /**
     * @return HasOne<DocumentTransfer, $this>
     */
    public function transfer(): HasOne
    {
        return $this->hasOne(DocumentTransfer::class);
    }

    /**
     * @param  Builder<DocumentManifest>  $query
     */
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function canTransitionTo(string $status): bool
    {
        return in_array($status, self::transitions($this->status), true);
    }

    /**
     * @return list<string>
     */
    public static function transitions(string $from): array
    {
        return match ($from) {
            'DRAFT' => ['SUBMITTED'],
            'SUBMITTED' => ['RECEIVED_COMPLETE', 'RECEIVED_PARTIAL', 'RECEIVED_NOTED', 'REJECTED'],
            default => [],
        };
    }

    public function isEditable(): bool
    {
        return $this->status === 'DRAFT';
    }
}
