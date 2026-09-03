<?php

declare(strict_types=1);

use App\Support\AuditSanitizer;

test('seluruh daftar field sensitif disanitasi', function (): void {
    $payload = [
        'password' => 'rahasia-uji-01',
        'PASSWORD_CONFIRMATION' => 'rahasia-uji-01',
        'Current_Password' => 'rahasia-uji-01',
        'remember_token' => 'token-uji-01',
        'Session' => 'sesi-uji-01',
        'SESSION_ID' => 'sesi-uji-01',
        'access_token' => 'token-uji-01',
        'refresh_token' => 'token-uji-01',
        'API_TOKEN' => 'token-uji-01',
        'api_key' => 'kunci-uji-01',
        'secret' => 'rahasia-uji-01',
        'ENCRYPTION_KEY' => 'kunci-uji-01',
        'database_url' => 'mysql://uji',
        'DB_PASSWORD' => 'rahasia-uji-01',
        'NIK' => '0000000000000000',
        'phone' => '0800000001',
        'Contact_Phone' => '0800000001',
        'TELEPHONE' => '0800000001',
        'whatsapp' => '0800000001',
        'Email' => 'uji@simapan.test',
        'ADDRESS' => 'Jalan Contoh 1',
        'alamat' => 'Jalan Contoh 1',
        'Full_Address' => 'Jalan Contoh 1',
        'document_content' => 'isi-uji-01',
        'file_content' => 'isi-uji-01',
        'uploaded_file' => 'berkas-uji-01',
        'attachment' => 'berkas-uji-01',
        'private_path' => '/rahasia/uji-01',
    ];

    $clean = AuditSanitizer::sanitize($payload);

    foreach ($payload as $key => $value) {
        expect($clean[$key])->toBe(AuditSanitizer::REDACTED);
    }
});

test('sanitasi bekerja case-insensitive dan nested', function (): void {
    $payload = [
        'user' => [
            'name' => 'Nama Contoh Uji',
            'Password' => 'rahasia-uji-01',
            'kontak' => [
                'Phone' => '0800000001',
                'EMAIL' => 'uji@simapan.test',
            ],
        ],
        'list' => [
            ['nik' => '0000000000000000'],
            ['code' => 'KODE-UJI-01'],
        ],
    ];

    $clean = AuditSanitizer::sanitize($payload);

    expect($clean['user']['name'])->toBe('Nama Contoh Uji');
    expect($clean['user']['Password'])->toBe(AuditSanitizer::REDACTED);
    expect($clean['user']['kontak']['Phone'])->toBe(AuditSanitizer::REDACTED);
    expect($clean['user']['kontak']['EMAIL'])->toBe(AuditSanitizer::REDACTED);
    expect($clean['list'][0]['nik'])->toBe(AuditSanitizer::REDACTED);
    expect($clean['list'][1]['code'])->toBe('KODE-UJI-01');
});

test('field non-sensitif tetap tercatat', function (): void {
    $payload = [
        'code' => 'KODE-UJI-01',
        'name' => 'Nama Contoh Uji',
        'status' => 'ACTIVE',
        'is_active' => true,
    ];

    expect(AuditSanitizer::sanitize($payload))->toBe($payload);
});
