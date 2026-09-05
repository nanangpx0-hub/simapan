<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Master\AllocationController;
use App\Http\Controllers\Master\AssignmentController;
use App\Http\Controllers\Master\DocumentController;
use App\Http\Controllers\Master\DocumentLocationController;
use App\Http\Controllers\Master\DocumentManifestController;
use App\Http\Controllers\Master\DocumentProcessingAssignmentController;
use App\Http\Controllers\Master\DocumentTransferController;
use App\Http\Controllers\Master\DocumentTypeController;
use App\Http\Controllers\Master\ReportingMonitoringController;
use App\Http\Controllers\Master\DsrtSampleController;
use App\Http\Controllers\Master\OfficerAliasController;
use App\Http\Controllers\Master\OfficerController;
use App\Http\Controllers\Master\RegionController;
use App\Http\Controllers\Master\SurveyPeriodController;
use App\Http\Controllers\Master\SurveyTypeController;
use App\Http\Controllers\Master\UpdatingManifestController;
use App\Http\Controllers\Master\WorkUnitController;
use App\Http\Controllers\ProfileController;
use App\Models\Allocation;
use App\Models\Document;
use App\Models\DocumentManifest;
use App\Models\DocumentProcessingAssignment;
use App\Models\DsrtSample;
use App\Models\Officer;
use App\Models\SurveyPeriod;
use App\Models\User;
use App\Models\WorkUnit;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    $user = request()->user();
    assert($user instanceof User);

    // Scoping data: PPL/PML/Pengolahan melihat data milik penugasannya,
    // Administrator/Pimpinan/Operator melihat agregat wilayah penuh.
    $scoped = $user->hasScopedDataAccess();
    $officerId = $user->officerId();

    $myAllocations = null;
    $myDsrtPending = null;
    $myDocuments = null;

    if ($scoped && $officerId !== null) {
        $myAllocations = Allocation::query()
            ->where('status', 'ACTIVE')
            ->whereHas('activeAssignments', fn ($q) => $q->where('officer_id', $officerId))
            ->count();

        $myDsrtPending = DsrtSample::query()
            ->where('record_status', 'DRAFT')
            ->whereHas('allocation.activeAssignments', fn ($q) => $q->where('officer_id', $officerId))
            ->count();

        $myDocuments = Document::query()
            ->whereIn('status', ['REGISTERED', 'IN_TRANSIT'])
            ->where(function ($q) use ($officerId): void {
                $q->whereHas('allocation.activeAssignments', fn ($a) => $a->where('officer_id', $officerId))
                    ->orWhereHas('processingAssignments', fn ($p) => $p->where('officer_id', $officerId)->where('status', 'ACTIVE'));
            })
            ->count();
    }

    // Kartu pemantauan khusus Tim Statistik Sosial (SOP Subject Matter).
    $isSocial = $user->hasRole('social_operator');
    $social = [
        'visible' => $isSocial,
        'incomplete_allocations' => null,
        'dsrt_pending' => null,
        'incoming_manifests' => null,
    ];

    if ($isSocial) {
        $social['incomplete_allocations'] = Allocation::query()
            ->whereIn('status', ['DRAFT', 'ACTIVE'])
            ->whereHas('period.surveyType', fn ($q) => $q->whereIn('code', User::SOCIAL_SURVEY_CODES))
            ->where(function ($q): void {
                $q->whereDoesntHave('activeAssignments', fn ($a) => $a->where('assignment_role', 'FIELD_OFFICER'))
                    ->orWhereDoesntHave('activeAssignments', fn ($a) => $a->where('assignment_role', 'FIELD_SUPERVISOR'));
            })
            ->count();

        $social['dsrt_pending'] = DsrtSample::query()
            ->where('record_status', 'DRAFT')
            ->whereHas('allocation.period.surveyType', fn ($q) => $q->whereIn('code', User::SOCIAL_SURVEY_CODES))
            ->count();

        $sosialUnitId = WorkUnit::where('code', 'SOSIAL')->value('id');
        $social['incoming_manifests'] = DocumentManifest::query()
            ->where('status', 'SUBMITTED')
            ->when($sosialUnitId !== null, fn ($q) => $q->where('from_work_unit_id', $sosialUnitId))
            ->count();
    }

    // Widget pemantauan ruang pengolahan (PLS/IPDS).
    $isProcessing = $user->hasRole('ipds_operator') || $user->hasRole('processing_supervisor');
    $processing = [
        'visible' => $isProcessing,
        'incoming_manifests' => null,
        'ready_docs' => null,
        'processing_docs' => null,
    ];

    if ($isProcessing) {
        $officerUnit = $user->officer?->workUnit?->code;
        $targets = in_array($officerUnit, DocumentProcessingAssignment::PROCESSING_UNIT_CODES, true)
            ? [$officerUnit]
            : DocumentProcessingAssignment::PROCESSING_UNIT_CODES;

        $processing['incoming_manifests'] = DocumentManifest::query()
            ->where('status', 'SUBMITTED')
            ->whereHas('toUnit', fn ($q) => $q->whereIn('code', $targets))
            ->count();

        $processing['ready_docs'] = Document::query()
            ->whereIn('status', ['RECEIVED', 'RECEIVED_PARTIAL'])
            ->whereHas('holder', fn ($q) => $q->whereHas('workUnit', fn ($w) => $w->whereIn('code', $targets)))
            ->whereDoesntHave('processingAssignments', fn ($q) => $q->where('status', 'ACTIVE'))
            ->count();

        $processing['processing_docs'] = DocumentProcessingAssignment::query()
            ->where('status', 'ACTIVE')
            ->whereHas('officer.workUnit', fn ($q) => $q->whereIn('code', $targets))
            ->count();
    }

    // Executive Monitoring Dashboard untuk Pimpinan (viewer).
    $isExecutive = $user->hasRole('viewer');
    $executive = [
        'visible' => $isExecutive,
        'field_total' => 0,
        'field_completed' => 0,
        'field_percent' => 0.0,
        'dsrt_total' => 0,
        'dsrt_verified' => 0,
        'dsrt_percent' => 0.0,
        'flow' => ['lapangan' => 0, 'menuju' => 0, 'diolah' => 0, 'selesai' => 0],
        'periods' => [],
        'by_type' => [],
    ];

    if ($isExecutive) {
        $executive['field_total'] = Allocation::query()->count();
        $executive['field_completed'] = Allocation::query()->where('status', 'COMPLETED')->count();
        $executive['field_percent'] = $executive['field_total'] > 0
            ? round($executive['field_completed'] / $executive['field_total'] * 100, 1)
            : 0.0;

        $executive['dsrt_total'] = DsrtSample::query()->count();
        $executive['dsrt_verified'] = DsrtSample::query()->where('record_status', 'VERIFIED')->count();
        $executive['dsrt_percent'] = $executive['dsrt_total'] > 0
            ? round($executive['dsrt_verified'] / $executive['dsrt_total'] * 100, 1)
            : 0.0;

        $executive['flow'] = [
            'lapangan' => Document::query()->where('status', 'REGISTERED')->count(),
            'menuju' => Document::query()->where('status', 'IN_TRANSIT')->count(),
            'diolah' => Document::query()->where('status', 'ASSIGNED_PROCESSING')->count(),
            'selesai' => Document::query()->whereIn('status', ['RECEIVED', 'RECEIVED_PARTIAL'])->count(),
        ];

        $executive['periods'] = SurveyPeriod::query()
            ->where('status', 'ACTIVE')
            ->orderBy('end_date')
            ->get(['code', 'name', 'end_date'])
            ->map(fn ($p) => [
                'code' => $p->code,
                'name' => $p->name,
                'end_date' => $p->end_date?->format('Y-m-d'),
                'days_left' => $p->end_date !== null ? (int) now()->startOfDay()->diffInDays($p->end_date, false) : null,
            ])->all();

        foreach (User::SOCIAL_SURVEY_CODES as $typeCode) {
            $allocBase = Allocation::query()->whereHas('period.surveyType', fn ($q) => $q->where('code', $typeCode));
            $total = (clone $allocBase)->count();
            $done = (clone $allocBase)->where('status', 'COMPLETED')->count();
            $verified = DsrtSample::query()
                ->where('record_status', 'VERIFIED')
                ->whereHas('allocation.period.surveyType', fn ($q) => $q->where('code', $typeCode))
                ->count();

            $executive['by_type'][] = [
                'code' => $typeCode,
                'total' => $total,
                'completed' => $done,
                'percent' => $total > 0 ? round($done / $total * 100, 1) : 0.0,
                'dsrt_verified' => $verified,
            ];
        }
    }

    return view('dashboard', [
        'stats' => [
            'users' => $user->can('admin.user.manage') ? User::query()->count() : null,
            'officers' => $user->can('master.officer.view') ? Officer::query()->where('status', 'ACTIVE')->count() : null,
            'allocations' => $user->can('allocation.view') ? ($scoped
                ? $myAllocations
                : Allocation::query()->where('status', 'ACTIVE')->count()) : null,
            'documents' => $user->can('document.view') ? ($scoped
                ? $myDocuments
                : Document::query()->whereIn('status', ['REGISTERED', 'IN_TRANSIT'])->count()) : null,
            'periods' => $user->can('master.survey_period.view') ? SurveyPeriod::query()->where('status', 'ACTIVE')->count() : null,
            'manifests' => $user->can('document.view') && ! $scoped ? DocumentManifest::query()->where('status', 'SUBMITTED')->count() : null,
        ],
        'tasks' => [
            'allocations' => $myAllocations,
            'dsrt_pending' => $myDsrtPending,
            'documents' => $myDocuments,
        ],
        'social' => $social,
        'processing' => $processing,
        'executive' => $executive,
    ]);
})->middleware(['auth', 'verified', 'permission:dashboard.view'])->name('dashboard');

