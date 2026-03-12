-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 12, 2026 at 07:07 AM
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
-- Table structure for table `win_baseline`
--

CREATE TABLE `win_baseline` (
  `id` int(11) NOT NULL,
  `box_no` int(11) DEFAULT 1,
  `hostname` varchar(100) DEFAULT '',
  `asset_inventory` varchar(100) DEFAULT '',
  `wds_formatting` varchar(50) DEFAULT '',
  `set_password` varchar(50) DEFAULT '',
  `enable_usb` varchar(50) DEFAULT '',
  `enable_audio` varchar(50) DEFAULT '',
  `removed_accounts` varchar(50) DEFAULT '',
  `rename_admin` varchar(50) DEFAULT '',
  `disable_usb_os` varchar(50) DEFAULT '',
  `install_sentinel` varchar(50) DEFAULT '',
  `verify_netskope` varchar(50) DEFAULT '',
  `install_pc_visor` varchar(50) DEFAULT '',
  `domain_join` varchar(50) DEFAULT '',
  `windows_update` varchar(50) DEFAULT '',
  `installed_softwares` varchar(50) DEFAULT '',
  `bitlocker_verify` varchar(50) DEFAULT '',
  `poc` varchar(100) DEFAULT '',
  `additional_installs` text DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `test_column` varchar(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `win_baseline`
--

INSERT INTO `win_baseline` (`id`, `box_no`, `hostname`, `asset_inventory`, `wds_formatting`, `set_password`, `enable_usb`, `enable_audio`, `removed_accounts`, `rename_admin`, `disable_usb_os`, `install_sentinel`, `verify_netskope`, `install_pc_visor`, `domain_join`, `windows_update`, `installed_softwares`, `bitlocker_verify`, `poc`, `additional_installs`, `remarks`, `test_column`) VALUES
(1, 1, 'test1', '', 'DONE', '', '', '', 'DONE', 'DONE', 'DONE', '', '', '', '', '', '', '', '', NULL, NULL, 'DONE'),
(2, 2, 'test2', '', 'DONE', 'DONE', 'DONE', '', 'DONE', '', '', '', '', '', '', '', '', '', '', NULL, NULL, 'DONE'),
(3, 3, 'test3', '', 'DONE', 'DONE', 'DONE', '', '', '', '', '', '', '', '', '', '', '', '', NULL, '', 'DONE'),
(4, 1, 'BGPC000032823P', '', 'DONE', 'DONE', '', 'DONE', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, 'DONE'),
(5, 2, 'BGPC000032827P', 'MIA 21', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', '', '', 'DONE'),
(6, 3, 'BGPC000032828P', 'MIA 22', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', NULL, NULL, ''),
(7, 4, 'BGPC000032833P', 'MIA 11', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'Vin', '', NULL, ''),
(8, 5, 'BGPC000032834P', 'MIA 16', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'Vin', NULL, NULL, ''),
(9, 6, 'BGPC000032836P', 'MIA 14', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', NULL, NULL, ''),
(10, 7, 'BGPC000032842P', 'MIA 04', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', NULL, NULL, ''),
(11, 8, 'SOPLAGWK032837', 'MIA 15', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', NULL, NULL, ''),
(12, 9, 'SOPLAGWK032838', 'MIA 12', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'DONE', 'John', NULL, NULL, ''),
(13, 10, 'SOPLAGWK032839', 'MIA 01', 'DONE', 'DONE', 'DONE', '', '', '', '', '', '', '', '', '', '', '', 'John', NULL, NULL, ''),
(14, 11, 'SOPLAGWK032840', 'MIA 02', 'DONE', '', 'DONE', '', '', '', '', '', '', '', '', '', '', '', 'John', NULL, NULL, ''),
(15, 12, 'SOPLAG 2323232', '', 'DONE', '', '', '', '', '', 'DONE', '', '', '', '', '', '', '', '', NULL, NULL, ''),
(16, 13, 'SOPLAG 2323232 test', '', 'DONE', 'DONE', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, ''),
(17, 14, 'DINOSAUR', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, ''),
(18, 15, 'test name', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', NULL, NULL, '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `win_baseline`
--
ALTER TABLE `win_baseline`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `win_baseline`
--
ALTER TABLE `win_baseline`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
