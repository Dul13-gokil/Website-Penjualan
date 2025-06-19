-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 19 Jun 2025 pada 05.07
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `toko_bangunan_222060`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_222060`
--

CREATE TABLE `admin_222060` (
  `id_222060` int(11) NOT NULL,
  `username_222060` varchar(100) DEFAULT NULL,
  `email_222060` varchar(100) DEFAULT NULL,
  `password_222060` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_222060`
--

INSERT INTO `admin_222060` (`id_222060`, `username_222060`, `email_222060`, `password_222060`) VALUES
(5, 'admin', 'aa@gmail.com', '$2y$10$CIvMUcqUOvAWuVfH82Jp7uiMb9QN5b0hP.FhH8AEgmFyvAEZ8b1q6'),
(6, 'admin1', 'bb@gmail.com', '$2y$10$n28eRVoQ21nwNTHRVc2VdOTubxgWTjSRNpa4be4AyzEzHbj7iZF7G');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cart_222060`
--

CREATE TABLE `cart_222060` (
  `id_222060` int(11) NOT NULL,
  `user_id_222060` int(11) NOT NULL,
  `product_name_222060` varchar(100) NOT NULL,
  `quantity_222060` int(11) NOT NULL,
  `price_222060` decimal(10,2) NOT NULL,
  `total_price_222060` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_222060`
--

