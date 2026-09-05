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
| Dokumen 2C-1 | `documents` + manifest + serah terima + holder + penugasan internal — workflow fisik SOSIAL→PENGOLAHAN_LS/IPDS (tanpa delete UI; tanpa pinjam/upload/arsip) |
| Pelaporan 2C-2 | `processing_entry_reports` (5 laporan SUSENAS-SERUTI) + tipe dokumen kanonik + reconciliation engine + SLA/deadline + notifikasi + dashboard konsolidasi real-time |

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
  + 3 Fase 2B (`dsrt.view/manage/verify`)
  + 4 Fase 2C-1 (`document.view/manage/receive/assign`); total 25.
  Nama menu UI boleh Indonesia. Katalog di `config/simapan_roles.php`.
- Pemetaan kanonik Fase 2 (`config/simapan_roles.php` → `role_permissions`, ditegakkan
  `RoleSeeder`, dikunci `RbacMatrixTest`):
  Administrator 25 izin; Viewer 8 `.view` + `audit.view` + basic
  + Executive Monitoring Dashboard (progres lapangan, verifikasi DSRT,
  alur dokumen, deadline periode, rekap per jenis survei) + ekspor
  rekap eksekutif per kecamatan + filter cepat audit pimpinan; Sosial
  (`work_unit/survey_period/type/region/officer.view`,
  `allocation.view/manage/assign`, `dsrt.view`, `document.view/manage`)
  + kartu Pemantauan Tim Sosial di dashboard (alokasi belum lengkap petugas,
  DSRT siap verifikasi, manifest masuk dari PML) + scope Livewire ke
  `SUSENAS/SERUTI`; IPDS (`work_unit/survey_period/region/officer.view`,
  `master.officer.manage`, `allocation.view`, `document.view/manage/receive/assign`);
  Was-Olah (`work_unit/officer.view`, `document.view/manage/receive/assign`)
  + widget Pemantauan Ruang Pengolahan (manifest belum diterima, siap olah,
  sedang diolah) + filter default manifest ke `PENGOLAHAN_LS/IPDS`;
  penugasan pengolahan boleh dari unit `PENGOLAHAN_LS/IPDS`
  (`PROCESSING_UNIT_CODES`) dengan aksi pengembalian `RETURNED`
  (`ReturnDocumentProcessingAssignment`, audit `returned`);
  PML (`allocation.view/assign`, `dsrt.view/verify`, `document.view/manage`);
  PPL (`allocation.view`, `dsrt.view/manage`, `document.view`); Olah (`document.view`).
  Basic (`dashboard.view`, `profile.manage`) menyertai semua role.
  Super Admin (`super_admin`) memegang 25 izin + `Gate::before` bypass
  (satu-satunya bypass; tanpa bypass email/ID) sehingga akses penuh ke semua
  modul. Manajemen role (create/update/delete + sinkronisasi permission)
  hanya untuk Super Admin: route `admin.roles.*` digerbangi middleware
  `can:super-admin` + `RolePolicy` + cek ulang di controller; role katalog
  (termasuk `super_admin`) tidak dapat direname/dihapus; role terpakai user
  tidak dapat dihapus; hanya Super Admin dapat menetapkan role `super_admin`
  atau mengubah/menghapus akun Super Admin; tak seorang pun boleh mengubah
  role dirinya sendiri. Role hanya di `users` via Spatie.
- Audit Super Admin: middleware `AuditSuperAdminRequests` (group `web`)
  mencatat setiap request mutasi Super Admin sebagai `super_admin_request`
  (method/path/route, tanpa input sensitif) di atas audit bisnis
  `role_created/role_updated/role_deleted` via `AuditLogger`.
