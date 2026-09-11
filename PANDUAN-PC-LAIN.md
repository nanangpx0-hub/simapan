# PANDUAN: Buka Proyek SIMAPAN di PC Lain via GitHub

> Repo: `https://github.com/nanangpx0-hub/simapan.git` — branch `main`
> Berlaku untuk: Laravel 12 + PHP 8.2 + MySQL 8.0 + Node 20+ (Windows / Laragon).

Status upload per 11 Sep 2026:
- `git push origin main` → **Everything up-to-date** (commit `ae04c5f` sudah di GitHub).
- Jadi PC lain tinggal `clone`, tidak perlu copy ZIP.

---

## 1. Yang harus di-install di PC baru

1. **Git** — https://git-scm.com/download/win (cek: `git --version`)
2. **PHP 8.2.30** — wajib seri `8.2.x` (cek: `php -v`).
   - Kalau pakai Laragon: gunakan Laragon Full + ganti PHP ke 8.2, atau
   - PHP native dari https://windows.php.net/download + aktifkan ekstensi:
     `bcmath, ctype, curl, dom, fileinfo, mbstring, openssl, pdo_mysql, tokenizer, xml, zip, gd`.
3. **Composer 2.x** — https://getcomposer.org/download (cek: `composer --version`)
4. **Node.js LTS >= 20 + npm** — https://nodejs.org (cek: `node -v && npm -v`)
5. **MySQL 8.0.x** — via Laragon (disarankan) atau MySQL Installer.
   Pastikan service MySQL **Running**.

> Versi ini dikunci di `composer.json` (`php ~8.2.0`, `laravel/framework ^12.0`).
> Jangan pakai PHP 8.3/8.4 — bisa gagal `composer install`.

---

## 2. Clone dari GitHub

Buka PowerShell / Terminal:

```powershell
# 1. Masuk ke folder kerja (sesuaikan; contoh Laragon)
cd C:\laragon\www

# 2. Clone
git clone https://github.com/nanangpx0-hub/simapan.git

# 3. Masuk proyek
cd simapan

# 4. Pastikan branch main terbaru
git checkout main
git pull origin main
```

> Kalau repo private: login GitHub saat diminta, atau pakai SSH
> (`git clone git@github.com:nanangpx0-hub/simapan.git`).

---

## 3. Setup `.env` + database

File `.env`, `vendor/`, `node_modules/`, dan dump `migrate/*.sql`
**tidak ikut ke GitHub** (lihat `.gitignore`) — jadi harus dibuat ulang.

```powershell
# 1. Copy contoh env
Copy-Item .env.example .env

# 2. Buat database kosong (via HeidiSQL/phpMyAdmin, atau CLI)
#    Nama default: simapan_db
```

Buat DB via MySQL CLI (atau lewati jika sudah buat via GUI):

```sql
CREATE DATABASE simapan_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lalu edit `.env` (Notepad/VS Code). Minimal:

```ini
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=simapan_db
DB_USERNAME=root
DB_PASSWORD=
# Jika MySQL pakai password, isi DB_PASSWORD.

# WAJIB diisi sebelum db:seed, kalau tidak seeder error:
SIMAPAN_ADMIN_NAME="SIMAPAN Administrator"
SIMAPAN_ADMIN_EMAIL="admin@simapan.test"
SIMAPAN_ADMIN_PASSWORD="Ganti-Password-Kuat-Min-8-Karakter"
```

> Jangan commit `.env` ke GitHub. Jangan pakai password asli/produksi di sini.

---

## 4. Install dependency + generate key + migrasi

Jalankan berurutan dari folder `simapan/`:

```powershell
# 1. PHP dependency
composer install

# 2. App key (wajib; kalau kosong session/login error)
php artisan key:generate

# 3a. OPSI A — data awal dummy/fresh (disarankan untuk PC baru):
php artisan migrate --seed

# 3b. OPSI B — data sama persis dengan PC lama:
# Dump .sql TIDAK ada di GitHub (di-ignore), jadi copy manual
# file migrate/simapan_db_backup_*.sql dari PC lama via flashdisk,
# taruh di folder migrate/, lalu:
# mysql -u root simapan_db < migrate\simapan_db_backup_YYYYMMDD_HHMMSS.sql
# php artisan migrate --force
# (atau jalankan otomatis: powershell -ExecutionPolicy Bypass -File migrate\migrate-to-pc.ps1)

# 4. Frontend
npm install
npm run build

# 5. Bersihkan cache
php artisan optimize:clear
php artisan permission:cache-reset
```

Verifikasi cepat (opsional tapi disarankan):

```powershell
php artisan test
.\vendor\bin\pint --test
```

---

## 5. Jalankan aplikasi

```powershell
php artisan serve --port=8000
```

Buka: http://localhost:8000/login

Login awal (data dummy/dev):

| Akun | Password |
|---|---|
| `admin@simapan.test` (Administrator) | isi `SIMAPAN_ADMIN_PASSWORD` di `.env` PC baru |
| `<role>@simapan.test` mis. `social_operator@simapan.test` | `Simapan-Dev-2026` |

> Kalau pakai Laragon: arahkan VirtualHost ke folder `public/`
> (mis. `simapan.test`), restart Apache/Nginx, buka `http://simapan.test/login`.

---

## 6. Update berikutnya (PC kedua dan seterusnya)

Setiap ada perubahan baru di GitHub, di PC lain cukup:

```powershell
cd C:\laragon\www\simapan
git pull origin main
composer install
npm install
npm run build
php artisan migrate --force
php artisan optimize:clear
```

Alur upload dari PC utama:

```powershell
git status
git add -A
git commit -m "tipe(scope): deskripsi singkat"
git push origin main
```

Gunakan Conventional Commits (`feat:`, `fix:`, `docs:`, `chore:`) sesuai `docs/workflows.md`.

---

## 7. Troubleshooting

| Gejala | Penyebab umum / solusi |
|---|---|
| `composer install` error versi PHP | `php -v` harus `8.2.x`. Ganti PHP di Laragon / PATH. |
| `SQLSTATE[HY000] [1049] Unknown database` | DB `simapan_db` belum dibuat. Buat dulu (langkah 3). |
| `Access denied for user 'root'` | `DB_USERNAME`/`DB_PASSWORD`/`DB_PORT` di `.env` salah. Samakan dengan MySQL Laragon. |
| `No application encryption key` | Belum `php artisan key:generate`. |
| Seeder error password kosong | Isi `SIMAPAN_ADMIN_PASSWORD` di `.env` sebelum `db:seed`. |
| Halaman putih / CSS tidak load | Belum `npm run build`. Jalankan ulang + `php artisan optimize:clear`. |
| `Permission denied` / 403 setelah login | Wajar jika role tanpa permission. Login sebagai admin, cek menu Manajemen Role. Lihat `docs/architecture.md`. |
| Data PC lama tidak muncul | Wajar — GitHub hanya bawa kode, bukan isi DB. Pakai OPSI B (copy `.sql` manual). |
| `git pull` conflict di `.env` | `.env` tidak di-track, jadi tidak konflik. Kalau conflict file lain: `git status`, resolve manual, jangan `--force`. |

Bantuan lanjutan: baca `README.md`, `docs/prd.md`, `docs/architecture.md`, `docs/database.md`, dan `migrate/README-MIGRASI.md` (metode copy-folder + restore dump).
