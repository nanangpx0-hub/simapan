<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\DocumentTypeExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDocumentTypeRequest;
use App\Http\Requests\Master\UpdateDocumentTypeRequest;
use App\Imports\DocumentTypeImport;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentType::class);

        return view('master.dokumen.jenis.index');
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentType::class);

        return view('master.dokumen.jenis.create');
    }

    public function store(StoreDocumentTypeRequest $request): RedirectResponse
    {
        Gate::authorize('create', DocumentType::class);

        DB::transaction(function () use ($request): void {
            DocumentType::create([
                'code' => $request->string('code')->toString(),
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('document_types.index');
    }

    public function edit(DocumentType $documentType): View
    {
        Gate::authorize('update', $documentType);

        return view('master.dokumen.jenis.edit', ['type' => $documentType]);
    }

    public function update(UpdateDocumentTypeRequest $request, DocumentType $documentType): RedirectResponse
    {
        Gate::authorize('update', $documentType);

        DB::transaction(function () use ($request, $documentType): void {
            $documentType->update([
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', $documentType->is_active),
            ]);
        });

        return redirect()->route('document_types.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', DocumentType::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new DocumentTypeExport($filters), 'document-types.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', DocumentType::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new DocumentTypeImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('document_types.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }
}