CREATE TABLE `order_222060` (
  `id_222060` int(11) NOT NULL,
  `user_id_222060` int(11) DEFAULT NULL,
  `username_222060` varchar(100) DEFAULT NULL,
  `product_name_222060` varchar(255) DEFAULT NULL,
  `quantity_222060` int(11) DEFAULT NULL,
  `price_222060` decimal(10,2) DEFAULT NULL,
  `total_price_222060` decimal(10,2) DEFAULT NULL,
  `order_status_222060` enum('pending','processed','completed','cancelled') DEFAULT NULL,
  `payment_method_222060` varchar(50) NOT NULL,
  `alamat_222060` text NOT NULL,
  `order_date_222060` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_222060`
--

INSERT INTO `order_222060` (`id_222060`, `user_id_222060`, `username_222060`, `product_name_222060`, `quantity_222060`, `price_222060`, `total_price_222060`, `order_status_222060`, `payment_method_222060`, `alamat_222060`, `order_date_222060`) VALUES
(159, 5, 'afat', 'Batako', 10, 1000.00, 10000.00, 'completed', 'Transfer Bank - BRI', 'Kolaka', '2025-04-30 03:47:50'),
(165, 5, 'afat', 'ARTCO Gerobak Sorong', 1, 300000.00, 300000.00, 'cancelled', 'COD', 'Lasusua', '2025-05-01 05:07:58'),
(166, 5, 'afat', 'Batako', 200, 1000.00, 200000.00, 'completed', 'Transfer Bank - BRI', 'Kolaka', '2025-05-06 19:53:55');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_report_222060`
--

CREATE TABLE `order_report_222060` (
  `id_222060` int(11) NOT NULL,
  `order_id_222060` int(11) DEFAULT NULL,
  `customer_name_222060` varchar(100) DEFAULT NULL,
  `product_222060` varchar(255) DEFAULT NULL,
  `quantity_222060` int(11) DEFAULT NULL,
  `price_222060` decimal(10,2) DEFAULT NULL,
  `status_222060` enum('pending','completed','cancelled') DEFAULT NULL,
  `completion_date_222060` date DEFAULT NULL,
  `notes_222060` text DEFAULT NULL,
  `created_at_222060` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_222060` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_report_222060`
--

INSERT INTO `order_report_222060` (`id_222060`, `order_id_222060`, `customer_name_222060`, `product_222060`, `quantity_222060`, `price_222060`, `status_222060`, `completion_date_222060`, `notes_222060`, `created_at_222060`, `updated_at_222060`) VALUES
(33, 159, 'afat', 'Batako', 10, 10000.00, 'completed', '2025-04-30', 'Pesanan Selesai', '2025-04-30 10:07:53', '2025-04-30 10:07:53'),
(37, 165, 'afat', 'ARTCO Gerobak Sorong', 1, 300000.00, 'cancelled', '2025-05-01', 'Dibatalkan', '2025-05-01 11:17:09', '2025-05-01 11:17:09'),
(38, 166, 'afat', 'Batako', 200, 200000.00, 'completed', '2025-05-25', 'Pesanan Selesai', '2025-05-25 07:49:21', '2025-05-25 07:49:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payment_222060`
--

CREATE TABLE `payment_222060` (
  `id_222060` int(11) NOT NULL,
  `user_id_222060` int(11) NOT NULL,
  `username_222060` varchar(255) NOT NULL,
  `product_name_222060` varchar(255) NOT NULL,
  `quantity_222060` int(11) NOT NULL,
  `price_222060` decimal(10,2) NOT NULL,
  `total_price_222060` decimal(10,2) NOT NULL,
  `alamat_222060` text NOT NULL,
  `payment_method_222060` varchar(100) NOT NULL,
  `order_status_222060` varchar(50) NOT NULL,
  `order_date_222060` datetime NOT NULL,
  `virtual_account_222060` varchar(255) NOT NULL,
  `payment_proof_222060` varchar(255) DEFAULT NULL,
  `payment_confirmation_222060` enum('confirmed','not_confirmed','cancelled') DEFAULT NULL,
  `arrival_confirmation_222060` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `payment_222060`
--

INSERT INTO `payment_222060` (`id_222060`, `user_id_222060`, `username_222060`, `product_name_222060`, `quantity_222060`, `price_222060`, `total_price_222060`, `alamat_222060`, `payment_method_222060`, `order_status_222060`, `order_date_222060`, `virtual_account_222060`, `payment_proof_222060`, `payment_confirmation_222060`, `arrival_confirmation_222060`) VALUES
(159, 5, 'afat', 'Batako', 10, 1000.00, 10000.00, 'Kolaka', 'Transfer Bank - BRI', 'Menunggu Konfirmasi', '2025-04-30 11:47:50', 'BRI-789247935253', 'payment_proof_159_1746006519.jpg', 'confirmed', '1746007667_paket-jnt-1-860x465.jpg'),
(165, 5, 'afat', 'ARTCO Gerobak Sorong', 1, 300000.00, 300000.00, 'Lasusua', 'COD', 'Cancelled', '2025-05-01 13:07:58', '', NULL, 'cancelled', NULL),
(166, 5, 'afat', 'Batako', 200, 1000.00, 200000.00, 'Kolaka', 'Transfer Bank - BRI', 'Pembayaran Terkonfirmasi', '2025-05-07 03:53:55', 'BRI-845399291496', 'payment_proof_166_1748159329.png', 'confirmed', '1748159358_ChatGPT Image May 24, 2025, 02_16_09 AM.png');

-- --------------------------------------------------------

--
-- Struktur dari tabel `product_222060`
--

CREATE TABLE `product_222060` (
  `id_222060` int(11) NOT NULL,
  `product_name_222060` varchar(255) DEFAULT NULL,
  `description_222060` text DEFAULT NULL,
  `product_image_222060` varchar(255) DEFAULT NULL,
  `price_222060` decimal(10,2) DEFAULT NULL,
  `created_at_222060` timestamp NOT NULL DEFAULT current_timestamp(),
  `stock_222060` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `product_222060`
--

INSERT INTO `product_222060` (`id_222060`, `product_name_222060`, `description_222060`, `product_image_222060`, `price_222060`, `created_at_222060`, `stock_222060`) VALUES
(5, 'Semen Tonasa', 'Kuat dan Tahan Lama', '67cfbf4f1210d.png', 200000.00, '2025-03-11 04:42:55', 0),
(6, 'ARTCO Gerobak Sorong', 'Kuat Menahan Beban yang Berat', '67cfbf84206f0.png', 300000.00, '2025-03-11 04:43:48', 1),
(7, 'Batako', 'Kuat', '67cfbf94666f0.png', 1000.00, '2025-03-11 04:44:04', 4800);

-- --------------------------------------------------------

--
-- Struktur dari tabel `super_admin_222060`
--

CREATE TABLE `super_admin_222060` (
  `id_222060` int(11) NOT NULL,
  `username_222060` varchar(100) NOT NULL,
  `email_222060` varchar(100) NOT NULL,
  `password_222060` varchar(255) NOT NULL,
  `created_at_222060` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at_222060` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `super_admin_222060`
--

INSERT INTO `super_admin_222060` (`id_222060`, `username_222060`, `email_222060`, `password_222060`, `created_at_222060`, `updated_at_222060`) VALUES
(4, 'superadmin', 'superadmin@example.com', '$2y$10$1ztXmHl0oYtiFmaCpAYnF.9ADe.DJT5iUG1bW41mgkohJDDYIH.CK', '2025-03-11 05:44:37', '2025-03-11 05:46:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `user_222060`
--

CREATE TABLE `user_222060` (
  `id_222060` int(11) NOT NULL,
  `username_222060` varchar(100) DEFAULT NULL,
  `email_222060` varchar(100) DEFAULT NULL,
  `password_222060` varchar(255) DEFAULT NULL,
  `role_222060` varchar(50) DEFAULT NULL,
  `created_at_222060` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `user_222060`
--

INSERT INTO `user_222060` (`id_222060`, `username_222060`, `email_222060`, `password_222060`, `role_222060`, `created_at_222060`) VALUES
(5, 'afat', 'aa@gmail.com', '$2y$10$hoed.ApyRD1NgZWQ.R089.lnNlTSevhyz1mXweHhKq1NNwfpNfH8S', 'customer', '2025-03-10 16:25:49'),
(6, 'tri', 'bb@gmail.com', '$2y$10$4dFEGLAHILxbVnQDyHZXwuPM9jQqorGm4dFrG95s/llT5eKvMWI7q', 'customer', '2025-03-10 16:29:44');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_222060`
--
ALTER TABLE `admin_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD UNIQUE KEY `username` (`username_222060`);

--
-- Indeks untuk tabel `cart_222060`
--
ALTER TABLE `cart_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD KEY `fk_user_id` (`user_id_222060`);

--
-- Indeks untuk tabel `order_222060`
--
ALTER TABLE `order_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD KEY `order_222060_ibfk_1` (`user_id_222060`);

--
-- Indeks untuk tabel `order_report_222060`
--
ALTER TABLE `order_report_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD KEY `order_report_222060_ibfk_1` (`order_id_222060`);

--
-- Indeks untuk tabel `payment_222060`
--
ALTER TABLE `payment_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD KEY `user_id_222060` (`user_id_222060`);

--
-- Indeks untuk tabel `product_222060`
--
ALTER TABLE `product_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD UNIQUE KEY `service_name` (`product_name_222060`);

--
-- Indeks untuk tabel `super_admin_222060`
--
ALTER TABLE `super_admin_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD UNIQUE KEY `email_222060` (`email_222060`);

--
-- Indeks untuk tabel `user_222060`
--
ALTER TABLE `user_222060`
  ADD PRIMARY KEY (`id_222060`),
  ADD UNIQUE KEY `username` (`username_222060`),
  ADD UNIQUE KEY `email` (`email_222060`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_222060`
--
ALTER TABLE `admin_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `cart_222060`
--
ALTER TABLE `cart_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT untuk tabel `order_222060`
--
ALTER TABLE `order_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT untuk tabel `order_report_222060`
--
ALTER TABLE `order_report_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT untuk tabel `payment_222060`
--
ALTER TABLE `payment_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT untuk tabel `product_222060`
--
ALTER TABLE `product_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT untuk tabel `super_admin_222060`
--
ALTER TABLE `super_admin_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `user_222060`
--
ALTER TABLE `user_222060`
  MODIFY `id_222060` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `cart_222060`
--
ALTER TABLE `cart_222060`
  ADD CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id_222060`) REFERENCES `user_222060` (`id_222060`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_222060`
--
ALTER TABLE `order_222060`
  ADD CONSTRAINT `order_222060_ibfk_1` FOREIGN KEY (`user_id_222060`) REFERENCES `user_222060` (`id_222060`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `order_report_222060`
--
ALTER TABLE `order_report_222060`
  ADD CONSTRAINT `order_report_222060_ibfk_1` FOREIGN KEY (`order_id_222060`) REFERENCES `order_222060` (`id_222060`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `payment_222060`
--
ALTER TABLE `payment_222060`
  ADD CONSTRAINT `fk_payment_order` FOREIGN KEY (`id_222060`) REFERENCES `order_222060` (`id_222060`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
