<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\SurveyPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreSurveyPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.survey_period.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9_-]{1,32}$/', 'unique:survey_periods,code'],
            'survey_type_id' => ['required', 'integer', Rule::exists('survey_types', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:150'],
            'period_type' => ['required', 'string', Rule::in(SurveyPeriod::PERIOD_TYPES)],
            'period_number' => ['nullable', 'integer'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(['DRAFT', 'ARCHIVED'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->checkPeriodNumber($validator);
        });
    }

    private function checkPeriodNumber(Validator $validator): void
    {
        $type = (string) $this->input('period_type');
        $number = $this->input('period_number');

        if (! in_array($type, SurveyPeriod::PERIOD_TYPES, true)) {
            return;
        }

        if ($type === 'TAHUNAN') {
            if ($number !== null) {
                $validator->errors()->add('period_number', 'Periode tahunan tidak memakai nomor periode.');
            }

            return;
        }

        $allowed = SurveyPeriod::PERIOD_NUMBERS[$type] ?? [];

        if ($number === null || ! in_array((int) $number, $allowed, true)) {
            $validator->errors()->add('period_number', 'Nomor periode tidak valid untuk tipe ini.');
        }
    }
}
