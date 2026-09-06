<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\SurveyType;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SurveyTypeTable extends Component
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
        $type = SurveyType::findOrFail($id);
        Gate::authorize('delete', $type);

        if ($type->periods()->exists()) {
            $count = $type->periods()->count();
            session()->flash('error', __(
                'Tidak dapat menghapus jenis survei ":code" karena masih digunakan oleh :count periode survei. '.
                'Langkah yang dapat dilakukan: 1) Hapus atau pindahkan semua periode survei yang menggunakan jenis survei ini, atau 2) Nonaktifkan jenis survei ini saja.',
                ['code' => $type->code, 'count' => $count]
            ));
            return;
        }

        $type->delete();
        session()->flash('status', __('Jenis survei ":code" berhasil dihapus.', ['code' => $type->code]));
        $this->resetPage();
    }

    public function exportUrl(): string
    {
        return route('master.jenis-survei.export', array_filter([
            'q' => $this->q ?: null,
            'status' => $this->status ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', SurveyType::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'code';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $types = SurveyType::query()
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

        return view('livewire.master.survey-type-table', [
            'types' => $types,
            'canManage' => auth()->user()?->can('master.survey_type.manage') ?? false,
        ]);
    }
}
