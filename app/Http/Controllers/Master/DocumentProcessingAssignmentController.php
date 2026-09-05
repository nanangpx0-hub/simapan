<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\AssignDocumentToProcessingOfficer;
use App\Actions\Master\ReturnDocumentProcessingAssignment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AssignDocumentToProcessingOfficerRequest;
use App\Models\Document;
use App\Models\DocumentLocation;
use App\Models\DocumentProcessingAssignment;
use App\Models\Officer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DocumentProcessingAssignmentController extends Controller
{
    /**
     * Petugas pengolahan yang dapat dipilih: aktif dan berunit
     * PENGOLAHAN_LS atau IPDS.
     */
    private function processingOfficers()
    {
        return Officer::query()
            ->active()
            ->whereHas('workUnit', fn ($query) => $query->whereIn('code', DocumentProcessingAssignment::PROCESSING_UNIT_CODES))
            ->with('workUnit')
            ->orderBy('code')
            ->get();
    }

    public function index(Document $document): View
    {
        Gate::authorize('view', $document);

        return view('master.dokumen.penugasan.index', [
            'document' => $document,
            'active' => $document->processingAssignments()->active()->with(['officer', 'assigner'])->get(),
            'history' => $document->processingAssignments()->with(['officer', 'assigner'])->orderByDesc('id')->paginate(15),
            'officers' => $this->processingOfficers(),
            'locations' => DocumentLocation::query()->where('is_active', true)->orderBy('code')->get(),
            'conditions' => Document::CONDITIONS,
        ]);
    }

    public function create(Document $document): View
    {
        Gate::authorize('create', DocumentProcessingAssignment::class);

        $officers = $this->processingOfficers();
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

    public function returnAssignment(
        Request $request,
        Document $document,
        DocumentProcessingAssignment $assignment,
        ReturnDocumentProcessingAssignment $action
    ): RedirectResponse {
        if ((int) $assignment->document_id !== (int) $document->getKey()) {
            abort(404);
        }

        Gate::authorize('update', $assignment);

        $validated = $request->validate([
            'document_location_id' => ['nullable', 'integer', Rule::exists('document_locations', 'id')->where('is_active', true)],
            'condition_code' => ['required', 'string', Rule::in(Document::CONDITIONS)],
        ]);

        $action->handle(
            $assignment,
            ($validated['document_location_id'] ?? null) !== null ? (int) $validated['document_location_id'] : null,
            (string) $validated['condition_code'],
            $request->user()
        );

        return redirect()->route('document_processing_assignments.index', $document);
    }
}
