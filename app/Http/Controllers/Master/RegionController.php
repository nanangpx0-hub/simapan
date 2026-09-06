<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\RegionExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreRegionRequest;
use App\Http\Requests\Master\UpdateRegionRequest;
use App\Imports\RegionImport;
use App\Models\Region;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RegionController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Region::class);

        return view('master.wilayah.index');
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

    public function destroy(Region $region): RedirectResponse
    {
        Gate::authorize('delete', $region);

        if ($region->children()->exists()) {
            return redirect()->route('master.wilayah.index')
                ->with('error', __('Tidak dapat menghapus wilayah karena masih memiliki sub-wilayah.'));
        }

        if ($region->allocations()->exists()) {
            return redirect()->route('master.wilayah.index')
                ->with('error', __('Tidak dapat menghapus wilayah karena masih terhubung dengan alokasi.'));
        }

        DB::transaction(function () use ($region): void {
            $region->delete();
        });

        return redirect()->route('master.wilayah.index')
            ->with('status', __('Wilayah berhasil dihapus.'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Region::class);

        $filters = $request->validate([
            'level' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new RegionExport($filters), 'regions.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Region::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new RegionImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('master.wilayah.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
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
