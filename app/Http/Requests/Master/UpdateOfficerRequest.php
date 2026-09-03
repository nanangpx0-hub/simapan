<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.officer.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Officer|null $officer */
        $officer = $this->route('officer');

        return [
            'code' => ['sometimes', 'string', Rule::in([$officer?->code])],
            'name' => ['required', 'string', 'max:150'],
            'normalized_name' => ['prohibited'],
            'work_unit_id' => ['required', 'integer', Rule::exists('work_units', 'id')->where('is_active', true)],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('officers', 'user_id')->ignore($officer?->getKey())],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9][0-9 ()\\-.]*$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'status' => ['sometimes', 'string', Rule::in(Officer::STATUSES)],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ];
    }
}