Route::middleware(['auth', 'permission:profile.manage'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'permission:admin.user.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('users/export', [UserController::class, 'export'])->name('users.export');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    Route::resource('users', UserController::class)->except(['show']);
});

Route::middleware(['auth', 'permission:admin.role.manage'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
});

// Manajemen role (create/update/delete + sinkronisasi permission) —
// hanya Super Admin (gate eksplisit + policy + controller).
Route::middleware(['auth', 'can:super-admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
});

Route::middleware(['auth', 'permission:master.survey_type.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('jenis-survei/export', [SurveyTypeController::class, 'export'])->name('jenis-survei.export');
    Route::get('jenis-survei', [SurveyTypeController::class, 'index'])->name('jenis-survei.index');
});

Route::middleware(['auth', 'permission:master.survey_type.manage'])->prefix('master')->name('master.')->group(function () {
    Route::post('jenis-survei/import', [SurveyTypeController::class, 'import'])->name('jenis-survei.import');
    Route::get('jenis-survei/create', [SurveyTypeController::class, 'create'])->name('jenis-survei.create');
    Route::post('jenis-survei', [SurveyTypeController::class, 'store'])->name('jenis-survei.store');
    Route::get('jenis-survei/{surveyType}/edit', [SurveyTypeController::class, 'edit'])->name('jenis-survei.edit');
    Route::match(['put', 'patch'], 'jenis-survei/{surveyType}', [SurveyTypeController::class, 'update'])->name('jenis-survei.update');
});

