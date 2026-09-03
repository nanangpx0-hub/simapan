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
| Master Unit | `work_units` tree |
| Master Survei | `survey_types` + `survey_periods` (`survey_type_id`, `period_type`, `period_number`) |
| Master Wilayah | `regions` (`code` non-unique global, `full_code` unique) |
| Master Petugas | `officers` (`normalized_name`, `softDeletes`) + `officer_aliases` |
| Audit | `audit_logs` append-only tanpa `updated_at` via Observer/Trait |

Fase lanjut (di luar Fase 1): penugasan, batch pengolahan, verifikasi, dashboard, impor.

## 4. Auth dan RBAC

- Session driver database/file; cookie `HttpOnly`, `SameSite=Lax`; CSRF di semua mutasi.
- Permission granular contoh:
  `master.unit.view/manage`, `master.periode.view/manage`,
  `master.wilayah.view/manage`, `master.petugas.view/manage`,
  `admin.user.manage`, `audit.view`, `audit.view.summary`.
- Pemetaan awal:
  Administrator semua izin; Operator Sos/IPDS hanya parsial berbasis tiket/penugasan
  (misal `ticket:SHOW-123` membatasi unit/wilayah yang boleh dibuka);
  PPL/PML/Olah/Was-Olah/Viewer akses baca master relevan di Fase 1, aksi penuh menyusul Fase 2.
- Policy per model (`WorkUnitPolicy` dst.) + `can:` di route + `@can` di Blade.
  Jangan mengandalkan kolom `role` string di client. `officers` tidak menyimpan role; role di `users`.

## 5. Komponen Aplikasi

- Model: `User`, `WorkUnit`, `SurveyType`, `SurveyPeriod`, `Region`, `Officer`, `OfficerAlias`, `AuditLog`.
- Observer/Trait `Auditable`: `created/updated/deleted` + event login → tulis audit tanpa PII/sensitive; filter field sebelum tulis.
- Livewire: `Master/*Table`, `Master/*Form`, `Admin/UserCrud`, `Admin/RoleCrud`,
  `Master/OfficerAliasManager`, `Audit/AuditLogTable` (read-only).
- FormRequest per entitas untuk validasi kode-string dan aturan hierarki.
- Factory + Seeder dummy per entitas untuk dev/test saja.

## 6. Route dan Halaman (kontrak awal)

- `/login`, `/dashboard` (auth).
- `/master/unit-kerja`, `/master/periode-survei`, `/master/wilayah`,
  `/master/petugas`, `/master/petugas/{officer}/alias` (auth + `can`).
- `/admin/users`, `/admin/roles` (admin).
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
- Pagination server-side untuk tabel master dan audit.
- Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403 — detail di `workflows.md`.
