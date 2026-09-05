<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Officer;
use App\Models\WorkUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Officer>
 */
class OfficerFactory extends Factory
{
    public function definition(): array
    {
        $unit = WorkUnit::firstOrCreate(
            ['code' => 'OFCT-UNIT'],
            ['name' => 'Unit Factory Petugas', 'is_active' => true]
        );

        return [
            'code' => 'OFCT-'.strtoupper(fake()->unique()->bothify('####')),
            'name' => 'Petugas Uji '.fake()->lastName(),
            'work_unit_id' => $unit->getKey(),
            'user_id' => null,
            'phone' => null,
            'email' => null,
            'status' => 'ACTIVE',
            'active_from' => now()->subMonth()->toDateString(),
            'active_until' => null,
        ];
    }
}