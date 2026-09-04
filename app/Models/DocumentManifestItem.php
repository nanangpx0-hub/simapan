<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentManifestItem extends Model
{
    use Auditable;

    protected $fillable = [
        'document_manifest_id',
        'document_id',
        'qty_sent',
        'condition_sent',
        'sent_note',
    ];

    protected function casts(): array
    {
        return [
            'qty_sent' => 'integer',
        ];
    }

    /**
     * @return list<string>
     */
    public function auditableFields(): array
    {
        return [
            'document_manifest_id',
            'document_id',
            'qty_sent',
            'condition_sent',
            'sent_note',
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
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
