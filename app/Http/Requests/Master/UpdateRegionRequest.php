<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Region;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRegionRequest extends FormRequest
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
        /** @var Region|null $region */
        $region = $this->route('region');

        return [
            'code' => ['sometimes', 'string', Rule::in([$region?->code])],
            'level' => ['sometimes', 'string', Rule::in([$region?->level])],
            'full_code' => ['prohibited'],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists('regions', 'id')->where('is_active', true)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Region|null $region */
            $region = $this->route('region');

            if (! $region instanceof Region) {
                return;
            }

            $parentId = $this->input('parent_id');

            $deactivating = $this->has('is_active') && ! (bool) $this->boolean('is_active');

            if ($deactivating && $region->activeChildren()->exists()) {
                $validator->errors()->add('is_active', 'Wilayah dengan child aktif tidak boleh dinonaktifkan.');
            }

            if ($region->level === 'PROVINSI') {
                if ($parentId !== null) {
                    $validator->errors()->add('parent_id', 'Provinsi tidak boleh memiliki parent.');
                }

                return;
            }

            if ($parentId === null) {
                $validator->errors()->add('parent_id', 'Parent wajib diisi untuk level ini.');

                return;
            }

            if ((int) $parentId === (int) $region->getKey()) {
                $validator->errors()->add('parent_id', 'Wilayah tidak boleh menjadi parent bagi dirinya sendiri.');

                return;
            }

            if ($region->children()->exists() && (int) $parentId !== (int) $region->parent_id) {
                $validator->errors()->add('parent_id', 'Wilayah yang memiliki child tidak dapat dipindahkan parent.');

                return;
            }

            $parent = Region::query()->find($parentId);

            if (! $parent instanceof Region) {
                return;
            }

            $expected = Region::PARENT_LEVELS[$region->level] ?? null;

            if ($expected === null || $parent->level !== $expected) {
                $validator->errors()->add('parent_id', 'Parent harus berlevel '.$expected.'.');

                return;
            }

            if ($this->createsCycle($region, (int) $parentId)) {
                $validator->errors()->add('parent_id', 'Parent tersebut akan membentuk siklus hierarki.');

                return;
            }

            $candidate = $parent->full_code.$region->code;

            if (Region::query()->where('full_code', $candidate)->where('id', '!=', $region->getKey())->exists()) {
                $validator->errors()->add('parent_id', 'Perpindahan ini menyebabkan full_code ganda.');
            }
        });
    }

    private function createsCycle(Region $region, int $parentId): bool
    {
        $visited = [];
        $current = Region::query()->find($parentId);

        while ($current instanceof Region) {
            if ((int) $current->getKey() === (int) $region->getKey()) {
                return true;
            }

            if (in_array($current->getKey(), $visited, true)) {
                break;
            }

            $visited[] = $current->getKey();
            $current = $current->parent;
        }

        return false;
    }
}
