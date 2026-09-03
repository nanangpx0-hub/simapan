<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * @var list<string>
     */
    private const FILTERABLE_ACTIONS = [
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

    /**
     * @var list<string>
     */
    private const FILTERABLE_TYPES = [
        User::class,
        SurveyType::class,
        WorkUnit::class,
        Region::class,
        SurveyPeriod::class,
        Officer::class,
        OfficerAlias::class,
    ];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        $filters = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer'],
            'action' => ['nullable', 'string'],
            'auditable_type' => ['nullable', 'string'],
            'auditable_id' => ['nullable', 'integer'],
            'event_uuid' => ['nullable', 'string', 'max:36'],
        ]);

        $logs = AuditLog::query()
            ->with('user')
            ->when(! empty($filters['date_from'] ?? null), function ($query) use ($filters): void {
                $query->where('created_at', '>=', $filters['date_from'].' 00:00:00');
            })
            ->when(! empty($filters['date_to'] ?? null), function ($query) use ($filters): void {
                $query->where('created_at', '<=', $filters['date_to'].' 23:59:59');
            })
            ->when(isset($filters['user_id']) && is_numeric($filters['user_id']), function ($query) use ($filters): void {
                $query->where('user_id', (int) $filters['user_id']);
            })
            ->when(($filters['action'] ?? null) && in_array($filters['action'], self::FILTERABLE_ACTIONS, true), function ($query) use ($filters): void {
                $query->where('action', $filters['action']);
            })
            ->when(($filters['auditable_type'] ?? null) && in_array($filters['auditable_type'], self::FILTERABLE_TYPES, true), function ($query) use ($filters): void {
                $query->where('auditable_type', $filters['auditable_type']);
            })
            ->when(isset($filters['auditable_id']) && is_numeric($filters['auditable_id']), function ($query) use ($filters): void {
                $query->where('auditable_id', (int) $filters['auditable_id']);
            })
            ->when(! empty($filters['event_uuid'] ?? null), function ($query) use ($filters): void {
                $query->where('event_uuid', (string) $filters['event_uuid']);
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $users = User::query()->orderBy('name')->get();

        return view('audit-logs.index', [
            'logs' => $logs,
            'users' => $users,
            'actions' => self::FILTERABLE_ACTIONS,
            'types' => self::FILTERABLE_TYPES,
            'filters' => $filters,
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('view', $auditLog);

        return view('audit-logs.show', ['log' => $auditLog->load('user')]);
    }
}
