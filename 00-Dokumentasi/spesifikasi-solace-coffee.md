# ☕ Digital Twin Solace Coffee
> **System Requirements & Architecture Specification**  
> *Representasi Digital & Platform Pemesanan Cafe Modern*

---

## 📜 1. Pengenalan Proyek (Introduction)

| Parameter | Detail Spesifikasi |
| :--- | :--- |
| **Nama Proyek** | **Digital Twin Solace Coffee** |
| **Konsep Utama** | Replikasi dan pemodelan digital (*Digital Twin*) dari sistem operasional pemesanan cafe fisik ke dalam platform web responsif (Desktop & Mobile). |
| **Masalah yang Diatasi** | Mengurangi antrean pemesanan manual, meningkatkan efisiensi transaksi, serta menyediakan katalog menu interaktif dan transparan. |
| **Tujuan Proyek** | Membangun ekosistem pemesanan kopi digital berbasis *full-stack* yang praktis bagi pelanggan dan terpusat bagi pengelola cafe. |

---

## 🔄 2. Metodologi & Manajemen Tim (Agile/Scrum)

### 📌 Pendekatan Metodologi
Proyek ini menggunakan kerangka kerja **Agile dengan pendekatan Scrum**. Alasannya, metode ini fleksibel terhadap perubahan kebutuhan dan memecah proyek besar menjadi siklus pendek (**Sprint**, 1–2 minggu). Setiap fitur (desain antarmuka, API, basis data) dikembangkan, diuji, dan dievaluasi secara bertahap.

### 👥 Pembagian Peran Tim
| Peran | Penanggung Jawab | Tanggung Jawab Utama |
| :--- | :--- | :--- |
| **Scrum Master** | *[Nama Anggota]* | Mengelola alur kerja dan komunikasi tim, memastikan Sprint sesuai target, memfasilitasi *daily standup*, dan menangani hambatan antar anggota. |
| **UI/UX Designer** | *[Nama Anggota]* | Membuat *wireframe*, *mockup* UI Desktop dan Mobile, serta *user flow* yang mudah digunakan pelanggan. |
| **Frontend Developer** | *[Nama Anggota]* | Mengubah desain menjadi antarmuka interaktif (HTML, CSS, JavaScript, atau React.js) yang responsif. |
| **Backend Developer** | *[Nama Anggota]* | Membangun *RESTful API* (Express.js/Laravel), logika autentikasi, keranjang, pembayaran, dan skema basis data MySQL. |

> 💡 Jika anggota kelompok lebih sedikit dari jumlah peran, satu orang dapat memegang dua peran yang beririsan.

### 📅 Perencanaan Sprint
| Sprint | Fokus Pengerjaan | Target Deliverables |
| :---: | :--- | :--- |
| **Sprint 1** | Analisis kebutuhan, *user story*, *flowchart*, *wireframe desktop*, *ERD*, dan arsitektur sistem | Dokumen desain, alur, dan *blueprint* teknis |
| **Sprint 2** | *Slicing* frontend UI, setup RESTful API, implementasi database MySQL | Prototipe komponen UI dan endpoint API dasar |
| **Sprint 3** | Integrasi Payment Gateway, Admin Panel, testing, dan deployment | Aplikasi *full-stack* siap rilis |

---

## 🎯 3. Analisis Kebutuhan Sistem (System Requirements)

### 👤 Aktor Sistem
| Aktor | Deskripsi Peran & Hak Akses |
| :---: | :--- |
| **Pelanggan** | Registrasi/login, melihat menu, memilih produk, checkout, membayar, dan melihat status pesanan. |
| **Admin Cafe** | Mengakses *Admin Panel* untuk mengelola data menu serta memantau dan mengubah status pesanan. |
| **Payment Gateway** | Sistem eksternal (QRIS, E-Wallet, Bank) yang memproses pembayaran. Bukan pengguna, sehingga tidak dibuat *user story*. |

