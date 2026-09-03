<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreOfficerAliasRequest;
use App\Http\Requests\Master\UpdateOfficerAliasRequest;
use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Support\NameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OfficerAliasController extends Controller
{
    private function findAlias(Officer $officer, OfficerAlias $alias): OfficerAlias
    {
        if ((int) $alias->officer_id !== (int) $officer->getKey()) {
            abort(404);
        }

        return $alias;
    }

    public function index(Officer $officer): View
    {
        Gate::authorize('view', $officer);

        return view('master.petugas.alias.index', [
            'officer' => $officer,
            'aliases' => $officer->aliases()->with('creator')->orderBy('alias_name')->paginate(15),
        ]);
    }

    public function create(Officer $officer): View
    {
        Gate::authorize('create', OfficerAlias::class);

        return view('master.petugas.alias.create', ['officer' => $officer]);
    }

    public function store(StoreOfficerAliasRequest $request, Officer $officer): RedirectResponse
    {
        Gate::authorize('create', OfficerAlias::class);

        $normalized = NameNormalizer::normalize((string) $request->input('alias_name'));

        $warning = Officer::query()
            ->where('id', '!=', $officer->getKey())
            ->where(function ($query) use ($normalized): void {
                $query->where('normalized_name', $normalized)
                    ->orWhereHas('aliases', function ($query) use ($normalized): void {
                        $query->where('normalized_alias', $normalized);
                    });
            })
            ->exists()
            ? 'Alias ini juga cocok dengan petugas lain; mohon dicek.'
            : null;

        DB::transaction(function () use ($request, $officer): void {
            $officer->aliases()->create([
                'alias_name' => $request->string('alias_name')->toString(),
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return redirect()
            ->route('master.officers.aliases.index', $officer)
            ->with('warning', $warning);
    }

    public function edit(Officer $officer, OfficerAlias $alias): View
    {
        $alias = $this->findAlias($officer, $alias);
        Gate::authorize('update', $alias);

        return view('master.petugas.alias.edit', ['officer' => $officer, 'alias' => $alias]);
    }

    public function update(UpdateOfficerAliasRequest $request, Officer $officer, OfficerAlias $alias): RedirectResponse
    {
        $alias = $this->findAlias($officer, $alias);
        Gate::authorize('update', $alias);

        DB::transaction(function () use ($request, $alias): void {
            $alias->update([
                'alias_name' => $request->string('alias_name')->toString(),
            ]);
        });

        return redirect()->route('master.officers.aliases.index', $officer);
    }
}
