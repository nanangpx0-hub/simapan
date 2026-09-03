# Klasifikasi Data — SIMAPAN

## 1. Level

| Level | Contoh | Penanganan |
|---|---|---|
| Publik | Nama produk, struktur menu, dokumen fondasi ini | boleh di repo |
| Internal | Kode unit/wilayah/petugas dummy, periode, struktur role | repo + akses internal |
| Terbatas | Nama petugas asli, user login, log aktivitas non-PII | akses peran; audit dibatasi |
| Terlarang | Data survei asli, NIK asli, telepon asli, alamat lengkap asli, password, token (JWT/API/session), kredensial, `.pem/.key`, dokumen asli | tidak boleh masuk repo/seed/test/log/dokumen |

## 2. Larangan Keras

- Tidak ada Data Terlarang di kode, migration, seeder, factory, test, log, atau `docs/`.
- Contoh di dokumen dan test wajib dummy (`PTG-001`, `Nama Contoh 01`, `Jl. Contoh No. 1` bila perlu format).
- Telepon bila wajib diuji: pakai pola dummy `0800-0000-00X` dan tandai dummy.

## 3. Audit Log

- Tabel `audit_logs` tidak mempunyai `updated_at` (hanya `created_at`).
- Boleh: actor, action, tipe+id objek, field non-PII yang berubah, IP, user agent, waktu.
- Dilarang: password, token apa pun, NIK, telepon, alamat lengkap, dan PII/sensitive fields lain; filter sebelum tulis.
- Akses baca penuh hanya Administrator; Viewer/Pimpinan hanya ringkas/agregat.
- Retensi minimal 1 tahun (final perlu konfirmasi); arsip offline terenkripsi setelahnya.

## 4. Penyimpanan dan Akses

- `.env` tidak di-commit; kredensial via env/secret manager.
- Upload dokumen asli di luar Fase 1; bila kelak ada, simpan non-executable, nama acak, validasi MIME/magic bytes, tanpa traversal.
- Akun default hanya untuk bootstrap lokal dan wajib diganti; tanpa password hardcoded.

## 5. Pelanggaran

- Bila Data Terlarang terlanjur masuk: hapus dari history (rotasi kredensial/token segera), catat insiden di luar repo, tambah test/guard agar tidak terulang.
