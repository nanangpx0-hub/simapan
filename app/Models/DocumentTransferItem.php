<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentTransferItem extends Model
{
    use Auditable;

    public const RECEIPT_STATUSES = [
        'COMPLETE',
        'PARTIAL',
        'NOT_RECEIVED',
        'DAMAGED',
        'REJECTED',
    ];

    protected $fillable = [
        'document_transfer_id',
        'document_manifest_item_id',
        'qty_sent',
        'qty_received',
        'condition_received',
        'receipt_status',
        'receipt_note',
    ];

    protected function casts(): array
    {
        return [
            'qty_sent' => 'integer',
            'qty_received' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_transfer_id',
            'document_manifest_item_id',
            'qty_sent',
            'qty_received',
            'condition_received',
            'receipt_status',
            'receipt_note',
        ];
    }

    /**
     * @return BelongsTo<DocumentTransfer, $this>
     */
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(DocumentTransfer::class, 'document_transfer_id');
    }

    /**
     * @return BelongsTo<DocumentManifestItem, $this>
     */
    public function manifestItem(): BelongsTo
    {
        return $this->belongsTo(DocumentManifestItem::class, 'document_manifest_item_id');
    }
}
