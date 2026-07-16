-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 16, 2026 at 09:39 AM
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
(1, 'admin', '$2y$10$BWcsWpZDezVB0HtwIfJFc.WU1IDXeleBuMddir4R9utPCqjQTN/Cq', 'Administrator', '2026-07-01 20:29:07');

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
(1, 'reguler', 'Cuci Reguler', 7000, 72, 'kilo', '3 Hari', 1, '2026-07-01 20:29:07', '2026-07-01 20:29:07'),
(2, 'express', 'Cuci Express', 10000, 24, 'kilo', '1 Hari', 1, '2026-07-01 20:29:07', '2026-07-01 20:29:07'),
(3, 'kilat', 'Cuci Kilat', 12000, 6, 'kilo', '6 Jam', 1, '2026-07-01 20:29:07', '2026-07-01 20:29:07'),
(4, 'satuan', 'Cuci Satuan', 10000, 1, 'satuan', '1 Jam', 1, '2026-07-01 20:48:46', '2026-07-01 20:48:46');

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

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `no_nota` varchar(20) NOT NULL,
  `nama_pelanggan` varchar(100) NOT NULL,
  `total_harga` decimal(12,0) NOT NULL,
  `deposit` decimal(12,0) NOT NULL DEFAULT 0,
  `sisa_bayar` decimal(12,0) NOT NULL DEFAULT 0,
  `tanggal_masuk` datetime NOT NULL,
  `tanggal_selesai` datetime NOT NULL,
  `status` enum('pending','selesai','diambil') DEFAULT 'pending',
  `catatan` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaksi_detail`
--

CREATE TABLE `transaksi_detail` (
  `id` int(11) NOT NULL,
  `transaksi_id` int(11) NOT NULL,
  `layanan_id` int(11) NOT NULL,
  `nama_layanan` varchar(100) NOT NULL,
  `label_durasi` varchar(30) NOT NULL,
  `tipe_hitungan` enum('kilo','satuan') NOT NULL DEFAULT 'kilo',
  `jumlah` decimal(8,2) NOT NULL,
  `harga_per_unit` decimal(10,0) NOT NULL,
  `subtotal` decimal(12,0) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_transaksi_lengkap`
-- (See below for the actual view)
--
CREATE TABLE `v_transaksi_lengkap` (
`id` int(11)
,`no_nota` varchar(20)
,`nama_pelanggan` varchar(100)
,`total_harga` decimal(12,0)
,`tanggal_masuk` datetime
,`tanggal_selesai` datetime
,`status` enum('pending','selesai','diambil')
,`catatan` text
,`created_at` datetime
,`jumlah_item` bigint(21)
,`daftar_layanan` mediumtext
);

-- --------------------------------------------------------

--
-- Structure for view `v_transaksi_lengkap`
--
DROP TABLE IF EXISTS `v_transaksi_lengkap`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_transaksi_lengkap`  AS SELECT `t`.`id` AS `id`, `t`.`no_nota` AS `no_nota`, `t`.`nama_pelanggan` AS `nama_pelanggan`, `t`.`total_harga` AS `total_harga`, `t`.`tanggal_masuk` AS `tanggal_masuk`, `t`.`tanggal_selesai` AS `tanggal_selesai`, `t`.`status` AS `status`, `t`.`catatan` AS `catatan`, `t`.`created_at` AS `created_at`, count(`d`.`id`) AS `jumlah_item`, group_concat(`d`.`nama_layanan` separator ', ') AS `daftar_layanan` FROM (`transaksi` `t` left join `transaksi_detail` `d` on(`d`.`transaksi_id` = `t`.`id`)) GROUP BY `t`.`id` ;

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
  ADD UNIQUE KEY `no_nota` (`no_nota`);

--
-- Indexes for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD PRIMARY KEY (`id`),
  ADD KEY `transaksi_id` (`transaksi_id`),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `transaksi_detail`
--
ALTER TABLE `transaksi_detail`
  ADD CONSTRAINT `td_layanan_fk` FOREIGN KEY (`layanan_id`) REFERENCES `layanan` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `td_transaksi_fk` FOREIGN KEY (`transaksi_id`) REFERENCES `transaksi` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
