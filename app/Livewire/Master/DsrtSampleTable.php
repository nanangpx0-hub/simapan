<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\Allocation;
use App\Models\DsrtSample;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DsrtSampleTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['nus', 'nurt', 'krt_name', 'record_status', 'enumeration_status', 'created_at'];

    public int $allocationId;

    #[Url]
    public string $q = '';

    #[Url]
    public string $recordStatus = '';

    #[Url]
    public string $enumerationStatus = '';

    #[Url]
    public int $perPage = 15;

    #[Url]
    public string $sortField = 'nurt';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function mount(int $allocationId): void
    {
        $this->allocationId = $allocationId;
    }

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingRecordStatus(): void
    {
        $this->resetPage();
    }

    public function updatingEnumerationStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'recordStatus', 'enumerationStatus', 'selectedId']);
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
        return route('allocations.dsrt.export', array_filter([
            'allocation' => $this->allocationId,
            'record_status' => $this->recordStatus ?: null,
            'enumeration_status' => $this->enumerationStatus ?: null,
            'q' => trim($this->q) !== '' ? trim($this->q) : null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', DsrtSample::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 15;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'nurt';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $allocation = Allocation::query()->findOrFail($this->allocationId);

        Gate::authorize('view', $allocation);

        $samples = DsrtSample::query()
            ->forAllocation((int) $allocation->getKey())
            ->when($this->recordStatus !== '' && in_array($this->recordStatus, DsrtSample::RECORD_STATUSES, true), fn ($q) => $q->where('record_status', $this->recordStatus))
            ->when($this->enumerationStatus !== '' && in_array($this->enumerationStatus, DsrtSample::ENUMERATION_STATUSES, true), fn ($q) => $q->where('enumeration_status', $this->enumerationStatus))
            ->when(trim($this->q) !== '', fn ($q) => $q->search(trim($this->q)))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.master.dsrt-sample-table', [
            'allocation' => $allocation,
            'samples' => $samples,
            'recordStatuses' => DsrtSample::RECORD_STATUSES,
            'enumerationStatuses' => DsrtSample::ENUMERATION_STATUSES,
            'canManage' => auth()->user()?->can('dsrt.manage') ?? false,
        ]);
    }
}
