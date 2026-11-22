-- Skema database Solace Coffee (Sprint 3)
-- Sesuai class diagram: User, Menu, Pesanan, DetailPesanan, Pembayaran
-- Kompatibel dengan MySQL 8 dan MariaDB 10.x

CREATE DATABASE IF NOT EXISTS solace_coffee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE solace_coffee;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,                       -- hash bcrypt, bukan teks asli
  role ENUM('pelanggan', 'admin') NOT NULL DEFAULT 'pelanggan',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS menu (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama_menu VARCHAR(100) NOT NULL,
  kategori ENUM('Kopi', 'Non-Kopi', 'Snack', 'Makanan Berat', 'Dessert') NOT NULL,
  harga INT NOT NULL CHECK (harga >= 0),
  deskripsi VARCHAR(255),
  tersedia TINYINT(1) NOT NULL DEFAULT 1,               -- 0 = dihapus dari daftar (soft delete)
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  tanggal TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status ENUM('menunggu', 'diproses', 'selesai', 'dibatalkan') NOT NULL DEFAULT 'menunggu',
  total_harga INT NOT NULL DEFAULT 0,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS detail_pesanan (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL,
  menu_id INT NOT NULL,
  jumlah INT NOT NULL CHECK (jumlah > 0),
  subtotal INT NOT NULL,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE,
  FOREIGN KEY (menu_id) REFERENCES menu(id)
);

CREATE TABLE IF NOT EXISTS pembayaran (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pesanan_id INT NOT NULL UNIQUE,                        -- 1 pesanan : 1 pembayaran (percobaan ulang memperbarui baris ini)
  metode ENUM('QRIS', 'DANA', 'OVO', 'GoPay', 'Transfer Bank') NOT NULL,
  jumlah_bayar INT NOT NULL,
  status ENUM('berhasil', 'gagal') NOT NULL,
  referensi VARCHAR(50),
  waktu_bayar TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (pesanan_id) REFERENCES pesanan(id) ON DELETE CASCADE
);
