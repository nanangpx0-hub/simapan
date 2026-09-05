<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\Officer;
use App\Models\WorkUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class OfficerTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['code', 'name', 'status', 'created_at'];

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $workUnitId = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'code';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingWorkUnitId(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'status', 'workUnitId', 'selectedId']);
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

    public function exportUrl(): string
    {
        return route('master.officers.export', array_filter([
            'search' => $this->search ?: null,
            'status' => $this->status ?: null,
            'work_unit_id' => $this->workUnitId ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Officer::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        $officers = Officer::query()
            ->with(['workUnit', 'user'])
            ->withCount('aliases')
            ->when($this->workUnitId !== '' && is_numeric($this->workUnitId), fn ($q) => $q->where('work_unit_id', (int) $this->workUnitId))
            ->when($this->status !== '' && in_array($this->status, Officer::STATUSES, true), fn ($q) => $q->where('status', $this->status))
            ->when(trim($this->search) !== '', fn ($q) => $q->search(trim($this->search)))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $units = Cache::remember('officer-table:units', 600, fn () => WorkUnit::query()->orderBy('code')->get(['id', 'code']));

        return view('livewire.master.officer-table', [
            'officers' => $officers,
            'units' => $units,
            'canManage' => auth()->user()?->can('master.officer.manage') ?? false,
        ]);
    }
}
