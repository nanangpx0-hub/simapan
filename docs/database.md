# Database — SIMAPAN Fase 1

Target: MySQL 8.0.30, InnoDB, `utf8mb4_unicode_ci`.
Konvensi: tabel `snake_case` plural, kolom `snake_case`, PK `id BIGINT UNSIGNED` auto-increment,
FK eksplisit, `timestamps()` kecuali `audit_logs` hanya `created_at`.

## 1. Aturan Kode-String (wajib)

Semua `code`/NKS/kode wilayah/kode petugas = `VARCHAR` di DB, validasi, dan payload; dilarang cast ke integer:

- menjaga leading zero (`001`, `3509`, `NKS-001`);
- format BPS stabil (`kode_kec`, `kode_desa`, `kode_petugas`);
- perbandingan dan join konsisten sebagai string di semua layer.

Validasi: `required|string|max:32`, regex per entitas (misal `^[A-Z0-9][A-Z0-9\-\.]*$`),
normalisasi `trim`; untuk alias/pencarian tambah kolom `normalized_*` (lowercase+trim).
Keunikan memakai unique index case-insensitive sesuai collation + cek aplikasi.

## 2. Tabel Fase 1

### Laravel bawaan

`users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` mengikuti default Laravel.
Fase 1A menambah `users.is_active` (`BOOL` default true, migration RBAC kecil, reversible);
login menolak user nonaktif dengan pesan umum.

### Spatie RBAC

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
dari publish `spatie/laravel-permission`. Jangan memodifikasi manual selain migration vendor.

### work_units (unit kerja) — terimplementasi Fase 1B-1

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | kode unit string, immutable |
| name | VARCHAR(150) | |
| parent_id | BIGINT U NULL FK>work_units.restrict | hierarki unit; larang self-parent/siklus |
| is_active | BOOL default true | unit ber-child tidak boleh dinonaktifkan sebelum child dipindah |
| timestamps | | |

Seeder standar (root): `SOSIAL`, `PENGOLAHAN_LS`, `IPDS`. Tanpa delete fisik UI.
Index: `parent_id`, `is_active`.

### survey_types (master tipe survei) — terimplementasi Fase 1B-1

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | kode tipe string, immutable |
| name | VARCHAR(150) | |
| description | TEXT NULL | |
| is_active | BOOL default true | inactive tidak untuk referensi modul baru |
| timestamps | | |

Seeder standar: `SUSENAS`, `SERUTI`. Tanpa delete fisik UI; nonaktifkan sebagai default.

### survey_periods (master periode survei) — terimplementasi Fase 1B-3

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | string, immutable |
| survey_type_id | BIGINT U FK>survey_types.restrict | wajib aktif saat create |
| name | VARCHAR(150) | boleh diubah hanya saat `DRAFT` |
| period_type | VARCHAR(20) | `SEMESTER`, `TRIWULAN`, `TAHUNAN`; immutable |
| period_number | TINYINT U NULL | `SEMESTER` 1–2, `TRIWULAN` 1–4, `TAHUNAN` null; immutable |
| year | SMALLINT U | 2000–2100; immutable |
| start_date | DATE | boleh diubah hanya saat `DRAFT` |
| end_date | DATE | `>= start_date`; boleh diubah hanya saat `DRAFT` |
| status | VARCHAR(20) default `DRAFT` | `DRAFT/ACTIVE/CLOSED/ARCHIVED`; via action, bukan update bebas |
| created_by | BIGINT U FK>users.restrict | |
| closed_by | BIGINT U NULL FK>users.nullOnDelete | diisi saat `CLOSED` |
| closed_at | DATETIME NULL | waktu server saat `CLOSED` |
| timestamps | | |

Transisi: `DRAFT→ACTIVE`, `DRAFT→ARCHIVED`, `ACTIVE→CLOSED`, `CLOSED→ARCHIVED`;
lainnya 422; tanpa reopen; `ARCHIVED` final. Status awal hanya `DRAFT`/`ARCHIVED`.
Satu `ACTIVE` per `survey_type + year + period_type + period_number`:
tanpa partial unique di MySQL 8 → action + transaction `lockForUpdate`.
Seeder dummy: `SUSENAS-S1-2099`, `SERUTI-T1-2099` (keduanya `DRAFT`).
Tanpa delete fisik UI.
Index: `survey_type_id`, `year`, `period_type`, `period_number`, `status`.

