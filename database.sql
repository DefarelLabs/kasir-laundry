-- ============================================================
--  DATABASE: db-kasir-laundry
--  Jalankan file ini di phpMyAdmin atau MySQL CLI
--  sebelum menjalankan aplikasi.
-- ============================================================

CREATE DATABASE IF NOT EXISTS `db_kasir_laundry`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `db_kasir_laundry`;

-- ------------------------------------------------------------
-- Tabel: admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  username   VARCHAR(50)  NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  nama       VARCHAR(100) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO admin (username, password, nama) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uivHd/uz2', 'Administrator');

-- ------------------------------------------------------------
-- Tabel: layanan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS layanan (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  kode         VARCHAR(20)  NOT NULL UNIQUE,
  nama         VARCHAR(100) NOT NULL,
  harga_per_kg DECIMAL(10,0) NOT NULL,
  durasi_jam   INT          NOT NULL,
  label_durasi VARCHAR(30)  NOT NULL,
  aktif        TINYINT(1)   DEFAULT 1,
  created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO layanan (kode, nama, harga_per_kg, durasi_jam, label_durasi) VALUES
('reguler', 'Cuci Reguler', 7000,  72, '3 Hari'),
('express', 'Cuci Express', 10000, 24, '1 Hari'),
('kilat',   'Cuci Kilat',   12000,  6, '6 Jam');

-- ------------------------------------------------------------
-- Tabel: transaksi
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS transaksi (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  no_nota         VARCHAR(20)   NOT NULL UNIQUE,
  nama_pelanggan  VARCHAR(100)  NOT NULL,
  layanan_id      INT           NOT NULL,
  berat_kg        DECIMAL(5,2)  NOT NULL,
  harga_per_kg    DECIMAL(10,0) NOT NULL,
  total_harga     DECIMAL(12,0) NOT NULL,
  tanggal_masuk   DATETIME      NOT NULL,
  tanggal_selesai DATETIME      NOT NULL,
  status          ENUM('pending','selesai','diambil') DEFAULT 'pending',
  catatan         TEXT          NULL,
  created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (layanan_id) REFERENCES layanan(id) ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Tabel: pengeluaran
-- Mencatat pengeluaran operasional (parfum, plastik, dll)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pengeluaran (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  tanggal     DATE          NOT NULL,
  keterangan  VARCHAR(200)  NOT NULL,
  jumlah      DECIMAL(12,0) NOT NULL,
  catatan     TEXT          NULL,
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- View: v_transaksi_lengkap
-- ------------------------------------------------------------
CREATE OR REPLACE VIEW v_transaksi_lengkap AS
SELECT
  t.id,
  t.no_nota,
  t.nama_pelanggan,
  l.nama          AS nama_layanan,
  l.label_durasi,
  t.berat_kg,
  t.harga_per_kg,
  t.total_harga,
  t.tanggal_masuk,
  t.tanggal_selesai,
  t.status,
  t.catatan,
  t.created_at
FROM transaksi t
JOIN layanan l ON t.layanan_id = l.id;
