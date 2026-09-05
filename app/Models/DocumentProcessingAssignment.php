<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentProcessingAssignment extends Model
{
    use Auditable;

    public const STATUSES = [
        'ACTIVE',
        'RETURNED',
        'CANCELLED',
    ];

    /**
     * Unit kerja yang boleh memegang penugasan pengolahan.
     *
     * @var list<string>
     */
    public const PROCESSING_UNIT_CODES = [
        'PENGOLAHAN_LS',
        'IPDS',
    ];

    protected $fillable = [
        'document_id',
        'officer_id',
        'assigned_by',
        'assigned_at',
        'returned_at',
        'status',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_id',
            'officer_id',
            'status',
            'note',
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
     * @param  Builder<DocumentProcessingAssignment>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'ACTIVE');
    }
}