### regions (master wilayah hierarkis) — terimplementasi Fase 1B-2

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| parent_id | BIGINT U NULL FK>regions.restrict | hierarki; larang self-parent/siklus |
| level | VARCHAR(40) | `PROVINSI`, `KAB_KOTA`, `KECAMATAN`, `DESA_KELURAHAN_NAGARI` |
| code | VARCHAR(32) | string; bukan unique global; immutable |
| full_code | VARCHAR(128) UNIQUE | string server-side; `root = code`, `child = parent.full_code + code`; immutable |
| name | VARCHAR(150) | boleh diubah |
| is_active | BOOL default true | tolak nonaktif bila masih punya child aktif |
| timestamps | | |

Aturan parent: `PROVINSI` tanpa parent; `KAB_KOTA`→`PROVINSI`;
`KECAMATAN`→`KAB_KOTA`; `DESA_KELURAHAN_NAGARI`→`KECAMATAN`;
parent harus aktif; pindah parent hanya bila tanpa child (jaga `full_code` descendant).
Tanpa delete fisik UI. SLS/Sub-SLS menyusul fase alokasi.
Seeder dummy: `99` → `9901` → `9901001` → `9901001001`.

Keunikan: `full_code UNIQUE` dan kombinasi `parent_id + level + code` UNIQUE.
MySQL membolehkan beberapa NULL pada unique index sehingga `full_code` unique
tetap menjadi perlindungan utama root region.
Index: `parent_id`, `level`, `is_active`, `parent_id + level + code`.

### officers (master petugas) — terimplementasi Fase 1B-4

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | string, unique global, immutable |
| name | VARCHAR(150) | boleh sama antar petugas; unik utama tetap `code` |
| normalized_name | VARCHAR(180), index | server-side (lowercase, trim, spasi tunggal); prohibited di request |
| work_unit_id | BIGINT U FK>work_units.restrict | wajib unit aktif |
| user_id | BIGINT U NULL UNIQUE FK>users.nullOnDelete | opsional; maksimal satu officer (tautan record soft-deleted dipertahankan) |
| phone | VARCHAR(30) NULL | sensitif; masking di UI |
| email | VARCHAR(150) NULL | sensitif; masking di UI |
| status | VARCHAR(20) default `ACTIVE` | `ACTIVE`/`INACTIVE`; UI normal memakai `INACTIVE` |
| active_from | DATE NULL | |
| active_until | DATE NULL | `>= active_from` bila keduanya diisi |
| timestamps | | |
| deleted_at | TIMESTAMP NULL | `softDeletes` proteksi internal; tanpa delete UI |

Tanpa `role_id`/`application_role_id`. Seeder dummy: `OFF-001/002/003`.
Index: `code`, `work_unit_id`, `status`, `normalized_name`, `user_id` (unique nullable;
MySQL membolehkan beberapa NULL, validasi aplikasi wajib).

### officer_aliases (alias nama petugas) — terimplementasi Fase 1B-4

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| officer_id | BIGINT U FK>officers.cascade | cascade hanya untuk purge DBA; soft-delete officer tidak menghapus fisik |
| alias_name | VARCHAR(150) | wajib |
| normalized_alias | VARCHAR(180) | server-side; prohibited di request |
| created_by | BIGINT U NULL FK>users.nullOnDelete | user login di UI; null untuk seeder |
| timestamps | | |

Unique: `(officer_id, normalized_alias)`; index `normalized_alias`.
Alias boleh dipakai petugas lain (warning lintas-petugas); alias ≠ nama utama sendiri.
Tanpa delete UI; tanpa soft delete (didokumentasikan: koreksi via edit; riwayat
perubahan menunggu modul audit log). Seeder dummy: `P. Contoh Satu`, `Petugas Contoh II`.

