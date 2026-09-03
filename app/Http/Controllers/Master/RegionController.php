<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRegionRequest;
use App\Http\Requests\Master\UpdateRegionRequest;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RegionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Region::class);

        $filters = $request->validate([
            'level' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $regions = Region::query()
            ->with('parent')
            ->withCount(['children as active_children_count' => function ($query): void {
                $query->where('is_active', true);
            }])
            ->when(($filters['level'] ?? null) && in_array($filters['level'], Region::LEVELS, true), function ($query) use ($filters): void {
                $query->where('level', $filters['level']);
            })
            ->when(isset($filters['parent_id']) && is_numeric($filters['parent_id']), function ($query) use ($filters): void {
                $query->where('parent_id', (int) $filters['parent_id']);
            })
            ->when(($filters['status'] ?? null) === 'aktif', function ($query): void {
                $query->where('is_active', true);
            })
            ->when(($filters['status'] ?? null) === 'nonaktif', function ($query): void {
                $query->where('is_active', false);
            })
            ->when(! empty($filters['q'] ?? null), function ($query) use ($filters): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $filters['q']).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('code', 'like', $keyword)
                        ->orWhere('full_code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy('full_code')
            ->paginate(15)
            ->withQueryString();

        $depths = [];
        foreach ($regions as $region) {
            $depths[$region->getKey()] = $this->depth($region);
        }

        $filterParents = Region::query()->orderBy('full_code')->get();

        return view('master.wilayah.index', [
            'regions' => $regions,
            'depths' => $depths,
            'levels' => Region::LEVELS,
            'filterParents' => $filterParents,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Region::class);

        $parents = Region::query()->active()->orderBy('full_code')->get();

        return view('master.wilayah.create', ['parents' => $parents, 'levels' => Region::LEVELS]);
    }

    public function store(StoreRegionRequest $request): RedirectResponse
    {
        Gate::authorize('create', Region::class);

        DB::transaction(function () use ($request): void {
            $parent = $request->input('parent_id') !== null
                ? Region::query()->findOrFail((int) $request->input('parent_id'))
                : null;

            $code = $request->string('code')->toString();

            $region = new Region([
                'parent_id' => $parent?->getKey(),
                'level' => $request->string('level')->toString(),
                'code' => $code,
                'name' => $request->string('name')->toString(),
                'is_active' => $request->boolean('is_active', true),
            ]);
            $region->full_code = $parent instanceof Region ? $parent->full_code.$code : $code;
            $region->save();
        });

        return redirect()->route('master.wilayah.index');
    }

    public function edit(Region $region): View
    {
        Gate::authorize('update', $region);

        $region->load('parent');
        $hasChildren = $region->children()->exists();

        $parents = Region::query()
            ->where('id', '!=', $region->getKey())
            ->where(function ($query) use ($region): void {
                $query->where('is_active', true);

                if ($region->parent_id !== null) {
                    $query->orWhere('id', $region->parent_id);
                }
            })
            ->orderBy('full_code')
            ->get();

        return view('master.wilayah.edit', [
            'region' => $region,
            'parents' => $parents,
            'hasChildren' => $hasChildren,
        ]);
    }

    public function update(UpdateRegionRequest $request, Region $region): RedirectResponse
    {
        Gate::authorize('update', $region);

        DB::transaction(function () use ($request, $region): void {
            $parentId = $request->input('parent_id') !== null ? (int) $request->input('parent_id') : null;

            $fullCode = $region->full_code;

            if ($parentId !== (int) $region->parent_id) {
                $parent = $parentId !== null ? Region::query()->findOrFail($parentId) : null;
                $fullCode = $parent instanceof Region ? $parent->full_code.$region->code : $region->code;
            }

            $region->full_code = $fullCode;
            $region->update([
                'name' => $request->string('name')->toString(),
                'parent_id' => $parentId,
                'is_active' => $request->boolean('is_active', $region->is_active),
            ]);
        });

        return redirect()->route('master.wilayah.index');
    }

    private function depth(Region $region): int
    {
        $depth = 0;
        $current = $region->parent;

        while ($current instanceof Region && $depth < 100) {
            $depth++;
            $current = $current->parent()->first();
        }

        return $depth;
    }
}
