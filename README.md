# SIMAPAN — Sistem Informasi Manajemen Pengolahan dan Pengawasan

Aplikasi web internal untuk manajemen pengolahan dan pengawasan Susenas–Seruti.

## Stack

- PHP 8.2.30 (`~8.2.0`)
- Laravel 12.x (kompatibel PHP 8.2)
- MySQL 8.0.30, InnoDB, `utf8mb4_unicode_ci`
- Blade + Livewire 3
- Auth session + CSRF, RBAC `spatie/laravel-permission`
- Test: Pest + Factory dummy
- UI: shell admin **AdminLTE 4** (Bootstrap 5, tanpa jQuery) di Vite; Tailwind + Alpine dipertahankan untuk konten di dalam shell; ikon Font Awesome

## Status

Bootstrap selesai: Laravel 12.x + Breeze (Blade + Alpine) + Pest + Pint.
Fase 1A selesai: RBAC Spatie (guard `web`), seeder idempotent, admin users/roles.
Fase 1B-1 selesai: master jenis survei + unit kerja (tanpa delete fisik UI).
Fase 1B-2 selesai: master wilayah hierarkis (tanpa delete fisik UI).
Fase 1B-3 selesai: master periode survei dengan workflow status (tanpa delete fisik UI).
Fase 1B-4 selesai: master petugas + alias (tanpa delete fisik UI).
Fase 1B-5 selesai: audit trail append-only + halaman read-only.
Fase 2A selesai: alokasi kegiatan + penugasan historis (tanpa delete UI).
Fase 2B selesai: DSRT Susenas nested alokasi (tanpa delete UI).
Fase 2C-1 selesai: dokumen fisik + manifest + serah terima + penugasan internal (tanpa delete UI; alur SOSIAL→PENGOLAHAN_LS/IPDS).
Pelaporan terintegrasi: tipe dokumen kanonik (`P_SUSENAS`, `VSEN_SUSENAS`, `VSERUTI`), laporan entri `processing_entry_reports` (5 laporan SUSENAS-SERUTI), reconciliation engine, SLA/deadline + notifikasi keterlambatan, dan dashboard konsolidasi real-time `/monitoring/pelaporan-dokumen` (+ ekspor Excel 5 dokumen).
Belum ada peminjaman/temuan/impor/finalisasi (tanpa Livewire, upload, Excel).

## Instalasi Lokal (Laragon, MySQL 8.0.30)

