# ☕ Digital Twin Solace Coffee
> **System Requirements & Architecture Specification**  
> *Representasi Digital & Platform Pemesanan Cafe Modern*

---

## 📜 1. Pengenalan Proyek (Introduction)

### 🫘 Latar Belakang & Tujuan
| Parameter | Detail Spesifikasi |
| :--- | :--- |
| **Nama Proyek** | **Digital Twin Solace Coffee** |
| **Konsep Utama** | Replikasi dan pemodelan digital (*Digital Twin*) dari sistem operasional pemesanan cafe fisik ke dalam platform web responsif (Desktop & Mobile). |
| **Masalah yang Diatasi** | Mengurangi antrean pemesanan manual, meningkatkan efisiensi transaksi, serta menyediakan katalog menu interaktif dan transparan. |
| **Tujuan Proyek** | Membangun ekosistem pemesanan kopi digital berbasis *full-stack* yang praktis bagi pelanggan dan terpusat bagi pengelola cafe. |

---

## 🔄 2. Metodologi & Manajemen Tim (Agile/Scrum)

### 📌 Penjelasan Pendekatan Metodologi
Dalam pengembangan proyek **Digital Twin Solace Coffee**, kami menggunakan kerangka kerja **Agile dengan pendekatan Scrum**. Alasan penggunaan metode ini adalah fleksibilitasnya dalam menghadapi perubahan kebutuhan serta kemampuannya memecah proyek besar menjadi siklus pengerjaan pendek yang disebut **Sprint** (biasanya berdurasi 1–2 minggu). 

Dengan metodologi ini, setiap fitur (mulai dari desain antarmuka, pembuatan API, hingga basis data) dapat dikembangkan, diuji, dan dievaluasi secara bertahap dan berkelanjutan hingga menghasilkan aplikasi *full-stack* yang siap rilis.

### 👥 Strategi & Pembagian Peran Tim (Role Delegation)
Struktur peran di bawah ini dirancang berdasarkan pembagian kompetensi fungsional agar pembagian tugas dalam kelompok lebih terarah. Silakan sesuaikan nama penanggung jawab sesuai dengan anggota kelompok Anda:

| Peran (Role) | Penanggung Jawab | Tanggung Jawab Utama & Alasan Pembagian |
| :--- | :--- | :--- |
| **Scrum Master** | *[Nama Anggota]* | **Fokus:** Pengelolaan alur kerja dan komunikasi tim.<br>**Tugas:** Memastikan jalannya Sprint sesuai target waktu, memfasilitasi diskusi harian (*daily standup*), serta menjadi penghubung jika ada hambatan teknis antar anggota. |
| **UI/UX Designer** | *[Nama Anggota]* | **Fokus:** Tampilan visual dan pengalaman pengguna.<br>**Tugas:** Menerjemahkan ide konsep cafe ke dalam bentuk *wireframe*, *mockup* UI untuk perangkat Desktop dan Mobile, serta merancang alur navigasi (*User Flow*) agar mudah digunakan pelanggan. |
| **Frontend Developer** | *[Nama Anggota]* | **Fokus:** Sisi klien (*Client-Side*).<br>**Tugas:** Mengubah desain visual dari UI/UX Designer menjadi kode program yang interaktif menggunakan HTML, CSS, JavaScript, atau framework modern (seperti React.js) yang terhubung responsif di berbagai layar. |
| **Backend Developer** | *[Nama Anggota]* | **Fokus:** Sisi server dan manajemen data.<br>**Tugas:** Membangun *RESTful API* (menggunakan Express.js/Laravel), mengatur logika bisnis autentikasi serta sistem keranjang/pembayaran, dan merancang struktur skema basis data MySQL. |

> 💡 *Catatan Tips:* Jika jumlah anggota kelompok lebih sedikit dari jumlah peran yang ada, satu orang dapat memegang dua peran yang beririsan (misalnya: Backend Developer juga merangkap sebagai pengelola basis data).

### 📅 Perencanaan Sprint (Sprint Planning)
Pengerjaan proyek dibagi ke dalam 3 tahapan *Sprint* utama agar beban kerja terdistribusi dengan rapi:

| Sprint | Fokus Pengerjaan | Target Deliverables (Hasil Akhir) |
| :---: | :--- | :--- |
| **Sprint 1** | Analisis Kebutuhan, *Flowchart*, *Wireframe Desktop*, *ERD*, & *Arsitektur Sistem* | Dokumen Desain, Perancangan Alur, & Blueprint Teknis Sistem |
| **Sprint 2** | Slicing Frontend UI, Setup RESTful API, & Implementasi Database MySQL | Prototipe Komponen UI yang berjalan dan Endpoint API dasar |
| **Sprint 3** | Integrasi Payment Gateway, Admin Panel, Sistem Testing, & Deployment | Aplikasi *Full-Stack* Digital Twin Solace Coffee Siap Rilis |

---

## 🎯 3. Analisis Kebutuhan Sistem (System Requirements)

### 👤 Aktor Sistem
| Aktor | Deskripsi Peran & Hak Akses |
| :---: | :--- |
| **Pelanggan (User)** | Mengakses platform web, melakukan registrasi/login, mengeksplorasi menu, memilih produk, checkout, dan membayar. |
| **Admin Cafe** | Mengakses *Admin Panel* khusus yang terhubung ke API terpusat untuk memantau dan mengelola data katalog menu. |