Route::middleware(['auth', 'permission:master.work_unit.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('unit-kerja/export', [WorkUnitController::class, 'export'])->name('unit-kerja.export');
    Route::get('unit-kerja', [WorkUnitController::class, 'index'])->name('unit-kerja.index');
});

Route::middleware(['auth', 'permission:master.region.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('wilayah/export', [RegionController::class, 'export'])->name('wilayah.export');
    Route::get('wilayah', [RegionController::class, 'index'])->name('wilayah.index');
});

Route::middleware(['auth', 'permission:master.region.manage'])->prefix('master')->name('master.')->group(function () {
    Route::post('wilayah/import', [RegionController::class, 'import'])->name('wilayah.import');
    Route::get('wilayah/create', [RegionController::class, 'create'])->name('wilayah.create');
    Route::post('wilayah', [RegionController::class, 'store'])->name('wilayah.store');
    Route::get('wilayah/{region}/edit', [RegionController::class, 'edit'])->name('wilayah.edit');
    Route::match(['put', 'patch'], 'wilayah/{region}', [RegionController::class, 'update'])->name('wilayah.update');
});

Route::middleware(['auth', 'permission:master.work_unit.manage'])->prefix('master')->name('master.')->group(function () {
    Route::post('unit-kerja/import', [WorkUnitController::class, 'import'])->name('unit-kerja.import');
    Route::get('unit-kerja/create', [WorkUnitController::class, 'create'])->name('unit-kerja.create');
    Route::post('unit-kerja', [WorkUnitController::class, 'store'])->name('unit-kerja.store');
    Route::get('unit-kerja/{workUnit}/edit', [WorkUnitController::class, 'edit'])->name('unit-kerja.edit');
    Route::match(['put', 'patch'], 'unit-kerja/{workUnit}', [WorkUnitController::class, 'update'])->name('unit-kerja.update');
});

