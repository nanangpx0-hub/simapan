<?php

declare(strict_types=1);

use App\Models\Allocation;
use App\Models\AuditLog;
use Database\Seeders\AllocationSeeder;
use Database\Seeders\DatabaseSeeder;

beforeEach(function (): void {
    putenv('SIMAPAN_ADMIN_NAME=Admin Dummy Uji');
    putenv('SIMAPAN_ADMIN_EMAIL=admin-uji@simapan.test');
    putenv('SIMAPAN_ADMIN_PASSWORD=dummy-uji-01');
    $_ENV['SIMAPAN_ADMIN_NAME'] = 'Admin Dummy Uji';
    $_ENV['SIMAPAN_ADMIN_EMAIL'] = 'admin-uji@simapan.test';
    $_ENV['SIMAPAN_ADMIN_PASSWORD'] = 'dummy-uji-01';
    $_SERVER['SIMAPAN_ADMIN_NAME'] = 'Admin Dummy Uji';
    $_SERVER['SIMAPAN_ADMIN_EMAIL'] = 'admin-uji@simapan.test';
    $_SERVER['SIMAPAN_ADMIN_PASSWORD'] = 'dummy-uji-01';
});

afterEach(function (): void {
    putenv('SIMAPAN_ADMIN_NAME');
    putenv('SIMAPAN_ADMIN_EMAIL');
    putenv('SIMAPAN_ADMIN_PASSWORD');
    unset($_ENV['SIMAPAN_ADMIN_NAME'], $_ENV['SIMAPAN_ADMIN_EMAIL'], $_ENV['SIMAPAN_ADMIN_PASSWORD']);
    unset($_SERVER['SIMAPAN_ADMIN_NAME'], $_SERVER['SIMAPAN_ADMIN_EMAIL'], $_SERVER['SIMAPAN_ADMIN_PASSWORD']);
});

test('allocation seeder idempotent dengan dua dummy', function (): void {
    $this->seed(AllocationSeeder::class);
    $this->seed(AllocationSeeder::class);

    expect(Allocation::count())->toBe(2);
    expect(Allocation::where('nks', 'NKS-2099-001')->exists())->toBeTrue();
    expect(Allocation::where('nks', 'NKS-2099-002')->exists())->toBeTrue();
    expect(Allocation::where('nks', 'NKS-2099-001')->firstOrFail()->status)->toBe('DRAFT');
});

test('database seeder tidak mengaudit dan tanpa data nyata', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(Allocation::count())->toBe(2);
    expect(AuditLog::count())->toBe(0);

    foreach (Allocation::all() as $allocation) {
        expect($allocation->nks)->toStartWith('NKS-2099-');
        expect($allocation->sls_name)->toContain('Contoh');
    }
});
