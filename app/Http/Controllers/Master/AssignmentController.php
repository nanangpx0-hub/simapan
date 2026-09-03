<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\AssignOfficer;
use App\Actions\Master\UnassignOfficer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAssignmentRequest;
use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\Officer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AssignmentController extends Controller
{
    public function index(Allocation $allocation): View
    {
        Gate::authorize('view', $allocation);

        return view('master.alokasi.penugasan.index', [
            'allocation' => $allocation,
            'active' => $allocation->activeAssignments()->with(['officer', 'assigner'])->get(),
            'history' => $allocation->assignments()->with(['officer', 'assigner'])->orderByDesc('id')->paginate(15),
            'roles' => Assignment::ROLES,
            'officers' => Officer::query()->active()->with('workUnit')->orderBy('code')->get(),
        ]);
    }

    public function store(
        StoreAssignmentRequest $request,
        Allocation $allocation,
        AssignOfficer $action
    ): RedirectResponse {
        Gate::authorize('create', Assignment::class);

        $officer = Officer::query()->findOrFail((int) $request->input('officer_id'));

        $action->handle(
            $allocation,
            $officer,
            (string) $request->input('assignment_role'),
            $request->input('employment_category') !== null ? (string) $request->input('employment_category') : null,
            $request->user()
        );

        return redirect()->route('allocations.assignments.index', $allocation);
    }

    public function unassign(
        Allocation $allocation,
        Assignment $assignment,
        UnassignOfficer $action
    ): RedirectResponse {
        if ((int) $assignment->allocation_id !== (int) $allocation->getKey()) {
            abort(404);
        }

        Gate::authorize('update', $assignment);

        $action->handle($assignment, request()->user());

        return redirect()->route('allocations.assignments.index', $allocation);
    }
}
