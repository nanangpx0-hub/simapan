<?php

declare(strict_types=1);

namespace App\Http\Requests\Master;

use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Support\NameNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateOfficerAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('master.officer.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'alias_name' => ['required', 'string', 'max:150'],
            'normalized_alias' => ['prohibited'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var Officer|null $officer */
            $officer = $this->route('officer');
            /** @var OfficerAlias|null $alias */
            $alias = $this->route('alias');

            if (! $officer instanceof Officer || ! $alias instanceof OfficerAlias) {
                return;
            }

            $normalized = NameNormalizer::normalize((string) $this->input('alias_name'));

            if ($normalized === '') {
                $validator->errors()->add('alias_name', 'Alias tidak boleh kosong setelah normalisasi.');

                return;
            }

            if ($normalized === $officer->normalized_name) {
                $validator->errors()->add('alias_name', 'Alias sama dengan nama utama petugas.');

                return;
            }

            $duplicate = $officer->aliases()
                ->where('normalized_alias', $normalized)
                ->where('id', '!=', $alias->getKey())
                ->exists();

            if ($duplicate) {
                $validator->errors()->add('alias_name', 'Alias ini sudah digunakan petugas tersebut.');
            }
        });
    }
}
