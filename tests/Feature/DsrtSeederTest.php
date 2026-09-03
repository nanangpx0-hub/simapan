<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\DsrtSample;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DsrtSampleSeeder;

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

test('dsrt seeder idempotent dengan tiga dummy draft', function (): void {
    $this->seed(DsrtSampleSeeder::class);
    $this->seed(DsrtSampleSeeder::class);

    expect(DsrtSample::count())->toBe(3);

    foreach (['NURT-001', 'NURT-002', 'NURT-003'] as $nurt) {
        $sample = DsrtSample::where('nurt', $nurt)->firstOrFail();
        expect($sample->record_status)->toBe('DRAFT');
    }
});

test('database seeder tidak mengaudit dan tanpa kontak nyata', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(DsrtSample::count())->toBe(3);
    expect(AuditLog::count())->toBe(0);

    foreach (DsrtSample::all() as $sample) {
        expect($sample->address)->toBeNull();
        expect($sample->contact_person)->toBeNull();
        expect($sample->contact_phone)->toBeNull();
        expect($sample->krt_name)->toContain('Contoh');
    }
});
