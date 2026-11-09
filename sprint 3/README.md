# Solace Coffee (Sprint 3)

Website pemesanan makanan dan minuman Solace Coffee: pelanggan dapat registrasi dan login, melihat menu,
mengelola keranjang, checkout, memilih metode pembayaran (QRIS, DANA, OVO, GoPay, Transfer Bank), dan melihat
status pesanan. Admin dapat mengelola menu dan memproses pesanan.

Arsitektur: **PHP native** tanpa framework, memakai PDO untuk MySQL dan REST API di `api.php`. Tampilan tetap berupa
HTML/CSS/JavaScript dan dilayani Apache melalui XAMPP.

## Struktur folder

```
solace-coffee/
├── api.php                     # REST API dan aturan bisnis
├── config.php                  # konfigurasi lingkungan dan koneksi PDO
├── seed.php                    # data awal: akun admin + 38 menu
├── .htaccess                   # routing API dan penyajian frontend melalui Apache
├── database/
│   └── schema.sql              # 5 tabel: users, menu, pesanan, detail_pesanan, pembayaran
└── frontend/public/
    ├── index.html, app.js       # halaman pelanggan
    ├── admin.html, admin.js     # panel admin
    └── styles.css
```

## Menjalankan

Prasyarat: XAMPP dengan PHP 8.1+ (ekstensi PDO MySQL aktif) dan MySQL/MariaDB.

1. Jalankan Apache dan MySQL dari XAMPP, lalu impor skema database:
   ```
   mysql -u root -p < database/schema.sql
   ```
2. Atur koneksi database (default XAMPP: `root`, tanpa password). Opsional, buat file `.env` di folder ini:
   ```
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_NAME=solace_coffee
   DB_USER=root
   DB_PASSWORD=
   JWT_SECRET=ganti-dengan-secret-acak-yang-panjang
   ```
3. Isi data awal (akun admin dan menu) dari Command Prompt pada folder proyek:
   ```
   php seed.php
   ```
4. Buka `http://localhost/solace-coffee-sprint3/` (pelanggan) dan
   `http://localhost/solace-coffee-sprint3/admin.html` (admin). URL dengan subfolder
   `solace-coffee/` juga tetap dapat digunakan.

Akun admin bawaan: `admin@solace.coffee` dengan password `Admin123!`. **Ganti setelah pertama kali login**.
Saat `php seed.php` dijalankan kembali, sandi akun admin yang sudah ada akan disetel ulang ke nilai
`ADMIN_PASSWORD` (default `Admin123!`). Tentukan `ADMIN_EMAIL` dan `ADMIN_PASSWORD` sebelum menjalankannya
untuk memakai kredensial lain. Akun non-admin dengan email yang sama tidak akan diubah dan akan menyebabkan seed gagal.

## Endpoint API

| Use case | Method dan path | Akses |
|---|---|---|
| UC-01 Registrasi | `POST /api/users/register` | publik |
| UC-02 Login | `POST /api/users/login` | publik |
| (profil) | `GET /api/users/me` | login |
| UC-03 Lihat Menu | `GET /api/menu`, `GET /api/menu?kategori=`, `GET /api/menu/:id` | publik |
| UC-08 Kelola Menu | `POST /api/menu`, `PUT /api/menu/:id`, `DELETE /api/menu/:id` | admin |
| UC-05 Checkout | `POST /api/pesanan` | login |
| UC-06 Pembayaran | `POST /api/pembayaran` | login (pemilik pesanan) |
| UC-09 Daftar Pesanan | `GET /api/pesanan` | admin |
| UC-10 Ubah Status | `PUT /api/pesanan/:id/status` | admin |
| UC-11 Status Pesanan | `GET /api/pesanan/user/:id` | login (pemilik atau admin) |
| (batal) | `PUT /api/pesanan/:id/batal` | login (pemilik pesanan) |

UC-04 Kelola Keranjang dijalankan di browser (tanpa API), dan UC-07 Lokasi berupa halaman statis.

## Catatan penting

- Pembayaran masih **simulasi** (di `api.php`). Untuk produksi, hubungkan API pembayaran ke payment gateway
  sungguhan (misalnya Midtrans atau Xendit).
- Total harga selalu dihitung server dari harga di database, bukan dari data yang dikirim browser.
- Password disimpan sebagai hash bcrypt. Ganti `JWT_SECRET` dengan teks acak yang panjang sebelum dipakai publik.
- Menu yang dihapus hanya disembunyikan (soft delete) supaya riwayat pesanan lama tetap utuh.
