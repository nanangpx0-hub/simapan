<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DocumentMasterSeeder::class);
        $this->call(WorkUnitSeeder::class);
        $this->call(OfficerSeeder::class);
        $this->call(AllocationSeeder::class);

        $author = User::query()->orderBy('id')->first();

        if (! $author instanceof User) {
            $author = User::factory()->create([
                'name' => 'Seeder Dokumen Uji',
                'email' => 'seeder-dokumen-uji@simapan.test',
            ]);
        }

        $type = DocumentType::where('code', 'KUESIONER')->firstOrFail();
        $sosial = WorkUnit::where('code', 'SOSIAL')->firstOrFail();

        $documents = [
            ['nks' => 'NKS-2099-001', 'number' => 'DOC-2099-001', 'title' => 'Berkas Contoh Satu'],
            ['nks' => 'NKS-2099-002', 'number' => 'DOC-2099-002', 'title' => 'Berkas Contoh Dua'],
        ];

        foreach ($documents as $row) {
            $allocation = Allocation::where('nks', $row['nks'])->first();

            if (! $allocation instanceof Allocation) {
                continue;
            }

            $document = Document::firstOrCreate(
                ['allocation_id' => $allocation->getKey(), 'title' => $row['title']],
                [
                    'document_type_id' => $type->getKey(),
                    'dsrt_sample_id' => null,
                    'document_number' => $row['number'],
                    'format' => 'PHYSICAL',
                    'quantity' => 1,
                    'status' => 'REGISTERED',
                    'created_by' => $author->getKey(),
                ]
            );

            if ($document->holder()->exists()) {
                continue;
            }

            $document->holder()->create([
                'holder_type' => 'WORK_UNIT',
                'work_unit_id' => $sosial->getKey(),
                'officer_id' => null,
                'document_location_id' => null,
                'condition_code' => 'GOOD',
                'assigned_by' => $author->getKey(),
                'assigned_at' => now(),
            ]);

            $document->holderHistories()->create([
                'to_holder_type' => 'WORK_UNIT',
                'to_work_unit_id' => $sosial->getKey(),
                'condition_after' => 'GOOD',
                'movement_type' => 'REGISTERED',
                'reference_type' => Document::class,
                'reference_id' => $document->getKey(),
                'moved_by' => $author->getKey(),
                'moved_at' => now(),
            ]);
        }

        DocumentManifest::firstOrCreate(
            ['manifest_number' => 'DM-20990101-001'],
            [
                'from_work_unit_id' => $sosial->getKey(),
                'to_work_unit_id' => WorkUnit::where('code', 'PENGOLAHAN_LS')->firstOrFail()->getKey(),
                'status' => 'DRAFT',
                'created_by' => $author->getKey(),
            ]
        );
    }
}
