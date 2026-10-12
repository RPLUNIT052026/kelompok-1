# 📊 Dokumentasi Perancangan Sistem - Solace Coffee

Dokumentasi ini merinci pemodelan sistem fungsional dan struktural dari platform **Digital Twin Solace Coffee** yang dibangun berdasarkan pendekatan *Object-Oriented Analysis and Design (OOAD)*.

---

## 1. Use Case Diagram (Interaksi Pengguna dengan Sistem)

<img src="./Use Case.drawio.png" width="800" alt="Use Case Diagram Solace Coffee">

---

## 👥 A. Identifikasi Aktor (Actors)

Sistem ini melibatkan tiga aktor dengan batas hak akses dan peran yang terdefinisi secara jelas:

*   **Pelanggan (*Primary Actor*)**: Pengguna akhir yang berinteraksi secara eksternal dengan website untuk menjelajahi produk, mengelola pesanan, dan melakukan transaksi belanja.
*   **Admin (*Secondary Actor*)**: Pengelola internal yang memiliki otoritas penuh untuk memperbarui menu serta mengonfirmasi dan melacak status pesanan masuk.
*   **Payment Gateway (*External Actor*)**: Sistem pihak ketiga (menyediakan opsi QRIS, E-Wallet, dan Transfer Bank) yang bekerja di latar belakang untuk melakukan otentikasi serta memproses pembayaran.

---

## ⚙️ B. Pembongkaran Fungsionalitas (Use Cases)

Batasan fungsionalitas sistem (*System Boundary*) dikelompokkan berdasarkan hak interaksi masing-masing aktor:

### a. Fitur Spesifik Pelanggan
*   **Registrasi Akun:** Proses pendaftaran bagi pengguna baru agar data kredensial mereka tersimpan dalam basis data sistem.
*   **Login:** Pintu masuk otentikasi bagi pelanggan terdaftar untuk mengakses hak istimewa transaksi.
*   **Lihat Menu:** Modul katalog publik yang menampilkan daftar variasi kopi dan makanan yang tersedia.
*   **Kelola Keranjang:** Fitur penampung pesanan sementara, memungkinkan pelanggan menambah atau mengurangi item sebelum dibayar.
*   **Lihat Lokasi dan Jam Operasional:** Informasi umum mengenai toko fisik Solace Coffee yang dapat diakses secara bebas tanpa prasyarat logis.

### b. Fitur Spesifik Admin
*   **Kelola Data Menu:** Hak eksklusif admin untuk melakukan operasi tambah, ubah, atau hapus (*CRUD*) pada entitas menu jualan.
*   **Lihat Daftar Pesanan:** Fitur pemantauan antrean transaksi masuk yang dilakukan oleh pelanggan secara *real-time*.
*   **Ubah Status Pesanan:** Otoritas untuk memperbarui tahapan pengerjaan pesanan dari proses awal hingga siap disajikan.

---

## 🔗 C. Analisis Logika Relasi Pembatas (`<<include>>`)

Kunci utama dari diagram ini terletak pada dua hubungan dependensi wajib (`<<include>>`) yang mengikat proses transaksi pelanggan:

a.  **Checkout Pesanan `<<include>>` Login**
    *   *Analisis:* Pelanggan diperbolehkan melihat menu dan mengelola keranjang secara bebas. Namun, sesaat sebelum memproses `Checkout Pesanan`, sistem secara wajib memicu fungsi `Login`. Artinya, transaksi tidak dapat dilanjutkan jika pelanggan belum terverifikasi masuk ke dalam akun mereka.
b.  **Checkout Pesanan `<<include>>` Pilih Metode Pembayaran**
    *   *Analisis:* Ketika proses checkout berhasil divalidasi, sistem secara otomatis akan menyertakan dan mengarahkan pengguna ke fungsi `Pilih Metode Pembayaran`. Use case ini sekaligus menjadi jembatan penghubung yang memicu keterlibatan langsung dari aktor **Payment Gateway**.

---

## 🔄 D. Penjelasan Runutan Logika Bisnis (Business Logic Flow)

Secara tekstual, interaksi antarkomponen dalam sistem ini berjalan melingkari skenario utama berikut:

