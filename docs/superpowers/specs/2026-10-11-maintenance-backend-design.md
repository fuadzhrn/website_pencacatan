# Desain Backend Preventive Maintenance X-Ray — Tahap 25 dan 26

## 1. Tujuan

Tahap ini membangun fondasi backend untuk Sistem Informasi Preventive Maintenance X-Ray. Fondasi tersebut mencakup skema database yang ternormalisasi, model dan relasi Eloquent, data awal, autentikasi berbasis session, redirect berdasarkan role, serta middleware pembatasan akses.

Implementasi harus mempertahankan tampilan desktop dan mobile yang sudah ada. Data laporan tanggal 1–31 dibentuk saat query atau export, bukan disimpan sebagai kolom tanggal terpisah.

## 2. Kondisi Proyek Saat Ini

Proyek menggunakan Laravel 13 dan PHP 8.3. Autentikasi session sederhana sudah tersedia melalui `AuthenticatedSessionController`, `LoginRequest`, route login/logout, model `User`, dan enum `UserRole`. Fondasi tersebut akan diperluas, bukan diganti dengan paket autentikasi baru.

Model `User` sudah menggunakan atribut `Fillable` dan `Hidden` Laravel serta cast password `hashed`. Model baru mengikuti konvensi yang sama.

## 3. Keputusan Arsitektur

### 3.1 Penyimpanan nilai status

Nilai role, status, frekuensi, dan tipe input disimpan dalam kolom string dengan panjang yang sesuai. Pendekatan ini dipilih agar perubahan daftar status pada tahap berikutnya tidak memerlukan perubahan native enum MySQL dan tetap mudah diuji pada database test Laravel.

`UserRole` yang sudah ada tetap menjadi enum PHP dan cast pada model `User`. Status domain lain tetap berupa string terkontrol pada tahap ini untuk menghindari penambahan abstraksi yang belum dibutuhkan.

### 3.2 Integritas data historis

Data transaksi maintenance harus menjaga jejak pemeriksaan. Relasi ke data master atau user yang wajib memakai pembatasan penghapusan. Foreign key opsional seperti petugas yang ditugaskan, verifier, reviewer, item temuan, dan detail temuan dapat menjadi `null` saat data induknya dihapus jika hal tersebut diperlukan untuk mempertahankan transaksi.

Penghapusan berantai hanya digunakan pada detail yang sepenuhnya dimiliki transaksi induk, yaitu detail checklist ketika pemeriksaan dihapus. Aplikasi pada tahap berikutnya sebaiknya tidak menyediakan penghapusan permanen terhadap pemeriksaan yang sudah menjadi histori.

### 3.3 Autentikasi

Autentikasi tetap menggunakan session Laravel dan `Auth::attempt()`. Kredensial login menyertakan status `active`, sehingga akun `inactive` tidak dapat masuk. Setelah login berhasil, session diregenerasi.

Redirect default berdasarkan role:

- Admin menuju route desktop `dashboard`.
- Petugas menuju route `mobile.dashboard`.
- Supervisor menuju route desktop `dashboard`.

Redirect intended tetap dipertahankan selama target yang diminta memang dapat diakses role tersebut. Logout mengakhiri session, melakukan invalidasi session, dan meregenerasi token CSRF.

## 4. Desain Database

### 4.1 `users`

Kolom tambahan pada tabel bawaan:

- `role`: string, default `petugas`, index.
- `phone`: string nullable.
- `status`: string, default `active`, index.

Email tetap unik. Password menggunakan cast `hashed` pada model.

### 4.2 `machines`

- `id`
- `equipment_name`
- `brand`
- `type`
- `serial_number`, unik
- `location`
- `airport`
- `status`, default `active`, index
- `description`, nullable text
- timestamps

### 4.3 `maintenance_categories`

- `id`
- `name`, unik
- `frequency`, index
- `description`, nullable text
- `status`, default `active`, index
- timestamps

Frekuensi yang valid: `daily`, `weekly`, `monthly`, `quarterly`, `semester`, dan `yearly`.

### 4.4 `maintenance_items`

- `id`
- `maintenance_category_id`, foreign key
- `item_name`
- `criteria`, text
- `input_type`
- `sort_order`, nullable unsigned small integer
- `status`, default `active`, index
- timestamps

Kombinasi kategori dan nama item dibuat unik agar item yang sama tidak terduplikasi di dalam satu kategori. Tipe input yang valid: `sesuai_tidak_sesuai`, `nilai_aktual`, dan `catatan`.

### 4.5 `maintenance_schedules`

- `id`
- `machine_id`, foreign key
- `maintenance_category_id`, foreign key
- `scheduled_date`, date
- `scheduled_time`, nullable time
- `assigned_user_id`, nullable foreign key ke users
- `status`, default `scheduled`
- `notes`, nullable text
- timestamps

Status yang valid: `scheduled`, `pending`, `in_progress`, `completed`, `overdue`, dan `cancelled`. Index gabungan digunakan pada `scheduled_date` dan `status` untuk kebutuhan jadwal harian dan monitoring.

### 4.6 `maintenance_checks`

- `id`
- `maintenance_schedule_id`, foreign key unik
- `machine_id`, foreign key
- `checked_by`, foreign key ke users
- `verified_by`, nullable foreign key ke users
- `check_date`, date
- `start_time`, nullable time
- `end_time`, nullable time
- `overall_status`, default `incomplete`
- `verification_status`, default `waiting`
- `general_note`, nullable text
- `supervisor_note`, nullable text
- `verified_at`, nullable timestamp
- timestamps

