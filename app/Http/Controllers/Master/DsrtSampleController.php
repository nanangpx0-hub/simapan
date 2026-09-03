<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\ArchiveDsrtSample;
use App\Actions\Master\VerifyDsrtSample;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDsrtSampleRequest;
use App\Http\Requests\Master\UpdateDsrtSampleRequest;
use App\Models\Allocation;
use App\Models\DsrtSample;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DsrtSampleController extends Controller
{
    private function findSample(Allocation $allocation, DsrtSample $sample): DsrtSample
    {
        if ((int) $sample->allocation_id !== (int) $allocation->getKey()) {
            abort(404);
        }

        return $sample;
    }

    private function ensureSusenas(Allocation $allocation): void
    {
        if ($allocation->period?->surveyType?->code !== 'SUSENAS') {
            abort(422, 'DSRT hanya untuk alokasi Susenas.');
        }
    }

    public function index(Allocation $allocation, Request $request): View
    {
        Gate::authorize('viewAny', DsrtSample::class);
        $this->ensureSusenas($allocation);

        $filters = $request->validate([
            'record_status' => ['nullable', 'string'],
            'enumeration_status' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        $samples = DsrtSample::query()
            ->forAllocation((int) $allocation->getKey())
            ->when(($filters['record_status'] ?? null) && in_array($filters['record_status'], DsrtSample::RECORD_STATUSES, true), function ($query) use ($filters): void {
                $query->where('record_status', $filters['record_status']);
            })
            ->when(($filters['enumeration_status'] ?? null) && in_array($filters['enumeration_status'], DsrtSample::ENUMERATION_STATUSES, true), function ($query) use ($filters): void {
                $query->where('enumeration_status', $filters['enumeration_status']);
            })
            ->when(! empty($filters['q'] ?? null), function ($query) use ($filters): void {
                $query->search((string) $filters['q']);
            })
            ->orderBy('nurt')
            ->paginate(15)
            ->withQueryString();

        return view('master.alokasi.dsrt.index', [
            'allocation' => $allocation->load(['period.surveyType', 'village']),
            'samples' => $samples,
            'recordStatuses' => DsrtSample::RECORD_STATUSES,
            'enumerationStatuses' => DsrtSample::ENUMERATION_STATUSES,
            'filters' => $filters,
        ]);
    }

    public function create(Allocation $allocation): View
    {
        Gate::authorize('create', DsrtSample::class);
        $this->ensureSusenas($allocation);

        return view('master.alokasi.dsrt.create', [
            'allocation' => $allocation,
            'enumerationStatuses' => DsrtSample::ENUMERATION_STATUSES,
        ]);
    }

    public function store(StoreDsrtSampleRequest $request, Allocation $allocation): RedirectResponse
    {
        Gate::authorize('create', DsrtSample::class);
        $this->ensureSusenas($allocation);

        DB::transaction(function () use ($request, $allocation): void {
            DsrtSample::create([
                'allocation_id' => $allocation->getKey(),
                'nus' => $request->string('nus')->toString(),
                'nurt' => $request->string('nurt')->toString(),
                'family_number' => $request->input('family_number'),
                'building_number' => $request->input('building_number'),
                'household_number' => $request->input('household_number'),
                'krt_name' => $request->string('krt_name')->toString(),
                'address' => $request->input('address'),
                'krt_education_code' => $request->input('krt_education_code'),
                'enumeration_status' => $request->string('enumeration_status', 'PENDING')->toString(),
                'contact_person' => $request->input('contact_person'),
                'contact_phone' => $request->input('contact_phone'),
                'notes' => $request->input('notes'),
                'record_status' => 'DRAFT',
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return redirect()->route('allocations.dsrt.index', $allocation);
    }

    public function show(Allocation $allocation, DsrtSample $dsrtSample): View
    {
        $dsrtSample = $this->findSample($allocation, $dsrtSample);
        Gate::authorize('view', $dsrtSample);
        $this->ensureSusenas($allocation);

        return view('master.alokasi.dsrt.show', [
            'allocation' => $allocation,
            'sample' => $dsrtSample->load(['creator', 'verifier', 'archiver']),
        ]);
    }

    public function edit(Allocation $allocation, DsrtSample $dsrtSample): View
    {
        $dsrtSample = $this->findSample($allocation, $dsrtSample);
        Gate::authorize('update', $dsrtSample);
        $this->ensureSusenas($allocation);

        return view('master.alokasi.dsrt.edit', [
            'allocation' => $allocation,
            'sample' => $dsrtSample,
            'enumerationStatuses' => DsrtSample::ENUMERATION_STATUSES,
        ]);
    }

    public function update(UpdateDsrtSampleRequest $request, Allocation $allocation, DsrtSample $dsrtSample): RedirectResponse
    {
        $dsrtSample = $this->findSample($allocation, $dsrtSample);
        Gate::authorize('update', $dsrtSample);
        $this->ensureSusenas($allocation);

        DB::transaction(function () use ($request, $dsrtSample): void {
            $dsrtSample->update([
                'family_number' => $request->input('family_number'),
                'building_number' => $request->input('building_number'),
                'household_number' => $request->input('household_number'),
                'krt_name' => $request->string('krt_name')->toString(),
                'address' => $request->input('address'),
                'krt_education_code' => $request->input('krt_education_code'),
                'enumeration_status' => $request->string('enumeration_status')->toString(),
                'contact_person' => $request->input('contact_person'),
                'contact_phone' => $request->input('contact_phone'),
                'notes' => $request->input('notes'),
            ]);
        });

        return redirect()->route('allocations.dsrt.index', $allocation);
    }

    public function verify(Request $request, Allocation $allocation, DsrtSample $dsrtSample, VerifyDsrtSample $action): RedirectResponse
    {
        $dsrtSample = $this->findSample($allocation, $dsrtSample);
        Gate::authorize('update', $dsrtSample);
        $this->ensureSusenas($allocation);

        if (! $request->user()->can('dsrt.verify')) {
            abort(403);
        }

        $action->handle($dsrtSample, $request->user());

        return redirect()->route('allocations.dsrt.show', [$allocation, $dsrtSample]);
    }

    public function archive(Request $request, Allocation $allocation, DsrtSample $dsrtSample, ArchiveDsrtSample $action): RedirectResponse
    {
        $dsrtSample = $this->findSample($allocation, $dsrtSample);
        Gate::authorize('update', $dsrtSample);
        $this->ensureSusenas($allocation);

        if (! $request->user()->can('dsrt.verify')) {
            abort(403);
        }

        $action->handle($dsrtSample, $request->user());

        return redirect()->route('allocations.dsrt.show', [$allocation, $dsrtSample]);
    }
}