### audit_logs (append-only) — terimplementasi Fase 1B-5

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| event_uuid | CHAR(36), index | UUID per event (sama untuk aksi terkait, misal sync role) |
| user_id | BIGINT U NULL FK>users.nullOnDelete | pelaku; NULL untuk sistem/gagal login; log bertahan bila user dihapus |
| action | VARCHAR(50) | misal `created/updated/deleted/activated/deactivated/parent_changed/user_linked/closed/archived/role_assigned/login_succeeded/login_failed/logout` |
| auditable_type | VARCHAR(255) | morph class |
| auditable_id | BIGINT U | morph id |
| old_values | JSON NULL | tersanitasi; hanya field berubah |
| new_values | JSON NULL | tersanitasi; hanya field berubah |
| metadata | JSON NULL | tersanitasi; misal `role_slug`, `status_before/after`, `change_source` |
| route_name | VARCHAR(150) NULL | nama route, bukan URI mentah |
| ip_address | VARCHAR(45) NULL | |
| user_agent | VARCHAR(1024) NULL | dibatasi 1024, di-escape di UI |
| created_at | TIMESTAMP | tanpa `updated_at`; tabel tidak mempunyai `updated_at`/`deleted_at` |

Index: `event_uuid`, `(user_id, created_at)`, `(auditable_type, auditable_id)`, `(action, created_at)`.
Larangan isi: password, token apa pun, NIK, telepon, alamat lengkap, dan PII/sensitive fields lain (lihat `data-classification.md`); filter sebelum tulis.
Aplikasi hanya `INSERT`/`SELECT`.

### allocations (alokasi kegiatan) — terimplementasi Fase 2A

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| survey_period_id | BIGINT U FK>survey_periods.restrict | jenis survei diturunkan dari periode |
| village_region_id | BIGINT U FK>regions.restrict | wajib desa aktif level `DESA_KELURAHAN_NAGARI` |
| nks | VARCHAR(32) | string, immutable; unique per periode |
| sls_code | VARCHAR(32) NULL | string, immutable |
| sub_sls_code | VARCHAR(32) NULL | string, immutable |
| sls_name | VARCHAR(255) | boleh diubah saat `DRAFT`/`ACTIVE` |
| status | VARCHAR(20) default `DRAFT` | `DRAFT/ACTIVE/SUSPENDED/COMPLETED/ARCHIVED`; via action |
| notes | TEXT NULL | boleh diubah saat `DRAFT`/`ACTIVE` |
| created_by | BIGINT U FK>users.restrict | |
| timestamps | | |

SLS/Sub-SLS melekat pada alokasi, bukan master wilayah. Transisi:
`DRAFT→ACTIVE/ARCHIVED`, `ACTIVE→SUSPENDED/COMPLETED`,
`SUSPENDED→ACTIVE/ARCHIVED`, `COMPLETED→ARCHIVED`; tanpa reopen.
Aktivasi mensyaratkan periode `ACTIVE`, desa valid, 4 assignment aktif.
Tanpa delete UI. Seeder dummy: `NKS-2099-001/002` (DRAFT).
Unique: `(survey_period_id, nks)`.
Index: `(survey_period_id, status)`, `village_region_id`, `nks`, `status`.

### assignments (penugasan historis) — terimplementasi Fase 2A

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| allocation_id | BIGINT U FK>allocations.cascade | cascade hanya purge DBA; tanpa delete UI |
| officer_id | BIGINT U FK>officers.restrict | wajib petugas aktif |
| assignment_role | VARCHAR(30) | `FIELD_OFFICER/FIELD_SUPERVISOR/PROCESSING_OFFICER/PROCESSING_SUPERVISOR` |
| employment_category | VARCHAR(20) NULL | `ORGANIK`/`MITRA`/null |
| is_active | BOOL default true | satu aktif per `(allocation, role)` via transaction + lock |
| started_at | DATETIME | waktu server |
| ended_at | DATETIME NULL | waktu server saat dinonaktifkan |
| assigned_by | BIGINT U FK>users.restrict | pelaku penugasan |
| timestamps | | |

Reassign menutup lama + membuat baru tanpa hapus riwayat; kelola hanya saat
`DRAFT/ACTIVE/SUSPENDED`. Unit petugas: lapangan→`SOSIAL`, pengolahan→`PENGOLAHAN_LS`.
Index: `allocation_id`, `officer_id`, `assignment_role`, `is_active`.

