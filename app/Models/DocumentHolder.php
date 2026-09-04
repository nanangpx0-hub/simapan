<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentHolder extends Model
{
    use Auditable;

    public const HOLDER_TYPES = [
        'WORK_UNIT',
        'OFFICER',
    ];

    protected $fillable = [
        'document_id',
        'holder_type',
        'work_unit_id',
        'officer_id',
        'document_location_id',
        'condition_code',
        'assigned_by',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_id',
            'holder_type',
            'work_unit_id',
            'officer_id',
            'document_location_id',
            'condition_code',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * @return BelongsTo<WorkUnit, $this>
     */
    public function workUnit(): BelongsTo
    {
        return $this->belongsTo(WorkUnit::class);
    }

    /**
     * @return BelongsTo<Officer, $this>
     */
    public function officer(): BelongsTo
    {
        return $this->belongsTo(Officer::class);
    }

    /**
     * @return BelongsTo<DocumentLocation, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(DocumentLocation::class, 'document_location_id');
    }
}
