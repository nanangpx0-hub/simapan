<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\ReceiveDocumentManifest;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\ReceiveDocumentManifestRequest;
use App\Models\Document;
use App\Models\DocumentLocation;
use App\Models\DocumentManifest;
use App\Models\DocumentTransferItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentTransferController extends Controller
{
    public function show(DocumentManifest $documentManifest): View
    {
        $transfer = $documentManifest->transfer()->firstOrFail();
        Gate::authorize('view', $transfer);

        return view('master.manifest.serah-terima.show', [
            'manifest' => $documentManifest->load(['fromUnit', 'toUnit']),
            'transfer' => $transfer->load('items.manifestItem.document'),
        ]);
    }

    public function edit(DocumentManifest $documentManifest): View
    {
        $transfer = $documentManifest->transfer()->firstOrFail();
        Gate::authorize('update', $transfer);

        $locations = DocumentLocation::query()->where('is_active', true)->orderBy('code')->get();

        return view('master.manifest.serah-terima.edit', [
            'manifest' => $documentManifest,
            'transfer' => $transfer->load('items.manifestItem.document'),
            'locations' => $locations,
            'conditions' => Document::CONDITIONS,
            'receiptStatuses' => DocumentTransferItem::RECEIPT_STATUSES,
        ]);
    }

    public function bastIndex(): View
    {
        $manifests = DocumentManifest::query()
            ->with(['fromUnit', 'toUnit'])
            ->withCount('items')
            ->where('status', '!=', 'DRAFT')
            ->orderByDesc('submitted_at')
            ->paginate(25);

        return view('master.dokumen.sampel.bast', [
            'manifests' => $manifests,
        ]);
    }

    public function update(
        ReceiveDocumentManifestRequest $request,
        DocumentManifest $documentManifest,
        ReceiveDocumentManifest $action
    ): RedirectResponse {
        $transfer = $documentManifest->transfer()->firstOrFail();
        Gate::authorize('update', $transfer);

        $items = [];

        foreach ($request->input('items', []) as $row) {
            $items[(int) $row['id']] = [
                'qty_received' => (int) ($row['qty_received'] ?? 0),
                'condition_received' => $row['condition_received'] ?? null,
                'receipt_status' => (string) ($row['receipt_status'] ?? ''),
                'receipt_note' => $row['receipt_note'] ?? null,
            ];
        }

        $action->handle(
            $documentManifest,
            $items,
            $request->input('document_location_id') !== null ? (int) $request->input('document_location_id') : null,
            $request->input('receipt_note'),
            $request->user()
        );

        return redirect()->route('document_manifests.show', $documentManifest);
    }
}
