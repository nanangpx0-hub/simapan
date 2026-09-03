<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAllocationRequest extends FormRequest
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
        return [
            'survey_period_id' => ['required', 'integer', 'exists:survey_periods,id'],
            'survey_type_id' => ['prohibited'],
            'village_region_id' => [
                'required',
                'integer',
                Rule::exists('regions', 'id')->where(function ($query): void {
                    $query->where('level', 'DESA_KELURAHAN_NAGARI')->where('is_active', true);
                }),
            ],
            'nks' => [
                'required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/',
                Rule::unique('allocations', 'nks')->where('survey_period_id', $this->input('survey_period_id')),
            ],
            'sls_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'sub_sls_code' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'sls_name' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'status' => ['prohibited'],
            'created_by' => ['prohibited'],
        ];
    }
}