Route::middleware(['auth', 'permission:master.survey_period.manage'])->prefix('master')->name('master.')->group(function () {
    Route::post('periode-survei/import', [SurveyPeriodController::class, 'import'])->name('survey_periods.import');
    Route::get('periode-survei/create', [SurveyPeriodController::class, 'create'])->name('survey_periods.create');
    Route::post('periode-survei', [SurveyPeriodController::class, 'store'])->name('survey_periods.store');
    Route::get('periode-survei/{surveyPeriod}/edit', [SurveyPeriodController::class, 'edit'])->name('survey_periods.edit');
    Route::match(['put', 'patch'], 'periode-survei/{surveyPeriod}', [SurveyPeriodController::class, 'update'])->name('survey_periods.update');
    Route::post('periode-survei/{surveyPeriod}/activate', [SurveyPeriodController::class, 'activate'])->name('survey_periods.activate');
    Route::post('periode-survei/{surveyPeriod}/close', [SurveyPeriodController::class, 'close'])->name('survey_periods.close');
    Route::post('periode-survei/{surveyPeriod}/archive', [SurveyPeriodController::class, 'archive'])->name('survey_periods.archive');
});

Route::middleware(['auth', 'permission:master.survey_period.view'])->prefix('master')->name('master.')->group(function () {
    Route::get('periode-survei/export', [SurveyPeriodController::class, 'export'])->name('survey_periods.export');
    Route::get('periode-survei', [SurveyPeriodController::class, 'index'])->name('survey_periods.index');
    Route::get('periode-survei/{surveyPeriod}', [SurveyPeriodController::class, 'show'])->name('survey_periods.show');
});

Route::middleware(['auth', 'permission:master.officer.manage'])->prefix('master')->name('master.')->group(function () {
    Route::post('petugas/import', [OfficerController::class, 'import'])->name('officers.import');
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
    Route::get('petugas/export', [OfficerController::class, 'export'])->name('officers.export');
    Route::get('petugas', [OfficerController::class, 'index'])->name('officers.index');
    Route::get('petugas/{officer}', [OfficerController::class, 'show'])->name('officers.show');
    Route::get('petugas/{officer}/alias', [OfficerAliasController::class, 'index'])->name('officers.aliases.index');
});

Route::middleware(['auth', 'permission:audit.view'])->group(function () {
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit_logs.index');
    Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit_logs.show');
});

