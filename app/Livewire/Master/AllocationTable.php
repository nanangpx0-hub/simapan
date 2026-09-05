<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AllocationTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['nks', 'created_at', 'status'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $surveyPeriodId = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $villageRegionId = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'nks';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingSurveyPeriodId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingVillageRegionId(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'surveyPeriodId', 'status', 'villageRegionId', 'selectedId']);
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
        return route('allocations.export', array_filter([
            'q' => $this->q ?: null,
            'survey_period_id' => $this->surveyPeriodId ?: null,
            'status' => $this->status ?: null,
            'village_region_id' => $this->villageRegionId ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Allocation::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        $user = auth()->user();
        $userModel = $user instanceof \App\Models\User ? $user : null;
        $officerId = $userModel?->officerId();

        $allocations = Allocation::query()
            ->with(['period.surveyType', 'village', 'activeAssignments.officer'])
            ->when($this->surveyPeriodId !== '' && is_numeric($this->surveyPeriodId), fn ($query) => $query->forPeriod((int) $this->surveyPeriodId))
            ->when($this->status !== '' && in_array($this->status, Allocation::STATUSES, true), fn ($query) => $query->byStatus($this->status))
            ->when($this->villageRegionId !== '' && is_numeric($this->villageRegionId), fn ($query) => $query->forVillage((int) $this->villageRegionId))
            ->when(trim($this->q) !== '', function ($query): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nks', 'like', $keyword)
                        ->orWhere('sls_code', 'like', $keyword)
                        ->orWhere('sls_name', 'like', $keyword);
                });
            })
            ->when((bool) ($userModel?->hasScopedDataAccess() ?? false), function ($query) use ($officerId): void {
                if ($officerId === null) {
                    $query->whereRaw('1 = 0');
                } else {
                    $query->whereHas('activeAssignments', fn ($q) => $q->where('officer_id', $officerId));
                }
            })
            ->when($userModel?->hasRole('social_operator') ?? false, fn ($query) => $query->whereHas(
                'period.surveyType',
                fn ($q) => $q->whereIn('code', \App\Models\User::SOCIAL_SURVEY_CODES)
            ))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $periods = Cache::remember('allocation-table:periods', 600, fn () => SurveyPeriod::query()->orderByDesc('year')->orderBy('code')->get(['id', 'code']));
        $villages = Cache::remember('allocation-table:villages', 600, fn () => Region::query()->where('level', 'DESA_KELURAHAN_NAGARI')->orderBy('full_code')->get(['id', 'full_code']));

        return view('livewire.master.allocation-table', [
            'allocations' => $allocations,
            'periods' => $periods,
            'villages' => $villages,
            'statuses' => Allocation::STATUSES,
            'roles' => Allocation::ASSIGNMENT_ROLES,
            'canManage' => auth()->user()?->can('allocation.manage') ?? false,
        ]);
    }
}
