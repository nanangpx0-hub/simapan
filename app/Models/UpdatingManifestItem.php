<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UpdatingManifestItem extends Model
{
    use Auditable;

    public const DELIVERY_STATUSES = [
        'DRAFT',
        'SENT_BY_SOCIAL',
        'RECEIVED_BY_PLS',
        'ASSIGNED_TO_PROCESSOR',
    ];

    public const CONDITIONS = [
        'GOOD',
        'DAMAGED',
        'INCOMPLETE',
    ];

    protected $fillable = [
        'document_manifest_id',
        'allocation_id',
        'nks',
        'kecamatan_code',
        'desa_code',
        'household_count_listing',
        'has_vsen_p',
        'has_peta_ws',
        'physical_condition',
        'processing_officer_id',
        'delivery_status',
        'receive_note',
    ];

    protected function casts(): array
    {
        return [
            'household_count_listing' => 'integer',
            'has_vsen_p' => 'boolean',
            'has_peta_ws' => 'boolean',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_manifest_id',
            'allocation_id',
            'nks',
            'household_count_listing',
            'has_vsen_p',
            'has_peta_ws',
            'physical_condition',
            'processing_officer_id',
            'delivery_status',
        ];
    }

    /**
     * @return BelongsTo<DocumentManifest, $this>
     */
    public function manifest(): BelongsTo
    {
        return $this->belongsTo(DocumentManifest::class, 'document_manifest_id');
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
    public function processor(): BelongsTo
    {
        return $this->belongsTo(Officer::class, 'processing_officer_id');
    }

    /**
     * @param  Builder<UpdatingManifestItem>  $query
     */
    public function scopeByDeliveryStatus(Builder $query, string $status): Builder
    {
        return $query->where('delivery_status', $status);
    }
}