1. Buat database `simapan_db` (`utf8mb4_unicode_ci`).
2. Salin `.env.example` menjadi `.env` lalu sesuaikan:
   `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
   `DB_DATABASE=simapan_db`, `DB_USERNAME=root`, `DB_PASSWORD=` (jangan commit `.env`).
3. `composer install`
4. `php artisan key:generate`
5. Pastikan database aktif adalah `simapan_db`, lalu `php artisan migrate` (jangan `migrate:fresh` bila bukan `simapan_db`).
6. `npm install && npm run build`
7. `php artisan test`, `./vendor/bin/pint --test`, dan (opsional) E2E Playwright sesuai `docs/e2e-testing.md`
8. Jalankan: via Laragon (VirtualHost ke `public/`) atau `php artisan serve`, lalu buka `/login` dan `/dashboard`.

## Dokumen

- `AGENTS.md` — instruksi permanen agent
- `docs/prd.md` — kebutuhan produk dan role/unit
- `docs/architecture.md` — arsitektur, auth, RBAC, modul
- `docs/database.md` — skema Fase 1 dan aturan kode-string
- `docs/workflows.md` — alur kerja dan commit kecil
- `docs/coding-standards.md` — PSR-12, strict types, konvensi
- `docs/data-classification.md` — klasifikasi data dan larangan data asli
- `docs/e2e-testing.md` — pengujian UI end-to-end Playwright (browser remote) per peran
- `docs/maintenance-log.md` — log perubahan kode hasil analisa/pemeliharaan

## Role

Super Admin, Administrator, PPL/Petugas Lapangan, PML/Pengawas Pendataan Lapangan,
Petugas Pengolahan, Pengawas Pengolahan, Operator Tim Statistik Sosial,
Operator IPDS, Viewer/Pimpinan.

## RBAC Fase 1A

Role sistem-terkelola (slug, guard `web`): `super_admin`, `administrator`,
`field_officer`, `field_supervisor`, `processing_officer`, `processing_supervisor`,
`social_operator`, `ipds_operator`, `viewer`. Label Indonesia hanya di UI
(lihat `config/simapan_roles.php`). Katalog role tidak dapat direname/dihapus;
Super Admin dapat membuat/menghapus role custom serta mengelola permission setiap
role via UI. `super_admin` adalah role tertinggi: hanya Super Admin yang dapat
assign/cabut role `super_admin` dan mengubah akun Super Admin; tidak ada user yang
boleh mengubah role dirinya sendiri.

15 permission kanonik (guard `web`): `dashboard.view`, `profile.manage`,
`admin.user.manage`, `admin.role.manage`, `audit.view`,
`master.work_unit.view/manage`, `master.survey_type.view/manage`,
`master.survey_period.view/manage`, `master.region.view/manage`,
  `master.officer.view/manage`, `allocation.view/manage/assign` (18 total sejak Fase 2A),
  `dsrt.view/manage/verify` (21 total sejak Fase 2B),
  `document.view/manage/receive/assign` (25 total sejak Fase 2C-1).

Matriks: `super_admin` dan `administrator` memegang semua 25; tujuh role lain
hanya `dashboard.view` + `profile.manage` (tanpa akses survei global; tiket
Operator Sos/IPDS menyusul). `Gate::before` hanya mem-bypass untuk `super_admin`
(tanpa bypass email/ID); manajemen role digerbangi ability `super-admin`.
Halaman `/profile` wajib `profile.manage` (fungsi Breeze selain otorisasi tidak diubah).

## Super Admin (tertinggi)

- Semua permission (25) + bypass Gate, sehingga akses penuh ke seluruh modul.
- Satu-satunya peran yang boleh: membuat role, mengubah role (rename/permission),
  menghapus role custom, dan menetapkan role `super_admin` ke akun lain.
- Role katalog (termasuk `super_admin`) tidak dapat dihapus; role terpakai user tidak dapat dihapus.
- Setiap request mutasi (POST/PUT/PATCH/DELETE) Super Admin dicatat ke audit
  (`super_admin_request`) di atas audit bisnis (`role_created/updated/deleted`,
  `role_assigned/removed`, dst.).


Seeder (idempotent, `php artisan db:seed` di local/testing):
`PermissionSeeder` → `RoleSeeder` → `DevelopmentAdminSeeder`.
Admin dummy memakai `.env` lokal (jangan commit `.env`):

- `SIMAPAN_ADMIN_NAME`, `SIMAPAN_ADMIN_EMAIL`, `SIMAPAN_ADMIN_PASSWORD`
- Seeder berhenti dengan error bila password kosong; tidak ada default tersembunyi.
- Tidak membuat admin kedua; tidak mereset password yang ada; hanya local/testing.

Cache: local/produksi awal `database`, testing `array`.
Setelah seed/deploy: `php artisan permission:cache-reset`, lalu `php artisan optimize:clear`.

Perintah: `php artisan db:seed`, `php artisan test`,
`vendor/bin/pint --test`, `npm run build`.

Respons: guest → redirect login; login tanpa permission → 403;
data tidak ditemukan → 404.

Belum di Fase 1A: scope wilayah/NKS, tiket Operator Sos/IPDS, audit log,
master data, DSRT, dokumen, temuan, monitoring, presensi, laporan harian.

## Master Fase 1B-1

Diimplementasikan: `survey_types` dan `work_units` (route `/master/jenis-survei`,
`/master/unit-kerja`; menu Indonesia disembunyikan via `@can`).

- Kode string (`VARCHAR(32)`), unique (DB + validasi), immutable, regex `^[A-Z0-9_-]{1,32}$`.
- Tanpa delete fisik via UI; nonaktifkan sebagai default.
- Survey type standar: `SUSENAS`, `SERUTI` (inactive tidak untuk referensi modul baru).
- Work unit standar (root): `SOSIAL`, `PENGOLAHAN_LS`, `IPDS`.
- Hierarki: larang self-parent dan siklus; parent nonaktif tidak untuk child baru;
  unit ber-child tidak boleh dinonaktifkan sebelum child dipindahkan (reparent);
  FK `parent_id` restrict; update relasi dalam transaction.
- Permission: `master.survey_type.view/manage`, `master.work_unit.view/manage`
  (guest → login; tanpa permission → 403; validasi → 422; tak ditemukan → 404).
- Seeder: `SurveyTypeSeeder`, `WorkUnitSeeder` (idempotent, via `db:seed`).
- Test: `SurveyTypeTest`, `WorkUnitTest`, `MasterSeederTest`, `MasterNavigationTest`.
- Data dummy only; jangan gunakan data survei asli.

## Master Fase 1B-2

Diimplementasikan: `regions` hierarkis sampai
`PROVINSI → KAB_KOTA → KECAMATAN → DESA_KELURAHAN_NAGARI`
(route `/master/wilayah`; menu Indonesia disembunyikan via `@can`).
SLS/Sub-SLS/NKS menyusul di fase alokasi.

- Semua code/`full_code` string; `full_code` unique global, dibentuk server-side
  (`root = code`, `child = parent.full_code + code`); tanpa asumsi panjang digit.
- Unique tambahan `parent_id + level + code`; MySQL membolehkan beberapa NULL
  sehingga `full_code` unique tetap perlindungan utama root.
- Level/parent: hanya 4 level; `PROVINSI` tanpa parent; parent harus level
  tepat di atasnya dan aktif; larang self-parent dan siklus.
- Immutable: `code`, `level`, `full_code`; boleh diubah: `name`, `is_active`;
  parent hanya boleh pindah bila region tanpa child (jaga konsistensi `full_code` descendant).
- Nonaktif ditolak bila masih punya child aktif; tanpa delete fisik UI.
- Seeder dummy: `99` Provinsi Contoh → `9901` Kabupaten Contoh →
  `9901001` Kecamatan Contoh → `9901001001` Desa Contoh (idempotent).
- Permission: `master.region.view/manage`
  (guest → login; tanpa permission → 403; validasi → 422; tak ditemukan → 404).
- Test: `RegionAccessTest`, `RegionHierarchyTest`, `RegionCodeTest`,
  `RegionSeederTest`, `RegionNavigationTest`.

## Master Fase 1B-3

Diimplementasikan: `survey_periods` (route `/master/periode-survei`,
nama route `master.survey_periods.*`; menu Indonesia disembunyikan via `@can`).

- Status: `DRAFT → ACTIVE`, `DRAFT → ARCHIVED`, `ACTIVE → CLOSED`,
  `CLOSED → ARCHIVED`; transisi lain 422; tanpa reopen; `ARCHIVED` final.
- Status awal hanya `DRAFT`/`ARCHIVED`; `ACTIVE` hanya via action aktivasi (POST).
- Immutable: `code`, `survey_type_id`, `period_type`, `period_number`, `year`;
  `name`/`start_date`/`end_date` hanya saat `DRAFT`; `closed_by`/`closed_at`
  diisi server saat `CLOSED`, dikosongkan saat aktivasi.
- Aturan slot: `SEMESTER` 1–2, `TRIWULAN` 1–4, `TAHUNAN` tanpa nomor;
  tahun 2000–2100; `end_date` ≥ `start_date`; tipe survei harus aktif.
- Satu `ACTIVE` per `survey_type + year + period_type + period_number`:
  tanpa partial unique di MySQL 8 → action + transaction `lockForUpdate`;
  susenas dan seruti boleh aktif bersamaan.
- Seeder dummy: `SUSENAS-S1-2099`, `SERUTI-T1-2099` (keduanya `DRAFT`;
  tanpa periode aktif; idempotent).
- Permission: `master.survey_period.view/manage`
  (guest → login; tanpa permission → 403; validasi/transisi → 422; tak ditemukan → 404).
- Test: `SurveyPeriodAccessTest`, `SurveyPeriodValidationTest`,
  `SurveyPeriodImmutableTest`, `SurveyPeriodTransitionTest`,
  `SurveyPeriodActiveTest`, `SurveyPeriodClosedTest`,
  `SurveyPeriodSeederTest`, `SurveyPeriodNavigationTest`.

## Master Fase 1B-4

Diimplementasikan: `officers` + `officer_aliases` sebagai identitas operasional
(route `/master/petugas`, alias nested; nama route `master.officers.*`;
menu Indonesia disembunyikan via `@can`). Beda dengan `users` (akun login +
role Spatie) dan penugasan periode (menyusul).

- Kode string (`VARCHAR(32)`), unique global (DB + validasi), immutable,
  regex `^[A-Za-z0-9_-]{1,32}$`.
- Tanpa `role_id`/`application_role_id`; role aplikasi tetap di `users`.
- Unit kerja wajib aktif; user opsional dan maksimal satu officer
  (unique DB; NULL ganda diizinkan MySQL, validasi aplikasi wajib;
  tautan dipertahankan record soft-deleted sebagai proteksi data).
- Status `ACTIVE`/`INACTIVE`; tanggal aktif valid; UI normal memakai `INACTIVE`,
  soft delete hanya proteksi internal; tanpa delete UI.
- Normalisasi server-side (lowercase, trim, spasi tunggal): `normalized_name`
  dihitung ulang saat simpan (index pencarian); nama boleh sama, kode unik.
- Alias: unik per `(officer_id, normalized_alias)`; alias boleh dipakai
  petugas lain (warning lintas-petugas); alias ≠ nama utama sendiri;
  `created_by` dari user login (null untuk seeder); tanpa delete UI.
- Phone/email sensitif: tak penuh di daftar; masking di detail
  (penuh hanya `master.officer.manage`); tak ada nilai nyata di seed/test/log.
- Seeder dummy: `OFF-001/002/003` + alias `P. Contoh Satu`, `Petugas Contoh II`.
- Permission: `master.officer.view/manage`
  (guest → login; tanpa permission → 403; validasi → 422; tak ditemukan → 404).
- Test: `OfficerAccessTest`, `OfficerValidationTest`,
  `OfficerStatusAndSoftDeleteTest`, `OfficerAliasTest`, `OfficerSeederTest`.
- Audit log menyusul fase terpisah; alokasi/DSRT/dokumen/temuan/monitoring/
  presensi/laporan belum dibuat.

## Audit Trail Fase 1B-5

Diimplementasikan: `audit_logs` append-only (tanpa `updated_at`/`deleted_at`)
+ halaman read-only `/audit-logs` (perlu `audit.view`; tanpa edit/hapus/export).

- Satu pintu: `AuditLogger`; sanitasi terpusat `AuditSanitizer`
  (`[REDACTED]`, case-insensitive, nested); trait `Auditable` + `AuditObserver`.
- Diaudit: User, SurveyType, WorkUnit, Region, SurveyPeriod, Officer,
  OfficerAlias + auth (login/logout/gagal) + role assign/remove + action periode.
- Tak disimpan: password, token, secret, NIK, phone, email, alamat, dokumen,
  file, private path, payload request, session/cookie, header auth.
- Konteks: `event_uuid`, pelaku (null untuk sistem/gagal login), nama route,
  IP, user agent (≤1024). Metadata aman: slug role, status before/after,
  sumber perubahan, hasil autentikasi.
- Perubahan bisnis + audit satu transaction (gagal audit menggagalkan aksi).
- Seeder tidak diaudit (strategi konsisten, diuji `AuditSeederTest`).
- Keterbatasan: bulk/raw query melewati observer — dilarang untuk model
  ter-audit tanpa logging eksplisit; guard model menolak update/delete
  (raw query tetap bisa — hanya via akses DB langsung, di luar aplikasi).
- Test: `AuditAccessTest`, `AuditSanitizerTest`, `AuditModelEventTest`,
  `AuditSpecialActionTest`, `AuditAppendOnlyTest`, `AuditSeederTest`.
- Alokasi/DSRT/dokumen/temuan/monitoring/presensi/laporan belum dibuat.

Dilarang memasukkan data survei asli atau data sensitif (NIK, telepon,
alamat, kredensial, token, dokumen asli) ke repository, test, maupun prompt AI.

## Alokasi Fase 2A

Diimplementasikan: `allocations` + `assignments` historis, CRUD manual
(route `/alokasi`, nama route `allocations.*`; menu Indonesia via `@can`).
Impor CSV/XLSX ditunda.

- SLS/Sub-SLS melekat pada alokasi, bukan master wilayah; jenis survei
  diturunkan dari periode (tanpa `survey_type_id` di alokasi).
- NKS string, unique per periode, immutable; SLS/Sub-SLS string immutable.
- Desa wajib aktif level `DESA_KELURAHAN_NAGARI`.
- Status: `DRAFT→ACTIVE`, `DRAFT→ARCHIVED`, `ACTIVE→SUSPENDED/COMPLETED`,
  `SUSPENDED→ACTIVE/ARCHIVED`, `COMPLETED→ARCHIVED`; lainnya 422; tanpa reopen.
- Aktivasi mensyaratkan periode `ACTIVE`, desa valid, dan 4 assignment aktif
  (`FIELD_OFFICER`, `FIELD_SUPERVISOR`, `PROCESSING_OFFICER`, `PROCESSING_SUPERVISOR`).
- Assignment historis: satu aktif per `(allocation, role)` via transaction +
  `lockForUpdate`; reassign menutup lama (`ended_at` server) tanpa hapus riwayat;
  `ORGANIK`/`MITRA`/null pada assignment; petugas sesuai unit
  (lapangan→SOSIAL, pengolahan→PENGOLAHAN_LS); kelola hanya saat
  `DRAFT/ACTIVE/SUSPENDED`.
- Edit data dasar hanya `DRAFT`/`ACTIVE` (nama/catatan); tanpa delete UI.
- Audit: `created/updated` observer + action `activated/suspended/resumed/
  completed/archived/assigned/reassigned/unassigned` (satu event per operasi,
  satu transaction dengan bisnis).
- Seeder dummy: `NKS-2099-001/002` (DRAFT; tanpa assignment aktif) + factory.
- Permission: `allocation.view/manage/assign`
  (guest → login; tanpa permission → 403; validasi/transisi → 422; tak ditemukan → 404).
- Test: `AllocationAccessTest`, `AllocationValidationTest`,
  `AllocationStatusTest`, `AssignmentHistoryTest`, `AssignmentAccessTest`,
  `AllocationAuditTest`, `AllocationSeederTest`.

## DSRT Fase 2B

Diimplementasikan: `dsrt_samples` nested alokasi Susenas
(route `/alokasi/{allocation}/dsrt`, nama route `allocations.dsrt.*`;
akses via detail alokasi, tanpa menu global; tanpa delete UI).
Impor CSV/XLSX, dokumen, temuan, cek DSRT, finalisasi belum dibuat.

- Identitas: `allocation` + `nus` + `nurt`; NUS/NURT unique per alokasi;
  semua nomor string (nol depan aman), immutable setelah create.
- Hanya alokasi Susenas (`surveyType.code = SUSENAS`); Seruti konsisten 422,
  mismatch nested 404.
- Lifecycle: `DRAFT → VERIFIED`, `DRAFT → ARCHIVED`, `VERIFIED → ARCHIVED`;
  lainnya 422; `ARCHIVED` final; tanpa reopen.
- `VERIFIED` = review administrasi siap rujukan lanjut (bukan finalisasi hasil).
- Ubah hanya saat `DRAFT` + alokasi `DRAFT/ACTIVE/SUSPENDED`;
  `verified_by/at`, `archived_by/at` server-side.
- Verify mensyaratkan NUS/NURT/KRT/enumerasi valid (+ notes untuk status khusus).
- Sensitif: `address`, `contact_person`, `contact_phone` — masking di daftar
  (telepon `••••` + 4 digit), penuh hanya `dsrt.manage` di detail;
  tak pernah di audit values, error, flash, seed, test.
- Form tak merepopulasi kontak/alamat setelah error validasi.
- Seeder dummy: 3 baris `NURT-001/002/003` DRAFT tanpa kontak (idempotent).
- Permission: `dsrt.view/manage/verify` (21 total)
  (guest → login; tanpa permission → 403; validasi/transisi → 422; tak ditemukan → 404).
- Test: `DsrtAccessTest`, `DsrtSusenasGateTest`, `DsrtValidationTest`,
  `DsrtStatusTest`, `DsrtMaskingTest`, `DsrtAuditTest`,
  `DsrtSeederTest`, `DsrtNavigationTest`.

## Dokumen Fase 2C-1

Diimplementasikan: dokumen fisik + manifest + serah terima + riwayat pemegang
+ penugasan internal (route `/dokumen`, `/manifest`; tanpa delete UI).
Tanpa peminjaman, upload, QR, OCR, notifikasi, arsip/musnah, temuan.

- Tepat-satu-konteks per dokumen (`allocation` XOR `dsrt_sample` — FormRequest +
  CHECK MySQL); format hanya `PHYSICAL`; tanpa file/isi/PII.
- Nomor manifest server-side `DM-YYYYMMDD-###` (transaction + lock + retry,
  unique sebagai jaring akhir); hanya SOSIAL → PENGOLAHAN_LS; maks 200 item.
