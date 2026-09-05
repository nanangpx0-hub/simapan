<?php

declare(strict_types=1);

namespace App\Exports;

use App\Models\UpdatingManifestItem;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UpdatingDocumentDeliveryExport implements FromCollection, ShouldAutoSize, WithHeadings
{
    use Exportable;

    public function __construct(private int $manifestId) {}

    public function collection(): Collection
    {
        $items = UpdatingManifestItem::query()
            ->where('document_manifest_id', $this->manifestId)
            ->with(['allocation.activeAssignments.officer', 'processor'])
            ->orderBy('nks')
            ->get();

        $rows = [];

        foreach ($items->values() as $i => $item) {
            $pcl = $item->allocation?->activeAssignments->firstWhere('assignment_role', 'FIELD_OFFICER')?->officer?->name ?? '';
            $pml = $item->allocation?->activeAssignments->firstWhere('assignment_role', 'FIELD_SUPERVISOR')?->officer?->name ?? '';

            $rows[] = [
                $i + 1,
                (string) $item->kecamatan_code,
                (string) $item->desa_code,
                (string) $item->nks,
                (int) $item->household_count_listing,
                $pcl,
                $pml,
                (string) ($item->processor?->name ?? ''),
                $item->has_vsen_p ? 'V' : '',
                $item->has_peta_ws ? 'V' : '',
                (string) $item->delivery_status,
            ];
        }

        return collect($rows);
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['No', 'Kecamatan', 'Desa', 'NKS', 'Jumlah Rumah Tangga', 'PCL', 'PML', 'Pengolah', 'VSEN26.P', 'Peta WS', 'Status'];
    }
}
