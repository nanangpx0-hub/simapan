<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentLocation;
use App\Models\DocumentManifest;
use App\Models\DocumentType;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DocumentSeeder;

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

test('document seeder idempotent dengan data dummy', function (): void {
    $this->seed(DocumentSeeder::class);
    $this->seed(DocumentSeeder::class);

    expect(DocumentType::count())->toBe(3);
    expect(DocumentLocation::count())->toBe(2);
    expect(Document::where('document_number', 'DOC-2099-001')->exists())->toBeTrue();
    expect(Document::where('document_number', 'DOC-2099-002')->exists())->toBeTrue();
    expect(DocumentManifest::where('manifest_number', 'DM-20990101-001')->exists())->toBeTrue();
});

test('database seeder tidak mengaudit dokumen', function (): void {
    $this->seed(DatabaseSeeder::class);

    expect(Document::count())->toBe(2);
    expect(AuditLog::count())->toBe(0);

    foreach (Document::all() as $document) {
        expect($document->title)->toContain('Contoh');
    }
});
