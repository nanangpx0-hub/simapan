<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Officer;
use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class OfficerSeeder extends Seeder
{
    /**
     * @var array<string, array{name: string, unit: string, status: string}>
     */
    private array $officers = [
        'OFF-001' => ['name' => 'Petugas Contoh Satu', 'unit' => 'SOSIAL', 'status' => 'ACTIVE'],
        'OFF-002' => ['name' => 'Petugas Contoh Dua', 'unit' => 'PENGOLAHAN_LS', 'status' => 'ACTIVE'],
        'OFF-003' => ['name' => 'Petugas Contoh Tiga', 'unit' => 'IPDS', 'status' => 'INACTIVE'],
    ];

    public function run(): void
    {
        $this->call(WorkUnitSeeder::class);

        foreach ($this->officers as $code => $attributes) {
            $unit = WorkUnit::where('code', $attributes['unit'])->firstOrFail();

            Officer::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $attributes['name'],
                    'work_unit_id' => $unit->getKey(),
                    'status' => $attributes['status'],
                ]
            );
        }
    }
}
