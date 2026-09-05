<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['name', 'email', 'created_at'];

    #[Url]
    public string $q = '';

    #[Url]
    public string $role = '';

    #[Url]
    public string $status = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'name';

    #[Url]
    public string $sortDir = 'asc';

    public ?int $selectedId = null;

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
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
        $this->reset(['q', 'role', 'status', 'selectedId']);
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
        return route('admin.users.export', array_filter([
            'q' => $this->q ?: null,
            'role' => $this->role ?: null,
            'status' => $this->status ?: null,
        ]));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', User::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        if (! in_array($this->sortField, self::SORTABLE, true)) {
            $this->sortField = 'name';
        }

        if (! in_array($this->sortDir, ['asc', 'desc'], true)) {
            $this->sortDir = 'asc';
        }

        $users = User::query()
            ->with('roles')
            ->when(trim($this->q) !== '', function ($query): void {
                $keyword = '%'.str_replace(['%', '_'], '', trim($this->q)).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword);
                });
            })
            ->when($this->role !== '', fn ($query) => $query->role($this->role))
            ->when($this->status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy($this->sortField, $this->sortDir)
            ->paginate($this->perPage);

        $roles = Cache::remember('user-table:roles', 600, fn () => config('simapan_roles.roles', []));

        return view('livewire.admin.user-table', [
            'users' => $users,
            'roles' => $roles,
            'canManage' => auth()->user()?->can('admin.user.manage') ?? false,
        ]);
    }
}