Satu jadwal hanya dapat menghasilkan satu pemeriksaan. Status hasil yang valid: `normal`, `has_finding`, dan `incomplete`. Status verifikasi: `waiting`, `verified`, dan `rejected`.

### 4.7 `maintenance_check_details`

- `id`
- `maintenance_check_id`, foreign key
- `maintenance_item_id`, foreign key
- `result_status`, default `belum_diperiksa`
- `actual_value`, nullable string
- `note`, nullable text
- `photo`, nullable string
- timestamps

Kombinasi pemeriksaan dan item dibuat unik. Hasil valid: `sesuai`, `tidak_sesuai`, `tidak_dijadwalkan`, dan `belum_diperiksa`.

### 4.8 `maintenance_findings`

- `id`
- `maintenance_check_id`, foreign key
- `maintenance_check_detail_id`, nullable foreign key
- `machine_id`, foreign key
- `maintenance_item_id`, nullable foreign key
- `reported_by`, foreign key ke users
- `finding_date`, date
- `finding_description`, text
- `follow_up`, nullable text
- `photo`, nullable string
- `status`, default `open`
- `reviewed_by`, nullable foreign key ke users
- `reviewed_at`, nullable timestamp
- `resolved_at`, nullable timestamp
- timestamps

Status valid: `open`, `review`, dan `resolved`. Index gabungan digunakan pada `status` dan `finding_date` untuk monitoring temuan.

## 5. Model dan Relasi

Model baru menggunakan mass-assignment yang eksplisit, cast tanggal yang sesuai, serta return type relasi Eloquent.

- `User`: assigned schedules, performed checks, verified checks, reported findings, dan reviewed findings.
- `Machine`: schedules, checks, dan findings.
- `MaintenanceCategory`: items dan schedules.
- `MaintenanceItem`: category, check details, dan findings.
- `MaintenanceSchedule`: machine, category, assigned user, dan satu check.
- `MaintenanceCheck`: schedule, machine, checking user, verifying user, details, dan findings.
- `MaintenanceCheckDetail`: check, item, dan findings terkait.
- `MaintenanceFinding`: check, optional check detail, machine, optional item, reporter, dan optional reviewer.

Nama relasi dibuat deskriptif untuk membedakan beberapa foreign key yang menunjuk model `User`.

## 6. Seeder

Seeder dipisah berdasarkan tanggung jawab:

1. `UserSeeder` membuat atau memperbarui tiga akun dummy yang diminta.
2. `MaintenanceMasterSeeder` membuat tiga mesin, enam kategori, dan item checklist.
3. `MaintenanceTransactionSeeder` membuat jadwal, pemeriksaan, detail pemeriksaan, dan temuan yang saling konsisten.
4. `DatabaseSeeder` menjalankan ketiganya dengan urutan dependency yang benar.

Akun dummy:

- `admin@example.com` / `password` / Admin / active
- `petugas@example.com` / `password` / Petugas / active
- `supervisor@example.com` / `password` / Supervisor / active

Seeder menggunakan identitas stabil seperti email, serial number, dan nama kategori agar dapat dijalankan ulang tanpa membuat duplikasi. `AdminUserSeeder` berbasis konfigurasi yang sudah ada tetap dipertahankan sebagai seeder opsional dan tidak menjadi bagian dari seeding dummy default.

## 7. Role Middleware dan Matriks Akses

Middleware `RoleMiddleware` menerima satu atau beberapa role dan didaftarkan dengan alias `role` pada `bootstrap/app.php`.

Matriks akses dasar:

| Area | Admin | Petugas | Supervisor |
| --- | --- | --- | --- |
| Dashboard desktop/mobile | Ya | Mobile | Ya |
| Data Mesin | Ya | Tidak | Tidak |
| Master Checklist | Ya | Tidak | Tidak |
| Jadwal | Ya | Ya | Tidak |
| Pemeriksaan Hari Ini | Ya | Ya | Tidak |
| Temuan | Ya | Ya | Ya |
| Riwayat | Ya | Ya | Ya |
| Laporan | Ya | Tidak | Ya |
| User Management | Ya | Tidak | Tidak |

Admin selalu disertakan pada seluruh group role. Route yang tidak memenuhi role menghasilkan respons HTTP 403. Tidak ada perubahan visual pada sidebar atau drawer dalam tahap backend ini.

## 8. Pengujian

Implementasi mengikuti test-first dan mencakup:

- Migration dapat dijalankan pada database kosong.
- Kolom, foreign key, unique constraint, dan index utama tersedia.
- Seluruh relasi Eloquent mengembalikan data yang benar.
- Seeder menghasilkan jumlah data minimum dan relasi transaksi yang valid.
- Ketiga akun dummy dapat login menggunakan password `password`.
- User nonaktif tidak dapat login.
- Redirect login sesuai role.
- Logout mengakhiri session.
- Guest diarahkan ke login.
- Role middleware mengizinkan dan menolak setiap kombinasi penting.
- Route desktop dan mobile tetap dapat dirender oleh role yang berwenang.
- Tidak ada kolom `tanggal_1` sampai `tanggal_31`.

Verifikasi akhir menjalankan `migrate:fresh --seed`, daftar route, pembersihan cache optimasi, formatter Pint, test suite Laravel penuh, dan test JavaScript yang sudah ada.

## 9. Batasan Tahap

Tahap ini tidak membuat CRUD, service penjadwalan otomatis, upload file nyata, verifikasi berbasis form, query laporan bulanan, export PDF/Excel, atau perubahan desain. Kolom foto hanya menyimpan path yang nantinya dikelola oleh fitur upload.

