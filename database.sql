-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 03, 2026 at 03:47 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_kasir_laundry`
--
CREATE DATABASE IF NOT EXISTS `db_kasir_laundry` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_kasir_laundry`;

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama`, `created_at`) VALUES
(1, 'admin', '$2y$10$MHKsHMSlXwbh6z8cVmIN5OuTjZfTT7Z7Q8NxaDeZcUrEJJXcD7cCG', 'Administrator', '2026-07-01 10:09:52');

-- --------------------------------------------------------

--
-- Table structure for table `layanan`
--

CREATE TABLE `layanan` (
  `id` int(11) NOT NULL,
  `kode` varchar(20) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `harga_per_kg` decimal(10,0) NOT NULL,
  `durasi_jam` int(11) NOT NULL,
  `tipe_hitungan` enum('kilo','satuan') NOT NULL DEFAULT 'kilo',
  `label_durasi` varchar(30) NOT NULL,
  `aktif` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `layanan`
--

INSERT INTO `layanan` (`id`, `kode`, `nama`, `harga_per_kg`, `durasi_jam`, `tipe_hitungan`, `label_durasi`, `aktif`, `created_at`, `updated_at`) VALUES
(1, 'reguler', 'Cuci Reguler', 7000, 72, 'kilo', '3 Hari', 1, '2026-07-01 10:09:52', '2026-07-01 10:09:52'),
(2, 'express', 'Cuci Express', 10000, 24, 'kilo', '1 Hari', 1, '2026-07-01 10:09:52', '2026-07-01 10:09:52'),
(3, 'kilat', 'Cuci Kilat', 12000, 6, 'kilo', '6 Jam', 1, '2026-07-01 10:09:52', '2026-07-01 10:09:52'),
(4, 'satuan', 'Cuci Satuan', 10000, 1, 'satuan', '1 Jam', 1, '2026-07-01 16:01:45', '2026-07-01 16:01:45');

-- --------------------------------------------------------

--
-- Table structure for table `pengeluaran`
--

CREATE TABLE `pengeluaran` (
  `id` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `keterangan` varchar(200) NOT NULL,
  `jumlah` decimal(12,0) NOT NULL,
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengeluaran`
--

INSERT INTO `pengeluaran` (`id`, `tanggal`, `keterangan`, `jumlah`, `catatan`, `created_at`, `updated_at`) VALUES
(1, '2026-07-01', 'Listrik', 20000, NULL, '2026-07-01 17:19:05', '2026-07-01 17:19:05');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `no_nota` varchar(20) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `layanan_id` int(11) NOT NULL,
  `berat_kg` decimal(5,2) NOT NULL,
  `tipe_hitungan` enum('kilo','satuan') NOT NULL DEFAULT 'kilo',
  `harga_per_kg` decimal(10,0) NOT NULL,
  `total_harga` decimal(12,0) NOT NULL,
  `tanggal_masuk` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `status` enum('pending','selesai','diambil') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `transaksi`
--

INSERT INTO `transaksi` (`id`, `no_nota`, `nama_pelanggan`, `layanan_id`, `berat_kg`, `tipe_hitungan`, `harga_per_kg`, `total_harga`, `tanggal_masuk`, `tanggal_selesai`, `status`, `catatan`, `created_at`) VALUES
(1, 'PL-20260701-001', 'farel', 2, 3.90, 'kilo', 10000, 39000, '2026-07-01 10:18:49', '2026-07-02 10:18:49', 'diambil', NULL, '2026-07-01 10:18:49'),
(2, 'PL-20260703-001', 'defarel', 3, 3.50, 'kilo', 12000, 42000, '2026-07-03 08:39:33', '2026-07-03 14:39:33', 'selesai', NULL, '2026-07-03 08:39:33');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_transaksi_lengkap`
-- (See below for the actual view)
--
CREATE TABLE `v_transaksi_lengkap` (
`id` int(11)
,`no_nota` varchar(20)
,`nama_pelanggan` varchar(100)
,`layanan_id` int(11)
,`nama_layanan` varchar(100)
,`label_durasi` varchar(30)
,`tipe_hitungan` enum('kilo','satuan')
,`berat_kg` decimal(5,2)
,`harga_per_kg` decimal(10,0)
,`total_harga` decimal(12,0)
,`tanggal_masuk` datetime
,`tanggal_selesai` datetime
,`status` enum('pending','selesai','diambil')
,`catatan` text
,`created_at` datetime
);

-- --------------------------------------------------------

--
-- Structure for view `v_transaksi_lengkap`
--
DROP TABLE IF EXISTS `v_transaksi_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_transaksi_lengkap`  AS SELECT `t`.`id` AS `id`, `t`.`no_nota` AS `no_nota`, `t`.`nama_pelanggan` AS `nama_pelanggan`, `t`.`layanan_id` AS `layanan_id`, `l`.`nama` AS `nama_layanan`, `l`.`label_durasi` AS `label_durasi`, `t`.`tipe_hitungan` AS `tipe_hitungan`, `t`.`berat_kg` AS `berat_kg`, `t`.`harga_per_kg` AS `harga_per_kg`, `t`.`total_harga` AS `total_harga`, `t`.`tanggal_masuk` AS `tanggal_masuk`, `t`.`tanggal_selesai` AS `tanggal_selesai`, `t`.`status` AS `status`, `t`.`catatan` AS `catatan`, `t`.`created_at` AS `created_at` FROM (`transaksi` `t` join `layanan` `l` on(`t`.`layanan_id` = `l`.`id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `layanan`
--
ALTER TABLE `layanan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode` (`kode`);

--
-- Indexes for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_nota` (`no_nota`),
  ADD KEY `layanan_id` (`layanan_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `layanan`
--
ALTER TABLE `layanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `pengeluaran`
--
ALTER TABLE `pengeluaran`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
