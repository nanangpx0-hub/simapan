# Impor Alokasi dari Excel — SIMAPAN

Dokumentasi proses integraci `data/Alokasi.xlsx` ke modul Alokasi, sesuai 7 langkah:
validasi struktur, pembersihan data, skrip migrasi, transaksi, verifikasi, logging,
dan staging/produksi.

## 1. Berkas source

`data/Alokasi.xlsx` memuat dua sheet:

| Sheet | Isi | Penanganan |
|---|---|---|
| `Daftar alokasi` | No, Kecamatan (kode+nama), Desa (kode+nama), NKS, Jumlah RT, PCL, PML, Pengolah | **Diimpor** |
| `Rincian` | No, Nama Lengkap, Email, **Password** | **DIKECUALIKAN** — kolom email/password adalah Data Terlarang (`docs/data-classification.md`); validasi strukturnya dan dilaporkan, tapi nil dimasukkan di DB/log/repo |

## 2. Langkah pengolahan

1. **Validasi struktur** — `App\Support\XlsxReader` (pembaca XLSX mandiri: shared
   strings, inline strings, boolean, angka, serial date via `styles.xml`).
   Sheet, header, dan tip data diperiksa kontra skema DB.
2. **Pembersihan** — baris kosong dibuang; NKS/kode numerik dengan leading zero
   dipertahankan sebagai string dan dikonversi dari format float Excel (`50536.0`
   → `50536`, `00327` tetap `00327`); duplikat NKS dalam berkas dilewati; NKS
   invalid pola dilewati; spasi nama dirapatakan.
3. **Skrip migrasi** — `php artisan simapan:import-alokasi` (command
   `App\Console\Commands\ImportAlokasi`).
4. **Transaksi** — seluruh impor dalam `DB::transaction`; rollback otomatis bila
   `Throwable` ter-buat di tengah.
5. **Verifikasi** — jumlah baris DB cocokkan menghobris valid berkas; sampel data
   per kolom dicabuk (NKS, desa, RT, penugasan, hierarki wilayah).
6. **Logging** — via Log channel: warning sheet dikecualikan, period dibuat,
   rollback+error, total impor, verifikasi.
7. **Staging→Produzioni** — uji di staging (`APP_ENV=local`) dulu; produksi wajib
   `--force` sesayakan.

## 3. Perintah

```powershell
# Dry-run (validasi + pembersihan, tanpa tulis DB)
php artisan simapan:import-alokasi --period=SUSENAS-S1-2026 --dry-run

# Staging (local)
php artisan simapan:import-alokasi --period=SUSENAS-S1-2026 --ensure-period ^
  --provinsi-name="JAWA TIMUR" --kabkota-name="KABUPATEN JEMBER"

# Produzioni (wajib --force setelah staging tervalidasi)
php artisan simapan:import-alokasi --period=SUSENAS-S1-2026 --ensure-period ^
  --provinsi-name="..." --kabkota-name="..." --force
```

### Opsi utama

| Opsi | Funzionalita |
|---|---|
| `--file` | Path XLSX (default `data/Alokasi.xlsx`) |
| `--period` | Kode survey period tujuan (wajib), mis. `SUSENAS-S1-2026` |
| `--ensure-period` | Buat period `DRAFT` dari pola kode bila belum ada |
| `--actor-email` | User sebagai `created_by`/`assigned_by` (default admin) |
| `--provinsi-code/--provinsi-name` `--kabkota-code/--kabkota-name` | Rantai wilayah root; nama wajib bila region belum ada di master |
| `--skip-assignments` | Impor alokasi saja tanpa penugasan PCL/PML/Pengolah |
| `--dry-run` | Validasi tanpa tulis DB |
| `--force` | Wajib di lingkungan produzi |

## 4. Relasi foreign key handling

- `survey_period_id` — resolver `--period`; period dibuat `DRAFT` bila `--ensure-period`.
- `village_region_id` — rantai `Provinsi → KAB_KOTA → Kecamatan → Desa` resolver
  (region belum ada dibuat, `is_active=true`).
- Assignment petugas — PCL/PML→unit `SOSIAL`, Pengolah→unit `PENGOLAHAN_LS`;
  nama diterosolver ke `Officer` (ternormalisasi + unit, buat bila belum ada,
  kode autogen `PPL-/PML-/PGO-####`). Konflik penugasan aktif berbeda petugas
  **dilewati non-destruktif** dan dilaporkan (logs).
- `created_by`/`assigned_by` — via `--actor-email`.

## 5. Hasil staging (2026-09-04, `APP_ENV=local`)

| Berkas | Nilai |
|---|---|
| Baris dibaca | 28 |
| Baris kosong dibuang | 971 |
| Baris valid | 28 |
| Duplikat/Error | 0 |
| Alokasi dibuat | 28 |
| Region dibuat | 47 (19 kecamatan + 28 desa) |
| Petugas dibuat | 51 |
| Penugasan dibuat | 84 |
| Verifikasi | Lulus (28 = 28; sampel kolom/hierarki terverifikasi) |

Rollback otomatis teruji: saat error `Undefined array key "PPL"`, transaksi
dirollback (period/region/alokasi = 0) dan terlat di log sebelum perbaikan.

## 6. Originalidad data

Sheet `Rincian` (email/password) **tidak** dimasukkan. Sheet `Daftar alokasi`
memuat data internal/terbatas (kode wilayah, NKS, nama petugas) — aman sesuai
`docs/data-classification.md`; tak ada NIK/telepon/alamat/kredensial impor-keh.