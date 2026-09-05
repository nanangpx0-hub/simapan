<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\DocumentLocation;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentLocationTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['code', 'name'];

    #[Url]
    public string $q = '';

    #[Url]
    public int $perPage = 15;

    #[Url]
    public string $sortField = 'code';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'selectedId']);
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
        return route('document_locations.export', array_filter([
            'q' => trim($this->q) !== '' ? trim($this->q) : null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', DocumentLocation::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 15;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'code';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $locations = DocumentLocation::query()
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        return view('livewire.master.document-location-table', [
            'locations' => $locations,
            'canManage' => auth()->user()?->can('document.manage') ?? false,
        ]);
    }
}
