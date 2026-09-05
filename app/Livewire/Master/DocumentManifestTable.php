<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\DocumentManifest;
use App\Models\DocumentProcessingAssignment;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentManifestTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['manifest_number', 'status', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $toUnitId = '';

    #[Url]
    public int $perPage = 15;

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingToUnitId(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'status', 'toUnitId', 'selectedId']);
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
        return route('document_manifests.export', array_filter([
            'status' => $this->status ?: null,
            'q' => trim($this->q) !== '' ? trim($this->q) : null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', DocumentManifest::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 15;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'created_at';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'desc';
        }

        $user = auth()->user();
        $userModel = $user instanceof User ? $user : null;
        $isProcessing = ($userModel?->hasRole('ipds_operator') ?? false)
            || ($userModel?->hasRole('processing_supervisor') ?? false);

        $manifests = DocumentManifest::query()
            ->with(['fromUnit', 'toUnit'])
            ->withCount('items')
            ->when($this->status !== '' && in_array($this->status, DocumentManifest::STATUSES, true), fn ($q) => $q->where('status', $this->status))
            ->when($this->toUnitId !== '' && is_numeric($this->toUnitId), fn ($q) => $q->where('to_work_unit_id', (int) $this->toUnitId))
            ->when($isProcessing && $this->toUnitId === '', fn ($q) => $q->whereHas(
                'toUnit',
                fn ($t) => $t->whereIn('code', DocumentProcessingAssignment::PROCESSING_UNIT_CODES)
            ))
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where('manifest_number', 'like', $keyword);
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $units = Cache::remember('manifest-table:units', 600, fn () => WorkUnit::query()->orderBy('code')->get(['id', 'code']));

        return view('livewire.master.document-manifest-table', [
            'manifests' => $manifests,
            'statuses' => DocumentManifest::STATUSES,
            'units' => $units,
            'canManage' => auth()->user()?->can('document.manage') ?? false,
        ]);
    }
}
