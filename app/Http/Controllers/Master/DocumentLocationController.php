<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Exports\DocumentLocationExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDocumentLocationRequest;
use App\Http\Requests\Master\UpdateDocumentLocationRequest;
use App\Imports\DocumentLocationImport;
use App\Models\DocumentLocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentLocationController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentLocation::class);

        return view('master.dokumen.lokasi.index');
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentLocation::class);

        return view('master.dokumen.lokasi.create');
    }

    public function store(StoreDocumentLocationRequest $request): RedirectResponse
    {
        Gate::authorize('create', DocumentLocation::class);

        DB::transaction(function () use ($request): void {
            DocumentLocation::create([
                'code' => $request->string('code')->toString(),
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
            ]);
        });

        return redirect()->route('document_locations.index');
    }

    public function edit(DocumentLocation $documentLocation): View
    {
        Gate::authorize('update', $documentLocation);

        return view('master.dokumen.lokasi.edit', ['location' => $documentLocation]);
    }

    public function update(UpdateDocumentLocationRequest $request, DocumentLocation $documentLocation): RedirectResponse
    {
        Gate::authorize('update', $documentLocation);

        DB::transaction(function () use ($request, $documentLocation): void {
            $documentLocation->update([
                'name' => $request->string('name')->toString(),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', $documentLocation->is_active),
            ]);
        });

        return redirect()->route('document_locations.index');
    }

    public function export(Request $request): BinaryFileResponse
    {
        Gate::authorize('viewAny', DocumentLocation::class);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
        ]);

        return Excel::download(new DocumentLocationExport($filters), 'document-locations.xlsx');
    }

    public function import(Request $request): RedirectResponse
    {
        Gate::authorize('create', DocumentLocation::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,csv', 'max:5120'],
        ]);

        $import = new DocumentLocationImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('document_locations.index')->with('status', __('Impor selesai: :n data baru.', ['n' => $import->imported]));
    }
}