- Submit mengunci item, membuat transfer snapshot, holder tetap SOSIAL.
- Terima per item (qty/kondisi/status + note bila non-COMPLETE);
  hasil REJECTED > PARTIAL > NOTED > COMPLETE; holder pindah hanya yang diterima.
- Satu pemegang aktif (unique DB + lock + validasi); riwayat append-only.
- Penugasan internal: dokumen `RECEIVED` + holder PENGOLAHAN_LS + officer
  ACTIVE unit PENGOLAHAN_LS; satu ACTIVE per dokumen; tanpa fitur kembali.
- Seeder dummy: `KUESIONER/DAFTAR_SAMPEL/BERITA_ACARA`,
  `LEMARI-CONTOH-A1/RUANG-ARSIP-CONTOH`, `DOC-2099-001/002`,
  manifest `DM-20990101-001` DRAFT (idempotent, tanpa audit).
- Permission: `document.view/manage/receive/assign` (25 total)
  (guest → login; tanpa permission → 403; validasi/transisi → 422; tak ditemukan → 404).
- Test: `DocumentAccessTest`, `DocumentValidationTest`,
  `ManifestWorkflowTest`, `TransferReceiptTest`, `HolderActiveTest`,
  `AssignInternalTest`, `DocumentAuditTest`, `DocumentSeederTest`,
  `DocumentNavigationTest`.

