<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\SurveyType;
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

test('seeder tidak menghasilkan audit log', function (): void {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(AuditLog::count())->toBe(0);
    expect(SurveyType::count())->toBeGreaterThan(0);
});
