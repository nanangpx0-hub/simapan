<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Katalog Role Sistem SIMAPAN (Fase 1A)
|--------------------------------------------------------------------------
| Slug role bersifat sistem-terkelola: perubahan hanya via seeder + kode.
| Label Indonesia hanya untuk tampilan UI.
*/

return [
    'roles' => [
        'super_admin' => 'Super Admin',
        'administrator' => 'Administrator',
        'field_officer' => 'Petugas Lapangan / PPL',
        'field_supervisor' => 'Pengawas Pendataan Lapangan / PML',
        'processing_officer' => 'Petugas Pengolahan',
        'processing_supervisor' => 'Pengawas Pengolahan',
        'social_operator' => 'Operator Tim Statistik Sosial',
        'ipds_operator' => 'Operator IPDS',
        'viewer' => 'Viewer / Pimpinan',
    ],

    'permissions' => [
        'dashboard.view',
        'profile.manage',
        'admin.user.manage',
        'admin.role.manage',
        'audit.view',
        'master.work_unit.view',
        'master.work_unit.manage',
        'master.survey_type.view',
        'master.survey_type.manage',
        'master.survey_period.view',
        'master.survey_period.manage',
        'master.region.view',
        'master.region.manage',
        'master.officer.view',
        'master.officer.manage',
        'allocation.view',
        'allocation.manage',
        'allocation.assign',
        'dsrt.view',
        'dsrt.manage',
        'dsrt.verify',
        'document.view',
        'document.manage',
        'document.receive',
        'document.assign',
    ],

    'basic_permissions' => [
        'dashboard.view',
        'profile.manage',
    ],

    /*
    |----------------------------------------------------------------------
    | Role Super Admin (tingkat tertinggi)
    |----------------------------------------------------------------------
    | Hanya pemegang role ini yang boleh mengelola role lain (create,
    | update, delete, sinkronisasi permission) serta menugaskan/mencabut
    | role super_admin pada akun pengguna. Dijaga via Gate 'super-admin'.
    */
    'super_role' => 'super_admin',

    /*
    |----------------------------------------------------------------------
    | Matriks Role-Permission Kanonik (Fase 2)
    |----------------------------------------------------------------------
    | Sumber kebenaran izin per role. Basic permissions (dashboard.view,
    | profile.manage) otomatis menyertai setiap role via RoleSeeder.
    */
    'role_permissions' => [
        'super_admin' => '*',

        'administrator' => '*',

        'viewer' => [
            'audit.view',
            'master.work_unit.view',
            'master.survey_type.view',
            'master.survey_period.view',
            'master.region.view',
            'master.officer.view',
            'allocation.view',
            'dsrt.view',
            'document.view',
        ],

        'social_operator' => [
            'master.work_unit.view',
            'master.survey_period.view',
            'master.survey_type.view',
            'master.region.view',
            'master.officer.view',
            'allocation.view',
            'allocation.manage',
            'allocation.assign',
            'dsrt.view',
            'document.view',
            'document.manage',
        ],

        'ipds_operator' => [
            'master.work_unit.view',
            'master.survey_period.view',
            'master.region.view',
            'master.officer.view',
            'master.officer.manage',
            'allocation.view',
            'document.view',
            'document.manage',
            'document.receive',
            'document.assign',
        ],

        'field_supervisor' => [
            'allocation.view',
            'allocation.assign',
            'dsrt.view',
            'dsrt.verify',
            'document.view',
            'document.manage',
        ],

        'field_officer' => [
            'allocation.view',
            'dsrt.view',
            'dsrt.manage',
            'document.view',
        ],

        'processing_supervisor' => [
            'master.work_unit.view',
            'master.officer.view',
            'document.view',
            'document.manage',
            'document.receive',
            'document.assign',
        ],

        'processing_officer' => [
            'document.view',
        ],
    ],
];
