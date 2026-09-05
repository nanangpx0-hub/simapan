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

    public function index(): View
    {
        Gate::authorize('viewAny', AuditLog::class);

        return view('audit-logs.index');
    }

    public function show(AuditLog $auditLog): View
    {
        Gate::authorize('view', $auditLog);

        return view('audit-logs.show', ['log' => $auditLog->load('user')]);
    }
}
