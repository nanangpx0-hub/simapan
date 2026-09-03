<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
use Illuminate\Database\Seeder;

class AllocationSeeder extends Seeder
{
    /**
     * @var array<string, array{period: string, village: string, sls: string, name: string}>
     */
    private array $allocations = [
        'NKS-2099-001' => [
            'period' => 'SUSENAS-S1-2099',
            'village' => '9901001001',
            'sls' => 'SLS-CONTOH-01',
            'name' => 'SLS Contoh Satu',
        ],
        'NKS-2099-002' => [
            'period' => 'SERUTI-T1-2099',
            'village' => '9901001001',
            'sls' => 'SLS-CONTOH-02',
            'name' => 'SLS Contoh Dua',
        ],
    ];

    public function run(): void
    {
        $this->call(SurveyPeriodSeeder::class);
        $this->call(RegionSeeder::class);

        $author = User::query()->orderBy('id')->first();

        if (! $author instanceof User) {
            $author = User::factory()->create([
                'name' => 'Seeder Alokasi Uji',
                'email' => 'seeder-alokasi-uji@simapan.test',
            ]);
        }

        foreach ($this->allocations as $nks => $attributes) {
            $period = SurveyPeriod::where('code', $attributes['period'])->firstOrFail();
            $village = Region::where('full_code', $attributes['village'])->firstOrFail();

            Allocation::firstOrCreate(
                ['survey_period_id' => $period->getKey(), 'nks' => $nks],
                [
                    'village_region_id' => $village->getKey(),
                    'sls_code' => $attributes['sls'],
                    'sub_sls_code' => '0',
                    'sls_name' => $attributes['name'],
                    'status' => 'DRAFT',
                    'created_by' => $author->getKey(),
                ]
            );
        }
    }
}
