# PRD — SIMAPAN Fase 1

Produk: SIMAPAN (Sistem Informasi Manajemen Pengolahan dan Pengawasan).
Sasaran: aplikasi web internal untuk manajemen pengolahan dan pengawasan Susenas–Seruti.

## 1. Latar Belakang

Pengolahan dan pengawasan masih tersebar di file lepas sehingga sulit dilacak:
siapa mengerjakan apa, status tiap batch, dan siapa mengubah data master.
Fase 1 membangun fondasi identitas, master, dan audit sebelum modul penugasan.

## 2. Ruang Lingkup Fase 1

Masuk:

- autentikasi session;
- role dan permission;
- unit kerja;
- master tipe survei (`survey_types`);
- master periode survei;
- master wilayah;
- master petugas dengan kode unik;
- alias nama petugas;
- audit trail inti.

Tidak masuk Fase 1: penugasan/NKS detail, impor kuesioner, dashboard pengolahan,
notifikasi, API mobile, pelaporan publik.

## 3. Pengguna, Role, Unit

Role resmi (8):

1. Administrator — kelola user, role, master, audit read.
2. PPL/Petugas Lapangan — data lapangan miliknya.
3. PML/Pengawas Pendataan Lapangan — awasi PPL dalam wilayahnya.
4. Petugas Pengolahan — olah batch yang ditugaskan.
5. Pengawas Pengolahan — verifikasi hasil pengolahan.
6. Operator Tim Statistik Sosial — akses parsial berbasis tiket/penugasan untuk master sosial dan periode.
7. Operator IPDS — akses parsial berbasis tiket/penugasan untuk wilayah/infrastruktur.
8. Viewer/Pimpinan — baca dashboard dan audit ringkas.

Unit resmi (3):

- Tim Statistik Sosial
- Tim Pengolahan dan Layanan Statistik
- Tim IPDS

Matriks ringkas:

| Kemampuan | Admin | PPL | PML | Olah | Was-Olah | Ops Sos | Ops IPDS | Viewer |
|---|:-:|:-:|:-:|:-:|:-:|:-:|:-:|:-:|
| Kelola user/role | x | | | | | | | |
| Kelola unit/periode/wilayah | x | | | | | x/parsial | x/parsial | |
| Kelola petugas + alias | x | | | | | x | | |
| Lihat audit penuh | x | | | | | | | ringkas |
| Aksi lapangan/pengolahan | | x | verifikasi | x | verifikasi | | | |

Izin efektif memakai permission Spatie + Policy, bukan sekadar label role.
Tabel `officers` tidak mempunyai `application_role_id`; role teknis melekat di `users` melalui Spatie.
Akses data di luar wewenang pada Fase 1 dan Fase 2 awal memakai 403.
Detail teknis di `docs/architecture.md`.

## 4. Kebutuhan Fungsional

### F1 Autentikasi

- Login/logout session, CSRF, proteksi brute-force dasar (throttle).
- Semua halaman selain login wajib auth.
- Kriteria: login salah ditolak tanpa bocor info user; sesi kedaluwarsa mengarah ke login.

### F2 Role dan Permission

- CRUD role/permission oleh Administrator via Spatie.
- Permission granular per master, misal `master.petugas.view`, `master.petugas.manage`.
- Kriteria: user tanpa permission mendapat 403 dan tidak melihat menu terkait.

### F3 Unit Kerja

- CRUD unit: kode unik, nama, parent opsional, aktif/nonaktif.
- Hapus unit beranak/dipakai petugas ditolak (restrict).
- Kriteria: kode duplikat ditolak; hierarki terbaca sebagai tree.

### F4 Master Tipe dan Periode Survei

- `survey_types`: kode string unik, nama (misal Susenas, Seruti dummy).
- `survey_periods` field: `survey_type_id` FK, kode string unik, nama, `year`, `period_type`, `period_number`, tanggal mulai/selesai, status `draft/active/closed`, pembuat.
- Semua kode adalah string di DB, validasi, dan payload.
- Aturan: `end > start`; satu periode aktif per kombinasi `survey_type + year + period_type + period_number`.
- Kriteria: aktivasi kedua pada kombinasi yang sama ditolak dengan 403/422 sesuai konteks otorisasi vs validasi.

### F5 Master Wilayah

- Field: `code` string (bukan unique global), `full_code` string unique, nama, level (`kab/kec/desa`), parent, aktif.
- Keunikan: `full_code UNIQUE` dan kombinasi `parent_id + level + code` UNIQUE.
- Aturan: `desa` wajib berparent `kec`, `kec` wajib berparent `kab`.
- Semua kode wilayah string untuk menjaga leading zero; dilarang cast ke integer.
- Kriteria: parent tidak valid ditolak; duplikat `full_code` atau kombinasi parent/level/code ditolak; akses di luar wewenang memakai 403.

### F6 Master Petugas

- Field: kode petugas string unik dan immutable, nama, `normalized_name`, unit kerja, user opsional, status; memakai `softDeletes`.
- Tidak ada `application_role_id`; role teknis di `users` via Spatie.
- Semua kode petugas string; dilarang cast ke integer.
- Kriteria: kode duplikat (termasuk beda case/spasi) ditolak; kode tidak berubah saat update; hapus bersifat soft delete.

### F7 Alias Nama Petugas

- Satu petugas boleh punya banyak alias (nama varian dari file eksternal).
- Unik per `(officer_id, normalized_alias)`; hapus petugas menghapus alias (cascade).
- Kriteria: pencarian nama varian menemukan petugas induk.

### F8 Audit Trail Inti

- Setiap create/update/delete master, login, dan perubahan role tercatat.
- Isi: actor, action, tipe+id objek, old/new values, IP, user agent, waktu.
- Append-only; tanpa `updated_at` (hanya `created_at`); baca penuh hanya Administrator (Viewer/Pimpinan ringkas).
- Dilarang menyimpan password, token, NIK, telepon, alamat lengkap, dan PII/sensitive fields lain.
- Kriteria: tiap mutasi Fase 1 meninggalkan tepat satu baris audit yang benar.

## 5. Kebutuhan Non-Fungsional

- Performa: daftar master < 500 baris tanpa paginasi berat; pagination standar.
- Keamanan: OWASP dasar (auth, CSRF, XSS escape, SQL prepared, allowlist order/kolom).
- Audit: retensi minimal 1 tahun, arsip offline setelahnya (keputusan retensi final perlu konfirmasi).
- Aksesibilitas: form Blade/Livewire dengan label dan pesan error jelas.

## 6. Data dan Contoh Dummy

Semua contoh memakai Factory dummy seperti `PTG-001`, `KEC-001`, `PER-2026-01`.
Dilarang data survei asli, NIK/telepon/alamat asli, kredensial, token, dokumen asli.

## 7. Kriteria Penerimaan Fase 1

1. Login/logout dan RBAC 8 role berjalan dengan test Pest hijau.
2. CRUD tipe survei, unit, periode, wilayah, petugas, alias berjalan dengan validasi kode-string.
3. Audit mencatat semua mutasi dan lolos `AuditTrailTest`; audit tanpa `updated_at` dan tanpa PII/sensitive.
4. Tidak ada temuan IDOR/role-bypass pada cakupan Fase 1; akses di luar wewenang memakai 403.
5. Dokumen dan migration sinkron.
