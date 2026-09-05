<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\DsrtSample;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        RegionSeeder::class,
        SurveyPeriodSeeder::class,
    ]);
});

test('daftar tidak menampilkan kontak dan alamat penuh', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-MSK-01');
    DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-MSK-01', 'nurt' => 'NURT-MSK-01', 'krt_name' => 'KRT Mask Uji',
        'address' => 'Jalan Mask Uji 123',
        'contact_person' => 'Kontak Mask Uji',
        'contact_phone' => '0800000001',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $response = $this->actingAs($admin)->get(route('allocations.dsrt.index', $alokasi));

    $response->assertOk();
    $response->assertDontSee('Jalan Mask Uji 123', false);
    $response->assertDontSee('Kontak Mask Uji', false);
    $response->assertDontSee('0800000001', false);
    $response->assertSee('NUS-MSK-01');
});

test('detail view-only termasking', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-MSK-02');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-MSK-02', 'nurt' => 'NURT-MSK-02', 'krt_name' => 'KRT Mask Uji',
        'address' => 'Jalan Mask Uji 123',
        'contact_person' => 'Kontak Mask Uji',
        'contact_phone' => '0800000001',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $response = $this->actingAs($viewer)->get(route('allocations.dsrt.show', [$alokasi, $sample]));

    $response->assertOk();
    $response->assertDontSee('Jalan Mask Uji 123', false);
    $response->assertDontSee('Kontak Mask Uji', false);
    $response->assertDontSee('0800000001', false);
    $response->assertSee('NUS-MSK-02');
});

test('detail manage boleh melihat nilai penuh', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-MSK-03');
    $sample = DsrtSample::create([
        'allocation_id' => $alokasi->getKey(),
        'nus' => 'NUS-MSK-03', 'nurt' => 'NURT-MSK-03', 'krt_name' => 'KRT Mask Uji',
        'address' => 'Jalan Mask Uji 123',
        'contact_person' => 'Kontak Mask Uji',
        'contact_phone' => '0800000001',
        'record_status' => 'DRAFT', 'created_by' => $admin->getKey(),
    ]);

    $manager = User::factory()->create();
    $manager->assignRole('viewer');
    $manager->givePermissionTo('dsrt.manage');

    $response = $this->actingAs($manager)->get(route('allocations.dsrt.show', [$alokasi, $sample]));

    $response->assertOk();
    $response->assertSee('Jalan Mask Uji 123', false);
    $response->assertSee('0800000001', false);
});

test('audit tidak memuat kontak alamat dan notes', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-MSK-04');

    $this->actingAs($admin)->post(route('allocations.dsrt.store', $alokasi), [
        'nus' => 'NUS-MSK-04', 'nurt' => 'NURT-MSK-04', 'krt_name' => 'KRT Mask Uji',
        'address' => 'Jalan Mask Uji 123',
        'contact_person' => 'Kontak Mask Uji',
        'contact_phone' => '0800000001',
        'notes' => 'Catatan Mask Uji',
    ])->assertRedirect();

    $rows = AuditLog::where('auditable_type', DsrtSample::class)->get();

    expect($rows)->not->toBeEmpty();

    $raw = json_encode([
        $rows->pluck('old_values')->all(),
        $rows->pluck('new_values')->all(),
        $rows->pluck('metadata')->all(),
    ]);

    expect($raw)->not->toContain('Jalan Mask Uji 123')
        ->and($raw)->not->toContain('Kontak Mask Uji')
        ->and($raw)->not->toContain('0800000001')
        ->and($raw)->not->toContain('Catatan Mask Uji');
});

test('error validasi tidak membocorkan kontak', function (): void {
    $admin = adminDsrtUji();
    $alokasi = alokasiSusenasUji($admin, 'NKS-DSRT-MSK-05');

    $response = $this->actingAs($admin)->from(route('allocations.dsrt.create', $alokasi))->post(
        route('allocations.dsrt.store', $alokasi),
        [
            'nus' => 'NUS-MSK-05', 'nurt' => 'NURT-MSK-05', 'krt_name' => '',
            'contact_phone' => '0800000001',
        ]
    );

    $response->assertSessionHasErrors('krt_name');
    $response->assertRedirect(route('allocations.dsrt.create', $alokasi));

    $follow = $this->actingAs($admin)->get(route('allocations.dsrt.create', $alokasi));
    $follow->assertOk();
    $follow->assertDontSee('0800000001', false);
});
