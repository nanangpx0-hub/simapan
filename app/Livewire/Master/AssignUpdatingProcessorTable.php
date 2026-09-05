<?php

declare(strict_types=1);

namespace App\Livewire\Master;

use App\Models\DocumentManifest;
use App\Models\DocumentProcessingAssignment;
use App\Models\Officer;
use App\Models\UpdatingManifestItem;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AssignUpdatingProcessorTable extends Component
{
    public int $manifestId;

    /** @var list<int> */
    public array $selected = [];

    public string $officerId = '';

    public function mount(int $manifestId): void
    {
        $this->manifestId = $manifestId;
    }

    public function toggleAll(): void
    {
        $ids = $this->assignableIds();

        $this->selected = count($this->selected) === count($ids) ? [] : $ids;
    }

    /**
     * @return list<int>
     */
    private function assignableIds(): array
    {
        return UpdatingManifestItem::query()
            ->where('document_manifest_id', $this->manifestId)
            ->where('delivery_status', 'RECEIVED_BY_PLS')
            ->orderBy('nks')
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    public function assign(): void
    {
        Gate::authorize('create', DocumentProcessingAssignment::class);

        $this->validate([
            'officerId' => ['required', 'integer'],
            'selected' => ['required', 'array', 'min:1'],
            'selected.*' => ['integer'],
        ]);

        $officer = Officer::query()->findOrFail((int) $this->officerId);

        if ($officer->status !== 'ACTIVE' || $officer->trashed()) {
            $this->addError('officerId', 'Petugas harus aktif dan tidak terhapus.');

            return;
        }

        if ($officer->workUnit?->code !== 'PENGOLAHAN_LS') {
            $this->addError('officerId', 'Petugas harus berasal dari unit PENGOLAHAN_LS.');

            return;
        }

        $ids = array_map('intval', $this->selected);

        DB::transaction(function () use ($ids, $officer): void {
            $items = UpdatingManifestItem::query()
                ->where('document_manifest_id', $this->manifestId)
                ->whereIn('id', $ids)
                ->where('delivery_status', 'RECEIVED_BY_PLS')
                ->lockForUpdate()
                ->get();

            foreach ($items as $item) {
                $item->processing_officer_id = $officer->getKey();
                $item->delivery_status = 'ASSIGNED_TO_PROCESSOR';
                $item->save();

                AuditLogger::log('assigned', $item, ['delivery_status' => 'RECEIVED_BY_PLS'], ['delivery_status' => 'ASSIGNED_TO_PROCESSOR'], [
                    'officer_id' => $officer->getKey(),
                ]);
            }
        });

        $this->selected = [];
        $this->officerId = '';

        session()->flash('status', __('Penugasan pengolah berhasil disimpan.'));
    }

    public function render(): View
    {
        Gate::authorize('viewAny', DocumentManifest::class);

        $manifest = DocumentManifest::query()->findOrFail($this->manifestId);

        $items = UpdatingManifestItem::query()
            ->where('document_manifest_id', $this->manifestId)
            ->with(['allocation', 'processor'])
            ->orderBy('nks')
            ->get();

        $officers = Officer::query()
            ->active()
            ->whereHas('workUnit', fn ($query) => $query->where('code', 'PENGOLAHAN_LS'))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('livewire.master.assign-updating-processor-table', [
            'manifest' => $manifest,
            'items' => $items,
            'officers' => $officers,
        ]);
    }
}
