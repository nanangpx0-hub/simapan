<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreWorkUnitRequest;
use App\Http\Requests\Master\UpdateWorkUnitRequest;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class WorkUnitController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', WorkUnit::class);

        $units = WorkUnit::query()->with('parent')->orderBy('code')->paginate(15);

        $depths = [];
        foreach ($units as $unit) {
            $depths[$unit->getKey()] = $this->depth($unit);
        }

        return view('master.unit-kerja.index', ['units' => $units, 'depths' => $depths]);
    }

    public function create(): View
    {
        Gate::authorize('create', WorkUnit::class);

        $parents = WorkUnit::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.unit-kerja.create', ['parents' => $parents]);
    }

    public function store(StoreWorkUnitRequest $request): RedirectResponse
    {
        Gate::authorize('create', WorkUnit::class);

        DB::transaction(function () use ($request): void {
            WorkUnit::create([
                'code' => $request->string('code')->toString(),
                'name' => $request->string('name')->toString(),
                'parent_id' => $request->input('parent_id'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('master.unit-kerja.index');
    }

    public function edit(WorkUnit $workUnit): View
    {
        Gate::authorize('update', $workUnit);

        $parents = WorkUnit::query()
            ->where(function ($query) use ($workUnit): void {
                $query->where('is_active', true)
                    ->where('id', '!=', $workUnit->getKey());

                if ($workUnit->parent_id !== null) {
                    $query->orWhere('id', $workUnit->parent_id);
                }
            })
            ->orderBy('code')
            ->get();

        return view('master.unit-kerja.edit', ['unit' => $workUnit->load('parent'), 'parents' => $parents]);
    }

    public function update(UpdateWorkUnitRequest $request, WorkUnit $workUnit): RedirectResponse
    {
        Gate::authorize('update', $workUnit);

        DB::transaction(function () use ($request, $workUnit): void {
            $workUnit->update([
                'name' => $request->string('name')->toString(),
                'parent_id' => $request->input('parent_id'),
                'is_active' => $request->boolean('is_active', $workUnit->is_active),
            ]);
        });

        return redirect()->route('master.unit-kerja.index');
    }

    private function depth(WorkUnit $unit): int
    {
        $depth = 0;
        $current = $unit->parent;

        while ($current instanceof WorkUnit && $depth < 100) {
            $depth++;
            $current = $current->parent()->first();
        }

        return $depth;
    }
}
