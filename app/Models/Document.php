<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use Auditable;

    public const STATUSES = [
        'REGISTERED',
        'IN_TRANSIT',
        'RECEIVED',
        'RECEIVED_PARTIAL',
        'REJECTED',
        'ASSIGNED_PROCESSING',
    ];

    public const CONDITIONS = [
        'GOOD',
        'INCOMPLETE',
        'DAMAGED',
        'ILLEGIBLE',
        'WRONG_IDENTITY',
        'MISSING',
        'NOT_APPLICABLE',
    ];

    protected $fillable = [
        'document_type_id',
        'allocation_id',
        'dsrt_sample_id',
        'document_number',
        'title',
        'format',
        'quantity',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_type_id',
            'allocation_id',
            'dsrt_sample_id',
            'document_number',
            'title',
            'format',
            'quantity',
            'status',
            'notes',
        ];
    }

    /**
     * @return BelongsTo<DocumentType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    /**
     * @return BelongsTo<Allocation, $this>
     */
    public function allocation(): BelongsTo
    {
        return $this->belongsTo(Allocation::class);
    }

    /**
     * @return BelongsTo<DsrtSample, $this>
     */
    public function dsrtSample(): BelongsTo
    {
        return $this->belongsTo(DsrtSample::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasOne<DocumentHolder, $this>
     */
    public function holder(): HasOne
    {
        return $this->hasOne(DocumentHolder::class);
    }

    /**
     * @return HasMany<DocumentHolderHistory, $this>
     */
    public function holderHistories(): HasMany
    {
        return $this->hasMany(DocumentHolderHistory::class);
    }

    /**
     * @return HasMany<DocumentManifestItem, $this>
     */
    public function manifestItems(): HasMany
    {
        return $this->hasMany(DocumentManifestItem::class);
    }

    /**
     * @return HasMany<DocumentProcessingAssignment, $this>
     */
    public function processingAssignments(): HasMany
    {
        return $this->hasMany(DocumentProcessingAssignment::class);
    }

    /**
     * @param  Builder<Document>  $query
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
            'REGISTERED' => ['IN_TRANSIT'],
            'IN_TRANSIT' => ['RECEIVED', 'RECEIVED_PARTIAL', 'REGISTERED'],
            'RECEIVED' => ['ASSIGNED_PROCESSING'],
            'RECEIVED_PARTIAL' => ['ASSIGNED_PROCESSING'],
            default => [],
        };
    }

    public function isEditable(): bool
    {
        return $this->status === 'REGISTERED';
    }
}
