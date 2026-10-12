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
Dokumen ini menjelaskan **Diagram Konteks** dari Sistem Website Solace Coffee. Diagram ini memandang seluruh sistem sebagai satu kesatuan proses tunggal (*inti pusat*) dan memetakan seluruh aliran data masuk (*input*) maupun data keluar (*output*) dari dan ke entitas luar (*terminator*).

---
<img src="./Context Model.drawio.png" width="800" alt="Context Diagram Solace Coffee">


## 👥 A. Entitas Luar (External Entities)

Terdapat tiga entitas luar yang berinteraksi langsung mengirim atau menerima data dari Sistem Website Solace Coffee:

*   **Pelanggan**: Pihak pengguna akhir yang memberikan data masukan pesanan dan menerima informasi timbal balik dari sistem.
*   **Admin**: Pihak pengelola internal toko yang menyuplai data pengaturan menu serta memperbarui progres layanan.
*   **Payment Gateway**: Entitas eksternal penyedia layanan keuangan (QRIS, E-Wallet, Bank) yang memproses validasi transaksi secara otomatis.

---

## 🔁 B. Aliran Data Masuk ke Sistem (Input Data Flows)

Aliran data yang **masuk dari entitas luar menuju ke dalam sistem** digambarkan oleh anak panah yang mengarah ke lingkaran tengah (*Sistem Website Solace Coffee*):

### a. Dari Pelanggan ke Sistem
*   **Data registrasi / login:** Kredensial akun pengguna untuk proses otentikasi.
*   **Pilihan menu & keranjang:** Data mengenai item kopi/makanan yang dipilih dan dimasukkan ke daftar belanja.
*   **Metode pembayaran:** Pilihan jalur transaksi keuangan yang dikehendaki pelanggan.

### b. Dari Admin ke Sistem
*   **Data menu (tambah/ubah/hapus):** Input data katalog berupa nama produk, harga, deskripsi, maupun penghapusan menu.
*   **Perubahan status pesanan:** Update berkala mengenai tahapan pembuatan pesanan (misal: pesanan diproses atau selesai).

### c. Dari Payment Gateway ke Sistem
*   **Status pembayaran (berhasil / gagal):** Sinyal respon digital yang menyatakan apakah transaksi keuangan yang dilakukan pelanggan sah dan selesai atau dibatalkan.

---

## ↩️ C. Aliran Data Keluar dari Sistem (Output Data Flows)

Aliran data yang **dikirim dari sistem menuju ke entitas luar** digambarkan oleh anak panah yang keluar dari lingkaran tengah:

### a. Dari Sistem ke Pelanggan
*   **Daftar menu & harga:** Informasi katalog terupdate yang siap dijelajahi oleh pelanggan.
*   **Info lokasi & jam buka:** Informasi umum profil operasional kedai Solace Coffee.
*   **Konfirmasi & status pesanan:** Pemberitahuan real-time mengenai nota transaksi dan status pengerjaan pesanan.

### b. Dari Sistem ke Admin
*   **Daftar pesanan masuk:** Notifikasi dan rincian pesanan baru yang harus segera diproses oleh tim dapur/barista.
*   **Data menu terkini:** Tampilan basis data menu saat ini yang sedang aktif tayang di website.

### c. Dari Sistem ke Payment Gateway
*   **Permintaan pembayaran (metode & total):** Data berupa jumlah tagihan (nominal uang) beserta opsi pembayaran pilihan pelanggan untuk diproses oleh sistem bank/e-wallet.

---

## 📌 D. Kesimpulan Batasan Sistem (System Boundary)

Melalui diagram konteks ini, terlihat jelas bahwa **Sistem Website Solace Coffee** bertindak sebagai **jembatan informasi otomatis**. Sistem tidak memproses uang secara mandiri melainkan mengalokasikannya ke *Payment Gateway*, dan sistem tidak membuat kopi secara fisik melainkan meneruskan datanya dalam bentuk *Daftar pesanan masuk* ke pihak *Admin* selaku eksekutor operasional.