### ⚙️ Matriks Kebutuhan Fungsional
| Modul Fitur | Kebutuhan Fungsional | Aktor | User Story |
| :--- | :--- | :---: | :---: |
| **Autentikasi** | Registrasi akun baru dan *login* dengan email & password. Sistem mengarahkan pengguna sesuai `role`. | Pelanggan, Admin | US-01, 02, 14, 15, 20 |
| **Eksplorasi Menu** | Katalog menu dengan gambar, deskripsi, harga, dan filter kategori. | Pelanggan | US-03, 04 |
| **Kategori Produk** | `Kopi`, `Non-Kopi`, `Snack`, `Makanan Berat`, `Dessert`. | Pelanggan | US-04 |
| **Informasi Cafe** | Peta lokasi dan jam operasional. | Pelanggan | US-13 |
| **Keranjang & Checkout** | Tambah/ubah/hapus item keranjang dan pemrosesan pesanan. | Pelanggan | US-05, 06, 07 |
| **Pembayaran Digital** | QRIS, E-Wallet (DANA, OVO, GoPay), dan Transfer Bank, termasuk konfirmasi dan penanganan gagal bayar. | Pelanggan | US-08, 09, 10, 11 |
| **Status Pesanan** | Pelanggan melihat status pesanan. Admin memantau, mencari, dan mengubah status. | Pelanggan, Admin | US-12, 17, 18, 19 |
| **Manajemen Katalog** | Pengelolaan data menu (*Create, Read, Update, Delete*) via *Admin Panel*. | Admin | US-16 |

---

## 📝 4. User Story

> Disusun dari diagram rancangan (Context, Flowchart, Activity, Sequence, Class, ERD, Arsitektur, Wireframe). Fitur di luar diagram tidak ditambahkan.

### A. Pelanggan

| ID | User Story | Kriteria Penerimaan | Sumber |
|---|---|---|---|
| US-01 | Sebagai pelanggan, saya ingin **registrasi akun** agar bisa memesan. | Data tersimpan di `USERS` dengan role "pelanggan". | Flowchart, Class |
| US-02 | Sebagai pelanggan, saya ingin **login** agar bisa mengakses sistem. | Kredensial valid: tampil daftar menu. Salah: pesan error (401) dan bisa input ulang. | Sequence, Activity, Flowchart |
| US-03 | Sebagai pelanggan, saya ingin **melihat katalog menu** agar tahu apa yang bisa dipesan. | Kartu menu menampilkan gambar, nama, deskripsi, dan harga. | Wireframe 1, Class |
| US-04 | Sebagai pelanggan, saya ingin **memfilter menu per kategori** agar cepat menemukan menu. | Katalog hanya menampilkan menu sesuai kategori yang dipilih. | Wireframe 1, ERD |
| US-05 | Sebagai pelanggan, saya ingin **menambah menu ke keranjang** agar bisa memesan beberapa menu. | Tombol "+ Keranjang" menyimpan ke `KERANJANG`. Ikon keranjang menampilkan jumlah item. | Wireframe 1, Activity, Class |
| US-06 | Sebagai pelanggan, saya ingin **mengubah jumlah dan menghapus item** agar pesanan sesuai. | Tombol +/− dan "Hapus" memperbarui subtotal dan total otomatis. | Wireframe 2, Class |
| US-07 | Sebagai pelanggan, saya ingin **checkout** agar pesanan tercatat. | Halaman checkout berisi nama, No. HP, alamat, ringkasan. Sistem membuat `PESANAN` dan `DETAIL_PESANAN` berstatus "Menunggu Pembayaran". | Wireframe 2–3, Sequence, ERD |
| US-08 | Sebagai pelanggan, saya ingin **memilih metode pembayaran** agar bisa membayar dengan nyaman. | Pilihan: QRIS, E-Wallet, Transfer Bank. QRIS menampilkan kode QR dan panduan. | Wireframe 3, Sequence |
| US-09 | Sebagai pelanggan, saya ingin **membayar pesanan** agar pesanan diproses. | "Bayar Sekarang" mengirim total dan metode ke Payment Gateway. Jika berhasil, status menjadi "Diproses". | Sequence, Activity, Context |
| US-10 | Sebagai pelanggan, saya ingin **menerima konfirmasi pesanan** agar yakin pembayaran berhasil. | Tampil nomor pesanan dan bukti pembayaran. | Sequence, Activity, Flowchart |
| US-11 | Sebagai pelanggan, saya ingin **diberi tahu jika pembayaran gagal** agar bisa mengulang atau membatalkan. | Tampil pesan gagal. Pilihan: bayar ulang atau batalkan (status "Dibatalkan"). | Sequence, Flowchart, Activity |
| US-12 | Sebagai pelanggan, saya ingin **melihat status pesanan** agar tahu progresnya. | Status terlihat (Menunggu Pembayaran, Diproses, Selesai). | Class, Context, Arsitektur |
| US-13 | Sebagai pelanggan, saya ingin **melihat lokasi dan jam operasional** agar tahu kapan dan di mana berkunjung. | Halaman utama menampilkan alamat, telepon, peta, dan jam buka. | Wireframe 1, Context |
| US-14 | Sebagai pelanggan, saya ingin **logout** agar akun aman. | Sesi berakhir setelah logout. | Class, Flowchart |

