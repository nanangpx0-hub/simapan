<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Assignment;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentHolder;
use App\Models\DocumentHolderHistory;
use App\Models\DocumentLocation;
use App\Models\DocumentManifest;
use App\Models\DocumentManifestItem;
use App\Models\DocumentProcessingAssignment;
use App\Models\DocumentTransfer;
use App\Models\DocumentTransferItem;
use App\Models\DocumentType;
use App\Models\DsrtSample;
use App\Models\Officer;
use App\Models\OfficerAlias;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DummyDataSeeder extends Seeder
{
    private array $statuses = ['DRAFT', 'ACTIVE', 'SUSPENDED', 'COMPLETED', 'ARCHIVED'];

    private array $documentStatuses = ['REGISTERED', 'IN_TRANSIT', 'RECEIVED', 'RECEIVED_PARTIAL', 'REJECTED', 'ASSIGNED_PROCESSING'];

    private array $manifestStatuses = ['DRAFT', 'SUBMITTED', 'RECEIVED'];

    private array $transferStatuses = ['PENDING', 'IN_TRANSIT', 'RECEIVED', 'COMPLETED'];

    private array $receiptStatuses = ['PENDING', 'RECEIVED', 'REJECTED', 'PARTIAL'];

    private array $processingStatuses = ['ACTIVE', 'RETURNED', 'COMPLETED'];

    private array $assignmentRoles = ['FIELD_OFFICER', 'FIELD_SUPERVISOR', 'PROCESSING_OFFICER', 'PROCESSING_SUPERVISOR'];

    private array $employmentCategories = ['ORGANIK', 'MITRA'];

    private array $holderTypes = ['WORK_UNIT', 'OFFICER'];

    private array $conditions = ['GOOD', 'INCOMPLETE', 'DAMAGED', 'ILLEGIBLE'];

    private array $movementTypes = ['REGISTERED', 'TRANSFERRED', 'ASSIGNED', 'RETURNED'];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedWorkUnits();
            $this->seedSurveyTypes();
            $this->seedSurveyPeriods();
            $this->seedRegions();
            $this->seedUsers();
            $this->seedOfficers();
            $this->seedOfficerAliases();
            $this->seedAllocations();
            $this->seedAssignments();
            $this->seedDsrtSamples();
            $this->seedDocumentTypes();
            $this->seedDocumentLocations();
            $this->seedDocuments();
            $this->seedDocumentManifests();
            $this->seedDocumentManifestItems();
            $this->seedDocumentHolders();
            $this->seedDocumentHolderHistories();
            $this->seedDocumentTransfers();
            $this->seedDocumentTransferItems();
            $this->seedDocumentProcessingAssignments();
            $this->seedAuditLogs();
        });
    }

    private function seedWorkUnits(): void
    {
        $existingCount = WorkUnit::count();
        $needed = max(0, 10 - $existingCount);

        for ($i = 0; $i < $needed; $i++) {
            WorkUnit::firstOrCreate(
                ['code' => 'UNIT-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Unit Dummy ' . ($i + 1),
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSurveyTypes(): void
    {
        $existingCount = SurveyType::count();
        $needed = max(0, 10 - $existingCount);

        for ($i = 0; $i < $needed; $i++) {
            SurveyType::firstOrCreate(
                ['code' => 'ST-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Survei Dummy ' . ($i + 1),
                    'description' => 'Deskripsi survei dummy untuk testing',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedSurveyPeriods(): void
    {
        $existingCount = SurveyPeriod::count();
        $needed = max(0, 10 - $existingCount);
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $year = 2026 + (int) ($i / 4);
            $semester = ($i % 4) + 1;
            $type = SurveyType::query()->skip($i % SurveyType::count())->first();

            SurveyPeriod::firstOrCreate(
                ['code' => 'SP-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'survey_type_id' => $type?->getKey() ?? 1,
                    'name' => 'Periode Dummy ' . ($i + 1),
                    'period_type' => 'SEMESTER',
                    'period_number' => $semester,
                    'year' => $year,
                    'start_date' => sprintf('%04d-%02d-01', $year, ($semester - 1) * 6 + 1),
                    'end_date' => sprintf('%04d-%02d-30', $year, $semester * 6),
                    'status' => 'DRAFT',
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedRegions(): void
    {
        $existingCount = Region::count();
        $needed = max(0, 60 - $existingCount);

        $provinsi = Region::where('level', 'PROVINSI')->orderBy('id')->first();
        $provinsiId = $provinsi?->getKey() ?? 1;

        $kabupaten = Region::where('level', 'KAB_KOTA')->where('parent_id', $provinsiId)->orderBy('id')->first();
        $kabupatenId = $kabupaten?->getKey() ?? ($provinsiId + 1);

        $kecamatan = Region::where('level', 'KECAMATAN')->where('parent_id', $kabupatenId)->orderBy('id')->first();
        $kecamatanId = $kecamatan?->getKey() ?? ($kabupatenId + 1);

        for ($i = 0; $i < $needed; $i++) {
            $code = 'DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $fullCode = $kecamatan->full_code ?? '99.01.01.2001';
            $fullCode .= '-' . $code;

            Region::firstOrCreate(
                ['full_code' => $fullCode],
                [
                    'parent_id' => $kecamatanId,
                    'level' => 'DESA_KELURAHAN_NAGARI',
                    'code' => $code,
                    'name' => 'Desa Dummy ' . ($i + 1),
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedUsers(): void
    {
        $existingCount = User::count();
        $needed = max(0, 10 - $existingCount);

        for ($i = 0; $i < $needed; $i++) {
            User::firstOrCreate(
                ['email' => 'dummy.user' . ($i + 1) . '@simapan.test'],
                [
                    'name' => 'Dummy User ' . ($i + 1),
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedOfficers(): void
    {
        $existingCount = Officer::count();
        $needed = max(0, 15 - $existingCount);
        $workUnits = WorkUnit::all();
        $users = User::all();

        for ($i = 0; $i < $needed; $i++) {
            $workUnit = $workUnits->random();
            $user = $users->random();
            $code = 'OFF-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $name = 'Petugas Dummy ' . ($i + 1);

            Officer::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'work_unit_id' => $workUnit->getKey(),
                    'user_id' => $user->getKey(),
                    'phone' => '0812345' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT),
                    'email' => 'officer.dummy' . ($i + 1) . '@simapan.test',
                    'status' => 'ACTIVE',
                    'active_from' => now()->subDays(rand(30, 365)),
                    'active_until' => now()->addDays(rand(30, 365)),
                ]
            );
        }
    }

    private function seedOfficerAliases(): void
    {
        $existingCount = OfficerAlias::count();
        $needed = max(0, 5 - $existingCount);
        $officers = Officer::all();

        for ($i = 0; $i < $needed; $i++) {
            $officer = $officers->random();
            $aliasName = 'Alias Dummy ' . ($i + 1);

            OfficerAlias::firstOrCreate(
                ['officer_id' => $officer->getKey(), 'normalized_alias' => Str::lower($aliasName)],
                [
                    'alias_name' => $aliasName,
                    'created_by' => 1,
                ]
            );
        }
    }

    private function seedAllocations(): void
    {
        $existingCount = Allocation::count();
        $needed = max(0, 15 - $existingCount);
        $periods = SurveyPeriod::all();
        $villages = Region::where('level', 'DESA_KELURAHAN_NAGARI')->get();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $period = $periods->random();
            $village = $villages->random();
            $code = 'NKS-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            Allocation::firstOrCreate(
                ['survey_period_id' => $period->getKey(), 'nks' => $code],
                [
                    'village_region_id' => $village->getKey(),
                    'sls_code' => 'SLS-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'sub_sls_code' => '0',
                    'sls_name' => 'SLS Dummy ' . ($i + 1),
                    'status' => 'DRAFT',
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedAssignments(): void
    {
        $existingCount = Assignment::count();
        $needed = max(0, 20 - $existingCount);
        $allocations = Allocation::all();
        $officers = Officer::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $allocation = $allocations->random();
            $officer = $officers->random();

            Assignment::firstOrCreate(
                [
                    'allocation_id' => $allocation->getKey(),
                    'officer_id' => $officer->getKey(),
                    'assignment_role' => $this->assignmentRoles[array_rand($this->assignmentRoles)],
                ],
                [
                    'employment_category' => $this->employmentCategories[array_rand($this->employmentCategories)],
                    'is_active' => true,
                    'started_at' => now()->subDays(rand(1, 30)),
                    'assigned_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedDsrtSamples(): void
    {
        $existingCount = DsrtSample::count();
        $needed = max(0, 12 - $existingCount);
        $allocations = Allocation::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $allocation = $allocations->random();
            $nus = 'NUS-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);
            $nurt = 'NURT-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            DsrtSample::firstOrCreate(
                ['allocation_id' => $allocation->getKey(), 'nurt' => $nurt],
                [
                    'nus' => $nus,
                    'family_number' => 'KK-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'building_number' => 'B-DUM-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'household_number' => 'H-DUM-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                    'krt_name' => 'KRT Dummy ' . ($i + 1),
                    'address' => 'Jl. Dummy No. ' . ($i + 1),
                    'krt_education_code' => 'EDU-' . ($i + 1),
                    'enumeration_status' => 'PENDING',
                    'contact_person' => 'Contact Dummy ' . ($i + 1),
                    'contact_phone' => '0812345' . str_pad((string) (rand(1000, 9999)), 4, '0', STR_PAD_LEFT),
                    'record_status' => 'DRAFT',
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedDocumentTypes(): void
    {
        $existingCount = DocumentType::count();
        $needed = max(0, 10 - $existingCount);

        for ($i = 0; $i < $needed; $i++) {
            DocumentType::firstOrCreate(
                ['code' => 'DOC-TYPE-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Jenis Dokumen Dummy ' . ($i + 1),
                    'description' => 'Deskripsi jenis dokumen dummy untuk testing',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDocumentLocations(): void
    {
        $existingCount = DocumentLocation::count();
        $needed = max(0, 10 - $existingCount);

        for ($i = 0; $i < $needed; $i++) {
            DocumentLocation::firstOrCreate(
                ['code' => 'LOC-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Lokasi Dummy ' . ($i + 1),
                    'description' => 'Deskripsi lokasi dummy untuk testing',
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedDocuments(): void
    {
        $existingCount = Document::count();
        $needed = max(0, 12 - $existingCount);
        $types = DocumentType::all();
        $allocations = Allocation::all();
        $dsrtSamples = DsrtSample::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $type = $types->random();
            $useAllocation = rand(0, 1) === 0 && $allocations->count() > 0;
            $allocation = $useAllocation ? $allocations->random() : null;
            $dsrtSample = ! $useAllocation && $dsrtSamples->count() > 0 ? $dsrtSamples->random() : null;
            $number = 'DOC-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT);

            Document::firstOrCreate(
                ['document_number' => $number],
                [
                    'document_type_id' => $type->getKey(),
                    'allocation_id' => $allocation?->getKey(),
                    'dsrt_sample_id' => $dsrtSample?->getKey(),
                    'title' => 'Dokumen Dummy ' . ($i + 1),
                    'format' => 'PHYSICAL',
                    'quantity' => rand(1, 5),
                    'status' => $this->documentStatuses[array_rand($this->documentStatuses)],
                    'notes' => 'Dokumen dummy untuk testing',
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedDocumentManifests(): void
    {
        $existingCount = DocumentManifest::count();
        $needed = max(0, 10 - $existingCount);
        $workUnits = WorkUnit::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $from = $workUnits->random();
            $to = $workUnits->random();
            while ($to->getKey() === $from->getKey()) {
                $to = $workUnits->random();
            }

            DocumentManifest::firstOrCreate(
                ['manifest_number' => 'DM-DUM-' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT)],
                [
                    'from_work_unit_id' => $from->getKey(),
                    'to_work_unit_id' => $to->getKey(),
                    'status' => $this->manifestStatuses[array_rand($this->manifestStatuses)],
                    'submitted_by' => $user?->getKey() ?? 1,
                    'submitted_at' => now()->subDays(rand(1, 30)),
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedDocumentManifestItems(): void
    {
        $existingCount = DocumentManifestItem::count();
        $needed = max(0, 8 - $existingCount);
        $manifests = DocumentManifest::all();
        $documents = Document::all();

        for ($i = 0; $i < $needed; $i++) {
            $manifest = $manifests->random();
            $document = $documents->random();

            DocumentManifestItem::firstOrCreate(
                ['document_manifest_id' => $manifest->getKey(), 'document_id' => $document->getKey()],
                [
                    'qty_sent' => rand(1, 10),
                    'condition_sent' => $this->conditions[array_rand($this->conditions)],
                    'sent_note' => 'Item dummy ' . ($i + 1),
                ]
            );
        }
    }

    private function seedDocumentHolders(): void
    {
        $existingCount = DocumentHolder::count();
        $needed = max(0, 12 - $existingCount);
        $documents = Document::all();
        $workUnits = WorkUnit::all();
        $officers = Officer::all();
        $locations = DocumentLocation::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $document = $documents->random();
            $holderType = $this->holderTypes[array_rand($this->holderTypes)];
            $workUnit = $holderType === 'WORK_UNIT' ? $workUnits->random() : null;
            $officer = $holderType === 'OFFICER' ? $officers->random() : null;
            $location = $locations->random();

            DocumentHolder::firstOrCreate(
                ['document_id' => $document->getKey()],
                [
                    'holder_type' => $holderType,
                    'work_unit_id' => $workUnit?->getKey(),
                    'officer_id' => $officer?->getKey(),
                    'document_location_id' => $location->getKey(),
                    'condition_code' => $this->conditions[array_rand($this->conditions)],
                    'assigned_by' => $user?->getKey() ?? 1,
                    'assigned_at' => now(),
                ]
            );
        }
    }

    private function seedDocumentHolderHistories(): void
    {
        $existingCount = DocumentHolderHistory::count();
        $needed = max(0, 15 - $existingCount);
        $documents = Document::all();
        $workUnits = WorkUnit::all();
        $officers = Officer::all();
        $locations = DocumentLocation::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $document = $documents->random();
            $toHolderType = $this->holderTypes[array_rand($this->holderTypes)];
            $toWorkUnit = $toHolderType === 'WORK_UNIT' ? $workUnits->random() : null;
            $toOfficer = $toHolderType === 'OFFICER' ? $officers->random() : null;
            $toLocation = $locations->random();

            DocumentHolderHistory::create([
                'document_id' => $document->getKey(),
                'to_holder_type' => $toHolderType,
                'to_work_unit_id' => $toWorkUnit?->getKey(),
                'to_officer_id' => $toOfficer?->getKey(),
                'to_document_location_id' => $toLocation->getKey(),
                'condition_after' => $this->conditions[array_rand($this->conditions)],
                'movement_type' => $this->movementTypes[array_rand($this->movementTypes)],
                'reference_type' => Document::class,
                'reference_id' => $document->getKey(),
                'moved_by' => $user?->getKey() ?? 1,
                'moved_at' => now()->subDays(rand(1, 30)),
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }

    private function seedDocumentTransfers(): void
    {
        $existingCount = DocumentTransfer::count();
        $needed = max(0, 8 - $existingCount);
        $manifests = DocumentManifest::whereDoesntHave('transfer')->get();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < min($needed, $manifests->count()); $i++) {
            $manifest = $manifests->random();

            DocumentTransfer::firstOrCreate(
                ['document_manifest_id' => $manifest->getKey()],
                [
                    'transfer_status' => $this->transferStatuses[array_rand($this->transferStatuses)],
                    'received_by' => $user?->getKey() ?? 1,
                    'received_at' => now()->subDays(rand(1, 30)),
                    'created_by' => $user?->getKey() ?? 1,
                ]
            );
        }
    }

    private function seedDocumentTransferItems(): void
    {
        $existingCount = DocumentTransferItem::count();
        $needed = max(0, 8 - $existingCount);
        $transfers = DocumentTransfer::all();
        $manifestItems = DocumentManifestItem::all();

        for ($i = 0; $i < $needed; $i++) {
            $transfer = $transfers->random();
            $manifestItem = $manifestItems->random();

            DocumentTransferItem::firstOrCreate(
                ['document_transfer_id' => $transfer->getKey(), 'document_manifest_item_id' => $manifestItem->getKey()],
                [
                    'qty_sent' => rand(1, 10),
                    'qty_received' => rand(0, 10),
                    'condition_received' => $this->conditions[array_rand($this->conditions)],
                    'receipt_status' => $this->receiptStatuses[array_rand($this->receiptStatuses)],
                ]
            );
        }
    }

    private function seedDocumentProcessingAssignments(): void
    {
        $existingCount = DocumentProcessingAssignment::count();
        $needed = max(0, 10 - $existingCount);
        $documents = Document::all();
        $officers = Officer::all();
        $user = User::query()->orderBy('id')->first();

        for ($i = 0; $i < $needed; $i++) {
            $document = $documents->random();
            $officer = $officers->random();

            DocumentProcessingAssignment::firstOrCreate(
                ['document_id' => $document->getKey(), 'officer_id' => $officer->getKey()],
                [
                    'assigned_by' => $user?->getKey() ?? 1,
                    'assigned_at' => now()->subDays(rand(1, 30)),
                    'status' => $this->processingStatuses[array_rand($this->processingStatuses)],
                ]
            );
        }
    }

    private function seedAuditLogs(): void
    {
        $existingCount = AuditLog::count();
        $needed = max(0, 20 - $existingCount);
        $users = User::all();

        $actions = ['created', 'updated', 'deleted', 'viewed'];
        $auditableTypes = [
            Document::class,
            Allocation::class,
            Officer::class,
            Region::class,
            SurveyPeriod::class,
        ];

        for ($i = 0; $i < $needed; $i++) {
            $user = $users->random();
            $type = $auditableTypes[array_rand($auditableTypes)];
            $model = $type::inRandomOrder()->first();

            if (! $model) {
                continue;
            }

            AuditLog::create([
                'event_uuid' => Str::uuid()->toString(),
                'user_id' => $user->getKey(),
                'action' => $actions[array_rand($actions)],
                'auditable_type' => $type,
                'auditable_id' => $model->getKey(),
                'old_values' => null,
                'new_values' => null,
                'metadata' => null,
                'route_name' => 'dummy.route',
                'ip_address' => '127.0.0.1',
                'user_agent' => 'DummySeeder/1.0',
                'created_at' => now()->subDays(rand(1, 30)),
            ]);
        }
    }
}
