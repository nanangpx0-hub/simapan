<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreRegionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.region.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'level' => ['required', 'string', Rule::in(Region::LEVELS)],
            'code' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9_-]{1,32}$/'],
            'full_code' => ['prohibited'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $level = (string) $this->input('level');
            $parentId = $this->input('parent_id');

            if ($level === 'PROVINSI') {
                if ($parentId !== null) {
                    $validator->errors()->add('parent_id', 'Provinsi tidak boleh memiliki parent.');
                }

                $this->checkFullCodeUnique($validator, (string) $this->input('code'));

                return;
            }

            if ($parentId === null) {
                $validator->errors()->add('parent_id', 'Parent wajib diisi untuk level ini.');

                return;
            }

            $parent = Region::query()->find($parentId);

            if (! $parent instanceof Region) {
                return;
            }

            $expected = Region::PARENT_LEVELS[$level] ?? null;

            if ($expected === null || $parent->level !== $expected) {
                $validator->errors()->add('parent_id', 'Parent harus berlevel '.$expected.'.');

                return;
            }

            $this->checkFullCodeUnique($validator, $parent->full_code.(string) $this->input('code'));
        });
    }

    private function checkFullCodeUnique(Validator $validator, string $fullCode): void
    {
        if (Region::query()->where('full_code', $fullCode)->exists()) {
            $validator->errors()->add('code', 'Kombinasi kode pada hierarki ini sudah digunakan.');
        }
    }
}
