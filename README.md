# Mystery Passenger v2 — Web Full

Aplikasi survei **Mystery Passenger** berbasis web: petugas/evaluator menumpang moda
transportasi secara anonim lalu mengisi lembar ceklist (SERVQUAL) dan laporan kegiatan.
Berbeda dari v1 (Laravel 12 + Filament 4.1), v2 **menjadikan moda transportasi dan
struktur formulir sebagai data**, bukan kode — kapal, bus, kereta, pesawat, dsb. dapat
ditambah sepenuhnya dari UI admin tanpa deploy ulang. Tersedia **Export PDF**, **Print**,
dan **Export CSV/XLSX**.

## Stack

| Aspek | Versi |
| --- | --- |
| PHP | 8.4 (image `php:8.4-fpm`, Node 22 menyatu di service `php`) |
| Laravel | 13.x |
| Filament | 5.x (Livewire 4, Tailwind 4) |
| Database | MySQL 8.4 |
| PDF | `barryvdh/laravel-dompdf` ^3.1 |
| Test | Pest 5 + `pest-plugin-livewire` |
| Runner | Docker Compose (nginx, php, mysql, queue, scheduler) |

## Prasyarat

- Docker Engine + Docker Compose v2
- Perintah `make`

## Instalasi cepat

```bash
make install     # build image, nyalakan service, composer install, key,
                 # migrate, seed, npm build
make user        # buat akun admin panel (atau pakai akun seeder)
```

Buka <http://localhost:8080/admin>.

Akun seeder bawaan (password semuanya `password`):

| Email | Peran |
| --- | --- |
| `admin@mysterypassenger.test` | Administrator |
| `reviewer@mysterypassenger.test` | Reviewer |
| `surveyor@mysterypassenger.test` | Surveyor |

> Port default: aplikasi `8080`, Vite `5173`, MySQL `3308` (host). Ubah via `.env`
> (`APP_PORT`, `VITE_PORT`, `DB_FORWARD_PORT`).

## Perintah penting (`make`)

Jalankan `make` atau `make help` untuk daftar lengkap.

| Perintah | Fungsi |
| --- | --- |
| `make install` | Setup awal lengkap |
| `make up` / `make down` | Nyalakan / matikan service |
| `make dev` | Nyalakan + Vite dev server (HMR) |
| `make shell` / `make shell-root` | Masuk kontainer `php` |
| `make db` | Shell MySQL interaktif |
| `make migrate` / `make fresh` / `make seed` | Migration / reset+seed / seed |
| `make artisan CMD="..."` | Jalankan artisan |
| `make composer CMD="..."` | Jalankan composer |
| `make npm CMD="..."` | Jalankan npm |
| `make build` | Build aset produksi |
| `make test` / `make test-unit` / `make test-feature` | Test suite |
| `make pint` / `make lint` / `make check` | Format & gerbang mutu |
| `make queue` / `make logs-queue` | Queue worker |
| `make dump` / `make restore FILE=...` | Backup / restore database |
| `make doctor` | Cek kesehatan toolchain & kontainer |

## Arsitektur singkat

```
TransportMode ──< FormTemplate ──┬──< TemplateSection ──< TemplateField   (profil perjalanan)
                                 ├──< QuestionGroup (nestable) ──< Questions ──< QuestionOptions
                                 └──< TemplateAssignments >── Users

Survey ──┬──< SurveyFieldValues >── TemplateFields
         ├──< SurveyAnswers >── Questions ──< SurveyAnswerMedia
         └── meta (JSON snapshot struktur template saat submit)
```

Model konseptual lengkap ada di `../plan/aplan/02-keputusan-arsitektur.md`.

## Menambah moda transportasi baru (tanpa kode)

1. Masuk sebagai admin → **Konfigurasi Formulir → Jenis Transportasi → Buat**.
   Isi nama (mis. "Kapal Ro-Ro"), kode dokumen (mis. `RORO`), ikon, dan warna.
