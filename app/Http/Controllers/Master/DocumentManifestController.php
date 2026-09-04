<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\SubmitDocumentManifest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\StoreDocumentManifestItemRequest;
use App\Http\Requests\Master\StoreDocumentManifestRequest;
use App\Http\Requests\Master\UpdateDocumentManifestRequest;
use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\WorkUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentManifestController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentManifest::class);

        $manifests = DocumentManifest::query()
            ->with(['fromUnit', 'toUnit'])
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(15);

        return view('master.manifest.index', ['manifests' => $manifests]);
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentManifest::class);

        $units = WorkUnit::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.manifest.create', ['units' => $units]);
    }

    public function store(StoreDocumentManifestRequest $request): RedirectResponse
    {
        Gate::authorize('create', DocumentManifest::class);

        $manifest = DB::transaction(function () use ($request): DocumentManifest {
            return DocumentManifest::create([
                'manifest_number' => $this->nextManifestNumber(),
                'from_work_unit_id' => (int) $request->input('from_work_unit_id'),
                'to_work_unit_id' => (int) $request->input('to_work_unit_id'),
                'status' => 'DRAFT',
                'created_by' => $request->user()->getKey(),
            ]);
        });

        return redirect()->route('document_manifests.show', $manifest);
    }

    public function show(DocumentManifest $documentManifest): View
    {
        Gate::authorize('view', $documentManifest);

        return view('master.manifest.show', [
            'manifest' => $documentManifest->load(['fromUnit', 'toUnit', 'items.document', 'transfer.items']),
            'documents' => Document::query()->where('status', 'REGISTERED')->orderByDesc('id')->limit(200)->get(),
            'conditions' => Document::CONDITIONS,
        ]);
    }

    public function edit(DocumentManifest $documentManifest): View
    {
        Gate::authorize('update', $documentManifest);

        $units = WorkUnit::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.manifest.edit', ['manifest' => $documentManifest, 'units' => $units]);
    }

    public function update(UpdateDocumentManifestRequest $request, DocumentManifest $documentManifest): RedirectResponse
    {
        Gate::authorize('update', $documentManifest);

        DB::transaction(function () use ($request, $documentManifest): void {
            $documentManifest->update([
                'from_work_unit_id' => (int) $request->input('from_work_unit_id'),
                'to_work_unit_id' => (int) $request->input('to_work_unit_id'),
            ]);
        });

        return redirect()->route('document_manifests.show', $documentManifest);
    }

    public function storeItem(StoreDocumentManifestItemRequest $request, DocumentManifest $documentManifest): RedirectResponse
    {
        Gate::authorize('update', $documentManifest);

        DB::transaction(function () use ($request, $documentManifest): void {
            $documentManifest->items()->create([
                'document_id' => (int) $request->input('document_id'),
                'qty_sent' => (int) $request->input('qty_sent'),
                'condition_sent' => (string) $request->input('condition_sent'),
                'sent_note' => $request->input('sent_note'),
            ]);
        });

        return redirect()->route('document_manifests.show', $documentManifest);
    }

    public function submit(DocumentManifest $documentManifest, SubmitDocumentManifest $action): RedirectResponse
    {
        Gate::authorize('update', $documentManifest);

        $action->handle($documentManifest, request()->user());

        return redirect()->route('document_manifests.show', $documentManifest);
    }

    /**
     * @throws \RuntimeException
     */
    private function nextManifestNumber(): string
    {
        $day = now()->format('Ymd');

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $sequence = DB::transaction(function () use ($day): int {
                $last = DocumentManifest::query()
                    ->where('manifest_number', 'like', 'DM-'.$day.'-%')
                    ->lockForUpdate()
                    ->orderByDesc('manifest_number')
                    ->first();

                if (! $last instanceof DocumentManifest) {
                    return 1;
                }

                return (int) mb_substr((string) $last->manifest_number, -3) + 1;
            });

            $candidate = 'DM-'.$day.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

            if (! DocumentManifest::query()->where('manifest_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Gagal membuat nomor manifest unik.');
    }
}
