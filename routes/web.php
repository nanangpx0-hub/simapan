<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Master\AllocationController;
use App\Http\Controllers\Master\AssignmentController;
use App\Http\Controllers\Master\DsrtSampleController;
use App\Http\Controllers\Master\OfficerAliasController;
use App\Http\Controllers\Master\OfficerController;
use App\Http\Controllers\Master\RegionController;
use App\Http\Controllers\Master\SurveyPeriodController;
use App\Http\Controllers\Master\SurveyTypeController;
use App\Http\Controllers\Master\WorkUnitController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

Route::middleware(['auth', 'permission:profile.manage'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'permission:admin.user.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware(['auth', 'permission:admin.role.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
});

Route::middleware(['auth', 'permission:master.survey_type.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('jenis-survei', [SurveyTypeController::class, 'index'])->name('jenis-survei.index');
});

Route::middleware(['auth', 'permission:master.survey_type.manage'])->prefix('master')->name('master.')->group(function () {
    Route::get('jenis-survei/create', [SurveyTypeController::class, 'create'])->name('jenis-survei.create');
    Route::post('jenis-survei', [SurveyTypeController::class, 'store'])->name('jenis-survei.store');
    Route::get('jenis-survei/{surveyType}/edit', [SurveyTypeController::class, 'edit'])->name('jenis-survei.edit');
    Route::match(['put', 'patch'], 'jenis-survei/{surveyType}', [SurveyTypeController::class, 'update'])->name('jenis-survei.update');
});

Route::middleware(['auth', 'permission:master.work_unit.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('unit-kerja', [WorkUnitController::class, 'index'])->name('unit-kerja.index');
});

Route::middleware(['auth', 'permission:master.region.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('wilayah', [RegionController::class, 'index'])->name('wilayah.index');
});

Route::middleware(['auth', 'permission:master.region.manage'])->prefix('master')->name('master.')->group(function () {
    Route::get('wilayah/create', [RegionController::class, 'create'])->name('wilayah.create');
    Route::post('wilayah', [RegionController::class, 'store'])->name('wilayah.store');
    Route::get('wilayah/{region}/edit', [RegionController::class, 'edit'])->name('wilayah.edit');
    Route::match(['put', 'patch'], 'wilayah/{region}', [RegionController::class, 'update'])->name('wilayah.update');
});

Route::middleware(['auth', 'permission:master.work_unit.manage'])->prefix('master')->name('master.')->group(function () {
    Route::get('unit-kerja/create', [WorkUnitController::class, 'create'])->name('unit-kerja.create');
    Route::post('unit-kerja', [WorkUnitController::class, 'store'])->name('unit-kerja.store');
    Route::get('unit-kerja/{workUnit}/edit', [WorkUnitController::class, 'edit'])->name('unit-kerja.edit');
    Route::match(['put', 'patch'], 'unit-kerja/{workUnit}', [WorkUnitController::class, 'update'])->name('unit-kerja.update');
});

Route::middleware(['auth', 'permission:master.survey_period.manage'])->prefix('master')->name('master.')->group(function () {
    Route::get('periode-survei/create', [SurveyPeriodController::class, 'create'])->name('survey_periods.create');
    Route::post('periode-survei', [SurveyPeriodController::class, 'store'])->name('survey_periods.store');
    Route::get('periode-survei/{surveyPeriod}/edit', [SurveyPeriodController::class, 'edit'])->name('survey_periods.edit');
    Route::match(['put', 'patch'], 'periode-survei/{surveyPeriod}', [SurveyPeriodController::class, 'update'])->name('survey_periods.update');
    Route::post('periode-survei/{surveyPeriod}/activate', [SurveyPeriodController::class, 'activate'])->name('survey_periods.activate');
    Route::post('periode-survei/{surveyPeriod}/close', [SurveyPeriodController::class, 'close'])->name('survey_periods.close');
    Route::post('periode-survei/{surveyPeriod}/archive', [SurveyPeriodController::class, 'archive'])->name('survey_periods.archive');
});

Route::middleware(['auth', 'permission:master.survey_period.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('periode-survei', [SurveyPeriodController::class, 'index'])->name('survey_periods.index');
    Route::get('periode-survei/{surveyPeriod}', [SurveyPeriodController::class, 'show'])->name('survey_periods.show');
});