Route::middleware(['auth', 'permission:document.manage'])->prefix('pemutakhiran')->name('updating_manifests.')->group(function () {
    Route::get('create', [UpdatingManifestController::class, 'create'])->name('create');
    Route::post('/', [UpdatingManifestController::class, 'store'])->name('store');
});

Route::middleware(['auth', 'permission:document.view'])->prefix('pemutakhiran')->name('updating_manifests.')->group(function () {
    Route::get('/', [UpdatingManifestController::class, 'index'])->name('index');
    Route::get('{manifest}', [UpdatingManifestController::class, 'show'])->name('show');
    Route::get('{manifest}/bast', [UpdatingManifestController::class, 'print'])->name('print');
    Route::get('{manifest}/export', [UpdatingManifestController::class, 'export'])->name('export');
});

Route::middleware(['auth', 'permission:document.receive'])->prefix('pemutakhiran')->name('updating_manifests.')->group(function () {
    Route::post('{manifest}/terima', [UpdatingManifestController::class, 'receive'])->name('receive');
});

Route::middleware(['auth', 'permission:document.assign'])->prefix('pemutakhiran')->name('updating_manifests.')->group(function () {
    Route::post('{manifest}/item/{item}/validasi-entri', [UpdatingManifestController::class, 'validateEntry'])->name('validate-entry');
});

Route::middleware(['auth', 'permission:allocation.manage'])->prefix('alokasi')->name('allocations.')->group(function () {
    Route::post('import', [AllocationController::class, 'import'])->name('import');
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
    Route::get('export', [AllocationController::class, 'export'])->name('export');
    Route::get('rekap-eksekutif/export', [AllocationController::class, 'exportExecutive'])->name('executive-export');
    Route::get('/', [AllocationController::class, 'index'])->name('index');
    Route::get('{allocation}', [AllocationController::class, 'show'])->name('show');
    Route::get('{allocation}/penugasan', [AssignmentController::class, 'index'])->name('assignments.index');
});