## Unit

Tim Statistik Sosial, Tim Pengolahan dan Layanan Statistik, Tim IPDS.

## Aturan Data Keras

- Semua `code`/NKS/kode wilayah/kode petugas adalah string (`VARCHAR`) di DB, validasi, dan payload; dilarang cast ke integer.
- Fase 1 mencakup `survey_types`; satu periode aktif per `survey_type + year + period_type + period_number`.
- `regions.code` bukan unique global; unik via `full_code` dan `parent_id + level + code`.
- `officers` memakai `normalized_name`, `softDeletes`, tanpa `application_role_id` (role di `users` via Spatie).
- `audit_logs` append-only, tanpa `updated_at`; tanpa password, token, NIK, telepon, alamat lengkap, atau PII/sensitive lain.
- Akses di luar wewenang Fase 1 dan Fase 2 awal memakai 403. Operator Sosial/IPDS parsial berbasis tiket/penugasan.
- Semua test memakai Pest + Factory dummy. Dilarang data survei asli, NIK/telepon/alamat asli, kredensial, token, dokumen asli.
- Migration append-only. Conventional Commits.

## Mulai (nanti, bukan sekarang)

1. `composer create-project laravel/laravel` versi kompatibel PHP 8.2.
2. Konfigurasi `.env` MySQL 8.0.30 lokal (jangan commit).
3. Ikuti `docs/workflows.md` untuk urutan Fase 1.
