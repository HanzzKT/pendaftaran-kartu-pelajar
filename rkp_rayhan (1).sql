-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 26, 2025 at 06:57 AM
-- Server version: 8.0.39
-- PHP Version: 8.2.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rkp_rayhan`
--

-- --------------------------------------------------------

--
-- Table structure for table `kartu_pelajar`
--

CREATE TABLE `kartu_pelajar` (
  `kartu_id` int NOT NULL,
  `pengajuan_id` int NOT NULL,
  `nomor_kartu` varchar(225) NOT NULL,
  `tanggal_terbit` varchar(225) NOT NULL,
  `file_kartu` varchar(225) NOT NULL,
  `status` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `kartu_pelajar`
--

INSERT INTO `kartu_pelajar` (`kartu_id`, `pengajuan_id`, `nomor_kartu`, `tanggal_terbit`, `file_kartu`, `status`) VALUES
(1, 6, 'KP-2025001', '2025-05-03', 'kartu_6_1748159664_kartu.pdf', 'closed'),
(2, 8, 'KP-2025002', '2025-05-06', 'kartu_andi_rev.png', 'Finish'),
(3, 9, 'KP-2025003', '2025-05-06', '', 'Unfinished'),
(4, 7, 'KP-2025004', '2025-05-08', '', 'Unfinished'),
(5, 10, 'KP-2025005', '2025-05-07', 'kartu_10_1747972174_kartu.pdf', 'closed'),
(6, 11, 'KP-202505-11', '2025-05-23', 'kartu_11_1747972718_kartu.pdf', 'Finish'),
(8, 15, 'KP-202505-15', '2025-05-25', 'kartu_15_1748160048_kartu.pdf', 'closed'),
(9, 16, 'KP-202505-16', '2025-05-25', 'kartu_16_1748164497_kartu.pdf', 'Finish'),
(10, 17, 'KP-202505-17', '2025-05-26', '', 'active'),
(11, 18, 'KP-202505-18', '2025-05-26', '', 'active'),
(12, 19, 'KP-202505-19', '2025-05-26', 'kartu_19_1748225161_kartu.pdf', 'Finish'),
(13, 20, 'KP-202505-20', '2025-05-26', 'kartu_20_1748225870_kartu.pdf', 'Finish'),
(14, 21, 'KP-202505-21', '2025-05-26', 'kartu_21_1748226140_kartu.pdf', 'Finish'),
(15, 22, 'KP-202505-22', '2025-05-26', 'kartu_22_1748228705_kartu.pdf', 'active'),
(16, 23, 'KP-202505-23', '2025-05-26', 'kartu_23_1748229726_kartu.pdf', 'closed'),
(17, 24, 'KP-202505-24', '2025-05-26', 'kartu_24_1748229945_kartu.pdf', 'Finish'),
(18, 25, 'KP-202505-25', '2025-05-26', 'kartu_25_1748230445_kartu.pdf', 'Finish'),
(19, 26, 'KP-202505-26', '2025-05-26', 'kartu_26_1748230829_kartu.pdf', 'Finish'),
(20, 27, 'KP-202505-27', '2025-05-26', '', 'Finish'),
(21, 28, 'KP-202505-28', '2025-05-26', '', 'Finish'),
(22, 31, 'KP-202505-31', '2025-05-26', '', 'Finish'),
(23, 32, 'KP-202505-32', '2025-05-26', '', 'Finish');

-- --------------------------------------------------------

--
-- Table structure for table `pengajuan_kartu`
--

CREATE TABLE `pengajuan_kartu` (
  `pengajuan_id` int NOT NULL,
  `user_id` int NOT NULL,
  `tanggal_pengajuan` varchar(225) NOT NULL,
  `foto` varchar(225) NOT NULL,
  `alamat` text NOT NULL,
  `status` varchar(225) NOT NULL,
  `catatan_admin` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pengajuan_kartu`
--

