-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 22, 2026 at 10:55 AM
-- Server version: 8.4.3
-- PHP Version: 8.3.36

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_visitor_pass`
--

-- --------------------------------------------------------

--
-- Table structure for table `log_akses`
--

CREATE TABLE `log_akses` (
  `id_log` int NOT NULL,
  `id_pass` int NOT NULL,
  `id_petugas` int NOT NULL,
  `waktu_checkin` datetime DEFAULT NULL,
  `waktu_checkout` datetime DEFAULT NULL,
  `status_pass` enum('Di Dalam Gedung','Kedaluwarsa') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Di Dalam Gedung'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `log_akses`
--

INSERT INTO `log_akses` (`id_log`, `id_pass`, `id_petugas`, `waktu_checkin`, `waktu_checkout`, `status_pass`) VALUES
(1, 1, 1, '2026-11-22 11:04:54', '2026-11-22 13:37:14', 'Kedaluwarsa'),
(2, 2, 4, '2026-11-22 09:04:54', NULL, 'Di Dalam Gedung'),
(3, 5, 1, '2026-11-22 13:37:23', '2026-11-22 13:38:23', 'Kedaluwarsa'),
(4, 3, 4, '2026-11-22 13:39:48', '2026-11-22 13:48:07', 'Kedaluwarsa'),
(5, 6, 1, '2026-11-22 17:15:53', '2026-11-22 17:15:57', 'Kedaluwarsa'),
(6, 7, 1, '2026-11-22 17:33:29', '2026-11-22 17:43:14', 'Kedaluwarsa'),
(7, 8, 5, '2026-11-22 17:54:18', NULL, 'Di Dalam Gedung');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_pass`
--

CREATE TABLE `pengajuan_pass` (
  `id_pass` int NOT NULL,
  `kode_pass` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_tamu` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_identitas` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instansi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_tujuan_pegawai` int NOT NULL,
  `keperluan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tgl_rencana` date NOT NULL,
  `status_approval` enum('Pending','Approved','Rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengajuan_pass`
--

INSERT INTO `pengajuan_pass` (`id_pass`, `kode_pass`, `nama_tamu`, `no_identitas`, `instansi`, `id_tujuan_pegawai`, `keperluan`, `tgl_rencana`, `status_approval`) VALUES
(1, 'PASS-20261112-1001', 'Ahmad Fauzi', '3275011205900001', 'PT Nusantara Digital', 2, 'Presentasi integrasi sistem server dan audit keamanan jaringan.', '2026-11-22', 'Approved'),
(2, 'PASS-20261112-1002', 'Dewi Lestari', '3174024508920003', 'CV Mitra Sejahtera', 3, 'Penyerahan dokumen penawaran tender pengadaan alat tulis kantor.', '2026-11-22', 'Approved'),
(3, 'PASS-20261112-1003', 'Rizky Pratama', '3671051903950002', 'PT Solusi Mandiri', 2, 'Konsultasi teknis pemeliharaan perangkat keras.', '2026-11-22', 'Approved'),
(4, 'PASS-20261112-1004', 'Nadia Putri', '3201016211940005', 'PT Global Logistik', 3, 'Kunjungan tanpa konfirmasi jadwal rapat.', '2026-11-22', 'Rejected'),
(5, 'PASS-20261122-5120', 'Edward Tjandra', '23123', 'PT Maju bersama', 1, 'rapat', '2026-11-22', 'Approved'),
(6, 'PASS-20261122-5620', 'edward', '12323', 'PT Maju bersama', 5, 'wewewewe', '2026-11-22', 'Approved'),
(7, 'PASS-20261122-8423', 'edward', '327142134512', 'PT Summarecon Agung TBK', 1, 'wewswewe', '2026-11-22', 'Approved'),
(8, 'PASS-20261122-6655', 'Edward Tjandra', '1231313213', 'pahoa', 1, 'weaweaerawer', '2026-11-22', 'Approved');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id_user` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `divisi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('admin','pegawai','keamanan') COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id_user`, `nama`, `divisi`, `username`, `password`, `role`) VALUES
(1, 'Administrator Sistem', 'IT & General Affairs', 'admin', '$2y$10$os6Z8fBlDFfWmUqE3yfp7.g5lNX6VBGEu4YDgIE3PyM5ar3Xowb8e', 'admin'),
(2, 'Budi Santoso, S.Kom.', 'Divisi Teknologi Informasi', 'budi', '$2y$10$5S11EZscaqDFucyVMmty9upChR1EuZYrGqrUHmSVX2dw2p7YZ7xri', 'pegawai'),
(3, 'Sari Rahmawati, S.E.', 'Divisi Keuangan & Pengadaan', 'sari', '$2y$10$K.ADI0trO81Hn/87WKTFW.0FoAWYI0Smq78pno5HVcUDoMbr9tD5m', 'pegawai'),
(4, 'Hendro Wijaya', 'Pos Keamanan Utama', 'satpam1', '$2y$10$Jk05EFNwJTUtAFBO2q9b3OuP4BQ7HDeyQm60/zl1L2pZqRibTLa3C', 'keamanan'),
(5, 'Edward', 'CEO', 'edward', '$2y$10$2bMN0uZSLtJZzVgN3CyNjOprYnAHJwbcteds01HbZHwFt2IXbM9qO', 'admin');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD PRIMARY KEY (`id_log`),
  ADD UNIQUE KEY `uk_log_pass` (`id_pass`),
  ADD KEY `idx_log_pass` (`id_pass`),
  ADD KEY `idx_log_petugas` (`id_petugas`);

--
-- Indexes for table `pengajuan_pass`
--
ALTER TABLE `pengajuan_pass`
  ADD PRIMARY KEY (`id_pass`),
  ADD UNIQUE KEY `uk_kode_pass` (`kode_pass`),
  ADD KEY `idx_tujuan_pegawai` (`id_tujuan_pegawai`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id_user`),
  ADD UNIQUE KEY `uk_username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `log_akses`
--
ALTER TABLE `log_akses`
  MODIFY `id_log` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `pengajuan_pass`
--
ALTER TABLE `pengajuan_pass`
  MODIFY `id_pass` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id_user` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `log_akses`
--
ALTER TABLE `log_akses`
  ADD CONSTRAINT `fk_log_pass` FOREIGN KEY (`id_pass`) REFERENCES `pengajuan_pass` (`id_pass`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_log_petugas` FOREIGN KEY (`id_petugas`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Constraints for table `pengajuan_pass`
--
ALTER TABLE `pengajuan_pass`
  ADD CONSTRAINT `fk_pengajuan_pegawai` FOREIGN KEY (`id_tujuan_pegawai`) REFERENCES `users` (`id_user`) ON DELETE RESTRICT ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
