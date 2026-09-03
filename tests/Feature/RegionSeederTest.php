<?php

declare(strict_types=1);

use App\Models\Region;
use Database\Seeders\RegionSeeder;

test('region seeder idempotent dengan empat record dummy', function (): void {
    $this->seed(RegionSeeder::class);
    $this->seed(RegionSeeder::class);

    expect(Region::count())->toBe(4);
});

test('full_code dummy terbentuk sesuai hierarchy', function (): void {
    $this->seed(RegionSeeder::class);

    $provinsi = Region::where('full_code', '99')->firstOrFail();
    $kabupaten = Region::where('full_code', '9901')->firstOrFail();
    $kecamatan = Region::where('full_code', '9901001')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    expect($provinsi->level)->toBe('PROVINSI');
    expect($provinsi->parent_id)->toBeNull();
    expect($kabupaten->level)->toBe('KAB_KOTA');
    expect($kabupaten->parent_id)->toBe($provinsi->id);
    expect($kecamatan->level)->toBe('KECAMATAN');
    expect($kecamatan->parent_id)->toBe($kabupaten->id);
    expect($desa->level)->toBe('DESA_KELURAHAN_NAGARI');
    expect($desa->parent_id)->toBe($kecamatan->id);
});
