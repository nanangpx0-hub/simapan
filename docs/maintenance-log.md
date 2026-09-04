# Log Pemeliharaan — SIMAPAN

Catatan perubahan kode hasil sesi analisa dan pengujian menyeluruh.
Format: tanggal — berkas — alasan/temuan. Urutan terbaru di atas.

## 2026-09-04 (impor) — Integrasi data Alokasi.xlsx

1. `app/Support/XlsxReader.php` (baru) — pembaca XLSX mandiri (ZipArchive +
   SimpleXML): shared strings, inline strings, boolean, angka, serial date
   via `styles.xml`; tanpa dependency baru.
2. `app/Console/Commands/ImportAlokasi.php` (baru) — command `simapan:import-alokasi`
   yang menesuaikan 7 langkah: validasi struktur, pembersihan, migrasi,
   transaksi+rollback, verifikasi, logging, guard produzi (`--force`).
3. Sheet `Rincian` (email/password) dikecualikan karena Data Terlarang;
   sheet `Daftar alokasi` diimpor (kode wilayah, NKS string dengan leading zero,
   jumlah RT→notes, penugasan PCL/PML/Pengolah→Assignment, rantai region).

Hasil staging (local): 28 baris valid / 0 error; impor 28 alokasi, 47 region,
51 petugas, 84 penugasan; verifikasi lulus; rollback otomatis teruji log jalan
(dari error `Undefined array key "PPL"` yang dirollback-keh sebelum perbaikan).
Dokumentasi: `docs/import-alokasi.md`.

## 2026-09-04 (lanjutan) — E2E di CI + alur interaktif PML (DSRT)

1. `tests/e2e/workflow-pml-dsrt.cjs` (baru) — alur interaktif PML:
   daftar alokasi → DSRT alokasi Susenas → isi DSRT baru (NUS/NURT unik
   timestamp, idempoten) → detail → verifikasi DRAFT→VERIFIED. Toleran re-run.
2. `database/seeders/DevelopmentRoleUserSeeder.php` — tambah izin level-user
   dev-only untuk `field_supervisor` (`allocation.view`, `dsrt.view`,
   `dsrt.manage`, `dsrt.verify`) agar alur bisnis PML dapat diuji E2E tanpa
   mengubah mapping role kanonik (`RbacMatrixTest` tetap lolos).
3. `tests/e2e/smoke-roles.cjs`, `tests/e2e/workflow-admin.cjs` — kredensial
   kini env-proses → `.env` → fallback dummy (aman CI tanpa `.env`); fallback
   launch Chromium headless lokal bila CDP tidak tersedia dalam 4 detik.
4. `.github/workflows/ci.yml` (baru) — job `pest` (sqlite) dan job `e2e`
   (build aset, .env sqlite, migrate+seed, `playwright install chromium`,
   `artisan serve`, ketiga skrip E2E, unggah artefak).
5. `docs/e2e-testing.md` — dokumentasi skrip PML, izin level-user dev, CI,
   dan hasil verifikasi terbaru.

Verifikasi: Pest 348 passed; smoke 8 peran (administrator 117 halaman,
PML 39 halaman — 0 temuan 404/5xx); workflow admin 8/8; workflow PML 10/10
pada mode CDP maupun launch lokal.

## 2026-09-04 — Analisa menyeluruh + pengujian Playwright semua peran

### Temuan dan perbaikan

1. **BUG (HTTP 500)** — `GET /dokumen/{document}/penugasan`
   - Temuan: view `resources/views/master/dokumen/penugasan/index.blade.php`
     memakai `$officers`, `$locations`, `$conditions`, tetapi
     `DocumentProcessingAssignmentController::index()` tidak mengirimkannya
     (`Undefined variable $officers` di `storage/logs/laravel.log`).
   - Perbaikan: `app/Http/Controllers/Master/DocumentProcessingAssignmentController.php`
     — `index()` kini mengirim `officers` (petugas aktif + unit kerja),
     `locations` (lokasi aktif), dan `conditions`.
2. **BUG (tautan mati → HTTP 404)** — tautan "Serah terima" pada halaman manifest
   - Temuan: `resources/views/master/manifest/show.blade.php` selalu merender
     tautan `document_transfers.show`, padahal `DocumentTransfer` baru dibuat
     saat manifest disubmit; manifest `DRAFT` pasti menghasilkan 404.
   - Perbaikan: tautan hanya dirender bila `$manifest->transfer` ada.

### Perubahan pendukung pengujian

3. `database/seeders/DevelopmentRoleUserSeeder.php` (baru) — user dummy satu per
   peran resmi (`<slug>@simapan.test`, password dev sama) khusus local/testing;
   terdaftar di `database/seeders/DatabaseSeeder.php`.
4. `tests/e2e/smoke-roles.cjs` (baru) — Playwright via CDP (browser remote
   headless): login + crawl seluruh halaman & tautan internal untuk 8 peran;
   rekam 4xx/5xx, error console, pageerror, aset gagal muat; screenshot
   dashboard; laporan `tests/e2e/artifacts/report.json`.
5. `tests/e2e/workflow-admin.cjs` (baru) — alur bisnis administrator:
   penugasan dokumen → tambah item manifest → submit → serah terima → terima
   item; laporan `tests/e2e/artifacts/workflow-report.json`.
6. `tests/Feature/DocumentUiRegressionTest.php` (baru) — test regresi Pest
   untuk kedua bug di atas (2 test, 4 assertions).
7. `.gitignore` — tambah `/tests/e2e/artifacts` (laporan/screenshot tidak di-commit).
8. `docs/e2e-testing.md` (baru) — cara menjalankan pengujian E2E remote + hasil
   verifikasi; `README.md` — rujukan dokumen E2E.
9. `package.json` / `package-lock.json` — devDependency `playwright`.

### Verifikasi

- Suite Pest penuh: 348 passed (1.565 assertions), termasuk 2 test regresi baru.
- `vendor/bin/pint` bersih untuk semua berkas yang diubah.
- Smoke E2E 8 peran: administrator 107 halaman (0 temuan), 7 peran lain 19
  halaman masing-masing (hanya 403 sesuai kontrak RBAC Fase 1/2).
- Workflow E2E administrator: 8/8 langkah sukses tanpa error console/5xx.
- Screenshot dashboard admin & viewer terverifikasi visual (menu mengikuti permission).
