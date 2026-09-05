<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\DocumentExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDocumentRequest;
use App\Http\Requests\Master\UpdateDocumentRequest;
use App\Imports\DocumentImport;
use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentLocation;
use App\Models\DocumentType;
use App\Models\DsrtSample;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', Document::class);

        return view('master.dokumen.index');
    }

    public function create(): View
    {
        Gate::authorize('create', Document::class);

        $types = DocumentType::query()->where('is_active', true)->orderBy('code')->get();
        $allocations = Allocation::query()->orderByDesc('id')->limit(200)->get();
        $samples = DsrtSample::query()->orderByDesc('id')->limit(200)->get();
        $locations = DocumentLocation::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.dokumen.create', [
            'types' => $types,
            'allocations' => $allocations,
            'samples' => $samples,
            'conditions' => Document::CONDITIONS,
            'locations' => $locations,
        ]);
    }

    public function store(StoreDocumentRequest $request): RedirectResponse
    {
        Gate::authorize('create', Document::class);

        DB::transaction(function () use ($request): void {
            $document = Document::create([
                'document_type_id' => (int) $request->input('document_type_id'),
                'allocation_id' => $request->input('allocation_id') !== null ? (int) $request->input('allocation_id') : null,
                'dsrt_sample_id' => $request->input('dsrt_sample_id') !== null ? (int) $request->input('dsrt_sample_id') : null,
                'document_number' => $request->input('document_number'),
                'title' => $request->string('title')->toString(),
                'format' => 'PHYSICAL',
                'quantity' => (int) $request->input('quantity', 1),
                'status' => 'REGISTERED',
                'notes' => $request->input('notes'),
                'created_by' => $request->user()->getKey(),
            ]);

            $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

            $document->holder()->create([
                'holder_type' => 'WORK_UNIT',
                'work_unit_id' => $sosial->getKey(),
                'officer_id' => null,
                'document_location_id' => $request->input('document_location_id') !== null ? (int) $request->input('document_location_id') : null,
                'condition_code' => $request->string('condition_code', 'GOOD')->toString(),
                'assigned_by' => $request->user()->getKey(),
                'assigned_at' => now(),
            ]);

            $document->holderHistories()->create([
                'to_holder_type' => 'WORK_UNIT',
                'to_work_unit_id' => $sosial->getKey(),
                'to_document_location_id' => $request->input('document_location_id') !== null ? (int) $request->input('document_location_id') : null,
                'condition_after' => $request->string('condition_code', 'GOOD')->toString(),
                'movement_type' => 'REGISTERED',
                'reference_type' => Document::class,
                'reference_id' => $document->getKey(),
                'moved_by' => $request->user()->getKey(),
                'moved_at' => now(),
            ]);
        });

        return redirect()->route('documents.index');
    }

    public function show(Document $document): View
    {
        Gate::authorize('view', $document);

        return view('master.dokumen.show', [
            'document' => $document->load([
                'type',
                'allocation.period',
                'dsrtSample.allocation',
                'holder.workUnit',
                'holder.officer',
                'holder.location',
                'holderHistories',
                'manifestItems.manifest',
                'processingAssignments.officer',
            ]),
        ]);
    }

    public function edit(Document $document): View
    {
        Gate::authorize('update', $document);

        $types = DocumentType::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.dokumen.edit', ['document' => $document, 'types' => $types]);
    }

    public function update(UpdateDocumentRequest $request, Document $document): RedirectResponse
    {
        Gate::authorize('update', $document);

        DB::transaction(function () use ($request, $document): void {
            $document->update([
                'document_type_id' => (int) $request->input('document_type_id', $document->document_type_id),
                'document_number' => $request->input('document_number'),
                'title' => $request->string('title')->toString(),
                'quantity' => (int) $request->input('quantity', $document->quantity),
                'notes' => $request->input('notes'),
            ]);
        });

        return redirect()->route('documents.show', $document);
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', Document::class);

        $filters = $request->validate([
            'status' => ['nullable', 'string'],
            'document_type_id' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new DocumentExport($filters), 'documents.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', Document::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new DocumentImport((int) $request->user()->getKey());
        Excel::import($import, $request->file('file'));

        return redirect()->route('documents.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }
}
