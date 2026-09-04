<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Document;
use App\Models\DocumentTransferItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReceiveDocumentManifestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.receive');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'integer', 'exists:document_transfer_items,id'],
            'items.*.qty_received' => ['required', 'integer', 'min:0'],
            'items.*.condition_received' => ['required', 'string', Rule::in(Document::CONDITIONS)],
            'items.*.receipt_status' => ['required', 'string', Rule::in(DocumentTransferItem::RECEIPT_STATUSES)],
            'items.*.receipt_note' => ['nullable', 'string'],
            'document_location_id' => ['nullable', 'integer', Rule::exists('document_locations', 'id')->where('is_active', true)],
            'receipt_note' => ['nullable', 'string'],
        ];
    }
}
