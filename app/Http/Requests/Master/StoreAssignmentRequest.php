<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('allocation.assign');
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
            'assignment_role' => ['required', 'string', Rule::in(Assignment::ROLES)],
            'employment_category' => ['nullable', 'string', Rule::in(Assignment::EMPLOYMENT_CATEGORIES)],
            'is_active' => ['prohibited'],
            'started_at' => ['prohibited'],
            'ended_at' => ['prohibited'],
            'assigned_by' => ['prohibited'],
            'allocation_id' => ['prohibited'],
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

            if (! $allocation->isAssignmentEditable()) {
                $validator->errors()->add('allocation', 'Penugasan tidak dapat dikelola pada status '.$allocation->status.'.');

                return;
            }

            $officer = Officer::query()->find($this->input('officer_id'));

            if (! $officer instanceof Officer) {
                return;
            }

            $expectedUnit = in_array($this->input('assignment_role'), ['FIELD_OFFICER', 'FIELD_SUPERVISOR'], true)
                ? 'SOSIAL'
                : 'PENGOLAHAN_LS';

            if ($officer->workUnit?->code !== $expectedUnit) {
                $validator->errors()->add('officer_id', 'Petugas harus berasal dari unit '.$expectedUnit.' untuk role ini.');
            }
        });
    }
}
