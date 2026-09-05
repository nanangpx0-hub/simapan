<?php

declare(strict_types=1);

use App\Exports\UpdatingDocumentDeliveryExport;
use App\Livewire\Master\AssignUpdatingProcessorTable;
use App\Models\Allocation;
use App\Models\DocumentManifest;
use App\Models\Officer;
use App\Models\ProcessingEntryReport;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\UpdatingManifestItem;
use App\Models\User;
use Database\Seeders\OfficerSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->seed([
        PermissionSeeder::class,
        RoleSeeder::class,
        SurveyTypeSeeder::class,
        WorkUnitSeeder::class,
        RegionSeeder::class,
        OfficerSeeder::class,
        SurveyPeriodSeeder::class,
    ]);
});

function pemutakhiranAlokasiUji(User $admin, int $count = 28): array
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();
    $allocations = [];

    for ($i = 0; $i < $count; $i++) {
        // NKS string dengan leading-zero yang terjaga.
        $nks = sprintf('%05d', 327 + $i);

        $allocations[] = Allocation::create([
            'survey_period_id' => $period->getKey(),
            'village_region_id' => $desa->getKey(),
            'nks' => $nks,
            'sls_name' => 'SLS Mutakhir Uji '.$nks,
            'status' => 'ACTIVE',
            'created_by' => $admin->getKey(),
        ]);
    }

    return $allocations;
}

function pemutakhiranPayloadUji(array $allocations): array
{
    $payload = ['selected' => [], 'rows' => []];

    foreach ($allocations as $i => $allocation) {
        $id = $allocation->getKey();
        $payload['selected'][] = $id;
        $payload['rows'][$id] = [
            'household_count_listing' => 80 + $i,
            'has_vsen_p' => '1',
            'has_peta_ws' => '1',
        ];
    }

    return $payload;
}

function manifestPemutakhiranUji(object $test, User $social, array $allocations): DocumentManifest
{
    $response = $test->actingAs($social)->post(
        route('updating_manifests.store'),
        pemutakhiranPayloadUji($allocations)
    );
    $response->assertRedirect();

    return DocumentManifest::orderByDesc('id')->firstOrFail();
}

test('sosial membuat manifest pemutakhiran 28 NKS lengkap', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin);

    $social = User::factory()->create();
    $social->assignRole('social_operator');

    $this->actingAs($social)->get(route('updating_manifests.create'))->assertOk();

    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    expect($manifest->manifest_number)->toStartWith('BAST-P-SUSENAS/');
    expect($manifest->status)->toBe('SUBMITTED');

    $items = $manifest->updatingItems()->orderBy('nks')->get();
    expect($items)->toHaveCount(28);
    expect($items->pluck('delivery_status')->unique()->all())->toBe(['SENT_BY_SOCIAL']);
    expect($items->first()->nks)->toBe('00327');
    expect($items->first()->household_count_listing)->toBe(80);
    expect($items->first()->has_vsen_p)->toBeTrue();
    expect($items->first()->has_peta_ws)->toBeTrue();
});

test('pls menerima manifest dengan checklist lengkap', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    $pls = User::factory()->create();
    $pls->assignRole('ipds_operator');

    $checks = ['items' => []];
    foreach ($manifest->updatingItems as $item) {
        $checks['items'][$item->getKey()] = ['has_vsen_p' => '1', 'has_peta_ws' => '1'];
    }

    $this->actingAs($pls)->post(route('updating_manifests.receive', $manifest), $checks)
        ->assertRedirect(route('updating_manifests.show', $manifest));

    expect($manifest->refresh()->status)->toBe('RECEIVED_BY_PLS');
    expect($manifest->updatingItems()->where('delivery_status', 'RECEIVED_BY_PLS')->count())->toBe(28);
    expect($manifest->updatingItems()->where('physical_condition', 'GOOD')->count())->toBe(28);
});

test('berkas tak lengkap tanpa catatan ditolak, dengan catatan INCOMPLETE', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin, 2);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    $pls = User::factory()->create();
    $pls->assignRole('ipds_operator');

    $ids = $manifest->updatingItems()->orderBy('nks')->pluck('id')->all();

    // Tanpa catatan → 302 error validasi.
    $this->actingAs($pls)->post(route('updating_manifests.receive', $manifest), [
        'items' => [
            $ids[0] => ['has_vsen_p' => '1', 'has_peta_ws' => '1'],
            $ids[1] => ['has_vsen_p' => '1'],
        ],
    ])->assertSessionHasErrors("items.{$ids[1]}");

    // Dengan catatan → INCOMPLETE.
    $this->actingAs($pls)->post(route('updating_manifests.receive', $manifest), [
        'items' => [
            $ids[0] => ['has_vsen_p' => '1', 'has_peta_ws' => '1'],
            $ids[1] => ['has_vsen_p' => '1', 'receive_note' => 'Peta WS tertinggal di desa'],
        ],
    ])->assertRedirect();

    $incomplete = UpdatingManifestItem::findOrFail($ids[1]);
    expect($incomplete->physical_condition)->toBe('INCOMPLETE');
    expect($incomplete->delivery_status)->toBe('RECEIVED_BY_PLS');
});

