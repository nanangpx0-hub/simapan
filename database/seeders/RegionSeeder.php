<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    public function run(): void
    {
        $provinsi = Region::firstOrCreate(
            ['full_code' => '99'],
            [
                'parent_id' => null,
                'level' => 'PROVINSI',
                'code' => '99',
                'name' => 'Provinsi Contoh',
                'is_active' => true,
            ]
        );

        $kabupaten = Region::firstOrCreate(
            ['full_code' => '9901'],
            [
                'parent_id' => $provinsi->getKey(),
                'level' => 'KAB_KOTA',
                'code' => '01',
                'name' => 'Kabupaten Contoh',
                'is_active' => true,
            ]
        );

        $kecamatan = Region::firstOrCreate(
            ['full_code' => '9901001'],
            [
                'parent_id' => $kabupaten->getKey(),
                'level' => 'KECAMATAN',
                'code' => '001',
                'name' => 'Kecamatan Contoh',
                'is_active' => true,
            ]
        );

        Region::firstOrCreate(
            ['full_code' => '9901001001'],
            [
                'parent_id' => $kecamatan->getKey(),
                'level' => 'DESA_KELURAHAN_NAGARI',
                'code' => '001',
                'name' => 'Desa Contoh',
                'is_active' => true,
            ]
        );
    }
}