Route::middleware(['auth', 'permission:master.officer.manage'])->prefix('master')->name('master.')->group(function () {
    Route::get('petugas/create', [OfficerController::class, 'create'])->name('officers.create');
    Route::post('petugas', [OfficerController::class, 'store'])->name('officers.store');
    Route::get('petugas/{officer}/edit', [OfficerController::class, 'edit'])->name('officers.edit');
    Route::match(['put', 'patch'], 'petugas/{officer}', [OfficerController::class, 'update'])->name('officers.update');
    Route::get('petugas/{officer}/alias/create', [OfficerAliasController::class, 'create'])->name('officers.aliases.create');
    Route::post('petugas/{officer}/alias', [OfficerAliasController::class, 'store'])->name('officers.aliases.store');
    Route::get('petugas/{officer}/alias/{alias}/edit', [OfficerAliasController::class, 'edit'])->name('officers.aliases.edit');
    Route::match(['put', 'patch'], 'petugas/{officer}/alias/{alias}', [OfficerAliasController::class, 'update'])->name('officers.aliases.update');
});

Route::middleware(['auth', 'permission:master.officer.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('petugas', [OfficerController::class, 'index'])->name('officers.index');
    Route::get('petugas/{officer}', [OfficerController::class, 'show'])->name('officers.show');
    Route::get('petugas/{officer}/alias', [OfficerAliasController::class, 'index'])->name('officers.aliases.index');
});

Route::middleware(['auth', 'permission:audit.view'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit_logs.show');
});

Route::middleware(['auth', 'permission:allocation.manage'])->prefix('alokasi')->name('allocations.')->group(function () {
    Route::get('create', [AllocationController::class, 'create'])->name('create');
    Route::post('/', [AllocationController::class, 'store'])->name('store');
    Route::get('{allocation}/edit', [AllocationController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{allocation}', [AllocationController::class, 'update'])->name('update');
    Route::post('{allocation}/activate', [AllocationController::class, 'activate'])->name('activate');
    Route::post('{allocation}/suspend', [AllocationController::class, 'suspend'])->name('suspend');
    Route::post('{allocation}/resume', [AllocationController::class, 'resume'])->name('resume');
    Route::post('{allocation}/complete', [AllocationController::class, 'complete'])->name('complete');
    Route::post('{allocation}/archive', [AllocationController::class, 'archive'])->name('archive');
});

Route::middleware(['auth', 'permission:allocation.assign'])->prefix('alokasi')->name('allocations.')->group(function () {
    Route::post('{allocation}/penugasan', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('{allocation}/penugasan/{assignment}/unassign', [AssignmentController::class, 'unassign'])->name('assignments.unassign');
});

Route::middleware(['auth', 'permission:allocation.view'])->prefix('alokasi')->name('allocations.')->group(function () {
    Route::get('/', [AllocationController::class, 'index'])->name('index');
    Route::get('{allocation}', [AllocationController::class, 'show'])->name('show');
    Route::get('{allocation}/penugasan', [AssignmentController::class, 'index'])->name('assignments.index');
});

Route::middleware(['auth', 'permission:dsrt.manage'])->prefix('alokasi/{allocation}/dsrt')->name('allocations.dsrt.')->group(function () {
    Route::get('create', [DsrtSampleController::class, 'create'])->name('create');
    Route::post('/', [DsrtSampleController::class, 'store'])->name('store');
    Route::get('{dsrtSample}/edit', [DsrtSampleController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{dsrtSample}', [DsrtSampleController::class, 'update'])->name('update');
    Route::post('{dsrtSample}/verify', [DsrtSampleController::class, 'verify'])->name('verify');
    Route::post('{dsrtSample}/archive', [DsrtSampleController::class, 'archive'])->name('archive');
});

Route::middleware(['auth', 'permission:dsrt.view'])->prefix('alokasi/{allocation}/dsrt')->name('allocations.dsrt.')->group(function () {
    Route::get('/', [DsrtSampleController::class, 'index'])->name('index');
    Route::get('{dsrtSample}', [DsrtSampleController::class, 'show'])->name('show');
});

require __DIR__.'/auth.php';
