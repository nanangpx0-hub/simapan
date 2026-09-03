<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\WorkUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateWorkUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.work_unit.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $workUnit = $this->route('workUnit');

        return [
            'code' => ['sometimes', 'string', Rule::in([$workUnit?->code])],
            'name' => ['required', 'string', 'max:150'],
            'parent_id' => ['nullable', 'integer', Rule::exists('work_units', 'id')->where('is_active', true)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var WorkUnit|null $workUnit */
            $workUnit = $this->route('workUnit');

            if (! $workUnit instanceof WorkUnit) {
                return;
            }

            $parentId = $this->input('parent_id');

            if ($parentId !== null && (int) $parentId === (int) $workUnit->getKey()) {
                $validator->errors()->add('parent_id', 'Unit kerja tidak boleh menjadi parent bagi dirinya sendiri.');
            }

            if ($parentId !== null && $this->createsCycle($workUnit, (int) $parentId)) {
                $validator->errors()->add('parent_id', 'Parent tersebut akan membentuk siklus hierarki.');
            }

            $deactivating = $this->has('is_active') && ! (bool) $this->boolean('is_active');

            if ($deactivating && $workUnit->children()->exists()) {
                $validator->errors()->add('is_active', 'Unit dengan child tidak boleh dinonaktifkan sebelum child dipindahkan.');
            }
        });
    }

    private function createsCycle(WorkUnit $workUnit, int $parentId): bool
    {
        $visited = [];
        $current = WorkUnit::query()->find($parentId);

        while ($current instanceof WorkUnit) {
            if ((int) $current->getKey() === (int) $workUnit->getKey()) {
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
