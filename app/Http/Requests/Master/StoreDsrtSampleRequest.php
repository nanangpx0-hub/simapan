<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Allocation;
use App\Models\DsrtSample;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDsrtSampleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('dsrt.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nus' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'nurt' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'family_number' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'building_number' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'household_number' => ['nullable', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'krt_name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'krt_education_code' => ['nullable', 'string', 'max:32'],
            'enumeration_status' => ['sometimes', 'string', Rule::in(DsrtSample::ENUMERATION_STATUSES)],
            'contact_person' => ['nullable', 'string', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
            'record_status' => ['prohibited'],
            'created_by' => ['prohibited'],
            'verified_by' => ['prohibited'],
            'verified_at' => ['prohibited'],
            'archived_by' => ['prohibited'],
            'archived_at' => ['prohibited'],
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

            if (! in_array($allocation->status, ['DRAFT', 'ACTIVE', 'SUSPENDED'], true)) {
                $validator->errors()->add('allocation', 'DSRT tidak dapat dikelola pada status '.$allocation->status.'.');

                return;
            }

            if (! $this->isSusenasAllocation($allocation)) {
                $validator->errors()->add('allocation', 'DSRT hanya untuk alokasi Susenas.');

                return;
            }

            if (! in_array($allocation->status, ['DRAFT', 'ACTIVE', 'SUSPENDED'], true)) {
                $validator->errors()->add('allocation', 'DSRT tidak dapat dikelola pada status '.$allocation->status.'.');

                return;
            }

            foreach (['nus', 'nurt'] as $field) {
                $value = $this->input($field);

                if ($value === null || $value === '') {
                    continue;
                }

                if (
                    DsrtSample::query()->where('allocation_id', $allocation->getKey())
                        ->where($field, (string) $value)->exists()
                ) {
                    $validator->errors()->add($field, 'Nilai ini sudah digunakan pada alokasi ini.');
                }
            }

            $this->checkNotesRequired($validator);
        });
    }

    private function isSusenasAllocation(Allocation $allocation): bool
    {
        return $allocation->period?->surveyType?->code === 'SUSENAS';
    }

    private function checkNotesRequired(Validator $validator): void
    {
        $status = (string) $this->input('enumeration_status', 'PENDING');
        $notes = trim((string) $this->input('notes', ''));

        if (in_array($status, DsrtSample::NOTE_REQUIRED_STATUSES, true) && $notes === '') {
            $validator->errors()->add('notes', 'Catatan wajib untuk status pencacahan ini.');
        }
    }
}
