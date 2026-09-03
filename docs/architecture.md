# Arsitektur — SIMAPAN Fase 1

## 1. Gaya Arsitektur

Monolit Laravel Blade + Livewire 3 untuk web internal. Tanpa API publik di Fase 1.
Pola: Route → Middleware (auth/can) → Livewire Component / Controller + FormRequest → Service/Model + Policy → Eloquent → MySQL 8.

## 2. Stack dan Versi

- PHP 8.2.30, `composer.json` kunci `~8.2.0`.
- Laravel 12.x (kompatibel PHP 8.2).
- MySQL 8.0.30, InnoDB, `utf8mb4_unicode_ci`.
- Livewire 3 + Blade; session + CSRF; `spatie/laravel-permission`.
- Test Pest.

## 3. Struktur Modul Fase 1

| Modul | Tanggung Jawab |
|---|---|
| Auth | Login/logout session, throttle, sesi |
| Identity/RBAC | User, Role, Permission (Spatie), Policy; `officers` tanpa `application_role_id` |
| Master Unit | `work_units` tree — terimplementasi Fase 1B-1 (tanpa delete UI) |
| Master Survei | `survey_types` — terimplementasi Fase 1B-1; `survey_periods` — terimplementasi Fase 1B-3 (workflow `DRAFT/ACTIVE/CLOSED/ARCHIVED`, tanpa delete UI) |
| Master Wilayah | `regions` hierarkis 4 level — terimplementasi Fase 1B-2 (tanpa delete UI; SLS/Sub-SLS menyusul fase alokasi) |
| Master Petugas | `officers` (`normalized_name`, `softDeletes`) + `officer_aliases` — terimplementasi Fase 1B-4 (tanpa delete UI; masking kontak) |
| Audit | `audit_logs` append-only tanpa `updated_at` — terimplementasi Fase 1B-5 (`AuditLogger`, `AuditSanitizer`, `Auditable`, observer, halaman read-only) |
| Alokasi 2A | `allocations` + `assignments` historis — CRUD manual + workflow status + penugasan (tanpa delete UI; impor ditunda) |
| DSRT 2B | `dsrt_samples` nested alokasi Susenas — CRUD + verify/archive (tanpa delete UI; impor/dokumen/temuan/finalisasi ditunda) |

Fase lanjut (di luar Fase 1): penugasan, batch pengolahan, verifikasi, dashboard, impor.

## 4. Auth dan RBAC

- Session driver database/file; cookie `HttpOnly`, `SameSite=Lax`; CSRF di semua mutasi.
- Permission kanonik (guard `web`, dot notation Inggris): 15 Fase 1A
  (`dashboard.view`, `profile.manage`,
  `admin.user.manage`, `admin.role.manage`, `audit.view`,
  `master.work_unit.view/manage`, `master.survey_type.view/manage`,
  `master.survey_period.view/manage`, `master.region.view/manage`,
  `master.officer.view/manage`)
  + 3 Fase 2A (`allocation.view/manage/assign`)
  + 3 Fase 2B (`dsrt.view/manage/verify`); total 21.
  Nama menu UI boleh Indonesia. Katalog di `config/simapan_roles.php`.
- Pemetaan:
  Administrator semua 21 izin; tujuh role lain hanya `dashboard.view` +
  `profile.manage` (tanpa akses survei global).
  Operator Sos/IPDS parsial berbasis tiket/penugasan menyusul (belum Fase 1A).
  Tanpa `Gate::before`; tanpa bypass email/ID; role hanya di `users` via Spatie.
- Cache permission: local/produksi awal `database`, testing `array`;
  clear via seeder (`PermissionRegistrar`), `permission:cache-reset`, `optimize:clear`.
- Policy per model (`WorkUnitPolicy` dst.) + `can:` di route + `@can` di Blade.
  Jangan mengandalkan kolom `role` string di client. `officers` tidak menyimpan role; role di `users`.

## 5. Komponen Aplikasi

- Model: `User` (`HasRoles`, `is_active`), `SurveyType`, `SurveyPeriod` (relasi type/creator/closer, scope slot/active), `Region`, `Officer`, `OfficerAlias`, `AuditLog`.
  (`WorkUnit` dan master menyusul; belum ada tabel bisnis di Fase 1A.)