### dsrt_samples (sampel rumah tangga Susenas) — terimplementasi Fase 2B

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| allocation_id | BIGINT U FK>allocations.restrict | nested; hanya alokasi Susenas |
| nus | VARCHAR(32) | string, immutable; unique per alokasi |
| nurt | VARCHAR(32) | string, immutable; unique per alokasi |
| family_number | VARCHAR(32) NULL | string |
| building_number | VARCHAR(32) NULL | string |
| household_number | VARCHAR(32) NULL | string |
| krt_name | VARCHAR(255) | wajib |
| address | TEXT NULL | sensitif; masking; tak masuk audit |
| krt_education_code | VARCHAR(32) NULL | string |
| enumeration_status | VARCHAR(30) default `PENDING` | 12 nilai enum |
| contact_person | VARCHAR(150) NULL | sensitif; masking; tak masuk audit |
| contact_phone | VARCHAR(30) NULL | sensitif; masking; tak masuk audit |
| notes | TEXT NULL | wajib untuk status khusus; tak masuk audit |
| record_status | VARCHAR(20) default `DRAFT` | `DRAFT/VERIFIED/ARCHIVED`; via action |
| created_by | BIGINT U FK>users.restrict | |
| verified_by | BIGINT U NULL FK>users.nullOnDelete | server-side |
| verified_at | DATETIME NULL | server-side |
| archived_by | BIGINT U NULL FK>users.nullOnDelete | server-side |
| archived_at | DATETIME NULL | server-side |
| timestamps | | |

`VERIFIED` = review administrasi siap rujukan (bukan finalisasi hasil).
Transisi: `DRAFT→VERIFIED/ARCHIVED`, `VERIFIED→ARCHIVED`; tanpa reopen.
Ubah hanya saat `DRAFT` + alokasi `DRAFT/ACTIVE/SUSPENDED`. Tanpa delete UI.
Seeder dummy: `NURT-001/002/003` DRAFT tanpa kontak.
Unique: `(allocation_id, nus)`, `(allocation_id, nurt)`.
Index: `(allocation_id, record_status)`, `enumeration_status`, `krt_name`.

## 3. Relasi

- `WorkUnit 1-N anak WorkUnit`; `WorkUnit 1-N Officer`.
- `SurveyType 1-N SurveyPeriod`.
- `Region 1-N anak Region` (level terkontrol).
- `Officer N-1 WorkUnit/User`; `Officer 1-N OfficerAlias` (cascade); `Officer` memakai `softDeletes` + `normalized_name`.
- `User N-N Role N-N Permission` (Spatie); role teknis hanya di `users`, bukan di `officers`.
- `AuditLog N-1 User`; `AuditLog morphTo` master + user; tanpa `updated_at`.
- Fase 2A: `SurveyPeriod 1-N Allocation`; `Region 1-N Allocation` (desa);
  `Allocation 1-N Assignment` (histori); `Officer 1-N Assignment`;
  `User 1-N Allocation/Assignment` (creator/assigner).
- Fase 2B: `Allocation 1-N DsrtSample`; `User 1-N DsrtSample` (creator/verifier/archiver).

## 4. Migration

- Satu migration per tabel di atas + publish Spatie, berurutan dependensi:
  `work_units → survey_types → survey_periods → regions → officers → officer_aliases → audit_logs`.
- Fase 2A menambah: `allocations → assignments` (setelah master).
- Fase 2B menambah: `dsrt_samples` (setelah alokasi).
- Append-only: perubahan memakai migration baru; larang edit migration merged/jalan.
- FK memakai `restrict` kecuali dinyatakan (`officers.user_id nullOnDelete`, `officer_aliases cascade`).
- Contoh nama: `2026_01_01_000001_create_work_units_table.php` (tanggal nyata saat implementasi).

## 5. Seed dan Test Data

Hanya Factory dummy (`PTG-001`, `KEC-001`). Dilarang NIK/telepon/alamat asli,
kredensial, token, dokumen asli. Seeder prod hanya role + admin awal (password via env/setup aman).
