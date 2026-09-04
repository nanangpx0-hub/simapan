<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\DocumentLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('document.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DocumentLocation|null $location */
        $location = $this->route('documentLocation');

        return [
            'code' => ['sometimes', 'string', Rule::in([$location?->code])],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
