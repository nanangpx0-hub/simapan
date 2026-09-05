<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\ActivateAllocation;
use App\Actions\Master\ArchiveAllocation;
use App\Actions\Master\CompleteAllocation;
use App\Actions\Master\ResumeAllocation;
use App\Actions\Master\SuspendAllocation;
use App\Exports\AllocationExport;
use App\Exports\ExecutiveProgressExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreAllocationRequest;
use App\Http\Requests\Master\UpdateAllocationRequest;
use App\Imports\AllocationImport;
use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AllocationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Allocation::class);

        return view('master.alokasi.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Allocation::class);

        $filters = $request->validate([
            'survey_period_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'village_region_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new AllocationExport($filters), 'allocations.xlsx');
    }

    public function exportExecutive(): BinaryFileResponse
    {
        Gate::authorize('viewAny', Allocation::class);

        return Excel::download(new ExecutiveProgressExport, 'rekap-eksekutif.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Allocation::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new AllocationImport($request->user()->getKey());
        Excel::import($import, $request->file('file'));

        return redirect()->route('allocations.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
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
