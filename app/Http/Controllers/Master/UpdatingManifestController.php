<?php

declare(strict_types=1);

namespace App\Http\Controllers\Master;

use App\Actions\Master\CreateUpdatingDocumentManifest;
use App\Actions\Master\ReceiveUpdatingManifest;
use App\Actions\Master\ValidateUpdatingEntry;
use App\Exports\UpdatingDocumentDeliveryExport;
use App\Http\Controllers\Controller;
use App\Models\Allocation;
use App\Models\DocumentManifest;
use App\Models\UpdatingManifestItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UpdatingManifestController extends Controller
{
    public function index(): View
    {
        Gate::authorize('viewAny', DocumentManifest::class);

        $manifests = DocumentManifest::query()
            ->whereHas('updatingItems')
            ->withCount('updatingItems')
            ->orderByDesc('id')
            ->paginate(15);

        return view('master.dokumen.pemutakhiran.index', ['manifests' => $manifests]);
    }

    public function create(): View
    {
        Gate::authorize('create', DocumentManifest::class);

        $allocations = Allocation::query()
            ->with(['period.surveyType', 'village.parent'])
            ->whereDoesntHave('updatingManifestItems')
            ->orderBy('nks')
            ->limit(500)
            ->get();

        return view('master.dokumen.pemutakhiran.create-manifest', ['allocations' => $allocations]);
    }

    public function store(Request $request, CreateUpdatingDocumentManifest $action): RedirectResponse
    {
        Gate::authorize('create', DocumentManifest::class);

        $validated = $request->validate([
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer', 'exists:allocations,id'],
            'rows' => ['required', 'array'],
            'rows.*.household_count_listing' => ['required', 'integer', 'min:0', 'max:99999'],
            'rows.*.has_vsen_p' => ['sometimes', 'boolean'],
            'rows.*.has_peta_ws' => ['sometimes', 'boolean'],
        ]);

        $rows = [];

        foreach (array_map('intval', $validated['selected']) as $allocationId) {
            $row = $validated['rows'][$allocationId] ?? null;

            if (! is_array($row)) {
                continue;
            }

            $rows[] = [
                'allocation_id' => $allocationId,
                'household_count_listing' => (int) $row['household_count_listing'],
                'has_vsen_p' => (bool) ($row['has_vsen_p'] ?? false),
                'has_peta_ws' => (bool) ($row['has_peta_ws'] ?? false),
            ];
        }

        $manifest = $action->handle($rows, $request->user());

        return redirect()->route('updating_manifests.show', $manifest);
    }

    public function show(DocumentManifest $manifest): View
    {
        Gate::authorize('view', $manifest);

        $manifest->load(['fromUnit', 'toUnit', 'updatingItems.allocation', 'updatingItems.processor']);

        return view('master.dokumen.pemutakhiran.show', ['manifest' => $manifest]);
    }

    public function receive(Request $request, DocumentManifest $manifest, ReceiveUpdatingManifest $action): RedirectResponse
    {
        Gate::authorize('receive', $manifest);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.has_vsen_p' => ['sometimes', 'boolean'],
            'items.*.has_peta_ws' => ['sometimes', 'boolean'],
            'items.*.receive_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $checks = [];

        foreach ($validated['items'] as $id => $row) {
            $checks[(int) $id] = [
                'has_vsen_p' => (bool) ($row['has_vsen_p'] ?? false),
                'has_peta_ws' => (bool) ($row['has_peta_ws'] ?? false),
                'receive_note' => $row['receive_note'] ?? null,
            ];
        }

        $action->handle($manifest, $checks, $request->user());

        return redirect()->route('updating_manifests.show', $manifest);
    }

    public function validateEntry(
        Request $request,
        DocumentManifest $manifest,
        UpdatingManifestItem $item,
        ValidateUpdatingEntry $action
    ): RedirectResponse {
        if ((int) $item->document_manifest_id !== (int) $manifest->getKey()) {
            abort(404);
        }

        Gate::authorize('assignUpdatingEntry', [$manifest, $item]);

        $validated = $request->validate([
            'entry_count' => ['required', 'integer', 'min:0', 'max:999999'],
        ]);

        $report = $action->handle($item, (int) $validated['entry_count'], $request->user());

        if ($report->entry_status === 'DISCREPANCY') {
            return redirect()->route('updating_manifests.show', $manifest)
                ->with('warning', __('Selisih entri NKS :nks: listing :target ruta, dientri :entry ruta.', [
                    'nks' => $item->nks,
                    'target' => $report->target_qty,
                    'entry' => $report->processed_qty,
                ]));
        }

        return redirect()->route('updating_manifests.show', $manifest)
            ->with('status', __('Entri NKS :nks sinkron dengan listing.', ['nks' => $item->nks]));
    }

    public function print(DocumentManifest $manifest): View
    {
        Gate::authorize('view', $manifest);

        $manifest->load(['fromUnit', 'toUnit', 'updatingItems.allocation.activeAssignments.officer', 'updatingItems.processor']);

        return view('master.dokumen.pemutakhiran.bast', ['manifest' => $manifest]);
    }

    public function export(DocumentManifest $manifest): BinaryFileResponse
    {
        Gate::authorize('view', $manifest);

        $safeNumber = str_replace(['/', '\\'], '-', (string) $manifest->manifest_number);

        return Excel::download(
            new UpdatingDocumentDeliveryExport((int) $manifest->getKey()),
            'pemutakhiran-'.$safeNumber.'.xlsx'
        );
    }
}