- Ownership scoping Fase 2: `User::officer()` (`HasOne` via `officers.user_id`) +
  helper `isOfficer/officerId/hasFullDataScope/activeAssignmentRoles/hasActiveAssignmentRole`
  + konstanta `ROLE_ASSIGNMENT_MAP/FULL_SCOPE_ROLES/SCOPED_ROLES`.
  `AllocationPolicy/DsrtSamplePolicy/DocumentPolicy` (trait `ScopesDataOwnership`)
  membatasi `view/update` non-Admin/Viewer/Operator pada data dengan
  `activeAssignments.officer_id == user.officer.id` (dokumen juga via
  `processingAssignments` aktif); di luar penugasan memakai 403.
  `AllocationTable/DocumentTable` memfilter query otomatis; `DsrtSampleTable` menegakkan
  `Gate view` alokasi induk. `AssignOfficer` menolak petugas berakun dengan Spatie role
  tak setara `assignment_role`. Dashboard menghitung statistik scoped + widget
  tugas aktif; tautan akun↔petugas dikelola di `Admin\UserController`.
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
- Controller Fase 2C-2 (pemutakhiran VSEN.P): `Master\UpdatingManifestController`
  (`/pemutakhiran`: index/show/print/export perlu `document.view`, create/store
  perlu `document.manage`, terima perlu `document.receive`, validasi-entri perlu
  `document.assign`); action `Create/ReceiveUpdatingManifest`,
  `ValidateUpdatingEntry` (lapor `DISCREPANCY` via `ProcessingEntryReport`);
  Livewire `AssignUpdatingProcessorTable` (bulk assign Pengolah, perlu
  `document.assign`); export `UpdatingDocumentDeliveryExport`; BAST print view
  + tanda tangan; tabel `updating_manifest_items` (lihat `docs/database.md`).
- Controller Fase 2C-1: `Master\DocumentController`, `Master\DocumentTypeController`,
  `Master\DocumentLocationController`, `Master\DocumentManifestController`,
  `Master\DocumentTransferController`, `Master\DocumentProcessingAssignmentController`;
  action `Submit/ReceiveDocumentManifest`, `AssignDocumentToProcessingOfficer`
  (transaction + `lockForUpdate`; nomor manifest server-side).
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
- `/dokumen`, `/dokumen/jenis`, `/dokumen/lokasi` (Fase 2C-1: perlu `document.view/manage`; tanpa delete).
- `/manifest`, `/manifest/{manifest}/serah-terima`, `/dokumen/{document}/penugasan` (Fase 2C-1: perlu `document.manage/receive/assign` sesuai aksi; tanpa delete).
- `/monitoring/pelaporan-dokumen` (Fase 2C-2: perlu `document.view`; dashboard konsolidasi 5 laporan + matriks rekonsiliasi + filter SLA/rekonsiliasi + ekspor Excel).
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

## Pelaporan Terintegrasi Fase 2C-2

Terimplementasi: tipe dokumen kanonik, laporan entri pengolahan, reconciliation engine, SLA/deadline, notifikasi keterlambatan, dan dashboard konsolidasi real-time.

- **Tipe dokumen kanonik** (`document_types`, seeder `DocumentTypeSeeder`):
  `P_SUSENAS` (Pemutakhiran SUSENAS), `VSEN_SUSENAS` (Sampel Utama SUSENAS Kor & Konsumsi), `VSERUTI` (Sampel SERUTI sub-sampel).
- **Laporan entri** (`processing_entry_reports`, migration `create_processing_entry_reports_table`):
  3 report_type (`PEMUTAKHIRAN_SUSENAS`, `SAMPEL_SUSENAS`, `SAMPEL_SERUTI`), 5 entry_status (`PENDING/IN_PROGRESS/COMPLETED/RECONCILED/DISCREPANCY`), linkage self-referencing Seruti→Susenas (`parent_entry_report_id`), target/processed/clean/error qty, officer penugas.
- **Service** `ProcessingEntryReportService`: create (validasi tipe dokumen sesuai report_type, cegah duplikat per allocation+report_type, tolak entri Seruti bila induk Susenas belum ada/bersih), start, updateProgress (COMPLETED mensyaratkan processed >= target).
- **Reconciliation engine** `DocumentReconciliationService::reconcileSubmissionVsEntry`:
  L1 vs L3 (NKS belum dientri), L2 vs L4 (gap ruta sampel 10/NKS), L4 vs L5 (Seruti tertaut Susenas bersih). Selisih → status `DISCREPANCY` + `reconciliation_note` + audit `recon_discrepancy`.
