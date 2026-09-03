<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\ActivateAllocation;
use App\Actions\Master\ArchiveAllocation;
use App\Actions\Master\CompleteAllocation;
use App\Actions\Master\ResumeAllocation;
use App\Actions\Master\SuspendAllocation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAllocationRequest;
use App\Http\Requests\Master\UpdateAllocationRequest;
use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AllocationController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Allocation::class);

        $filters = $request->validate([
            'survey_period_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'village_region_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $allocations = Allocation::query()
            ->with(['period.surveyType', 'village', 'activeAssignments.officer'])
            ->when(isset($filters['survey_period_id']) && is_numeric($filters['survey_period_id']), function ($query) use ($filters): void {
                $query->forPeriod((int) $filters['survey_period_id']);
            })
            ->when(($filters['status'] ?? null) && in_array($filters['status'], Allocation::STATUSES, true), function ($query) use ($filters): void {
                $query->byStatus($filters['status']);
            })
            ->when(isset($filters['village_region_id']) && is_numeric($filters['village_region_id']), function ($query) use ($filters): void {
                $query->forVillage((int) $filters['village_region_id']);
            })
            ->when(! empty($filters['q'] ?? null), function ($query) use ($filters): void {
                $keyword = '%'.str_replace(['%', '_'], '', (string) $filters['q']).'%';
                $query->where(function ($query) use ($keyword): void {
                    $query->where('nks', 'like', $keyword)
                        ->orWhere('sls_name', 'like', $keyword);
                });
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $periods = SurveyPeriod::query()->orderByDesc('year')->orderBy('code')->get();
        $villages = Region::query()->where('level', 'DESA_KELURAHAN_NAGARI')->orderBy('full_code')->get();

        return view('master.alokasi.index', [
            'allocations' => $allocations,
            'periods' => $periods,
            'villages' => $villages,
            'statuses' => Allocation::STATUSES,
            'roles' => Allocation::ASSIGNMENT_ROLES,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Allocation::class);

        $periods = SurveyPeriod::query()->where('status', '!=', 'ARCHIVED')->orderByDesc('year')->orderBy('code')->get();
        $villages = Region::query()
            ->where('level', 'DESA_KELURAHAN_NAGARI')
            ->where('is_active', true)
            ->orderBy('full_code')
            ->get();

        return view('master.alokasi.create', ['periods' => $periods, 'villages' => $villages]);
    }

    public function store(StoreAllocationRequest $request): RedirectResponse
    {
        Gate::authorize('create', Allocation::class);

        DB::transaction(function () use ($request): void {
            Allocation::create([
                'survey_period_id' => (int) $request->input('survey_period_id'),
                'village_region_id' => (int) $request->input('village_region_id'),
                'nks' => $request->string('nks')->toString(),
                'sls_code' => $request->input('sls_code'),
                'sub_sls_code' => $request->input('sub_sls_code'),
                'sls_name' => $request->string('sls_name')->toString(),
                'status' => 'DRAFT',
                'notes' => $request->input('notes'),
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return redirect()->route('allocations.index');
    }

    public function show(Allocation $allocation): View
    {
        Gate::authorize('view', $allocation);

        return view('master.alokasi.show', [
            'allocation' => $allocation->load([
                'period.surveyType',
                'village',
                'creator',
                'activeAssignments.officer',
                'assignments.officer',
                'assignments.assigner',
            ]),
        ]);
    }

    public function edit(Allocation $allocation): View
    {
        Gate::authorize('update', $allocation);

        if (! $allocation->isEditable()) {
            abort(403, 'Alokasi status '.$allocation->status.' tidak dapat diubah.');
        }

        return view('master.alokasi.edit', ['allocation' => $allocation->load(['period.surveyType', 'village'])]);
    }

    public function update(UpdateAllocationRequest $request, Allocation $allocation): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        DB::transaction(function () use ($request, $allocation): void {
            $allocation->update([
                'sls_name' => $request->string('sls_name')->toString(),
                'notes' => $request->input('notes'),
            ]);
        });

        return redirect()->route('allocations.index');
    }

    public function activate(Request $request, Allocation $allocation, ActivateAllocation $action): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        $action->handle($allocation, $request->user());

        return redirect()->route('allocations.show', $allocation);
    }

    public function suspend(Request $request, Allocation $allocation, SuspendAllocation $action): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        $action->handle($allocation, $request->user());

        return redirect()->route('allocations.show', $allocation);
    }

    public function resume(Request $request, Allocation $allocation, ResumeAllocation $action): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        $action->handle($allocation, $request->user());

        return redirect()->route('allocations.show', $allocation);
    }

    public function complete(Request $request, Allocation $allocation, CompleteAllocation $action): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        $action->handle($allocation, $request->user());

        return redirect()->route('allocations.show', $allocation);
    }

    public function archive(Request $request, Allocation $allocation, ArchiveAllocation $action): RedirectResponse
    {
        Gate::authorize('update', $allocation);

        $action->handle($allocation, $request->user());

        return redirect()->route('allocations.show', $allocation);
    }
}