test('pls menugaskan pengolah via bulk assign', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin, 3);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    $pls = User::factory()->create();
    $pls->assignRole('ipds_operator');

    $checks = ['items' => []];
    foreach ($manifest->updatingItems as $item) {
        $checks['items'][$item->getKey()] = ['has_vsen_p' => '1', 'has_peta_ws' => '1'];
    }
    $this->actingAs($pls)->post(route('updating_manifests.receive', $manifest), $checks)->assertRedirect();

    $officer = Officer::where('code', 'OFF-002')->firstOrFail();
    $ids = $manifest->updatingItems()->orderBy('nks')->pluck('id')->map(fn ($id): int => (int) $id)->all();

    Livewire::actingAs($pls)
        ->test(AssignUpdatingProcessorTable::class, ['manifestId' => $manifest->getKey()])
        ->set('selected', $ids)
        ->set('officerId', (string) $officer->getKey())
        ->call('assign')
        ->assertHasNoErrors();

    expect($manifest->updatingItems()->where('delivery_status', 'ASSIGNED_TO_PROCESSOR')->count())->toBe(3);
    expect($manifest->updatingItems()->where('processing_officer_id', $officer->getKey())->count())->toBe(3);

    $this->actingAs($pls)->get(route('updating_manifests.show', $manifest))
        ->assertOk()->assertSee($officer->name, false);
});

test('bulk assign ditolak untuk role tanpa document.assign', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin, 1);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    $entry = User::factory()->create();
    $entry->assignRole('processing_officer');

    $officer = Officer::where('code', 'OFF-002')->firstOrFail();
    $ids = $manifest->updatingItems()->pluck('id')->map(fn ($id): int => (int) $id)->all();

    Livewire::actingAs($entry)
        ->test(AssignUpdatingProcessorTable::class, ['manifestId' => $manifest->getKey()])
        ->set('selected', $ids)
        ->set('officerId', (string) $officer->getKey())
        ->call('assign')
        ->assertForbidden();

    expect($manifest->updatingItems()->where('delivery_status', 'ASSIGNED_TO_PROCESSOR')->count())->toBe(0);
});

test('export dan bast memuat 28 NKS identik', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);

    $collection = (new UpdatingDocumentDeliveryExport((int) $manifest->getKey()))->collection();

    expect($collection)->toHaveCount(28);
    expect($collection->first()[3])->toBe('00327');
    expect($collection->first()[4])->toBe(80);
    expect($collection->last()[3])->toBe(sprintf('%05d', 327 + 27));

    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get(route('updating_manifests.export', $manifest))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($viewer)->get(route('updating_manifests.print', $manifest))
        ->assertOk()
        ->assertSee($manifest->manifest_number, false)
        ->assertSee('BERITA ACARA SERAH TERIMA', false);
});

test('selisih entri vs listing memicu diskrepansi', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('administrator');
    $allocations = pemutakhiranAlokasiUji($admin, 1);

    $social = User::factory()->create();
    $social->assignRole('social_operator');
    $manifest = manifestPemutakhiranUji($this, $social, $allocations);
    $item = $manifest->updatingItems()->firstOrFail();

    // Listing 80 ruta (indeks 0), dientri 77 → DISCREPANCY.
    $wasolah = User::factory()->create();
    $wasolah->assignRole('processing_supervisor');

    $this->actingAs($wasolah)->post(
        route('updating_manifests.validate-entry', [$manifest, $item]),
        ['entry_count' => 77]
    )->assertRedirect()->assertSessionHas('warning');

    $report = ProcessingEntryReport::query()
        ->where('allocation_id', $item->allocation_id)
        ->where('report_type', 'PEMUTAKHIRAN_SUSENAS')
        ->firstOrFail();

    expect($report->target_qty)->toBe(80);
    expect($report->processed_qty)->toBe(77);
    expect($report->entry_status)->toBe('DISCREPANCY');

    // Entri sinkron → COMPLETED.
    $this->actingAs($wasolah)->post(
        route('updating_manifests.validate-entry', [$manifest, $item]),
        ['entry_count' => 80]
    )->assertRedirect()->assertSessionHas('status');

    expect($report->refresh()->entry_status)->toBe('COMPLETED');
});

test('viewer dan ppl dibatasi pada modul pemutakhiran', function (): void {
    $viewer = User::factory()->create();
    $viewer->assignRole('viewer');

    $this->actingAs($viewer)->get(route('updating_manifests.index'))->assertOk();
    $this->actingAs($viewer)->get(route('updating_manifests.create'))->assertForbidden();
    $this->actingAs($viewer)->post(route('updating_manifests.store'), [])->assertForbidden();

    $ppl = User::factory()->create();
    $ppl->assignRole('field_officer');

    // PPL boleh memantau (document.view) tetapi tidak boleh membuat/menerima.
    $this->actingAs($ppl)->get(route('updating_manifests.index'))->assertOk();
    $this->actingAs($ppl)->get(route('updating_manifests.create'))->assertForbidden();
    $this->actingAs($ppl)->post(route('updating_manifests.store'), [])->assertForbidden();
});
