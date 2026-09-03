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

### Spatie RBAC

`roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions`
dari publish `spatie/laravel-permission`. Jangan memodifikasi manual selain migration vendor.

### work_units (unit kerja)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | kode unit, immutable |
| name | VARCHAR(255) | |
| parent_id | BIGINT U NULL FK>work_units.restrict | hierarki unit |
| is_active | BOOL default true | |
| timestamps | | |

Index: `parent_id`, `is_active`.

### survey_types (master tipe survei, Fase 1)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | kode tipe string, immutable (misal `SSN`, `SRT` dummy) |
| name | VARCHAR(255) | |
| is_active | BOOL default true | |
| timestamps | | |

### survey_periods (master periode survei)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| survey_type_id | BIGINT U FK>survey_types.restrict | |
| code | VARCHAR(32) UNIQUE | misal `SSN-2026-01` dummy (string) |
| name | VARCHAR(255) | |
| year | SMALLINT U | string-like year? simpan integer tahun, tapi semua kode tetap string |
| period_type | VARCHAR(32) | misal `triwulan`, `semester` |
| period_number | SMALLINT U | nomor periode dalam tipe |
| start_date | DATE | |
| end_date | DATE `end > start` | |
| status | ENUM `draft,active,closed` default draft | |
| created_by | BIGINT U FK>users.restrict | |
| timestamps | | |

Aturan: satu `active` per kombinasi `survey_type_id + year + period_type + period_number` (cek aplikasi + unique index kondisional).
Index: `survey_type_id`, `year`, `period_type`, `period_number`, `status`.

### regions (master wilayah)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) | kode BPS sebagai string; bukan unique global |
| full_code | VARCHAR(64) UNIQUE | kode penuh path string, misal `35.09.01.001` dummy |
| name | VARCHAR(255) | |
| level | ENUM `kab,kec,desa` | |
| parent_id | BIGINT U NULL FK>regions.restrict | kab NULL, kec>kab, desa>kec |
| is_active | BOOL default true | |
| timestamps | | |

Keunikan: `full_code UNIQUE` dan kombinasi `parent_id + level + code` UNIQUE (tangani `parent_id NULL` dengan `COALESCE`/kolom bantu bila perlu karena NULL tidak ikut unique MySQL).
Index: `parent_id`, `level`, `is_active`, `code`.

### officers (master petugas)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| code | VARCHAR(32) UNIQUE | kode petugas string, immutable |
| name | VARCHAR(255) | nama samaran dummy di test |
| normalized_name | VARCHAR(255) | `mb_strtolower(trim())` untuk pencarian |
| work_unit_id | BIGINT U FK>work_units.restrict | |
| user_id | BIGINT U NULL FK>users.nullOnDelete | login opsional; tanpa `application_role_id` |
| status | ENUM `active,inactive` default active | |
| timestamps | | |
| deleted_at | TIMESTAMP NULL | `softDeletes` wajib |

Index: `code`, `work_unit_id`, `status`, `normalized_name`, `name` (prefix).

### officer_aliases (alias nama petugas)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| officer_id | BIGINT U FK>officers.cascade | |
| alias_name | VARCHAR(255) | varian penulisan nama |
| normalized_alias | VARCHAR(255) | `mb_strtolower(trim())` |
| created_by | BIGINT U FK>users.restrict | |
| timestamps | | |

Unique: `(officer_id, normalized_alias)`. Index: `normalized_alias`.

### audit_logs (append-only)

| Kolom | Tipe | Keterangan |
|---|---|---|
| id | BIGINT U PK | |
| user_id | BIGINT U NULL FK>users.restrict | actor, NULL untuk sistem |
| action | VARCHAR(32) | `create,update,delete,login,role-assign` |
| auditable_type | VARCHAR(255) | morph class |
| auditable_id | BIGINT U | morph id |
| old_values | JSON NULL | |
| new_values | JSON NULL | |
| ip_address | VARCHAR(45) NULL | |
| user_agent | TEXT NULL | |
| created_at | TIMESTAMP | tanpa `updated_at`; tabel tidak mempunyai `updated_at` |

Index: `(auditable_type, auditable_id)`, `user_id`, `created_at`, `action`.
Larangan isi: password, token apa pun, NIK, telepon, alamat lengkap, dan PII/sensitive fields lain (lihat `data-classification.md`); filter sebelum tulis.
Aplikasi hanya `INSERT`/`SELECT`.

## 3. Relasi

- `WorkUnit 1-N anak WorkUnit`; `WorkUnit 1-N Officer`.
- `SurveyType 1-N SurveyPeriod`.
- `Region 1-N anak Region` (level terkontrol).
- `Officer N-1 WorkUnit/User`; `Officer 1-N OfficerAlias` (cascade); `Officer` memakai `softDeletes` + `normalized_name`.
- `User N-N Role N-N Permission` (Spatie); role teknis hanya di `users`, bukan di `officers`.
- `AuditLog N-1 User`; `AuditLog morphTo` master + user; tanpa `updated_at`.

## 4. Migration

- Satu migration per tabel di atas + publish Spatie, berurutan dependensi:
  `work_units → survey_types → survey_periods → regions → officers → officer_aliases → audit_logs`.
- Append-only: perubahan memakai migration baru; larang edit migration merged/jalan.
- FK memakai `restrict` kecuali dinyatakan (`officers.user_id nullOnDelete`, `officer_aliases cascade`).
- Contoh nama: `2026_01_01_000001_create_work_units_table.php` (tanggal nyata saat implementasi).

## 5. Seed dan Test Data

Hanya Factory dummy (`PTG-001`, `KEC-001`). Dilarang NIK/telepon/alamat asli,
kredensial, token, dokumen asli. Seeder prod hanya role + admin awal (password via env/setup aman).
