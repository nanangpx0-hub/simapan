# Workflows — SIMAPAN Fase 1

## 1. Prinsip

- Perubahan kecil yang dapat di-commit satu per satu.
- Satu task = implementasi + dokumen terkait + test.
- Jangan refactor di luar scope; jangan campur format dengan logika.

## 2. Alur Master (Fase 1)

### Unit / Periode / Wilayah / Petugas

1. List + cari + pagination (auth + `view` permission).
2. Create: validasi FormRequest (kode-string unik, parent valid).
3. Update: kode `code` immutable (abaikan/tolak jika diubah); catat audit old/new.
4. Delete/arsip: tolak bila dipakai (`restrict`); alias ikut cascade hanya untuk petugas.
5. Tulis `audit_logs` otomatis via Observer.

### Aturan khusus

- Tipe survei dulu, lalu periode: aktivasi dicek `satu active per survey_type + year + period_type + period_number`; `closed` tidak bisa kembali `active` tanpa admin.
- Wilayah: level harus konsisten dengan parent; `code` bukan unique global, cek `full_code` dan `parent_id + level + code`.
- Petugas: kode string unik case-insensitive dan immutable; isi `normalized_name`; soft delete; alias dinormalisasi sebelum cek unik.
- Audit: halaman read-only; filter tanggal/model/actor; tanpa tombol edit/hapus; tanpa PII/sensitive.

## 3. Otorisasi dan Anti-Bocor

- Semua route auth + `can:permission`. Operator Sosial/IPDS dibatasi tiket/penugasan (`where` tiket) + cek Policy.
- Ownership di query (`where` pemilik/wilayah/tiket) + cek ulang Policy.
- Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403.
- Uji tiga sudut pandang bila relevan (misal PPL A, PPL B, PML/Admin).

## 4. Testing

- Semua test memakai Pest + Factory dummy; tanpa data asli.
- Wajib per scope: IDOR, role bypass, CSRF (mutasi tanpa token ditolak),
  SQL injection (order/kolom allowlist), XSS (escape Blade), workflow status, idempotensi create (retry aman).
- Perintah (setelah kode ada): `php artisan test` dan `./vendor/bin/pest --filter=NamaTest`.

## 5. Git dan Commit Kecil

Conventional Commits:

- `feat(master): ...`, `fix(auth): ...`, `docs(db): ...`, `test(audit): ...`, `chore: ...`.

Urutan Fase 1 (satu commit per baris, boleh dipecah lagi):

1. `chore: init laravel 12 + mysql8 config` (nanti).
2. `feat(auth): session login + dashboard`.
3. `feat(rbac): spatie role/permission + seeder`.
4. `feat(master): work_units`.
5. `feat(master): survey_types`.
6. `feat(master): survey_periods`.
7. `feat(master): regions`.
8. `feat(master): officers kode unik + normalized_name + softDeletes`.
9. `feat(master): officer_aliases`.
10. `feat(audit): audit_logs append-only + halaman`.
11. `docs: sinkronisasi prd/architecture/database`.

Sebelum commit: `git status`, baca diff, pastikan tanpa `.env`/kredensial/dokumen asli.

## 6. Definisi Selesai

- Test scope hijau; dokumen sinkron; tanpa data Terlarang; laporan berisi file berubah, hasil test, risiko, lanjutan.
