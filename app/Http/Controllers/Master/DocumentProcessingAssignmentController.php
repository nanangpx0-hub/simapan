<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\AssignDocumentToProcessingOfficer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AssignDocumentToProcessingOfficerRequest;
use App\Models\Document;
use App\Models\DocumentLocation;
use App\Models\DocumentProcessingAssignment;
use App\Models\Officer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentProcessingAssignmentController extends Controller
{
    public function index(Document $document): View
    {
        Gate::authorize('view', $document);

        return view('master.dokumen.penugasan.index', [
            'document' => $document,
            'active' => $document->processingAssignments()->active()->with(['officer', 'assigner'])->get(),
            'history' => $document->processingAssignments()->with(['officer', 'assigner'])->orderByDesc('id')->paginate(15),
            'officers' => Officer::query()->active()->with('workUnit')->orderBy('code')->get(),
            'locations' => DocumentLocation::query()->where('is_active', true)->orderBy('code')->get(),
            'conditions' => Document::CONDITIONS,
        ]);
    }

    public function create(Document $document): View
    {
        Gate::authorize('create', DocumentProcessingAssignment::class);

        $officers = Officer::query()->active()->with('workUnit')->orderBy('code')->get();
        $locations = DocumentLocation::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.dokumen.penugasan.create', [
            'document' => $document,
            'officers' => $officers,
            'locations' => $locations,
            'conditions' => Document::CONDITIONS,
        ]);
    }

    public function store(
        AssignDocumentToProcessingOfficerRequest $request,
        Document $document,
        AssignDocumentToProcessingOfficer $action
    ): RedirectResponse {
        Gate::authorize('create', DocumentProcessingAssignment::class);

        $officer = Officer::query()->findOrFail((int) $request->input('officer_id'));

        $action->handle(
            $document,
            $officer,
            $request->input('document_location_id') !== null ? (int) $request->input('document_location_id') : null,
            (string) $request->input('condition_code'),
            $request->input('note'),
            $request->user()
        );

        return redirect()->route('document_processing_assignments.index', $document);
    }
}
