# Coding Standards — SIMAPAN

## 1. Wajib

- PSR-12 untuk semua PHP.
- `declare(strict_types=1);` di setiap file PHP murni (model, service, policy, request, test).
- Database `snake_case`; tabel plural; FK `*_id`; index berawalan logis.
- Migration append-only; tidak mengedit migration yang sudah merge/jalan.
- Conventional Commits (lihat `workflows.md`).

## 2. PHP 8.2 dan Laravel

- Kunci `php: ~8.2.0`; pakai Laravel 12.x; hindari sintaks khusus 8.3+ selama Fase 1.
- Hindari dynamic properties (deprecated 8.2); deklarasikan properti/relasi eksplisit.
- Gunakan type-hint (`string`, `int`, `?Model`, return type) dan `readonly` seperlunya.
- Eloquent strict: `$fillable` eksplisit; semua `code` dijaga string dan immutable via mutator/validasi; `officers` tanpa `application_role_id`.
- Query via Eloquent/query builder (prepared); order/kolom dinamis memakai allowlist const.
- `officers` wajib `SoftDeletes` + `normalized_name`; `audit_logs` tanpa `updated_at`.

## 3. Blade dan Livewire 3

- Escape default `{{ }}`; `{!! !!}` hanya dengan sanitasi dan review.
- Validasi di FormRequest/Livewire `rules()`; pesan error Indonesia yang jelas.
- Component Livewire kecil per tabel/form; hindari logika bisnis di Blade.
- CSRF `@csrf` di form non-Livewire; method spoofing `@method` benar.

## 4. Audit dan Log

- Tulis audit lewat satu pintu (`AuditObserver`/`Auditable` trait) agar format konsisten; filter PII/sensitive sebelum tulis.
- Jangan `dd()`/`dump()` di commit; log memakai channel Laravel tanpa PII Terlarang.

## 5. Test

- Pest + Factory dummy; nama test deskriptif (`OfficerCodeUniqueTest`).
- Satu perilaku satu test; gunakan `RefreshDatabase`.
- Dilarang fixture dari data survei/NIK/telepon/alamat/kredensial/token/dokumen asli.

## 6. Contoh Pola (sketsa, bukan kode final)

- Request: `code => required|string|max:32|unique:officers,code` + tolak ubah `code` saat update; semua kode string, dilarang cast integer.
- Policy: `viewAny/view/update/delete` cek permission Spatie + tiket/penugasan/ownership bila relevan; tolak dengan 403 di Fase 1/2 awal.
- Observer: `created/updated/deleted => AuditLog::create([... old/new ...])` tanpa field Terlarang dan tanpa PII/sensitive.