*   **Fase Penjelajahan:** Pelanggan mengumpulkan data awal terkait toko melalui fungsi informasi operasional dan melihat-lihat katalog menu yang tersedia.
*   **Fase Akumulasi:** Item pilihan dimasukkan ke dalam fungsi pengelola keranjang belanja.
*   **Fase Validasi Transaksi:** Pelanggan melakukan checkout, di mana sistem memaksa pemeriksaan status akun melalui fungsi login. Jika valid, sistem langsung melemparkan alur transaksi ke menu pemilihan metode pembayaran.
*   **Fase Penyelesaian & Pemrosesan:** Pihak *Payment Gateway* mengesahkan pembayaran tersebut. Setelah dana terverifikasi masuk, giliran sisi *Admin* yang bertindak untuk memantau pesanan melalui daftar transaksi dan melakukan pembaruan status pengerjaan secara berkala hingga pesanan selesai diproses.


---

## 2. Context Diagram (Batasan & Aliran Data Sistem)

<img src="./Context Model.drawio.png" width="800" alt="Context Diagram Solace Coffee">

### 💡 Penjelasan & Cara Kerja:
Diagram ini memandang sistem *Website Solace Coffee* sebagai satu kesatuan proses tunggal yang berinteraksi dengan tiga entitas luar:

* **Pelanggan ➔ Sistem:** Mengirimkan data registrasi/login, pilihan menu, isi keranjang, dan pilihan pembayaran. Sistem kemudian membalas dengan menampilkan daftar menu, harga, informasi lokasi, serta status konfirmasi pesanan[cite: 3].
* **Admin ➔ Sistem:** Mengirimkan pembaruan data menu serta perubahan status pesanan, dan menerima rekapitulasi daftar pesanan masuk secara *real-time*[cite: 3].
* **Payment Gateway ➔ Sistem:** Menjadi perantara pihak ketiga yang menerima permintaan pembayaran (total dan metode) serta mengembalikan status transaksi (*berhasil* atau *gagal*) ke dalam sistem[cite: 3].

---

## 3. Sequence Diagram (Alur Proses Checkout & Pembayaran)

<img src="./Sequence Diagram.drawio.png" width="800" alt="Sequence Diagram Solace Coffee">

### 💡 Penjelasan & Cara Kerja (Alur Step-by-Step):
1. **Pelanggan** menekan tombol *Checkout* pada antarmuka *Website (Frontend)*.
2. Sistem merespons dengan menampilkan pilihan metode pembayaran[cite: 4].
3. Setelah pelanggan memilih metode dan menekan *Bayar*, Frontend mengirimkan permintaan (`POST /api/pesanan`) ke **Backend API**[cite: 4].
4. **Backend API** menyimpan data pesanan berstatus *menunggu* ke dalam **Database**[cite: 4].
5. Setelah Database mengembalikan ID pesanan, Backend meneruskan permintaan tagihan pembayaran ke **Payment Gateway**[cite: 4].
6. **Kondisi Berhasil (`alt`):** Jika pembayaran terkonfirmasi sukses oleh *Payment Gateway*, Backend memerintahkan Database untuk memperbarui status pesanan menjadi *diproses*, lalu menampilkan halaman konfirmasi sukses beserta nomor pesanan ke layar pelanggan[cite: 4].
7. **Kondisi Gagal:** Jika transaksi ditolak/gagal, sistem menampilkan pesan error pembayaran kepada pelanggan[cite: 4].

---

## 4. Class Diagram (Struktur Objek & Basis Data)

<img src="./Class Diagram.drawio.png" width="800" alt="Class Diagram Solace Coffee">

### 💡 Penjelasan & Cara Kerja:
* **Inheritance (Pewarisan):** Kelas induk `User` diturunkan menjadi dua aktor turunan yaitu `Pelanggan` dan `Admin`, yang masing-masing mewarisi atribut dasar seperti `id`, `nama`, `email`, dan `password`.
* **Relasi Pesanan & Menu:**
  * `Pelanggan` dapat membuat banyak objek `Pesanan` (relasi 1 ke `*`)[cite: 2].
  * Satu `Pesanan` terdiri dari beberapa `DetailPesanan` yang merujuk pada data `Menu` tertentu[cite: 2].
  * `Admin` bertugas *mengelola* data `Menu` yang tersedia di aplikasi[cite: 2].
  * Proses transaksi diikat oleh kelas `Pembayaran` yang mencatat metode, jumlah bayar, waktu, serta status transaksi[cite: 2].

---
*Developed with ☕ by Kelompok 1 - Rekayasa Perangkat Lunak*
