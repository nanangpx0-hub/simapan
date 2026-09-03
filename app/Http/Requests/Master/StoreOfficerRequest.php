<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOfficerRequest extends FormRequest
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
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/', 'unique:officers,code'],
            'name' => ['required', 'string', 'max:150'],
            'normalized_name' => ['prohibited'],
            'work_unit_id' => ['required', 'integer', Rule::exists('work_units', 'id')->where('is_active', true)],
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'unique:officers,user_id'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[+0-9][0-9 ()\\-.]*$/'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'status' => ['sometimes', 'string', Rule::in(Officer::STATUSES)],
            'active_from' => ['nullable', 'date'],
            'active_until' => ['nullable', 'date', 'after_or_equal:active_from'],
        ];
    }
}
