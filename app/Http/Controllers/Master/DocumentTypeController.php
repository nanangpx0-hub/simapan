<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDocumentTypeRequest;
use App\Http\Requests\Master\UpdateDocumentTypeRequest;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentType::class);

        $types = DocumentType::query()->orderBy('code')->paginate(15);

        return view('master.dokumen.jenis.index', ['types' => $types]);
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
}
