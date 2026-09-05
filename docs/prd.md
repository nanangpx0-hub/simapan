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

Role resmi (9):

1. Super Admin — tertinggi: akses penuh seluruh modul; satu-satunya pengelola role (create/update/delete, sinkron permission) dan penentu role `super_admin`.
2. Administrator — kelola user, master, audit read; assign/cabut role non-super.
3. PPL/Petugas Lapangan — data lapangan miliknya.
4. PML/Pengawas Pendataan Lapangan — awasi PPL dalam wilayahnya.
5. Petugas Pengolahan — olah batch yang ditugaskan.
6. Pengawas Pengolahan — verifikasi hasil pengolahan.
7. Operator Tim Statistik Sosial — akses parsial berbasis tiket/penugasan untuk master sosial dan periode.
8. Operator IPDS — akses parsial berbasis tiket/penugasan untuk wilayah/infrastruktur.
9. Viewer/Pimpinan — baca dashboard dan audit ringkas.

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
- Halaman `/profile` wajib auth + permission `profile.manage` (guest → login, tanpa permission → 403).
- Kriteria: login salah ditolak tanpa bocor info user; sesi kedaluwarsa mengarah ke login.

### F2 Role dan Permission

- Role sistem-terkelola read-only di UI Fase 1A; perubahan role/permission sistem hanya via seeder dan kode.
- Administrator memberi/mencabut role pengguna lain (bukan dirinya sendiri) via Spatie.
- Permission granular per master, misal `master.officer.view`, `master.officer.manage`.
- Matriks kanonik Fase 2 (`config/simapan_roles.php`, dikunci `RbacMatrixTest`):
  Administrator 25 izin; Viewer seluruh `.view` + `audit.view` ringkas
  (dashboard eksekutif + ekspor rekap + filter audit pimpinan,
  dikunci `ViewerExecutiveWorkflowTest`);
  Sosial (`work_unit/survey_period/type/region/officer.view`,
  `allocation.view/manage/assign`, `dsrt.view`, `document.view/manage`,
  kartu pemantauan + scope SUSENAS/SERUTI, dikunci `SocialOperatorWorkflowTest`);
  IPDS (`work_unit/survey_period/region/officer.view`, `master.officer.manage`,
  `allocation.view`, `document.view/manage/receive/assign`);
  Was-Olah (`work_unit/officer.view`, `document.view/manage/receive/assign`,
  widget ruang pengolahan + filter manifest + aksi pengembalian RETURNED,
  dikunci `ProcessingWorkflowTest`);
  PML (`allocation.view/assign`, `dsrt.view/verify`, `document.view/manage`);
  PPL (`allocation.view`, `dsrt.view/manage`, `document.view`);
  Was-Olah (`document.view/receive/assign`); Olah (`document.view`).
- Ownership Fase 2: PPL/PML/Pengolahan hanya melihat data yang ditugaskan kepadanya
  (`activeAssignments.officer_id == user.officer.id`); Admin/Pimpinan/Operator agregat penuh.
  Akses di luar penugasan memakai 403; penugasan menolak akun petugas dengan role tak setara.
  Akun User dapat ditautkan opsional ke entitas Officer; dashboard menampilkan statistik
  scoped + widget tugas aktif; menu dinamis mengikuti permission view.
- Kriteria: user tanpa permission mendapat 403 dan tidak melihat menu terkait;
  PPL A vs alokasi PPL B 403; PML vs NKS tak diawasi 403; Viewer read-only 200, mutasi 403
  (`RoleOwnershipScopingTest`).

### F3 Unit Kerja

- CRUD unit: kode unik, nama, parent opsional, aktif/nonaktif.
- Fase 1B-1: tanpa delete fisik via UI (nonaktifkan sebagai default); larang self-parent/siklus; parent nonaktif tidak untuk child baru.
- Hapus unit beranak/dipakai petugas ditolak (restrict).
- Kriteria: kode duplikat ditolak; hierarki terbaca sebagai tree.

### F4 Master Tipe dan Periode Survei

- Fase 1B-3 terimplementasi: status `DRAFT/ACTIVE/CLOSED/ARCHIVED` dengan
  action (`activate/close/archive` via POST); `code`/tipe/nomor/tahun immutable;
  `closed_by`/`closed_at` server-side; tanpa delete UI; tanpa reopen;
  seeder dummy `SUSENAS-S1-2099`, `SERUTI-T1-2099` (keduanya `DRAFT`).
  Detail di `docs/database.md`.
- `survey_types`: kode string unik, nama (misal Susenas, Seruti dummy).
- `survey_periods` field: `survey_type_id` FK, kode string unik, nama, `year`, `period_type`, `period_number`, tanggal mulai/selesai, status `draft/active/closed`, pembuat.
- Semua kode adalah string di DB, validasi, dan payload.
- Aturan: `end > start`; satu periode aktif per kombinasi `survey_type + year + period_type + period_number`.
- Kriteria: aktivasi kedua pada kombinasi yang sama ditolak dengan 403/422 sesuai konteks otorisasi vs validasi.

### F5 Master Wilayah

- Fase 1B-2 terimplementasi: 4 level generik (`PROVINSI` → `KAB_KOTA` →
  `KECAMATAN` → `DESA_KELURAHAN_NAGARI`), `full_code` server-side unique,
  tanpa delete fisik UI, seeder dummy (`99` → `9901` → `9901001` → `9901001001`).
  SLS/Sub-SLS menyusul fase alokasi. Detail di `docs/database.md`.

