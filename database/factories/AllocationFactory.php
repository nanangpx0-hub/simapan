<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\SurveyType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Allocation>
 */
class AllocationFactory extends Factory
{
    public function definition(): array
    {
        $type = SurveyType::firstOrCreate(
            ['code' => 'JTIPE-FACT'],
            ['name' => 'Tipe Factory Uji', 'is_active' => true]
        );

        $admin = User::factory()->create();

        $period = SurveyPeriod::firstOrCreate(
            ['code' => 'FACT-PERIOD-01'],
            [
                'survey_type_id' => $type->getKey(),
                'name' => 'Periode Factory Uji',
                'period_type' => 'SEMESTER',
                'period_number' => 1,
                'year' => 2099,
                'start_date' => '2099-01-01',
                'end_date' => '2099-06-30',
                'status' => 'DRAFT',
                'created_by' => $admin->getKey(),
            ]
        );

        $provinsi = Region::firstOrCreate(
            ['full_code' => 'FACT-P'],
            ['parent_id' => null, 'level' => 'PROVINSI', 'code' => 'FACT-P', 'name' => 'Prov Factory Uji', 'is_active' => true]
        );
        $kabupaten = Region::firstOrCreate(
            ['full_code' => 'FACT-PFACT-K'],
            ['parent_id' => $provinsi->getKey(), 'level' => 'KAB_KOTA', 'code' => 'FACT-K', 'name' => 'Kab Factory Uji', 'is_active' => true]
        );
        $kecamatan = Region::firstOrCreate(
            ['full_code' => 'FACT-PFACT-KFACT-C'],
            ['parent_id' => $kabupaten->getKey(), 'level' => 'KECAMATAN', 'code' => 'FACT-C', 'name' => 'Kec Factory Uji', 'is_active' => true]
        );
        $desa = Region::firstOrCreate(
            ['full_code' => 'FACT-PFACT-KFACT-CFACT-D'],
            ['parent_id' => $kecamatan->getKey(), 'level' => 'DESA_KELURAHAN_NAGARI', 'code' => 'FACT-D', 'name' => 'Desa Factory Uji', 'is_active' => true]
        );

        return [
            'survey_period_id' => $period->getKey(),
            'village_region_id' => $desa->getKey(),
            'nks' => 'NKS-2099-'.fake()->unique()->numerify('###'),
            'sls_code' => 'SLS-CONTOH-'.fake()->unique()->numerify('##'),
            'sub_sls_code' => '0',
            'sls_name' => 'SLS Contoh Uji '.fake()->word(),
            'status' => 'DRAFT',
            'notes' => null,
            'created_by' => $admin->getKey(),
        ];
    }
}
