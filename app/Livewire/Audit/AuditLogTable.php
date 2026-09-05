<?php

declare(strict_types=1);

namespace App\Livewire\Audit;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\DocumentManifest;
use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class AuditLogTable extends Component
{
    use WithPagination;

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public const SORTABLE = ['created_at'];

    public const FILTERABLE_ACTIONS = [
        'created',
        'updated',
        'deleted',
        'activated',
        'deactivated',
        'parent_changed',
        'user_linked',
        'user_unlinked',
        'closed',
        'archived',
        'role_assigned',
        'role_removed',
        'login_succeeded',
        'login_failed',
        'logout',
    ];

    public const FILTERABLE_TYPES = [
        User::class,
        SurveyType::class,
        WorkUnit::class,
        Region::class,
        SurveyPeriod::class,
        Officer::class,
        OfficerAlias::class,
        Allocation::class,
        Assignment::class,
        DocumentManifest::class,
    ];

    /**
     * Filter cepat event penting untuk pimpinan: perubahan status alokasi,
     * serah terima manifest, dan pergantian petugas.
     *
     * @var array<string, class-string>
     */
    public const EXECUTIVE_PRESETS = [
        'alokasi' => Allocation::class,
        'manifest' => DocumentManifest::class,
        'petugas' => Assignment::class,
    ];

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    #[Url]
    public string $userId = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $auditableType = '';

    #[Url]
    public string $auditableId = '';

    #[Url]
    public string $eventUuid = '';

    #[Url]
    public int $perPage = 10;

    #[Url]
    public string $sortField = 'created_at';

    #[Url]
    public string $sortDir = 'desc';

    public ?int $selectedId = null;

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingAuditableType(): void
    {
        $this->resetPage();
    }

    public function updatingAuditableId(): void
    {
        $this->resetPage();
    }

    public function updatingEventUuid(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['dateFrom', 'dateTo', 'userId', 'action', 'auditableType', 'auditableId', 'eventUuid', 'selectedId']);
        $this->resetPage();
    }

    public function preset(string $key): void
    {
        if (! array_key_exists($key, self::EXECUTIVE_PRESETS)) {
            return;
        }

        $this->auditableType = self::EXECUTIVE_PRESETS[$key];
        $this->action = '';
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
            $this->sortDir = 'desc';
        }

        $this->resetPage();
    }

    public function selectRow(int $id): void
    {
        $this->selectedId = $this->selectedId === $id ? null : $id;
    }

    public function render(): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        if (! in_array($this->perPage, self::PER_PAGE_OPTIONS, true)) {
            $this->perPage = 10;
        }

        $logs = AuditLog::query()
            ->with('user')
            ->when(trim($this->dateFrom) !== '', fn ($query) => $query->where('created_at', '>=', trim($this->dateFrom).' 00:00:00'))
            ->when(trim($this->dateTo) !== '', fn ($query) => $query->where('created_at', '<=', trim($this->dateTo).' 23:59:59'))
            ->when($this->userId !== '' && is_numeric($this->userId), fn ($query) => $query->where('user_id', (int) $this->userId))
            ->when($this->action !== '' && in_array($this->action, self::FILTERABLE_ACTIONS, true), fn ($query) => $query->where('action', $this->action))
            ->when($this->auditableType !== '' && in_array($this->auditableType, self::FILTERABLE_TYPES, true), fn ($query) => $query->where('auditable_type', $this->auditableType))
            ->when($this->auditableId !== '' && is_numeric($this->auditableId), fn ($query) => $query->where('auditable_id', (int) $this->auditableId))
            ->when(trim($this->eventUuid) !== '', fn ($query) => $query->where('event_uuid', trim($this->eventUuid)))
            ->orderBy($this->sortField, $this->sortDir)
            ->orderByDesc('id')
            ->paginate($this->perPage);

        $users = Cache::remember('audit-table:users', 600, fn () => User::query()->orderBy('name')->get(['id', 'name']));

        return view('livewire.audit.audit-log-table', [
            'logs' => $logs,
            'users' => $users,
            'actions' => self::FILTERABLE_ACTIONS,
            'types' => self::FILTERABLE_TYPES,
        ]);
    }
}
