<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSurveyTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.survey_type.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $surveyType = $this->route('surveyType');

        return [
            'code' => ['sometimes', 'string', Rule::in([$surveyType?->code])],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
