<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\DsrtSample;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DocumentImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function __construct(private int $userId) {}

    public function collection(Collection $rows): void
    {
        $types = DocumentType::query()->where('is_active', true)->pluck('id', 'code');
        $sosial = WorkUnit::where('code', 'SOSIAL')->first();

        foreach ($rows as $row) {
            $title = trim((string) ($row['title'] ?? ''));
            $typeCode = trim((string) ($row['type_code'] ?? ''));
            $allocationNks = trim((string) ($row['allocation_nks'] ?? ''));
            $dsrtNurt = trim((string) ($row['dsrt_nurt'] ?? ''));
            $quantity = (int) ($row['quantity'] ?? 1);

            if ($title === '' || ! $types->has($typeCode)) {
                continue;
            }

            if ($quantity < 1) {
                $quantity = 1;
            }

            $hasAllocation = $allocationNks !== '';
            $hasSample = $dsrtNurt !== '';

            if ($hasAllocation === $hasSample) {
                continue;
            }

            $allocationId = null;
            $sampleId = null;

            if ($hasAllocation) {
                $allocationId = Allocation::where('nks', $allocationNks)->value('id');

                if ($allocationId === null) {
                    continue;
                }
            }

            if ($hasSample) {
                $sampleId = DsrtSample::where('nurt', $dsrtNurt)->value('id');

                if ($sampleId === null) {
                    continue;
                }
            }

            $document = Document::create([
                'document_type_id' => (int) $types->get($typeCode),
                'allocation_id' => $allocationId,
                'dsrt_sample_id' => $sampleId,
                'document_number' => trim((string) ($row['document_number'] ?? '')) ?: null,
                'title' => $title,
                'format' => 'PHYSICAL',
                'quantity' => $quantity,
                'status' => 'REGISTERED',
                'created_by' => $this->userId,
            ]);

            if ($sosial) {
                $document->holder()->create([
                    'holder_type' => 'WORK_UNIT',
                    'work_unit_id' => $sosial->getKey(),
                    'officer_id' => null,
                    'document_location_id' => null,
                    'condition_code' => 'GOOD',
                    'assigned_by' => $this->userId,
                    'assigned_at' => now(),
                ]);

                $document->holderHistories()->create([
                    'to_holder_type' => 'WORK_UNIT',
                    'to_work_unit_id' => $sosial->getKey(),
                    'condition_after' => 'GOOD',
                    'movement_type' => 'REGISTERED',
                    'reference_type' => Document::class,
                    'reference_id' => $document->getKey(),
                    'moved_by' => $this->userId,
                    'moved_at' => now(),
                ]);
            }

            $this->imported++;
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type_code' => ['required', 'string', 'max:32'],
            'allocation_nks' => ['nullable', 'string', 'max:32'],
            'dsrt_nurt' => ['nullable', 'string', 'max:32'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'document_number' => ['nullable', 'string', 'max:64'],
        ];
    }
}
