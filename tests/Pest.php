<?php

use App\Models\Allocation;
use App\Models\Region;
use App\Models\SurveyPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

function adminDsrtUji(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('administrator');

    return $admin;
}

function alokasiSusenasUji(User $admin, string $nks = 'NKS-DSRT-001'): Allocation
{
    $period = SurveyPeriod::where('code', 'SUSENAS-S1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'SLS DSRT Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}

function alokasiSerutiUji(User $admin, string $nks = 'NKS-DSRT-SERUTI'): Allocation
{
    $period = SurveyPeriod::where('code', 'SERUTI-T1-2099')->firstOrFail();
    $desa = Region::where('full_code', '9901001001')->firstOrFail();

    return Allocation::create([
        'survey_period_id' => $period->getKey(),
        'village_region_id' => $desa->getKey(),
        'nks' => $nks,
        'sls_name' => 'SLS Seruti Uji',
        'status' => 'DRAFT',
        'created_by' => $admin->getKey(),
    ]);
}
