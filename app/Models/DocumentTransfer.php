<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTransfer extends Model
{
    use Auditable;

    public const STATUSES = [
        'PENDING',
        'RECEIVED',
        'REJECTED',
    ];

    public const RECEIPT_RESULTS = [
        'COMPLETE',
        'PARTIAL',
        'NOTED',
        'REJECTED',
    ];

    protected $fillable = [
        'document_manifest_id',
        'transfer_status',
        'received_by',
        'received_at',
        'checked_by',
        'checked_at',
        'receipt_result',
        'receipt_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'received_at' => 'datetime',
            'checked_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_manifest_id',
            'transfer_status',
            'receipt_result',
            'receipt_note',
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
     * @return HasMany<DocumentTransferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(DocumentTransferItem::class);
    }
}