## 3. Sequence Diagram (Alur Proses Checkout & Pembayaran)

<img src="./Sequence Diagram.drawio.png" width="800" alt="Sequence Diagram Solace Coffee">
Dokumen ini menjelaskan **Sequence Diagram** yang menggambarkan interaksi antar-objek dan urutan kronologis pesan (*messages*) dalam proses checkout hingga penyelesaian pembayaran pada Website Solace Coffee.

---

## 🏗️ A. Komponen & Objek Sistem (Lifelines)

Terdapat 5 komponen utama yang terlibat dalam urutan proses ini, bergerak dari sisi pengguna hingga ke infrastruktur belakang (*backend*):

1.  **Pelanggan**: Aktor yang menginisiasi tindakan dan menerima respon visual.
2.  **Website (Frontend)**: Antarmuka aplikasi tempat pengguna berinteraksi langsung.
3.  **Backend API**: Repositori logika bisnis yang memproses permintaan dari frontend dan menjembatani data.
4.  **Database**: Sistem penyimpanan terpusat untuk menyimpan dan memperbarui data transaksi.
5.  **Payment Gateway**: Pihak ketiga eksternal yang memvalidasi otentikasi transaksi keuangan.

---

## 🔄 B. Kronologi Urutan Alur Utama (Sequential Messages)

Berikut adalah tahapan interaksi berurutan berdasarkan nomor urut kronologis pada diagram:

a.  **Klik Checkout**: `Pelanggan` menekan tombol checkout pada antarmuka `Website (Frontend)`.
b.  **Tampilkan metode bayar**: `Website (Frontend)` merespon dengan menampilkan pilihan cara pembayaran kepada `Pelanggan`.
c.  **Pilih metode, klik Bayar**: `Pelanggan` menentukan opsi pembayaran dan menekan tombol konfirmasi bayar.
d.  **POST /api/pesanan**: `Website (Frontend)` mengirimkan data transaksi ke `Backend API` melalui metode permintaan POST.
e.  **INSERT pesanan (menunggu)**: `Backend API` memerintahkan `Database` untuk memasukkan data pesanan baru dengan status awal berupa *menunggu pembayaran*.
f.  **Return id pesanan**: `Database` mengembalikan informasi identifikasi (*ID*) unik pesanan yang baru dibuat ke `Backend API`.
g.  **Minta pembayaran (total, metode)**: `Backend API` melakukan komunikasi eksternal meminta otorisasi transaksi ke `Payment Gateway` dengan menyertakan nominal total tagihan dan metode yang dipilih.
h.  **Status pembayaran**: `Payment Gateway` mengirimkan status akhir respon hasil transaksi keuangan kembali ke `Backend API`.

---

## 🔀 C. Analisis Percabangan Kondisi (Fragment Alt)

Sistem menggunakan blok kondisi alternatif (**alt**) untuk memisahkan logika penanganan sistem berdasarkan respon dari *Payment Gateway*:

### Kondisi A: [pembayaran berhasil]
Jika transaksi disahkan oleh penyedia pembayaran, urutan pesan yang berjalan adalah:
*   **9. UPDATE status -> diproses**: `Backend API` memperbarui data di `Database` untuk mengubah status pesanan dari *menunggu* menjadi *diproses*.
*   **10. Sukses + no pesanan**: `Backend API` mengirimkan sinyal keberhasilan beserta nomor pesanan resmi ke `Website (Frontend)`.
*   **11. Tampilkan konfirmasi**: `Website (Frontend)` menampilkan halaman atau pop-up sukses kepada `Pelanggan`.

### Kondisi B: [pembayaran gagal]
Jika transaksi ditolak atau tidak sah, sistem berbelok ke alur alternatif:
*   **12. Respon pembayaran gagal**: `Backend API` meneruskan sinyal kegagalan transaksi ke `Website (Frontend)`.
*   **13. Tampilkan pesan gagal**: `Website (Frontend)` memunculkan notifikasi peringatan kegagalan pembayaran agar dapat dicoba kembali oleh `Pelanggan`.



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
