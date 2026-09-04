<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\DocumentManifest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateDocumentManifestRequest extends FormRequest
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
        return [
            'from_work_unit_id' => ['required', 'integer', 'exists:work_units,id'],
            'to_work_unit_id' => ['required', 'integer', 'different:from_work_unit_id', 'exists:work_units,id'],
            'manifest_number' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var DocumentManifest|null $manifest */
            $manifest = $this->route('documentManifest');

            if (! $manifest instanceof DocumentManifest) {
                return;
            }

            if (! $manifest->isEditable()) {
                $validator->errors()->add('status', 'Manifest status '.$manifest->status.' tidak dapat diubah.');
            }
        });
    }
}
