<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Document;
use App\Models\DocumentHolder;
use App\Models\DocumentManifest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentManifestItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_id' => ['required', 'integer', 'exists:documents,id'],
            'qty_sent' => ['required', 'integer', 'min:1'],
            'condition_sent' => ['required', 'string', Rule::in(Document::CONDITIONS)],
            'sent_note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var DocumentManifest|null $manifest */
            $manifest = $this->route('documentManifest');

            if (! $manifest instanceof DocumentManifest) {
                return;
            }

            if (! $manifest->isEditable()) {
                $validator->errors()->add('document_manifest_id', 'Item hanya dapat diubah pada manifest DRAFT.');

                return;
            }

            if ($manifest->items()->count() >= DocumentManifest::MAX_ITEMS) {
                $validator->errors()->add('document_manifest_id', 'Manifest maksimal '.DocumentManifest::MAX_ITEMS.' item.');

                return;
            }

            $document = Document::query()->find($this->input('document_id'));

            if (! $document instanceof Document) {
                return;
            }

            if ($document->status !== 'REGISTERED') {
                $validator->errors()->add('document_id', 'Hanya dokumen REGISTERED yang dapat dimasukkan manifest.');
            }

            $holder = $document->holder;

            if (! $holder instanceof DocumentHolder || $holder->holder_type !== 'WORK_UNIT' || (int) $holder->work_unit_id !== (int) $manifest->from_work_unit_id) {
                $validator->errors()->add('document_id', 'Dokumen harus berada pada unit pengirim.');
            }

            if ($manifest->items()->where('document_id', $document->getKey())->exists()) {
                $validator->errors()->add('document_id', 'Dokumen sudah ada pada manifest ini.');
            }
        });
    }
}
