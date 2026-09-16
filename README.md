# SIGAP Payroll — Aplikasi Slip Gaji Karyawan
Latihan UKK Junior Web Programmer 2026

## 1. Deskripsi Aplikasi
Aplikasi web sederhana untuk login karyawan dan mencetak slip gaji, dengan
perhitungan otomatis:

```
Total Penghasilan = Gaji Pokok + Lembur
Total Potongan    = Pinjaman Karyawan
Gaji Bersih       = Total Penghasilan - Total Potongan
```

## 2. Struktur Folder
```
kode-program/
├── database/
│   └── db_slipgaji.sql    -> Skema database (import ini dulu ke phpMyAdmin!)
├── assets/
│   ├── css/style.css        -> Stylesheet utama seluruh halaman
│   └── js/script.js         -> Kalkulasi live + refresh captcha (AJAX)
├── config/
│   ├── koneksi.php          -> Koneksi PDO ke MySQL
│   ├── data.php             -> Fungsi akses data (query ke database) & fungsi bantu
│   └── auth_guard.php       -> Komponen reusable untuk proteksi halaman (cek login)
├── libs/PHPMailer/          -> Library pihak ketiga (tidak dipakai default lagi, lihat bag. 5)
├── login.php                -> Form login
├── proses_login.php         -> Validasi login (server-side)
├── logout.php                -> Hapus session
├── captcha.php               -> Endpoint AJAX generate captcha
├── dashboard.php             -> Halaman utama setelah login: tabel gaji bersih + aksi
├── form_gaji.php             -> Halaman input data gaji (dibuka lewat tombol "+ Tambah")
├── cetak_slip.php            -> Hasil slip gaji (hitung ulang di server, simpan ke DB, cetak PDF)
├── riwayat.php                -> Riwayat slip gaji yang pernah dicetak (dari database)
├── kirim_email.php           -> Buka jendela compose Gmail terisi otomatis (tanpa SMTP)
├── kirim_wa.php               -> Kirim slip gaji via WhatsApp (link wa.me)
└── README.md                 -> Dokumen ini
```

## 2a. Database
Aplikasi ini pakai **MySQL** (lewat PDO), dengan 4 tabel:

| Tabel | Fungsi |
|---|---|
| `users` | Akun login karyawan. Password disimpan ter-**hash** (bcrypt), bukan teks biasa. |
| `riwayat_gaji` | Histori setiap slip gaji yang pernah dihitung/dicetak, terhubung ke `users` lewat `user_id` (foreign key). |
| `periode_gaji` | Rentang periode (awal-akhir) yang dibuat lewat fitur "Kelola Periode Gaji" di dashboard. |
| `periode_karyawan` | Snapshot data karyawan (nama/nik/jabatan/gaji_pokok) yang dipilih untuk suatu periode. |

**Wajib dilakukan sebelum menjalankan aplikasi:**
1. Buka phpMyAdmin (`http://localhost/phpmyadmin`).
2. Klik tab **Import**, pilih file `database/db_slipgaji.sql`, lalu klik **Go**.
   Ini otomatis membuat database `db_slipgaji` beserta ke-4 tabel di atas dan 1 akun demo.
   **Cukup file ini saja** — file `migrasi_periode.sql` dan `migrasi_periode_v2.sql` di
   folder yang sama sudah digabung ke sini dan cuma disimpan sebagai arsip, tidak
   perlu dijalankan lagi untuk setup baru.
3. Cek `config/koneksi.php` — default-nya sudah cocok dengan setting XAMPP standar
   (`host: localhost`, `user: root`, `password: kosong`). Ubah jika perlu.

## 3. Alur Aplikasi
1. User membuka `login.php`, memasukkan email & password.
2. `proses_login.php` mengecek kecocokan data di `config/data.php`.
   Jika berhasil, session `is_login` diset `true` lalu diarahkan ke
   `dashboard.php`.
3. Halaman yang butuh login (`dashboard.php`, `form_gaji.php`, `cetak_slip.php`,
   `kirim_email.php`, `kirim_wa.php`) selalu memanggil `require
   config/auth_guard.php` di baris pertama — jika belum login, otomatis
   dilempar kembali ke `login.php`.
4. **`dashboard.php`** adalah halaman utama setelah login: menampilkan tabel
   riwayat slip gaji (dari `riwayat_gaji`) dengan kolom **Nama**, **Jabatan**,
   **Gaji Bersih**, dan **Aksi**. Di atas tabel ada tombol **"+ Tambah"** yang
   akan memunculkan pop-up modal **"Pilih Periode"** (memilih Bulan & Tahun
   penggajian dengan cut-off tanggal 25, contoh `25 Nov – 25 Des 2026`) sebelum
   masuk ke halaman `form_gaji.php`. Setiap baris di tabel punya 4 aksi:
   - **Cetak PDF** — membuka `cetak_slip.php?id=..` di tab baru dan langsung
     memicu dialog cetak bawaan browser (`window.print()`), yang lalu bisa
     disimpan sebagai PDF.
   - **Kirim Email** — membuka modal untuk mengisi alamat email **secara
     manual** (diisi admin, bukan otomatis dari akun user), lalu
     `kirim_email.php` menyiapkan pesan (ringkasan slip + link cetak PDF-nya)
     dan membuka jendela **compose Gmail** (mail.google.com) di tab baru
     dengan To/Subjek/Isi sudah terisi otomatis — admin tinggal mengecek lalu
     klik **Send**.
   - **Kirim WhatsApp** — membuka modal untuk mengisi nomor WhatsApp **secara
     manual**, lalu `kirim_wa.php` menyiapkan pesan (ringkasan slip + link
     cetak PDF-nya) dan membuka WhatsApp Web/App lewat link resmi `wa.me`
     dengan pesan itu sudah terisi — persis seperti alur kirim email, hanya
     medianya WhatsApp.
