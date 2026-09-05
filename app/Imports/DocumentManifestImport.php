<?php

declare(strict_types=1);

namespace App\Imports;

use App\Models\DocumentManifest;
use App\Models\WorkUnit;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DocumentManifestImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $imported = 0;

    public function __construct(private int $userId) {}

    public function collection(Collection $rows): void
    {
        $units = WorkUnit::query()->where('is_active', true)->pluck('id', 'code');

        foreach ($rows as $row) {
            $fromCode = trim((string) ($row['from_unit_code'] ?? ''));
            $toCode = trim((string) ($row['to_unit_code'] ?? ''));

            if ($fromCode === '' || $toCode === '' || $fromCode === $toCode) {
                continue;
            }

            if (! $units->has($fromCode) || ! $units->has($toCode)) {
                continue;
            }

            DocumentManifest::create([
                'manifest_number' => $this->nextNumber(),
                'from_work_unit_id' => (int) $units->get($fromCode),
                'to_work_unit_id' => (int) $units->get($toCode),
                'status' => 'DRAFT',
                'created_by' => $this->userId,
            ]);

            $this->imported++;
        }
    }

    private function nextNumber(): string
    {
        $day = now()->format('Ymd');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $last = DocumentManifest::query()
                ->where('manifest_number', 'like', 'DM-'.$day.'-%')
                ->orderByDesc('manifest_number')
                ->first();

            $sequence = $last instanceof DocumentManifest
                ? (int) mb_substr((string) $last->manifest_number, -3) + 1
                : 1;

            $candidate = 'DM-'.$day.'-'.str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);

            if (! DocumentManifest::query()->where('manifest_number', $candidate)->exists()) {
                return $candidate;
            }
        }

        return 'DM-'.$day.'-'.str_pad((string) random_int(100, 999), 3, '0', STR_PAD_LEFT).'-'.time();
    }

    public function rules(): array
    {
        return [
            'from_unit_code' => ['required', 'string', 'max:32'],
            'to_unit_code' => ['required', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
