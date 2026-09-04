<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentManifestRequest extends FormRequest
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
            'submitted_by' => ['prohibited'],
            'submitted_at' => ['prohibited'],
            'received_by' => ['prohibited'],
            'received_at' => ['prohibited'],
            'created_by' => ['prohibited'],
        ];
    }
}
