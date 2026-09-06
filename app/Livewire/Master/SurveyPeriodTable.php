<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SurveyPeriodTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['code', 'year', 'status', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $surveyTypeId = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $year = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'year';

    #[Url]
    public string $sortDir = 'desc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingSurveyTypeId(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['q', 'surveyTypeId', 'status', 'year', 'selectedId']);
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
        $period = SurveyPeriod::findOrFail($id);
        Gate::authorize('delete', $period);

        if ($period->allocations()->exists()) {
            $count = $period->allocations()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus periode survei ":code" karena masih terhubung dengan :count alokasi. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua alokasi yang menggunakan periode survei ini, atau 2) Arsipkan periode survei ini saja.',
                ['code' => $period->code, 'count' => $count]
            ));
            return;
        }

        $period->delete();
        session()->flash('status', __('Periode survei ":code" berhasil dihapus.', ['code' => $period->code]));
        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('master.survey_periods.export', array_filter([
            'survey_type_id' => $this->surveyTypeId ?: null,
            'status' => $this->status ?: null,
            'year' => $this->year ?: null,
            'q' => $this->q ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', SurveyPeriod::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'year';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'desc';
        }

        $periods = SurveyPeriod::query()
            ->with(['surveyType'])
            ->when($this->surveyTypeId !== '' && is_numeric($this->surveyTypeId), fn ($q) => $q->where('survey_type_id', (int) $this->surveyTypeId))
            ->when($this->status !== '' && in_array($this->status, SurveyPeriod::STATUSES, true), fn ($q) => $q->where('status', $this->status))
            ->when($this->year !== '' && is_numeric($this->year), fn ($q) => $q->where('year', (int) $this->year))
            ->when(trim($this->q) !== '', function ($q): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $q->where(function ($q) use ($keyword): void {
                    $q->where('code', 'like', $keyword)->orWhere('name', 'like', $keyword);
                });
            })
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $types = Cache::remember('survey-period-table:types', 600, fn () => SurveyType::query()->orderBy('code')->get(['id', 'code']));

        return view('livewire.master.survey-period-table', [
            'periods' => $periods,
            'types' => $types,
            'statuses' => SurveyPeriod::STATUSES,
            'canManage' => auth()->user()?->can('master.survey_period.manage') ?? false,
        ]);
    }
}
