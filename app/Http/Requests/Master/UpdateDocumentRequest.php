<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDocumentRequest extends FormRequest
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
            'document_type_id' => ['sometimes', 'integer', Rule::exists('document_types', 'id')->where('is_active', true)],
            'document_number' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'status' => ['prohibited'],
            'allocation_id' => ['prohibited'],
            'dsrt_sample_id' => ['prohibited'],
            'format' => ['prohibited'],
            'created_by' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Document|null $document */
            $document = $this->route('document');

            if (! $document instanceof Document) {
                return;
            }

            if ($document->status !== 'REGISTERED') {
                $validator->errors()->add('status', 'Dokumen status '.$document->status.' tidak dapat diubah.');
            }
        });
    }
}
