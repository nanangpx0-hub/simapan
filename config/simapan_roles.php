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
    ],

    'basic_permissions' => [
        'dashboard.view',
        'profile.manage',
    ],
];
