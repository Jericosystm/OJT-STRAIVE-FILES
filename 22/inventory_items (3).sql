-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 11:28 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `ojt project`
--

-- --------------------------------------------------------

--
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `host_name` varchar(100) NOT NULL,
  `serial_num` varchar(100) NOT NULL,
  `device_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `machine_movement` varchar(20) DEFAULT NULL,
  `dispose_date` date DEFAULT NULL,
  `dispose_time` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `asset_name`, `host_name`, `serial_num`, `device_type`, `status`, `machine_movement`, `dispose_date`, `dispose_time`, `remarks`, `updated_at`) VALUES
(15, 'aaa', 'sss', '123', 'Laptop', 'Active', 'Release', NULL, NULL, '', '2026-03-13 07:18:45'),
(16, 'aaa2', 'sss2', '123', 'Laptop', 'Vacant', NULL, NULL, NULL, NULL, '2026-03-06 11:42:02'),
(17, '45345', 'tgtrh', '7868', 'Laptop', 'Vacant', NULL, NULL, NULL, '', '2026-03-06 11:48:02'),
(19, 'fghfgh', '-08890789', 'vvxcv', 'Laptop', 'Vacant', NULL, NULL, NULL, '', '2026-03-06 11:48:44'),
(20, 'jjjjjj', '99999', '777777', 'Laptop', 'Dispose', NULL, NULL, NULL, 'ffffff', '2026-03-08 23:09:09'),
(21, 'jjjjjj8', '999997', '777777', 'Laptop', 'Vacant', NULL, NULL, NULL, '', '2026-03-09 01:20:47');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
