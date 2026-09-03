<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\WorkUnit;
use Illuminate\Database\Seeder;

class WorkUnitSeeder extends Seeder
{
    /**
     * @var array<string, string>
     */
    private array $units = [
        'SOSIAL' => 'Tim Statistik Sosial',
        'PENGOLAHAN_LS' => 'Tim Pengolahan dan Layanan Statistik',
        'IPDS' => 'Tim IPDS',
    ];

    public function run(): void
    {
        foreach ($this->units as $code => $name) {
            WorkUnit::firstOrCreate(
                ['code' => $code],
                [
                    'name' => $name,
                    'parent_id' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}
