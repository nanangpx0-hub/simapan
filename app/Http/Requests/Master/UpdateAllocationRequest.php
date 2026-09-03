<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Allocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('allocation.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Allocation|null $allocation */
        $allocation = $this->route('allocation');

        return [
            'survey_period_id' => ['sometimes', 'integer', Rule::in([$allocation?->survey_period_id])],
            'survey_type_id' => ['prohibited'],
            'village_region_id' => ['sometimes', 'integer', Rule::in([$allocation?->village_region_id])],
            'nks' => ['sometimes', 'string', Rule::in([$allocation?->nks])],
            'sls_code' => ['sometimes', 'nullable', 'string', Rule::in([$allocation?->sls_code])],
            'sub_sls_code' => ['sometimes', 'nullable', 'string', Rule::in([$allocation?->sub_sls_code])],
            'sls_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Allocation|null $allocation */
            $allocation = $this->route('allocation');

            if (! $allocation instanceof Allocation) {
                return;
            }

            if (! $allocation->isEditable()) {
                $validator->errors()->add('status', 'Alokasi status '.$allocation->status.' tidak dapat diubah.');
            }
        });
    }
}
