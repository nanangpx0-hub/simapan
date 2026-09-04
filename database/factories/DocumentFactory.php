<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentType;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $type = DocumentType::firstOrCreate(
            ['code' => 'JDOC-FACT'],
            ['name' => 'Jenis Factory Uji', 'is_active' => true]
        );

        $allocation = Allocation::factory()->create();
        $sosial = WorkUnit::where('code', 'SOSIAL')->first();

        if (! $sosial instanceof WorkUnit) {
            $sosial = WorkUnit::create(['code' => 'SOSIAL', 'name' => 'Unit Factory Uji', 'is_active' => true]);
        }

        $admin = User::factory()->create();

        return [
            'document_type_id' => $type->getKey(),
            'allocation_id' => $allocation->getKey(),
            'dsrt_sample_id' => null,
            'document_number' => 'DOC-2099-'.fake()->unique()->numerify('###'),
            'title' => 'Berkas Factory Uji '.fake()->word(),
            'format' => 'PHYSICAL',
            'quantity' => 1,
            'status' => 'REGISTERED',
            'notes' => null,
            'created_by' => $admin->getKey(),
        ];
    }
}
