<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHolderHistory extends Model
{
    use Auditable;

    public const UPDATED_AT = null;

    public const MOVEMENT_TYPES = [
        'REGISTERED',
        'MANIFEST_SUBMITTED',
        'MANIFEST_RECEIVED',
        'PROCESSING_ASSIGNED',
    ];

    protected $fillable = [
        'document_id',
        'from_holder_type',
        'from_work_unit_id',
        'from_officer_id',
        'from_document_location_id',
        'to_holder_type',
        'to_work_unit_id',
        'to_officer_id',
        'to_document_location_id',
        'condition_before',
        'condition_after',
        'movement_type',
        'reference_type',
        'reference_id',
        'moved_by',
        'moved_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'moved_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_id',
            'to_holder_type',
            'to_work_unit_id',
            'to_officer_id',
            'condition_after',
            'movement_type',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