### B. Admin

| ID | User Story | Kriteria Penerimaan | Sumber |
|---|---|---|---|
| US-15 | Sebagai admin, saya ingin **login ke dashboard admin** agar bisa mengelola toko. | Sistem cek `USERS.role`. Jika "admin", diarahkan ke dashboard. | Flowchart, ERD, Arsitektur |
| US-16 | Sebagai admin, saya ingin **mengelola data menu (CRUD)** agar katalog selalu terbaru. | Perubahan tersimpan di `MENU` dan tampil di katalog pelanggan. | Flowchart, Class, Wireframe 4 |
| US-17 | Sebagai admin, saya ingin **melihat daftar pesanan masuk** agar bisa memantau transaksi. | Daftar menampilkan No. Pesanan, Pelanggan, Tanggal, Total, Status. | Wireframe 4, Context |
| US-18 | Sebagai admin, saya ingin **mencari dan memfilter pesanan** agar cepat menemukan pesanan. | Cari by no. pesanan/pelanggan, filter status, ada pagination. | Wireframe 4 |
| US-19 | Sebagai admin, saya ingin **mengubah status pesanan** agar pelanggan tahu progresnya. | Tombol "Ubah Status Pesanan" mengubah status dan terlihat di sisi pelanggan. | Wireframe 4, Class, Context |
| US-20 | Sebagai admin, saya ingin **logout** agar akses dashboard aman. | Logout lewat menu sidebar. | Flowchart, Wireframe 4 |

---

## 🎨 5. Perancangan Sistem (Design & Architecture)

### 🔀 5.1 Flowchart Pemesanan
| Langkah | Aktivitas Pengguna / Sistem | Output / Status Sistem | Story |
| :---: | :--- | :--- | :---: |
| **1** | Pengguna membuka web **Solace Coffee** | Halaman utama tampil | US-03 |
| **2** | Registrasi atau login. Sistem cek `USERS.role` | Pelanggan ke katalog, admin ke dashboard | US-01, 02, 15 |
| **3** | Memilih produk dan menambah ke keranjang | Item masuk `KERANJANG` | US-05, 06 |
| **4** | Checkout dan memilih metode pembayaran | `PESANAN` & `DETAIL_PESANAN` dibuat, status "Menunggu Pembayaran" | US-07, 08 |
| **5** | Pembayaran diproses via Payment Gateway | Sukses: status "Diproses" dan konfirmasi pesanan. Gagal: opsi bayar ulang atau batal | US-09, 10, 11 |

