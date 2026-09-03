<?php

declare(strict_types=1);

use App\Models\SurveyType;
use App\Models\WorkUnit;
use Database\Seeders\SurveyTypeSeeder;
use Database\Seeders\WorkUnitSeeder;

test('survey type seeder idempotent dengan data standar', function (): void {
    $this->seed(SurveyTypeSeeder::class);
    $this->seed(SurveyTypeSeeder::class);

    expect(SurveyType::count())->toBe(2);
    expect(SurveyType::where('code', 'SUSENAS')->exists())->toBeTrue();
    expect(SurveyType::where('code', 'SERUTI')->exists())->toBeTrue();
});

test('work unit seeder idempotent dengan tiga unit root', function (): void {
    $this->seed(WorkUnitSeeder::class);
    $this->seed(WorkUnitSeeder::class);

    expect(WorkUnit::count())->toBe(3);

    foreach (['SOSIAL', 'PENGOLAHAN_LS', 'IPDS'] as $code) {
        $unit = WorkUnit::where('code', $code)->firstOrFail();
        expect($unit->parent_id)->toBeNull();
        expect($unit->is_active)->toBeTrue();
    }
});