- **SLA/deadline** (migration `add_sla_columns_to_survey_periods_table`):
  4 kolom deadline di `survey_periods` (`pemutakhiran_submission_deadline`, `pemutakhiran_entry_deadline`, `sampel_submission_deadline`, `sampel_entry_deadline`). Helper model: `isOverdue()`, `daysRemaining()`, `slaStatus()` (ON_TRACK/WARNING/OVERDUE).
- **Notifikasi** `MonitorReportingDeadlines` (command `reporting:monitor-deadlines`, terjadwal harian):
  H-3 & H+0 submission → Tim Sosial; H-2 & H+0 entri → Tim Pengolahan/IPDS; rekap overdue → Pimpinan. Notifikasi in-app database via `ReportingDeadlineNotification`.
- **Dashboard** `/monitoring/pelaporan-dokumen` (Livewire `ReportingMonitoringDashboard`, perlu `document.view`):
  5 metric summary card, matriks rekonsiliasi per NKS (status L1/L2/L3/L4/L5/recon/SLA), filter periode + status SLA + status rekonsiliasi, tombol ekspor Excel (`ReportingReconciliationExport`).
- **Audit**: setiap create/update/delete laporan entri tercatat via trait `Auditable`; mutasi Super Admin dicatat tambahan via middleware `AuditSuperAdminRequests`.
- Test: `DocumentReportingWorkflowTest` (8 test: create 5 laporan, validasi tipe dokumen, tolak Seruti tanpa induk, SLA on-track vs overdue, ekspor Excel, deteksi DISCREPANCY, RECONCILED bila lengkap).

## 8. Keputusan Arsitektur

- Tetap monolit sampai akhir Fase 2; ekstraksi service bila batch pengolahan berat.
- Semua `code`/NKS/kode wilayah/kode petugas selalu string di DB, validasi, dan payload; dilarang cast ke integer.
- Satu periode aktif per `survey_type + year + period_type + period_number`.
- Satu assignment aktif per `allocation + assignment_role` (transaction + lock; tanpa partial unique).
- Pagination server-side untuk tabel master dan audit.
- Tabel Livewire 3 (`App\Livewire\*\*Table` + `WithPagination` + `#[Url]`): perPage allowlist 10/25/50/100, filter teks (debounce 500ms) + kategori/tanggal/rentang, tombol Reset, info total `Menampilkan X–Y dari Z`, sort allowlist, `overflow-x-auto`, hover + selected row. Opsi filter di-cache 600 detik.
- Ekspor `.xlsx` (maatwebsite/excel 3.1 + phpspreadsheet): GET `*/export` di grup permission `.view`, menghormati filter aktif, tanpa PII (tanpa password/token/NIK/telepon/alamat/notes). Audit-logs TANPA export (read-only).
- Impor `.xlsx/.csv` (maks 5MB): POST `*/import` di grup permission `.manage`, validasi heading + code-string + FK by code, skip duplikat, audit via model events.
- Dependensi: `livewire/livewire ^3.6`, `maatwebsite/excel *` (phpspreadsheet, zipstream). Layout memuat `@livewireStyles/@livewireScripts`; Alpine disediakan Livewire.
- UI shell: AdminLTE 4 (`admin-lte` npm, Bootstrap 5.3 peer, tanpa jQuery) dikompilasi via Vite (`resources/scss/adminlte.scss`: primary indigo `#4f46e5`, sidebar 280px gelap indigo-950). Order CSS: AdminLTE/Bootstrap dulu, lalu Tailwind (`resources/css/app.css`) agar utility Tailwind menang di area konten. JS: `bootstrap` + `admin-lte` (data-api) + `@fortawesome/fontawesome-free` di `resources/js/app.js`. Dilarang menambah jQuery/select2 lama (konflik Alpine/Livewire). Struktur layout: `layouts/app.blade.php` (app-header/app-sidebar/app-main/app-footer) dan `layouts/guest.blade.php` (login-box).
- Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403 — detail di `workflows.md`.
