<?php

declare(strict_types=1);

use App\Models\SurveyPeriod;
use Database\Seeders\SurveyPeriodSeeder;
use Database\Seeders\SurveyTypeSeeder;

test('survey period seeder idempotent dengan dua draft dummy', function (): void {
    $this->seed(SurveyTypeSeeder::class);
    $this->seed(SurveyPeriodSeeder::class);
    $this->seed(SurveyPeriodSeeder::class);

    expect(SurveyPeriod::count())->toBe(2);

    $susenas = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $seruti = SurveyPeriod::where('code', 'SERUTI-T1-2099')->firstOrFail();

    expect($susenas->status)->toBe('DRAFT');
    expect($susenas->period_type)->toBe('SEMESTER');
    expect($susenas->period_number)->toBe(1);
    expect($susenas->year)->toBe(2099);
    expect($seruti->status)->toBe('DRAFT');
    expect($seruti->period_type)->toBe('TRIWULAN');
    expect($seruti->period_number)->toBe(1);
    expect($seruti->year)->toBe(2099);
});
