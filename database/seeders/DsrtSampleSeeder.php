<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\DsrtSample;
use App\Models\User;
use Illuminate\Database\Seeder;

class DsrtSampleSeeder extends Seeder
{
    /**
     * @var array<string, array{nus: string, krt: string}>
     */
    private array $samples = [
        'NURT-001' => ['nus' => 'NUS-001', 'krt' => 'KRT Contoh Satu'],
        'NURT-002' => ['nus' => 'NUS-002', 'krt' => 'KRT Contoh Dua'],
        'NURT-003' => ['nus' => 'NUS-003', 'krt' => 'KRT Contoh Tiga'],
    ];

    public function run(): void
    {
        $this->call(AllocationSeeder::class);

        $allocation = Allocation::where('nks', 'NKS-2099-001')->firstOrFail();
        $author = User::query()->orderBy('id')->first();

        if (! $author instanceof User) {
            $author = User::factory()->create([
                'name' => 'Seeder DSRT Uji',
                'email' => 'seeder-dsrt-uji@simapan.test',
            ]);
        }

        foreach ($this->samples as $nurt => $attributes) {
            DsrtSample::firstOrCreate(
                ['allocation_id' => $allocation->getKey(), 'nurt' => $nurt],
                [
                    'nus' => $attributes['nus'],
                    'krt_name' => $attributes['krt'],
                    'address' => null,
                    'enumeration_status' => 'PENDING',
                    'contact_person' => null,
                    'contact_phone' => null,
                    'record_status' => 'DRAFT',
                    'created_by' => $author->getKey(),
                ]
            );
        }
    }
}