Route::middleware(['auth', 'permission:dsrt.manage'])->prefix('alokasi/{allocation}/dsrt')->name('allocations.dsrt.')->group(function () {
    Route::post('import', [DsrtSampleController::class, 'import'])->name('import');
    Route::get('create', [DsrtSampleController::class, 'create'])->name('create');
    Route::post('/', [DsrtSampleController::class, 'store'])->name('store');
    Route::get('{dsrtSample}/edit', [DsrtSampleController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{dsrtSample}', [DsrtSampleController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'permission:dsrt.verify'])->prefix('alokasi/{allocation}/dsrt')->name('allocations.dsrt.')->group(function () {
    Route::post('{dsrtSample}/verify', [DsrtSampleController::class, 'verify'])->name('verify');
    Route::post('{dsrtSample}/archive', [DsrtSampleController::class, 'archive'])->name('archive');
});

Route::middleware(['auth', 'permission:dsrt.view'])->prefix('alokasi/{allocation}/dsrt')->name('allocations.dsrt.')->group(function () {
    Route::get('export', [DsrtSampleController::class, 'export'])->name('export');
    Route::get('/', [DsrtSampleController::class, 'index'])->name('index');
    Route::get('{dsrtSample}', [DsrtSampleController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'permission:document.manage'])->prefix('dokumen')->name('documents.')->group(function () {
    Route::post('import', [DocumentController::class, 'import'])->name('import');
    Route::get('create', [DocumentController::class, 'create'])->name('create');
    Route::post('/', [DocumentController::class, 'store'])->name('store');
    Route::get('{document}/edit', [DocumentController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{document}', [DocumentController::class, 'update'])->name('update');
});

// Dashboard konsolidasi pelaporan 5 dokumen SUSENAS-SERUTI.
Route::middleware(['auth', 'permission:document.view'])->group(function () {
    Route::get('/monitoring/pelaporan-dokumen', [ReportingMonitoringController::class, 'index'])
        ->name('monitoring.pelaporan-dokumen');
});

Route::middleware(['auth', 'permission:document.manage'])->prefix('dokumen')->name('document_types.')->group(function () {
    Route::post('jenis/import', [DocumentTypeController::class, 'import'])->name('import');
    Route::get('jenis/create', [DocumentTypeController::class, 'create'])->name('create');
    Route::post('jenis', [DocumentTypeController::class, 'store'])->name('store');
    Route::get('jenis/{documentType}/edit', [DocumentTypeController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], 'jenis/{documentType}', [DocumentTypeController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'permission:document.manage'])->prefix('dokumen')->name('document_locations.')->group(function () {
    Route::post('lokasi/import', [DocumentLocationController::class, 'import'])->name('import');
    Route::get('lokasi/create', [DocumentLocationController::class, 'create'])->name('create');
    Route::post('lokasi', [DocumentLocationController::class, 'store'])->name('store');
    Route::get('lokasi/{documentLocation}/edit', [DocumentLocationController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], 'lokasi/{documentLocation}', [DocumentLocationController::class, 'update'])->name('update');
});

Route::middleware(['auth', 'permission:document.manage'])->prefix('manifest')->name('document_manifests.')->group(function () {
    Route::post('import', [DocumentManifestController::class, 'import'])->name('import');
    Route::get('create', [DocumentManifestController::class, 'create'])->name('create');
    Route::post('/', [DocumentManifestController::class, 'store'])->name('store');
    Route::get('{documentManifest}/edit', [DocumentManifestController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '{documentManifest}', [DocumentManifestController::class, 'update'])->name('update');
    Route::post('{documentManifest}/item', [DocumentManifestController::class, 'storeItem'])->name('items.store');
    Route::post('{documentManifest}/submit', [DocumentManifestController::class, 'submit'])->name('submit');
});

Route::middleware(['auth', 'permission:document.assign'])->prefix('dokumen')->name('document_processing_assignments.')->group(function () {
    Route::get('{document}/penugasan', [DocumentProcessingAssignmentController::class, 'index'])->name('index');
    Route::get('{document}/penugasan/create', [DocumentProcessingAssignmentController::class, 'create'])->name('create');
    Route::post('{document}/penugasan', [DocumentProcessingAssignmentController::class, 'store'])->name('store');
    Route::post('{document}/penugasan/{assignment}/kembali', [DocumentProcessingAssignmentController::class, 'returnAssignment'])->name('return');
});

Route::middleware(['auth', 'permission:document.view'])->prefix('dokumen')->name('document_types.')->group(function () {
    Route::get('jenis/export', [DocumentTypeController::class, 'export'])->name('export');
    Route::get('jenis', [DocumentTypeController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'permission:document.view'])->prefix('dokumen')->name('document_locations.')->group(function () {
    Route::get('lokasi/export', [DocumentLocationController::class, 'export'])->name('export');
    Route::get('lokasi', [DocumentLocationController::class, 'index'])->name('index');
});

Route::middleware(['auth', 'permission:document.view'])->prefix('dokumen')->name('documents.')->group(function () {
    Route::get('export', [DocumentController::class, 'export'])->name('export');
    Route::get('/', [DocumentController::class, 'index'])->name('index');
    Route::get('{document}', [DocumentController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'permission:document.view'])->prefix('manifest')->name('document_manifests.')->group(function () {
    Route::get('export', [DocumentManifestController::class, 'export'])->name('export');
    Route::get('/', [DocumentManifestController::class, 'index'])->name('index');
    Route::get('{documentManifest}', [DocumentManifestController::class, 'show'])->name('show');
});

Route::middleware(['auth', 'permission:document.receive'])->prefix('manifest/{documentManifest}/serah-terima')->name('document_transfers.')->group(function () {
    Route::get('/', [DocumentTransferController::class, 'show'])->name('show');
    Route::get('periksa', [DocumentTransferController::class, 'edit'])->name('edit');
    Route::match(['put', 'patch'], '/', [DocumentTransferController::class, 'update'])->name('update');
});

require __DIR__.'/auth.php';
