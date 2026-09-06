<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\WorkUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class WorkUnitTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['code', 'name', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'code';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingQ(): void
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
        $this->reset(['q', 'status', 'selectedId']);
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
        $unit = WorkUnit::findOrFail($id);
        Gate::authorize('delete', $unit);

        if ($unit->children()->exists()) {
            $count = $unit->children()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus unit kerja ":code" karena masih memiliki :count sub-unit kerja. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua sub-unit kerja terlebih dahulu, atau 2) Nonaktifkan unit kerja ini saja.',
                ['code' => $unit->code, 'count' => $count]
            ));
            return;
        }

        if ($unit->officers()->exists()) {
            $count = $unit->officers()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus unit kerja ":code" karena masih terhubung dengan :count petugas. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua petugas ke unit kerja lain terlebih dahulu, atau 2) Nonaktifkan unit kerja ini saja.',
                ['code' => $unit->code, 'count' => $count]
            ));
            return;
        }

        $unit->delete();
        session()->flash('status', __('Unit kerja ":code" berhasil dihapus.', ['code' => $unit->code]));
        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('master.unit-kerja.export', array_filter([
            'q' => $this->q ?: null,
            'status' => $this->status ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', WorkUnit::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'code';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $units = WorkUnit::query()
            ->with('parent')
            ->when($this->status === 'aktif', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'nonaktif', fn ($q) => $q->where('is_active', false))
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.master.work-unit-table', [
            'units' => $units,
            'canManage' => auth()->user()?->can('master.work_unit.manage') ?? false,
        ]);
    }
}