### ⚙️ Matriks Kebutuhan Fungsional (Fitur Aplikasi)
| Modul Fitur | Kebutuhan Fungsional | Akses Aktor |
| :--- | :--- | :---: |
| **Autentikasi** | Registrasi akun baru serta autentikasi *Login* menggunakan email & password. | Pelanggan |
| **Eksplorasi Menu** | Navigasi halaman utama (*Home*, *Menu*, *Lokasi*, *Login*), *Hero Banner*, & filter kategori. | Pelanggan |
| **Kategori Produk** | Pengelompokan produk: `Kopi`, `Non-Kopi`, `Snack`, `Makanan Berat`, dan `Dessert`. | Pelanggan |
| **Informasi Cafe** | Penampilan peta lokasi fisik cafe terintegrasi dan informasi jam operasional *live*. | Pelanggan |
| **Keranjang & Checkout**| Pemilihan item produk, manajemen keranjang belanja, dan pemrosesan pesanan. | Pelanggan |
| **Pembayaran Digital**| Integrasi metode pembayaran: **QRIS**, **DANA**, **OVO**, **GoPay**, dan **Transfer Bank**. | Pelanggan |
| **Manajemen Katalog** | Fitur Pengelolaan Data Menu (*Create, Read, Update, Delete*) via *Admin Panel*. | Admin |

---

## 🎨 4. Perancangan Sistem secara Visual & Logika (Design & Architecture)

### 🔀 1. Flowchart Pemesanan (Alur Transaksi Digital)
| Langkah | Aktivitas Pengguna / Sistem | Output / Status Sistem |
| :---: | :--- | :--- |
| **1** | Pengguna membuka platform web **Solace Coffee** | Menampilkan Halaman Utama |
| **2** | Verifikasi Akun (Registrasi jika belum ada / Login jika sudah ada) | Sesi Akun Terverifikasi |
| **3** | Mengakses Katalog, memilih produk, & menambah ke keranjang | Item Masuk Keranjang Belanja |
| **4** | Melakukan Checkout & Memilih Metode Pembayaran Digital | Form Pembayaran Terbentuk |
| **5** | Pemrosesan Pembayaran (QRIS / E-Wallet / Bank Transfer) | **Konfirmasi Pesanan** (jika sukses) / Retransaksi (jika gagal) |

---

### 🖥️ 2. Wireframe & Mockup UI Desktop Layout
| Komponen Layout | Elemen Visual & Fungsi |
| :--- | :--- |
| **Header / Navbar** | Logo Brand Solace Coffee, Menu Navigasi (*Home*, *Menu*, *Lokasi*), & Tombol Login/Akun. |
| **Hero Banner** | Visual banner cafe utama dilengkapi tombol *Call-to-Action* ("Lihat Menu"). |
| **Bilah Filter** | Filter interaktif kategori: `Kopi` \| `Non-Kopi` \| `Snack` \| `Makanan Berat` \| `Dessert`. |
| **Product Grid** | Grid kartu produk (Menu 1 – Menu 4) yang menampilkan foto, nama produk, & harga. |
| **Info Visitor** | Area peta lokasi fisik cafe dan informasi jam operasional. |
| **Footer** | Informasi kontak cafe, tautan media sosial, dan catatan *copyright*. |

---

### 🏗️ 3. Arsitektur Teknologi & Basis Data (ERD)

#### 💻 Stack Arsitektur Sistem
| Layer Arsitektur | Teknologi / Framework | Fungsi dalam Digital Twin |
| :--- | :--- | :--- |
| **Client Side (Frontend)** | React.js / HTML5, CSS3, JS (Desktop & Mobile) | Tampilan antarmuka web yang responsif dan interaktif. |
| **Communication Layer** | RESTful HTTP Requests | Jalur komunikasi data antara aplikasi *Client* dan *Server*. |
| **Server Side (Backend API)**| Express.js / Laravel | Mengelola autentikasi, logika pemesanan, dan aturan bisnis. |
| **Database Engine** | MySQL Relational Database | Menyimpan data terstruktur secara aman dan konsisten. |
| **Management Interface** | Admin Panel Web | Panel kontrol khusus pengelola untuk update katalog menu. |

#### 🗄️ Relasi Basis Data (Entity Relationship Diagram - ERD)
| Nama Tabel | Atribut Utama | Relasi & Deskripsi |
| :--- | :--- | :--- |
| **`USERS`** | `id` (PK), `nama`, `email`, `password` | **1 to N** ke `PESANAN` *(Satu user dapat membuat banyak pesanan)* |
| **`MENU`** | `id` (PK), `nama_menu`, `kategori`, `harga` | **1 to N** ke `PESANAN` *(Satu menu dapat dipesan di banyak pesanan)* |
| **`PESANAN`** | `id` (PK), `user_id` (FK), `menu_id` (FK), `jumlah`, `status`, `tanggal` | Tabel Penghubung Log Pemesanan & Transaksi |

---

<p align="center">
  <i>Developed with ☕ for <b>Digital Twin Solace Coffee Project</b></i>
</p>
