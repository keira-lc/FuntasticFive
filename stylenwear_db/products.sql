-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Nov 29, 2025 at 12:23 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `stylenwear_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `tite` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `tite`, `price`, `image`) VALUES
(1, 'Tube Top with Sides Drawstrings', 275.00, 'product1.jpg'),
(2, 'Tie Dye Top', 260.00, 'product2.jpg'),
(3, 'Satin Top', 350.00, 'product3.jpg'),
(4, 'Floral Dress', 299.00, 'product4.jpg'),
(5, 'Hat Flat Gatsby Vintage', 260.00, 'product5.jpg'),
(6, 'Leather Belt', 499.00, 'product6.jpg'),
(7, 'Patent Leather Lace-Up Derby Shoes', 500.00, 'product7.jpg'),
(8, 'Black Tie', 250.00, 'product8.jpg'),
(9, 'Ear Cuff', 1200.00, 'product9.jpg'),
(11, 'Silver Bracelet', 799.00, 'product10.jpg'),
(12, 'Gold Ring', 1499.00, 'product11.jpg'),
(13, 'Diamond Earrings', 2959.00, 'product12.jpg');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