2. **Template Formulir → Buat**: pilih moda, beri nama, simpan sebagai draf.
3. Pada template, buka relation manager:
   - **Bagian Profil Perjalanan** — kelompokkan field (mis. "Informasi Kapal").
   - **Field Profil Perjalanan** — tambah field dinamis (label, key, tipe, opsi,
     validasi, apakah tampil di tabel/PDF).
   - **Penugasan Surveyor** — tugaskan template ke surveyor.
4. Klik **Susun Pertanyaan** untuk menyusun indikator → pertanyaan → opsi, tipe
   jawaban, bobot, kewajiban bukti foto, dan conditional logic.
5. Klik **Terbitkan** pada tabel template. Setelah terbit, struktur terkunci;
   gunakan **Versi Baru** untuk revisi (membuat draf dengan struktur terkloning).
6. Atur kop surat/logo/tanda tangan di **Pengaturan Cetak**.

## Alur survei

1. Surveyor membuat survei (wizard: pilih moda + template, identitas awal) → diarahkan
   ke halaman pengisian dinamis.
2. Isi profil perjalanan + setiap indikator (bukti foto opsional/wajib). Draf tersimpan
   otomatis tiap 60 detik.
3. **Kirim Survei** → status `submitted`, skor dihitung, snapshot `meta` ditulis.
4. Reviewer/admin meninjau lewat aksi **Review** → `under_review` → `approved`/`rejected`
   (notifikasi database dikirim ke surveyor).
5. PDF/Print/Export tersedia kapan saja; PDF lama tetap reproducible walau template
   direvisi karena memakai snapshot.

## Peran & otorisasi

- **admin** — semua; hanya admin yang boleh CRUD moda/template/user/pengaturan cetak.
- **reviewer** — lihat semua survei, ubah status, cetak/export; moda & template read-only.
- **surveyor** — hanya survei miliknya sendiri, dari template yang ditugaskan.

Otorisasi ditegakkan lewat Policy Laravel (`app/Policies`) + scope `Survey::visibleTo()`.

## Keamanan

- Bukti foto disimpan di disk privat (`storage/app/private/survey-media`), **tidak** di
  webroot; diakses lewat rute terotorisasi `surveys/{survey}/media/{media}` dengan
  proteksi IDOR.
- Foto di-embed ke PDF sebagai data URI (aman dari SSRF; `enable_remote=false`, chroot
  dibatasi).
- Export diberi proteksi formula injection dan ditulis ke disk privat.
- Akun non-aktif ditolak middleware `EnsureUserIsActive`.

## Testing

```bash
make test               # seluruh suite (94+ test)
make coverage           # dengan laporan coverage (butuh xdebug)
make check              # Pint + test
```

Test memakai database MySQL terpisah `v2mysterypassenger_test` (lihat `.env.testing`).

## Produksi

Lihat `../plan/aplan/09-docker-makefile.md` §8. Ringkasnya: `APP_ENV=production`,
`APP_DEBUG=false`, `APP_KEY` baru, terminasi TLS di reverse proxy, hapus pemetaan port
MySQL ke host, `opcache.validate_timestamps=0` + `make optimize`, dan jadwalkan
`make dump` untuk backup.

## Struktur direktori utama

```
app/
├── Enums/                 # UserRole, AnswerType, SurveyStatus, dst.
├── Filament/              # Resources, Schemas (builder form dinamis), Widgets, Exports
├── Http/Controllers/      # PDF, Print, Media, Export download
├── Jobs/                  # GenerateSurveyPdfBundle, PruneExpiredExportFiles
├── Models/                # 13 model domain
├── Observers/             # pembersihan media, invalidasi cache kolom dinamis
├── Policies/              # otorisasi per peran
├── Services/              # scoring, submission, snapshot, state machine, versioning, PDF
└── Support/AnswerTypes/   # 9 handler tipe jawaban (strategy pattern)
docker/                    # nginx, php (Dockerfile+ini), mysql init
resources/views/pdf|print  # Blade dokumen cetak
```
