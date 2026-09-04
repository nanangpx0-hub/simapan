# Pengujian E2E (Playwright) — SIMAPAN

Pengujian antarmuka end-to-end memakai **Playwright** dengan browser Chromium yang
dijalankan **remote (headless) melalui Chrome DevTools Protocol (CDP)** sehingga
agen/CI dapat mengendalikannya secara otonom tanpa interaksi manual.

## 1. Prasyarat

| Komponen | Ketentuan |
|---|---|
| Node.js | >= 20 |
| Package Playwright | `npm install` (playwright tersimpan sebagai devDependency) |
| Browser | Chromium cache Playwright (`%LOCALAPPDATA%\ms-playwright`) |
| Server app | `php artisan serve --host=127.0.0.1 --port=8000` |
| Database | MySQL `simapan_db` ter-seed (`php artisan migrate --seed --force`) |

Seeder `DevelopmentRoleUserSeeder` (local/testing saja) membuat satu user dummy
per peran resmi (`<slug>@simapan.test`, password dev `Simapan-Dev-2026`), mis.
`field_officer@simapan.test`, `processing_officer@simapan.test`, dst.
Administrator memakai kredensial `SIMAPAN_ADMIN_*` dari `.env`.

## 2. Menjalankan browser remote

```powershell
& "$env:LOCALAPPDATA\ms-playwright\chromium-1228\chrome-win64\chrome.exe" `
  --headless=new --remote-debugging-port=9222 --no-first-run `
  --user-data-dir="$env:TEMP\simapan-e2e-profile" --window-size=1440,900 about:blank
```

Verifikasi: `Invoke-WebRequest http://127.0.0.1:9222/json/version`.

## 3. Skrip yang tersedia

| Skrip | Cakupan | Perintah |
|---|---|---|
| `tests/e2e/smoke-roles.cjs` | Login + crawl semua halaman & tautan internal untuk 8 peran; rekam HTTP 4xx/5xx, error console, pageerror, gagal muat aset; screenshot dashboard per peran; laporan `artifacts/report.json` | `node tests/e2e/smoke-roles.cjs` |
| `tests/e2e/workflow-admin.cjs` | Alur bisnis administrator: penugasan dokumen → tambah item manifest → submit manifest → serah terima → penerimaan item; laporan `artifacts/workflow-report.json` | `node tests/e2e/workflow-admin.cjs` |
| `tests/e2e/workflow-pml-dsrt.cjs` | Alur interaktif PML: daftar alokasi → DSRT alokasi Susenas → isi DSRT baru → detail → verifikasi (DRAFT→VERIFIED); laporan `artifacts/workflow-pml-report.json` | `node tests/e2e/workflow-pml-dsrt.cjs` |

Ketiga skrip otomatis memilih mode browser: bila CDP (`E2E_CDP_URL`) tidak
dapat dihubungi dalam 4 detik, Chromium headless diluncurkan lokal — sehingga
skrip yang sama jalan di mesin dev (browser remote) maupun di CI.
Kredensial dibaca dari env proses terlebih dahulu, lalu `.env`, lalu fallback
dummy dev (aman untuk CI tanpa `.env`).

## 3a. Alur interaktif non-administrator (PML mengisi DSRT)

Kontrak RBAC Fase 1/2 memberi tujuh peran non-admin hanya `dashboard.view` +
`profile.manage` (dikunci `RbacMatrixTest` — mapping role tidak boleh diubah).
Agar alur bisnis peran non-admin tetap dapat diuji E2E,
`DevelopmentRoleUserSeeder` (dev/testing saja) memberikan **izin level-user**
(direct permission Spatie pada user, bukan pada role):

| User | Izin level-user dev |
|---|---|
| `field_supervisor@simapan.test` (PML) | `allocation.view`, `dsrt.view`, `dsrt.manage`, `dsrt.verify` |

Dengan itu `workflow-pml-dsrt.cjs` menguji: login PML → `/alokasi` →
`/alokasi/{id}/dsrt` (hanya alokasi Susenas) → isi DSRT baru (NUS/NURT unik
berbasis timestamp agar idempoten) → cek baris muncul di daftar → buka detail →
submit verifikasi → status `VERIFIED`. Skrip toleran re-run (sampel yang sudah
`VERIFIED` dianggap lulus dengan catatan).

`VERIFIED` dianggap lulus dengan catatan).

## 3b. CI (GitHub Actions)

`.github/workflows/ci.yml` menjalankan dua job pada setiap push ke `main` dan
pull request:

1. **pest** — `composer install` lalu `php artisan test` (sqlite `:memory:`
   sesuai `phpunit.xml`).
2. **e2e** — build aset (`npm ci && npm run build`), siap `.env` CI berbasis
   **sqlite**, `migrate --seed` (seeder dummy + user per peran),
   `playwright install chromium`, `php artisan serve`, lalu menjalankan ketiga
   skrip E2E di atas (mode launch lokal, tanpa CDP). Artefak
   (laporan + screenshot) diunggah sebagai artifact job.

Status HTTP yang dianggap wajar pada smoke test: `200`, `301/302/303/307` (redirect),
dan `403` (kontrak RBAC Fase 1/2 untuk akses di luar wewenang).
`404` dan `5xx` selalu dianggap temuan.

## 4. Hasil verifikasi terakhir (2026-09-04)

- Suite Pest penuh: **348 passed (1.565 assertions)**.
- Smoke test 8 peran: administrator 117 halaman; PML 39 halaman (izin
  level-user dev aktif — termasuk halaman alokasi & DSRT); enam peran lain
  19 halaman. Tanpa 404/5xx; 403 sesuai kontrak RBAC.
- Workflow administrator: 8/8 langkah sukses, tanpa error console/5xx.
- Workflow PML (DSRT): 10/10 langkah sukses — mode browser remote (CDP) dan
  mode launch lokal (simulasi CI) keduanya lolos.
- Screenshot per peran tersimpan di `tests/e2e/artifacts/`.

## 5. Regresi yang pernah ditemukan dan diperbaiki

1. `GET /dokumen/{document}/penugasan` → **500** (`Undefined variable $officers`):
   `DocumentProcessingAssignmentController::index` kini mengirim `officers`,
   `locations`, dan `conditions` ke view.
2. Tautan **Serah terima** pada `manifest/show` selalu dirender walaupun
   `DocumentTransfer` belum ada (manifest masih `DRAFT`) → **404**:
   tautan kini hanya dirender bila `$manifest->transfer` ada.

Keduanya dikunci test regresi `tests/Feature/DocumentUiRegressionTest.php`.

## 6. Ketentuan

- Skrip E2E hanya memakai data dummy seeder; dilarang memakai data survei asli.
- Folder `tests/e2e/artifacts/` (laporan + screenshot) tidak di-commit.
- Password dev di seeder hanya untuk local/testing; seeder menolak jalan di produksi.
