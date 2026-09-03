# AGENTS.md — Instruksi Permanen AI Coding Agent SIMAPAN

> Wajib dibaca sebelum menganalisis atau mengubah repository.

## 1. Produk dan Stack

SIMAPAN: Sistem Informasi Manajemen Pengolahan dan Pengawasan.
Aplikasi web internal untuk manajemen pengolahan dan pengawasan Susenas–Seruti.

Stack resmi:

| Komponen | Ketentuan |
|---|---|
| Runtime | PHP 8.2.30 (kunci `~8.2.0` di `composer.json`) |
| Framework | Laravel 12.x (kompatibel PHP 8.2) |
| Database | MySQL 8.0.30, InnoDB, `utf8mb4_unicode_ci` |
| UI | Blade + Livewire 3 |
| Auth | Session + CSRF (web internal) |
| RBAC | `spatie/laravel-permission` |
| Test | Pest + Factory dummy |

Dilarang menginstal package atau membuat kode aplikasi selama tahap fondasi dokumentasi. Dilarang memakai data survei asli.

## 2. Sumber Kebenaran

Urutan resolusi konflik:

1. Kode, migration, dan database yang benar-benar berjalan.
2. Test otomatis yang relevan.
3. `docs/database.md`, `docs/architecture.md`, `docs/prd.md`.
4. Dokumen lain di `docs/`.

Dokumen minimum setiap task:

1. `AGENTS.md` ini.
2. `README.md`.
3. `docs/prd.md`, `docs/architecture.md`, `docs/database.md`.
4. `docs/workflows.md`, `docs/coding-standards.md`, `docs/data-classification.md` sesuai scope.

## 3. Role dan Unit

Role resmi:

- Administrator
- PPL/Petugas Lapangan
- PML/Pengawas Pendataan Lapangan
- Petugas Pengolahan
- Pengawas Pengolahan
- Operator Tim Statistik Sosial
- Operator IPDS
- Viewer/Pimpinan

Unit resmi:

- Tim Statistik Sosial
- Tim Pengolahan dan Layanan Statistik
- Tim IPDS

Aturan otorisasi:

- Izin efektif ditentukan middleware, Gate/Policy, dan permission Spatie, bukan sekadar nilai kolom `role`.
- Tabel `officers` tidak mempunyai `application_role_id`; role teknis melekat di `users` melalui Spatie.
- Operator Tim Statistik Sosial dan Operator IPDS hanya mendapat akses parsial berbasis tiket/penugasan, bukan akses global master.
- Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403 (bukan 404).
- Identitas pemilik berasal dari user terautentikasi, jangan percaya `user_id` atau `role` dari client.
- Terapkan ownership pada query dan verifikasi ulang di controller/service/policy.
- Uji minimal tiga sudut pandang untuk data sensitif peran (misal PPL A, PPL B, PML/Admin).

## 4. Database dan Migration

- Semua NKS/kode wilayah/kode petugas disimpan sebagai string (`VARCHAR`), bukan integer. Alasan: leading zero, format BPS, dan stabilitas kode.
- Semua `code`/NKS/kode wilayah/kode petugas adalah string di DB, validasi, dan payload; dilarang cast ke integer.
- Tabel Fase 1 mencakup `survey_types`; `survey_periods` merujuk `survey_type_id` dan memakai `period_type` + `period_number`.
- Satu periode aktif per kombinasi `survey_type + year + period_type + period_number`.
- `regions.code` bukan unique global; keunikan memakai `full_code UNIQUE` dan kombinasi `parent_id + level + code` UNIQUE.
- `officers` memakai `normalized_name` dan `softDeletes`; tidak ada `application_role_id`.
- PK mengikuti pola Laravel (`BIGINT UNSIGNED` default); FK eksplisit dengan `restrict`/`cascade` sesuai `docs/database.md`.
- Timestamp standar Laravel (`timestamps()`); audit log hanya `created_at`, tanpa `updated_at`.
- Soft delete hanya bila schema modul menerapkannya (wajib untuk `officers`).
- Semua perubahan schema memakai migration baru yang append-only. Jangan edit migration yang sudah di-merge/jalan.
- Collation mengikuti `utf8mb4_unicode_ci` kecuali migration aktual menyatakan lain.

## 5. Audit Trail

- Tabel `audit_logs` bersifat append-only: hanya `INSERT` dan `SELECT`. Tidak ada `UPDATE`/`DELETE` dari aplikasi.
- Tabel `audit_logs` tidak mempunyai `updated_at` (hanya `created_at`).
- Wajib mencatat: actor, action, tipe+id objek, nilai lama/baru, IP, user agent, waktu.
- Dilarang menyimpan password, token (JWT/API/session), NIK, telepon, atau alamat lengkap di log.
- Dilarang menyimpan PII/sensitive fields lain di log; filter sebelum tulis audit.
- Kebijakan retensi dan akses baca hanya admin/pimpinan ditentukan di `docs/prd.md` dan `docs/data-classification.md`.

## 6. Keamanan dan Data

- Jangan commit `.env`, kredensial, token, private key, `.pem`, `.key`.
- Semua SQL via query builder/Eloquent prepared statement; nama kolom/order memakai allowlist.
- Escape output Blade dengan `{{ }}`; hindari `{!! !!}` tanpa sanitasi.
- Semua mutasi web wajib CSRF dan validasi method.
- Semua endpoint wajib autentikasi + otorisasi sesuai kontrak.
- Klasifikasi data mengacu `docs/data-classification.md`. Data Terlarang tidak boleh masuk repo, seed, test, log, atau dokumen.

## 7. Kontrak dan Sinkronisasi Dokumentasi

Jika route, payload, response, permission, workflow, atau schema berubah, sinkronkan dalam task yang sama:

- implementasi;
- `docs/prd.md` / `docs/architecture.md` / `docs/database.md` yang relevan;
- test kontrak/otorisasi;
- panduan terkait.

## 8. Cara Kerja dan Testing

1. Jalankan `git status`; anggap perubahan yang ada milik pengguna.
2. Baca dokumen serta kode relevan sebelum mengedit.
3. Jangan refactor di luar scope.
4. Selesaikan perubahan kecil yang dapat di-commit satu per satu.
5. Semua test memakai Factory dummy. Dilarang data survei asli, NIK asli, telepon asli, alamat asli, kredensial, token, atau dokumen asli.
6. Cakup IDOR, role bypass, CSRF, SQL injection, XSS, workflow status, dan idempotensi sesuai scope.
7. Laporan akhir berisi file berubah, hasil test, risiko, dan pekerjaan lanjutan.

Konvensi: PSR-12, `declare(strict_types=1)`, snake_case database, migration append-only, Conventional Commits. Test memakai Pest. Detail di `docs/coding-standards.md` dan `docs/workflows.md`.

> Agent yang tidak mengikuti AGENTS.md ini tidak diizinkan mengubah kode produksi.
