<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.work_unit.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]{1,32}$/', 'unique:work_units,code'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists('work_units', 'id')->where('is_active', true)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