- Desain lanjutan (level kab/kec/desa dan SLS menyusul fase alokasi):
- Field: `code` string (bukan unique global), `full_code` string unique, nama, level (`kab/kec/desa`), parent, aktif.
- Keunikan: `full_code UNIQUE` dan kombinasi `parent_id + level + code` UNIQUE.
- Aturan: `desa` wajib berparent `kec`, `kec` wajib berparent `kab`.
- Semua kode wilayah string untuk menjaga leading zero; dilarang cast ke integer.
- Kriteria: parent tidak valid ditolak; duplikat `full_code` atau kombinasi parent/level/code ditolak; akses di luar wewenang memakai 403.

### F6 Master Petugas

- Fase 1B-4 terimplementasi: status `ACTIVE/INACTIVE`, tanggal aktif,
  phone/email sensitif (masking), user opsional satu officer,
  tanpa delete UI (soft delete proteksi internal). Detail di `docs/database.md`.
- Field: kode petugas string unik dan immutable, nama, `normalized_name`, unit kerja, user opsional, status; memakai `softDeletes`.
- Tidak ada `application_role_id`; role teknis di `users` via Spatie.
- Semua kode petugas string; dilarang cast ke integer.
- Kriteria: kode duplikat (termasuk beda case/spasi) ditolak; kode tidak berubah saat update; hapus bersifat soft delete.

### F7 Alias Nama Petugas

- Fase 1B-4 terimplementasi: alias boleh dipakai petugas lain (warning),
  alias ≠ nama utama sendiri, `created_by` dari user login, tanpa delete UI.
- Satu petugas boleh punya banyak alias (nama varian dari file eksternal).
- Unik per `(officer_id, normalized_alias)`; hapus petugas menghapus alias (cascade).
- Kriteria: pencarian nama varian menemukan petugas induk.

### F8 Audit Trail Inti

- Fase 1B-5 terimplementasi: `AuditLogger` satu pintu, `AuditSanitizer`
  (`[REDACTED]`), trait `Auditable` + observer, listener auth, halaman
  read-only `/audit-logs` (perlu `audit.view`); seeder tidak diaudit.
  Detail di `docs/database.md`.

- Setiap create/update/delete master, login, dan perubahan role tercatat.
- Isi: actor, action, tipe+id objek, old/new values, IP, user agent, waktu.
- Append-only; tanpa `updated_at` (hanya `created_at`); baca penuh hanya Administrator (Viewer/Pimpinan ringkas).
- Dilarang menyimpan password, token, NIK, telepon, alamat lengkap, dan PII/sensitive fields lain.
- Kriteria: tiap mutasi Fase 1 meninggalkan tepat satu baris audit yang benar.

### F9 Pelaporan Terintegrasi SUSENAS-SERUTI

- **Fase 2C-2 terimplementasi**: tipe dokumen kanonik (`P_SUSENAS`, `VSEN_SUSENAS`, `VSERUTI`), laporan entri pengolahan (5 laporan: Pemutakhiran, Sampel Susenas, Sampel Seruti), reconciliation engine verifikasi silang, SLA/deadline + notifikasi keterlambatan, dashboard konsolidasi real-time `/monitoring/pelaporan-dokumen`.
- **Entri data**: setiap NKS punya 3 laporan (PEMUTAKHIRAN_SUSENAS, SAMPEL_SUSENAS, SAMPEL_SERUTI). Laporan Seruti hanya dapat dibuat bila laporan Susenas induk sudah ada dan berstatus bersih (COMPLETED/RECONCILED).
- **Verifikasi silang otomatis**: membandingkan L1 vs L3 (NKS belum dientri), L2 vs L4 (gap ruta sampel), L4 vs L5 (linkage Seruti→Susenas). Selisih otomatis ditandai DISCREPANCY dengan catatan detail per NKS.
- **SLA/deadline**: 4 batas waktu per periode (serah pemutakhiran, entri pemutakhiran, serah sampel, entri sampel). Status ON_TRACK/WARNING/OVERDUE dihitung otomatis.
- **Notifikasi**: peringatan H-3 & H+0 untuk penyerahan (Tim Sosial), H-2 & H+0 untuk entri (Tim Pengolahan/IPDS), rekap overdue harian untuk Pimpinan.
- **Dashboard**: 5 kartu indikator utama, matriks rekonsiliasi per NKS, filter periode/status SLA/status rekonsiliasi, ekspor Excel laporan rekonsiliasi 5 dokumen.
- **Otorisasi**: dashboard & ekspor memerlukan `document.view`; rekonsiliasi dijalankan via command terjadwal atau trigger manual oleh role berwenang.
- **Kriteria**: kelima laporan dapat dibuat dengan format valid; selisih terdeteksi otomatis; entri Seruti ditolak bila induk belum ada; status SLA sesuai deadline; ekspor Excel berhasil; dashboard menampilkan matriks dengan benar.

## 5. Kebutuhan Non-Fungsional

- Performa: tabel Livewire server-side (perPage 10/25/50/100, debounce filter 500ms, cache opsi 600 detik, lazy pagination); ekspor streaming `.xlsx`; impor maks 5MB.
- Tabel: navigasi halaman + total `Menampilkan X–Y dari Z`, filter teks/kategori/tanggal/rentang + Reset, hover + selected row, responsif `overflow-x-auto`. Ekspor menghormati filter; audit-logs tanpa ekspor.
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
