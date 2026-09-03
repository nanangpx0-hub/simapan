# SIMAPAN — Sistem Informasi Manajemen Pengolahan dan Pengawasan

Aplikasi web internal untuk manajemen pengolahan dan pengawasan Susenas–Seruti.

## Stack

- PHP 8.2.30 (`~8.2.0`)
- Laravel 12.x (kompatibel PHP 8.2)
- MySQL 8.0.30, InnoDB, `utf8mb4_unicode_ci`
- Blade + Livewire 3
- Auth session + CSRF, RBAC `spatie/laravel-permission`
- Test: Pest + Factory dummy

## Status

Bootstrap selesai: Laravel 12.x + Breeze (Blade + Alpine) + Pest + Pint.
Belum ada modul bisnis (tanpa Spatie, Livewire, upload, Excel).

## Instalasi Lokal (Laragon, MySQL 8.0.30)

1. Buat database `simapan_db` (`utf8mb4_unicode_ci`).
2. Salin `.env.example` menjadi `.env` lalu sesuaikan:
   `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, `DB_PORT=3306`,
   `DB_DATABASE=simapan_db`, `DB_USERNAME=root`, `DB_PASSWORD=` (jangan commit `.env`).
3. `composer install`
4. `php artisan key:generate`
5. Pastikan database aktif adalah `simapan_db`, lalu `php artisan migrate` (jangan `migrate:fresh` bila bukan `simapan_db`).
6. `npm install && npm run build`
7. `php artisan test` dan `./vendor/bin/pint --test`
8. Jalankan: via Laragon (VirtualHost ke `public/`) atau `php artisan serve`, lalu buka `/login` dan `/dashboard`.

## Dokumen

- `AGENTS.md` — instruksi permanen agent
- `docs/prd.md` — kebutuhan produk dan role/unit
- `docs/architecture.md` — arsitektur, auth, RBAC, modul
- `docs/database.md` — skema Fase 1 dan aturan kode-string
- `docs/workflows.md` — alur kerja dan commit kecil
- `docs/coding-standards.md` — PSR-12, strict types, konvensi
- `docs/data-classification.md` — klasifikasi data dan larangan data asli

## Role

Administrator, PPL/Petugas Lapangan, PML/Pengawas Pendataan Lapangan,
Petugas Pengolahan, Pengawas Pengolahan, Operator Tim Statistik Sosial,
Operator IPDS, Viewer/Pimpinan.

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
