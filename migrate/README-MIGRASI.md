# Panduan Migrasi SIMAPAN ke PC Lain

Paket ini berisi skrip untuk memindahkan aplikasi SIMAPAN (Laravel 12 + MySQL)
dari PC ini ke PC lain. Berjalan di **Laragon maupun native** (auto-detect).

## Prerekwisit di PC TUJUAN
- Windows + PowerShell
- **PHP 8.2** (PATH terminal atau Laragon)
- **Composer** (PATH)
- **Node.js >= 20** (PATH) + npm
- **MySQL 8.0.x** aktif (database kosong bernama sesuai `.env`, mis. `simapan_db`)

## Langkah (jalankan di PC SUMBER dulu)

1. Jalankan backup database:
   ```powershell
   powershell -ExecutionPolicy Bypass -File backup-db.ps1
   ```
   → menghasilkan `simapan_db_backup_<timestamp>.sql` di folder ini.
2. Copiar **seluruh folder proyek** (termasuk folder `migrate/`) ke PC tujuan.
   Rekomendasi lokasi: `C:\laragon\www\simapan` (Laragon) atau mana pun.
   > `vendor/` dan `node_modules/` boleh ikut, tapi skrip setup akan install ulang;
   > jangan copiar ke GitHub bila dump berisi data internal.

## Langkah (di PC TUJUAN)

1. Pastikan MySQL aktif dan buat database bila kosong:
   ```sql
   CREATE DATABASE simapan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   (atau via phpMyAdmin/Laragon)
2. Dari folder `migrate/`, jalankan:
   ```powershell
   powershell -ExecutionPolicy Bypass -File migrate-to-pc.ps1
   ```
   Skrip akan:
   - Deteksi PHP/Composer/Node (PATH atau Laragon)
   - Install `composer install` + `npm ci` + `npm run build`
   - Cek `APP_KEY` (buat bila kosong)
   - **Tanya**: apakah meng-import dump SQL yang ditemukan → Ya = data lengkap dari PC sumber; Tidak = `migrate --seed` dummy
   - `optimize:clear`
3. Jalankan aplikasi dengan salah satu:
   ```powershell
   php artisan serve --port=8000
   ```
   atau pada Laragon: VirtualHost `simapan.test` → `public/`.

## Login awal (dev/local)

| Email | Password |
|---|---|
| `admin@simapan.test` (Administrator) | dari `.env` `SIMAPAN_ADMIN_PASSWORD` |
| `<role>@simapan.test` (peran lain, mis. `social_operator@simapan.test`) | `Simapan-Dev-2026` |

> Kredensial dev **hanya local**. Di produksi wajib ganti password user lewat UI Admin.

## Catatan penting

- `.env` + `APP_KEY`: better copiar dari PC lama agar data tersimpan (session tidak
  valid). Skrip **tidak** menghapus `.env` yang dicopy.
- `data/Alokasi.xlsx` (git-ignored) copiar manual bila dibutuhkan — hati-hati sheet
  `Rincian` memuat email/password (Data Terlarang; jangan simpan di repo/publik).
- Skrip migrasi impor (`simapan:import-alokasi`) tidak dijalankan otomatis;
  jalankan manual bila di PC baru ingin data alokasi (lihat `docs/import-alokasi.md`).
- E2E Playwright (opsional): `npx playwright install chromium` setelah `npm ci`.

## Rollback
- Tidak ada operasi destruktif: skrip hanya install/build/migrate/seed/import-dump.
- Bila gagal: taut `.env`, `composer install`, `npm run build`, `migrate --force`
  langkah demi langkah.