### 🖥️ 5.2 Wireframe Desktop
| No. | Halaman | Elemen Utama | Story |
| :---: | :--- | :--- | :---: |
| **1** | Landing Page & Katalog Menu | Navbar (logo, navigasi, keranjang, login), filter kategori, kartu menu, lokasi toko, peta, jam operasional | US-03, 04, 05, 13 |
| **2** | Keranjang | Daftar item dengan qty (+/−), subtotal, tombol Hapus, ringkasan pesanan, "Lanjut ke Checkout" | US-06, 07 |
| **3** | Checkout & Pembayaran | Form nama, No. HP, alamat, pilihan metode bayar, kode QRIS, "Bayar Sekarang" | US-07, 08, 09 |
| **4** | Dashboard Admin | Sidebar (Kelola Data Menu, Daftar Pesanan Masuk, Logout), pencarian, filter status, tabel pesanan, "Ubah Status Pesanan" | US-16 – 20 |

### 🏗️ 5.3 Arsitektur Teknologi

| Layer | Teknologi | Fungsi |
| :--- | :--- | :--- |
| **Client (Frontend)** | Website Pelanggan dan Dashboard Admin (React.js / HTML5, CSS3, JS) | Antarmuka responsif untuk pelanggan dan admin |
| **Komunikasi** | HTTPS · REST API | Jalur data antara client dan server |
| **Backend** | REST API Server (Express.js / Laravel) | Logika bisnis dan autentikasi |
| **Database** | MySQL (6 tabel) | Penyimpanan data terstruktur |
| **External Services** | Payment Gateway API (QRIS, DANA, OVO, Bank) | Pemrosesan pembayaran pihak ketiga |

### 🗄️ 5.4 Relasi Basis Data (ERD)

| Tabel | Atribut Utama | Relasi |
| :--- | :--- | :--- |
| **`USERS`** | `id` (PK), `nama`, `email`, `password`, `no_hp`, `alamat`, `role` ('pelanggan' / 'admin') | 1:N ke `KERANJANG` dan `PESANAN` |
| **`MENU`** | `id` (PK), `nama_menu`, `kategori`, `harga`, `deskripsi`, `gambar` | 1:N ke `KERANJANG` dan `DETAIL_PESANAN` |
| **`KERANJANG`** | `id` (PK), `user_id` (FK), `menu_id` (FK), `jumlah` | Satu baris per item keranjang |
| **`PESANAN`** | `id` (PK), `user_id` (FK), `tanggal`, `total_harga`, `status` | 1:N ke `DETAIL_PESANAN`, 1:1 ke `PEMBAYARAN` |
| **`DETAIL_PESANAN`** | `id` (PK), `pesanan_id` (FK), `menu_id` (FK), `jumlah`, `subtotal` | Rincian item per pesanan |
| **`PEMBAYARAN`** | `id` (PK), `pesanan_id` (FK), `metode_pembayaran`, `jumlah_bayar`, `status_pembayaran`, `waktu_bayar` | 1:1 ke `PESANAN` |

---

## ⚠️ 6. Catatan Konsistensi Antar Diagram

1. **Metode pembayaran:** Flowchart memuat PayLater dan GoPay, wireframe hanya QRIS, E-Wallet (DANA/OVO), dan Transfer Bank. PayLater tidak dimasukkan ke kebutuhan.
2. **Kategori menu:** Flowchart menyebut 5 kategori, sedangkan filter di wireframe hanya Semua, Makanan, Minuman, Dessert.
3. **Pembayaran gagal:** Activity langsung kembali ke checkout, Flowchart punya opsi bayar ulang/batal. Status "Dibatalkan" belum ada di wireframe admin.
4. **Registrasi:** Ada di Flowchart dan Class, tetapi tidak di Sequence, Activity, maupun wireframe.
5. **Relasi Keranjang:** Class 1:1 (satu keranjang per pelanggan), ERD 1:N (satu baris per item).
6. **Status "Selesai":** Ada di wireframe admin, tetapi belum ada alur yang menjelaskan kapan statusnya berubah.
7. **Alamat dan No. HP:** Diminta di checkout, tetapi ERD menyimpannya di `USERS`, bukan `PESANAN`.

---

<p align="center">
  <i>Developed with ☕ for <b>Digital Twin Solace Coffee Project</b></i>
</p>
