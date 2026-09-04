<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Document;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignDocumentToProcessingOfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.assign');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'officer_id' => [
                'required', 'integer',
                Rule::exists('officers', 'id')->where(function ($query): void {
                    $query->where('status', 'ACTIVE')->whereNull('deleted_at');
                }),
            ],
            'document_location_id' => ['nullable', 'integer', Rule::exists('document_locations', 'id')->where('is_active', true)],
            'condition_code' => ['required', 'string', Rule::in(Document::CONDITIONS)],
            'note' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $officer = Officer::query()->find($this->input('officer_id'));

            if (! $officer instanceof Officer) {
                return;
            }

            if ($officer->workUnit?->code !== 'PENGOLAHAN_LS') {
                $validator->errors()->add('officer_id', 'Petugas harus berasal dari unit PENGOLAHAN_LS.');
            }
        });
    }
}
