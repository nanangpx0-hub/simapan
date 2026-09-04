<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDocumentRequest extends FormRequest
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
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')->where('is_active', true)],
            'allocation_id' => ['nullable', 'integer', 'exists:allocations,id'],
            'dsrt_sample_id' => ['nullable', 'integer', 'exists:dsrt_samples,id'],
            'document_number' => ['nullable', 'string', 'max:64'],
            'title' => ['required', 'string', 'max:255'],
            'format' => ['required', 'string', Rule::in(['PHYSICAL'])],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'condition_code' => ['sometimes', 'string', Rule::in(Document::CONDITIONS)],
            'document_location_id' => ['nullable', 'integer', Rule::exists('document_locations', 'id')->where('is_active', true)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasAllocation = $this->input('allocation_id') !== null;
            $hasSample = $this->input('dsrt_sample_id') !== null;

            if ($hasAllocation === $hasSample) {
                $validator->errors()->add('allocation_id', 'Dokumen wajib terkait tepat satu allocation atau satu sampel DSRT.');
            }
        });
    }
}