- Policy Fase 1A: `UserPolicy` (↔ `admin.user.manage`), `RolePolicy` (↔ `admin.role.manage`).
- Controller Fase 1A: `Admin\UserController` (CRUD + pengaman self/last-admin + audit role),
  `Admin\RoleController@index` (katalog read-only), `AuditLogController` (read-only);
  middleware `permission:` di route + `@can` di menu.
- Controller Fase 1B: `Master\SurveyTypeController`, `Master\WorkUnitController`,
  `Master\RegionController`, `Master\SurveyPeriodController`,
  `Master\OfficerController`, `Master\OfficerAliasController`;
  action `Activate/Close/ArchiveSurveyPeriod` (transaction + `lockForUpdate`
  untuk satu slot aktif).
- Controller Fase 2A: `Master\AllocationController`, `Master\AssignmentController`;
  action `Activate/Suspend/Resume/Complete/ArchiveAllocation`, `Assign/UnassignOfficer`
  (transaction + `lockForUpdate` untuk satu assignment aktif per role).
- Controller Fase 2B: `Master\DsrtSampleController`;
  action `Verify/ArchiveDsrtSample` (transaction; tanpa reopen).
- Observer/Trait `Auditable`: `created/updated/deleted` + event login → tulis audit tanpa PII/sensitive; filter field sebelum tulis.
- Livewire: `Master/*Table`, `Master/*Form`, `Admin/UserCrud`, `Admin/RoleCrud`,
  `Master/OfficerAliasManager`, `Audit/AuditLogTable` (read-only).
- FormRequest per entitas untuk validasi kode-string dan aturan hierarki.
- Factory + Seeder dummy per entitas untuk dev/test saja.

## 6. Route dan Halaman (kontrak awal)

- `/login`, `/dashboard` (auth + `dashboard.view`).
- `/profile` (auth + `profile.manage`; fungsi update profil/password/hapus akun Breeze tidak diubah selain authorization).
- `/admin/users` (CRUD, perlu `admin.user.manage`), `/admin/roles` (read-only, perlu `admin.role.manage`).
- `/master/jenis-survei`, `/master/unit-kerja` (Fase 1B-1: list perlu `.view`, tulis perlu `.manage`; tanpa endpoint delete).
- `/master/wilayah` (Fase 1B-2: filter level/parent/status/pencarian; perlu `master.region.view/manage`; tanpa endpoint delete).
- `/master/periode-survei` (Fase 1B-3: perlu `master.survey_period.view/manage`; action status via POST `activate/close/archive`; tanpa endpoint delete).
- `/master/petugas` + `/master/petugas/{officer}/alias` (Fase 1B-4: perlu `master.officer.view/manage`; tanpa endpoint delete).
- `/audit-logs` (Fase 1B-5: perlu `audit.view`; read-only, filter, tanpa edit/hapus/export).
- `/alokasi` (Fase 2A: perlu `allocation.view/manage/assign`; CRUD manual + penugasan historis + action status POST; tanpa delete).
- `/alokasi/{allocation}/dsrt` (Fase 2B: perlu `dsrt.view/manage/verify`; nested Susenas; action verify/archive POST; tanpa delete).
- `/master/petugas`, `/master/petugas/{officer}/alias` (Fase 1B-4: perlu `master.officer.view/manage`; tanpa endpoint delete).
- Master, audit-log, dan modul lain menyusul (kontrak awal tetap di bawah).
- `/master/unit-kerja`, `/master/periode-survei`, `/master/wilayah` (kontrak awal; sudah terimplementasi di atas).
- `/audit-logs` (admin penuh; pimpinan ringkas — perlu konfirmasi filter).
- Semua route web; tanpa API JSON di Fase 1.

## 7. Data Flow Audit

Mutasi master → Eloquent event → `AuditObserver` → `INSERT audit_logs`
(actor, action, morph type/id, old/new JSON, IP, UA, waktu).
Tidak ada path update/delete ke `audit_logs` dari aplikasi; enforcement di
Policy + DB grant (opsional read-only user) + review kode.

## 8. Keputusan Arsitektur

- Tetap monolit sampai akhir Fase 2; ekstraksi service bila batch pengolahan berat.
- Semua `code`/NKS/kode wilayah/kode petugas selalu string di DB, validasi, dan payload; dilarang cast ke integer.
- Satu periode aktif per `survey_type + year + period_type + period_number`.
- Satu assignment aktif per `allocation + assignment_role` (transaction + lock; tanpa partial unique).
- Pagination server-side untuk tabel master dan audit.
- Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403 — detail di `workflows.md`.
