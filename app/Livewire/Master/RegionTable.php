<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\Region;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class RegionTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['full_code', 'code', 'name', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $level = '';

    #[Url]
    public string $parentId = '';

    #[Url]
    public string $status = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'full_code';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingLevel(): void
    {
        $this->resetPage();
    }

    public function updatingParentId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'level', 'parentId', 'status', 'selectedId']);
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if (! in_array($field, self::SORTABLE, true)) {
            return;
        }

        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }

        $this->resetPage();
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
    }

    public function delete(int $id): void
    {
        $region = Region::findOrFail($id);
        Gate::authorize('delete', $region);

        if ($region->children()->exists()) {
            $count = $region->children()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus wilayah ":code" karena masih memiliki :count sub-wilayah. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua sub-wilayah terlebih dahulu, atau 2) Nonaktifkan wilayah ini saja.',
                ['code' => $region->full_code, 'count' => $count]
            ));
            return;
        }

        if ($region->allocations()->exists()) {
            $count = $region->allocations()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus wilayah ":code" karena masih terhubung dengan :count alokasi. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua alokasi yang menggunakan wilayah ini, atau 2) Nonaktifkan wilayah ini saja.',
                ['code' => $region->full_code, 'count' => $count]
            ));
            return;
        }

        $region->delete();
        session()->flash('status', __('Wilayah ":code" berhasil dihapus.', ['code' => $region->full_code]));
        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('master.wilayah.export', array_filter([
            'level' => $this->level ?: null,
            'parent_id' => $this->parentId ?: null,
            'status' => $this->status ?: null,
            'q' => $this->q ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Region::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'full_code';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $regions = Region::query()
            ->with('parent')
            ->withCount(['children as active_children_count' => fn ($q) => $q->where('is_active', true)])
            ->when($this->level !== '' && in_array($this->level, Region::LEVELS, true), fn ($q) => $q->where('level', $this->level))
            ->when($this->parentId !== '' && is_numeric($this->parentId), fn ($q) => $q->where('parent_id', (int) $this->parentId))
            ->when($this->status === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)
                        ->orWhere('full_code', 'like', $keyword)
                        ->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $parents = Cache::remember('region-table:parents', 600, fn () => Region::query()->orderBy('full_code')->get(['id', 'full_code', 'name']));

        return view('livewire.master.region-table', [
            'regions' => $regions,
            'parents' => $parents,
            'levels' => Region::LEVELS,
            'canManage' => auth()->user()?->can('master.region.manage') ?? false,
        ]);
    }
}
