<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\SurveyPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSurveyPeriodRequest extends FormRequest
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
        /** @var SurveyPeriod|null $period */
        $period = $this->route('surveyPeriod');

        return [
            'code' => ['sometimes', 'string', Rule::in([$period?->code])],
            'survey_type_id' => ['sometimes', 'integer', Rule::in([$period?->survey_type_id])],
            'period_type' => ['sometimes', 'string', Rule::in([$period?->period_type])],
            'period_number' => ['sometimes', 'nullable', 'integer', Rule::in([$period?->period_number])],
            'year' => ['sometimes', 'integer', Rule::in([$period?->year])],
            'name' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var SurveyPeriod|null $period */
            $period = $this->route('surveyPeriod');

            if (! $period instanceof SurveyPeriod) {
                return;
            }

            if ($period->status !== 'DRAFT') {
                $validator->errors()->add('status', 'Hanya periode DRAFT yang dapat diubah; gunakan action status.');

                return;
            }

            if ($this->has('period_number') && $period->period_type !== 'TAHUNAN') {
                $allowed = SurveyPeriod::PERIOD_NUMBERS[$period->period_type] ?? [];
                $number = $this->input('period_number');

                if ($number === null || ! in_array((int) $number, $allowed, true)) {
                    $validator->errors()->add('period_number', 'Nomor periode tidak valid untuk tipe ini.');
                }
            }
        });
    }
}