INSERT INTO `pengajuan_kartu` (`pengajuan_id`, `user_id`, `tanggal_pengajuan`, `foto`, `alamat`, `status`, `catatan_admin`) VALUES
(24, 9, '2025-05-26', '1748229919_Packaging.jpeg', 'wehsfkjbasj', 'Finish', ''),
(25, 8, '2025-05-26', '1748230415_Packaging.jpeg', 'diahwdians', 'Finish', ''),
(27, 12, '2025-05-26', '1748233694_Packaging.jpeg', 'bekasi', 'Finish', ''),
(28, 13, '2025-05-26', '1748234504_Red Bull Energy Drinks Social Media Poster.jpeg', 'jdandksajk', 'Finish', ''),
(29, 6, '2025-05-26', '1748235070_Red Bull Energy Drinks Social Media Poster.jpeg', 'djknflksdnk', 'ditolak', 'gajelas'),
(30, 5, '2025-05-26', '1748235382_Red Bull Energy Drinks Social Media Poster.jpeg', 'djknflksdnk', 'menunggu', ''),
(31, 14, '2025-05-26', '1748235457_Lays chips.jpeg', 'jnsdjkfnskdj', 'Finish', ''),
(32, 15, '2025-05-26', '1748235590_Red Bull Energy Drinks Social Media Poster.jpeg', 'jakarta', 'Finish', '');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int NOT NULL,
  `username` varchar(225) NOT NULL,
  `password` varchar(225) NOT NULL,
  `role` varchar(10) NOT NULL,
  `nama_lengkap` varchar(225) NOT NULL,
  `nis` varchar(100) NOT NULL,
  `kelas` varchar(225) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `nama_lengkap`, `nis`, `kelas`) VALUES
(1, 'andi123', '$2y$10$jWBEsE07flTNvnF4/sM23.rHUuZJr1MyNPFthRgg7WjKCew4oc8K.', 'siswa', 'Andi Saputra', '00112233', 'X IPA 1'),
(4, 'admin1', '$2y$10$Fvg7WJY9yZseOX6KTjjdKOeyDsrmPOvqCdOkfMo1v0wz4bQlfiOFe', 'admin', 'Admin Utama', '-', '-'),
(6, 'han', '$2y$10$zFpsQlGTHIjUS/61q0C87.sIR..7zasIW7rKgaGhRAYbivvxMsRYG', 'siswa', 'rayhan', '232410076', 'XI RPL 4'),
(7, 'apa', 'apa', 'siswa', 'apa', '23294329', 'XI RPL 5'),
(8, '1', '$2y$10$3dlheCYRQl1gJ0PHlFnAEu9FHnthVJcn8LZvNTc2I2RV3a4pknaAa', 'siswa', 'han1', '2492498239', 'X IPA 3'),
(9, '2', '$2y$10$6wwi5eUdMaZruB7Ibu95r.4KtjGycmrGxnzLljv.cswMXdIWD/uu.', 'siswa', 'sidi', '37824287', 'X TKJ 1'),
(10, '3', '$2y$10$X3rU2OCEM6djuAXM6mN9BuJcW1ws5MjyEkckWPp6c10UBDxCsFCKS', 'siswa', '3', '1289324329', 'XI RPL 5'),
(11, '4', '$2y$10$tNH9es64tSg2MiAN8E6t5.Wk1x0o4iJgbBtSpuzLnivwAWt2h6saa', 'siswa', '4', '39823742947', 'X IPA 3'),
(12, 'reynan', '$2y$10$ZpCR0cJ9iOMwEJdepgyKMuGMYiJ8Pxf72Eoc2NgE7rDYlPQofza/e', 'siswa', 'reynandita', '2984239483', 'XI RPL 4'),
(13, '5', '$2y$10$13u.MRdUkzi7PujxIHZoQudHBpWFVCrjyGEAiAM9xybbLifBrba/C', 'siswa', 'isdnkfnwkasj', '9843298432', 'X IPA 3'),
(14, '6', '$2y$10$sDuKHsi.vzbWJa5EGYi/puyVchhM9RyFyh3IkwLMHDe22rZ6ryF3i', 'siswa', '6', '8921293821', 'X TKJ 1'),
(15, 'han1', '$2y$10$4sxl.KXyvj96clCdghaNP.bkLfYkaOygNPDhZunP43CS6fgod1YOW', 'siswa', 'han', '87324723874', 'XI RPL 5');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `kartu_pelajar`
--
ALTER TABLE `kartu_pelajar`
  ADD PRIMARY KEY (`kartu_id`),
  ADD UNIQUE KEY `pengajuan_id` (`pengajuan_id`);

--
-- Indexes for table `pengajuan_kartu`
--
ALTER TABLE `pengajuan_kartu`
  ADD PRIMARY KEY (`pengajuan_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `kartu_pelajar`
--
ALTER TABLE `kartu_pelajar`
  MODIFY `kartu_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `pengajuan_kartu`
--
ALTER TABLE `pengajuan_kartu`
  MODIFY `pengajuan_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
