-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 13 Mar 2026 pada 17.02
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `balisoulcreations`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `categories`
--

CREATE TABLE `categories` (
  `Id` int(11) NOT NULL,
  `Name` varchar(100) NOT NULL,
  `ParentId` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `categories`
--

INSERT INTO `categories` (`Id`, `Name`, `ParentId`) VALUES
(7, 'Furniture', NULL),
(13, 'Sofa', 7),
(15, 'Bedside', 7),
(21, 'Wardrobe', 7),
(24, 'Basket', NULL),
(25, 'Basket Laundry', 24),
(26, 'Basket Mini', 24),
(27, 'Basket Planter', 24),
(28, 'Basket Storage', 24),
(29, 'Basket Picnic', 24),
(30, 'Basket Bin', 24),
(31, 'Throw or Blanket', NULL),
(32, 'Throw', 31),
(33, 'Cushion', 31),
(34, 'Mosquito Net', 31),
(35, 'Lamp Shade', NULL),
(36, 'Ceiling lamp', 35),
(37, 'Standing Lamp', 35),
(38, 'Table Lamp', 35),
(39, 'Wall Lamp', 35),
(40, 'Carpet or Rugs', NULL),
(41, 'Round Carpet', 40),
(42, 'Rectangular Carpet', 40),
(43, 'Oval Carpet', 40),
(44, 'Home Decor', NULL),
(45, 'Wall Decoration', 44),
(46, 'Wall Hanging', 44),
(47, 'Standing Decor', 44),
(48, 'Miniature Decor', 44),
(49, 'Dry Flower', NULL),
(50, 'Dry Flower', 49),
(51, 'Deco Stick', 49);

-- --------------------------------------------------------

--
-- Struktur dari tabel `orders`
--

CREATE TABLE `orders` (
  `id` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `tax` decimal(10,2) NOT NULL,
  `shipping` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(50) NOT NULL,
  `payment_proof` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `fullname`, `phone`, `address`, `payment_method`, `subtotal`, `tax`, `shipping`, `total`, `status`, `payment_proof`, `created_at`) VALUES
('BSC-555C0', 1, 'Intan Amanda', '089650917081', 'Jl. Tukad Badung', 'QRIS', 1400000.00, 154000.00, 50000.00, 1604000.00, 'Awaiting Verification', 'uploads/payments/1773414895_Cream Style Bedside Table Modern Simple Design Storage Nightstands With Drawers Mesitas De Noche.jpg', '2026-03-13 15:14:44');

-- --------------------------------------------------------

--
-- Struktur dari tabel `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` varchar(50) NOT NULL,
  `product_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `qty`, `price`) VALUES
(1, 'BSC-555C0', 9, 1, 1400000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `Id` int(11) NOT NULL,
  `CategoryId` int(11) DEFAULT NULL,
  `Name` varchar(255) NOT NULL,
  `Price` decimal(10,2) NOT NULL,
  `Stock` int(11) NOT NULL,
  `Material` varchar(100) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`Id`, `CategoryId`, `Name`, `Price`, `Stock`, `Material`, `image`) VALUES
(7, 15, 'Bedside with 2 Drawer', 1200000.00, 50, 'Wood', 'uploads/products/1773413489_Modern Style Nightstand Decoration Aesthetic Simple Design Night Table Storage Drawer Mesas De.webp'),
(8, 15, 'Dark Bedside', 1475000.00, 50, 'Wood', 'uploads/products/1773413518_Modern Farmhouse Fluted Walnut Nightstand — Elegant Narrow Accent Table.webp'),
(9, 15, 'Olive Bedside', 1400000.00, 500, 'wood', 'uploads/products/1773413605_Cream Style Bedside Table Modern Simple Design Storage Nightstands With Drawers Mesitas De Noche.jpg');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `fullname`, `email`, `password`, `created_at`) VALUES
(1, 'Intan Amanda', 'tannamandaa@gmail.com', '$2y$10$4dxO3NXoc6bukNtsDHGaHuPtnpiIwCcOte7t.2X6hZlC3LPAAUFqe', '2026-03-13 14:12:42');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `ParentId` (`ParentId`);

--
-- Indeks untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indeks untuk tabel `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `CategoryId` (`CategoryId`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `categories`
--
ALTER TABLE `categories`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT untuk tabel `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT untuk tabel `products`
--
ALTER TABLE `products`
  MODIFY `Id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `categories`
--
ALTER TABLE `categories`
  ADD CONSTRAINT `categories_ibfk_1` FOREIGN KEY (`ParentId`) REFERENCES `categories` (`Id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Ketidakleluasaan untuk tabel `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`);

--
-- Ketidakleluasaan untuk tabel `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`Id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
