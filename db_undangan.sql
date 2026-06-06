-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Jun 06, 2026 at 11:12 AM
-- Server version: 8.0.30
-- PHP Version: 8.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_undangan`
--

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(50) NOT NULL,
  `nilai` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('alamat_gedung', 'Kota Tasikmalaya, West Java'),
('backsound', 'uploads/audio/backsound_1780739708.mp3'),
('bg_cover', 'uploads/bg_cover_1780740411.jpeg'),
('foto_galeri_1', 'uploads/galeri_1_1780736956.jpeg'),
('foto_galeri_2', 'uploads/galeri_2_1780736956.jpeg'),
('foto_galeri_3', 'uploads/galeri_3_1780737468.jpeg'),
('foto_galeri_4', 'https://images.unsplash.com/photo-1519741497674-611481863552?q=80&w=800'),
('foto_galeri_5', 'https://images.unsplash.com/photo-1511285560929-80b456fea0bc?q=80&w=800'),
('foto_pria', 'uploads/file_pria_1780740558.jpeg'),
('foto_wanita', 'uploads/file_wanita_1780737810.jpeg'),
('jam_akad', '08.00 - 10.00 WIB'),
('jam_resepsi', '11.00 - Selesai'),
('link_maps', 'https://maps.google.com/maps?q=Gedung%20Renald%2C%20Kota%20Tasikmalaya%2C%20West%20Java&t=&z=15&ie=UTF8&iwloc=&output=embed'),
('nama_gedung', 'Gedung Renald'),
('nama_pria', 'Asep '),
('nama_wanita', 'Salha'),
('ortu_pria', 'Putra dari Bapak Fulan & Ibu Fulanah'),
('ortu_wanita', 'Putri dari Bapak Agung & Ibu Rita'),
('tanggal_acara', '2026-06-26');

-- --------------------------------------------------------

--
-- Table structure for table `rekening`
--

CREATE TABLE `rekening` (
  `id` int NOT NULL,
  `nama_bank` varchar(50) NOT NULL,
  `norek` varchar(100) NOT NULL,
  `pemilik` varchar(100) NOT NULL,
  `logo_bank` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `rekening`
--

INSERT INTO `rekening` (`id`, `nama_bank`, `norek`, `pemilik`, `logo_bank`) VALUES
(1, 'Permata', '32780819828', 'Agung', 'uploads/logos/logo_1780736995_143.png');

-- --------------------------------------------------------

--
-- Table structure for table `tamu`
--

CREATE TABLE `tamu` (
  `id` int NOT NULL,
  `nama` varchar(150) NOT NULL,
  `instansi_jabatan` varchar(150) DEFAULT NULL,
  `status_kehadiran` enum('Hadir','Tidak Hadir','Belum Konfirmasi') DEFAULT 'Belum Konfirmasi',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `tamu`
--

INSERT INTO `tamu` (`id`, `nama`, `instansi_jabatan`, `status_kehadiran`, `created_at`) VALUES
(4, 'Agung Baitul Hikmah', '', 'Belum Konfirmasi', '2026-06-06 08:00:32');

-- --------------------------------------------------------

--
-- Table structure for table `ucapan`
--

CREATE TABLE `ucapan` (
  `id` int NOT NULL,
  `parent_id` int DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `kehadiran` varchar(50) NOT NULL,
  `pesan` text NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ucapan`
--

INSERT INTO `ucapan` (`id`, `parent_id`, `nama`, `kehadiran`, `pesan`, `created_at`) VALUES
(2, NULL, 'Agung Baitul Hikmah', 'Hadir', 'Bissmillah', '2026-06-06 10:18:50'),
(3, NULL, 'Abdi Fajar Maulana', 'Hadir', 'Selamat Menempuh Hidup Baru', '2026-06-06 10:28:50'),
(4, NULL, 'Siti Fauziah', 'Tidak Hadir', 'Ah asik', '2026-06-06 10:50:43'),
(7, 4, 'Mempelai ✨', 'Hadir', 'ok', '2026-06-06 11:00:28'),
(8, 4, 'Iman', 'Hadir', 'Tega Banget Ga Hadir', '2026-06-06 11:04:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `rekening`
--
ALTER TABLE `rekening`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tamu`
--
ALTER TABLE `tamu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `ucapan`
--
ALTER TABLE `ucapan`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rekening`
--
ALTER TABLE `rekening`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tamu`
--
ALTER TABLE `tamu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `ucapan`
--
ALTER TABLE `ucapan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