5. Di `form_gaji.php`, user mengisi Gaji Pokok, Lembur, Pinjaman Karyawan, dan
   captcha. `assets/js/script.js` menghitung total secara *live* di browser
   supaya user langsung melihat estimasi Gaji Bersih.
6. Saat disubmit, `cetak_slip.php`:
   - Mencocokkan jawaban captcha dengan yang tersimpan di `$_SESSION` (bukan
     dari input client) agar tidak bisa dimanipulasi lewat DevTools.
   - Menghitung ULANG semua total di sisi server (server-side calculation) —
     ini praktik wajib, karena nilai dari JavaScript tidak boleh dipercaya
     100% oleh server.
   - Menyimpan slip ke tabel `riwayat_gaji` dan menampilkannya, dengan tombol
     **Cetak PDF**, **Kirim Email**, dan **Kirim WhatsApp** (sama seperti di
     `dashboard.php`).
7. `kirim_email.php` **tidak** mengirim email otomatis dari server (tidak
   lagi memakai SMTP/PHPMailer, karena sering gagal karena App
   Password/2FA/firewall). Sebagai gantinya, ia membuka jendela compose
   Gmail lewat link resmi `mail.google.com/mail/?view=cm` dengan field
   To/Subjek/Isi sudah terisi. `kirim_wa.php` melakukan hal serupa lewat
   link `wa.me` (WhatsApp Web/App) — keduanya menyiapkan draf pesan, admin
   yang menekan tombol kirim terakhir.

   > Folder `libs/PHPMailer/` masih ada di proyek ini kalau suatu saat ingin
   > kembali ke pengiriman email otomatis dari server (lihat riwayat kode
   > sebelumnya), tapi **tidak dipakai** oleh alur default saat ini.

## 4. Cara Menjalankan (XAMPP / LAMPP)
1. Jalankan **Apache** dan **MySQL** dari XAMPP/LAMPP Control Panel.
2. Import database sesuai langkah di bagian 2a di atas.
3. Salin folder `kode-program` ke dalam `htdocs` (XAMPP) atau `htdocs`/`www`
   (LAMPP), misalnya jadi `htdocs/sigap-payroll`.
4. Buka browser ke `http://localhost/sigap-payroll/login.php`.
5. Login dengan akun demo:
   - Email: `admin@gmail.com`
   - Sandi: `admin123`

Jika muncul pesan "Koneksi database gagal", berarti langkah import database
belum dilakukan, atau setting di `config/koneksi.php` tidak cocok dengan
MySQL di komputer Anda.

## 5. Fitur Kirim Email & Kirim WhatsApp
Kedua fitur ini **tidak butuh konfigurasi apa pun** (tidak perlu SMTP, App
Password, atau API key). Saat tombol "Kirim Email" / "Kirim WhatsApp" di
`dashboard.php` (atau `cetak_slip.php`) ditekan dan alamat/nomor tujuan
diisi manual di modal:
- **Kirim Email** membuka tab baru berisi jendela compose Gmail
  (`mail.google.com`) dengan To/Subjek/Isi sudah terisi otomatis dari data
  slip yang dipilih. Admin tinggal klik **Send**.
- **Kirim WhatsApp** membuka tab baru berisi WhatsApp Web/App (`wa.me`)
  dengan pesan sudah terisi. Admin tinggal klik **Kirim**.

Karena pengiriman terakhir dilakukan manual oleh admin lewat Gmail/WhatsApp
itu sendiri, tidak ada kredensial server yang perlu disiapkan — cukup admin
sudah login ke akun Gmail/WhatsApp Web di browser yang sama.

## 6. Coding Standard yang Diterapkan
- Penamaan variabel & fungsi memakai `camelCase` (PHP) dan deskriptif
  (`$totalPenghasilan`, bukan `$tp`).
- Setiap file diawali docblock komentar menjelaskan tujuan file.
- Validasi input selalu dilakukan di server (tidak hanya mengandalkan
  `required` di HTML atau validasi JavaScript).
- Autentikasi memakai session PHP standar; proteksi halaman memakai satu
  komponen reusable (`auth_guard.php`) supaya tidak duplikasi kode.
- Output ke HTML memakai `htmlspecialchars()` untuk mencegah XSS.
- Perhitungan uang menggunakan fungsi terpusat `rupiah()` di `config/data.php`
  supaya format Rupiah konsisten di semua halaman.

## 7. Debugging
Jika halaman blank/error, aktifkan tampilan error PHP untuk pengecekan
(nonaktifkan lagi saat sudah selesai):
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```
Error pengiriman email dicatat lewat `error_log()` di `kirim_email.php` dan
bisa dilihat di `xampp/apache/logs/error.log`.
