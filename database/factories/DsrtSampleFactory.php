<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\DsrtSample;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DsrtSample>
 */
class DsrtSampleFactory extends Factory
{
    public function definition(): array
    {
        $allocation = Allocation::factory()->create();
        $author = User::factory()->create();

        return [
            'allocation_id' => $allocation->getKey(),
            'nus' => 'NUS-'.fake()->unique()->numerify('###'),
            'nurt' => 'NURT-'.fake()->unique()->numerify('###'),
            'family_number' => 'KK-'.fake()->numerify('###'),
            'building_number' => 'B-'.fake()->numerify('##'),
            'household_number' => 'RT-'.fake()->numerify('##'),
            'krt_name' => 'KRT Contoh Uji '.fake()->word(),
            'address' => null,
            'krt_education_code' => null,
            'enumeration_status' => 'PENDING',
            'contact_person' => null,
            'contact_phone' => null,
            'notes' => null,
            'record_status' => 'DRAFT',
            'created_by' => $author->getKey(),
        ];
    }
}
