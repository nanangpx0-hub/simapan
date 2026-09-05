<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class DocumentTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['document_number', 'title', 'status', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $documentTypeId = '';

    #[Url]
    public int $perPage = 10;

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

    public function updatingDocumentTypeId(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'status', 'documentTypeId', 'selectedId']);
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
        return route('documents.export', array_filter([
            'status' => $this->status ?: null,
            'document_type_id' => $this->documentTypeId ?: null,
            'q' => trim($this->q) !== '' ? trim($this->q) : null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Document::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'created_at';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'desc';
        }

        $user = auth()->user();
        $userModel = $user instanceof \App\Models\User ? $user : null;
        $officerId = $userModel?->officerId();

        $documents = Document::query()
            ->with(['type', 'allocation', 'dsrtSample'])
            ->when($this->status !== '' && in_array($this->status, Document::STATUSES, true), fn ($q) => $q->where('status', $this->status))
            ->when($this->documentTypeId !== '' && is_numeric($this->documentTypeId), fn ($q) => $q->where('document_type_id', (int) $this->documentTypeId))
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('document_number', 'like', $keyword)->orWhere('title', 'like', $keyword);
                });
            })
            ->when((bool) ($userModel?->hasScopedDataAccess() ?? false), function ($query) use ($officerId): void {
                if ($officerId === null) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->where(function ($q) use ($officerId): void {
                        $q->whereHas('allocation.activeAssignments', fn ($a) => $a->where('officer_id', $officerId))
                            ->orWhereHas('processingAssignments', fn ($p) => $p->where('officer_id', $officerId)->where('status', 'ACTIVE'));
                    });
                }
            })
            ->when($userModel?->hasRole('social_operator') ?? false, function ($query): void {
                $query->where(function ($q): void {
                    $q->whereDoesntHave('allocation')
                        ->orWhereHas(
                            'allocation.period.surveyType',
                            fn ($t) => $t->whereIn('code', \App\Models\User::SOCIAL_SURVEY_CODES)
                        );
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $types = Cache::remember('document-table:types', 600, fn () => DocumentType::query()->orderBy('code')->get(['id', 'code']));

        return view('livewire.master.document-table', [
            'documents' => $documents,
            'types' => $types,
            'statuses' => Document::STATUSES,
            'canManage' => auth()->user()?->can('document.manage') ?? false,
        ]);
    }
}
