<?php

declare(strict_types=1);

use App\Models\Officer;
use App\Models\OfficerAlias;
use Database\Seeders\OfficerAliasSeeder;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\WorkUnitSeeder;

test('officer dan alias seeder idempotent', function (): void {
    $this->seed(WorkUnitSeeder::class);
    $this->seed(OfficerSeeder::class);
    $this->seed(OfficerAliasSeeder::class);
    $this->seed(OfficerSeeder::class);
    $this->seed(OfficerAliasSeeder::class);

    expect(Officer::count())->toBe(3);
    expect(OfficerAlias::count())->toBe(2);
});

test('tiga petugas dummy tersedia dengan unit dan status benar', function (): void {
    $this->seed(OfficerAliasSeeder::class);

    $satu = Officer::where('code', 'OFF-001')->firstOrFail();
    $dua = Officer::where('code', 'OFF-002')->firstOrFail();
    $tiga = Officer::where('code', 'OFF-003')->firstOrFail();

    expect($satu->workUnit->code)->toBe('SOSIAL');
    expect($satu->status)->toBe('ACTIVE');
    expect($dua->workUnit->code)->toBe('PENGOLAHAN_LS');
    expect($dua->status)->toBe('ACTIVE');
    expect($tiga->workUnit->code)->toBe('IPDS');
    expect($tiga->status)->toBe('INACTIVE');
});

test('alias dummy tersedia dengan normalisasi benar dan tanpa kontak nyata', function (): void {
    $this->seed(OfficerAliasSeeder::class);

    $satu = Officer::where('code', 'OFF-001')->firstOrFail();
    $dua = Officer::where('code', 'OFF-002')->firstOrFail();

    expect($satu->aliases()->where('normalized_alias', 'p. contoh satu')->exists())->toBeTrue();
    expect($dua->aliases()->where('normalized_alias', 'petugas contoh ii')->exists())->toBeTrue();

    expect(Officer::whereNotNull('phone')->count())->toBe(0);
    expect(Officer::whereNotNull('email')->count())->toBe(0);
});
