-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 13, 2026 at 12:40 AM
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
-- Database: `ojt project`
--

-- --------------------------------------------------------

--
-- Table structure for table `all_assets_master`
--

CREATE TABLE `all_assets_master` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `host_name` varchar(100) DEFAULT NULL,
  `serial_num` varchar(100) DEFAULT NULL,
  `device_type` varchar(50) NOT NULL,
  `location` varchar(50) DEFAULT 'WFH',
  `department` varchar(100) DEFAULT NULL,
  `cubicle_no` varchar(20) DEFAULT NULL,
  `switch_port` varchar(255) DEFAULT NULL,
  `grid_row` int(11) DEFAULT NULL,
  `grid_col` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Vacant',
  `user_assigned` varchar(100) DEFAULT NULL,
  `date_returned` date DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `dispose_date` date DEFAULT NULL,
  `dispose_time` time DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `all_assets_master`
--

INSERT INTO `all_assets_master` (`id`, `asset_name`, `host_name`, `serial_num`, `device_type`, `location`, `department`, `cubicle_no`, `switch_port`, `grid_row`, `grid_col`, `status`, `user_assigned`, `date_returned`, `remarks`, `dispose_date`, `dispose_time`, `updated_at`, `created_at`) VALUES
(1, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-001', 'SW165P02', 1, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(2, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-002', 'SW161P10', 1, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(3, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-003', 'SW161P09', 1, 3, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(4, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-004', 'SW161P08', 1, 4, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(5, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-005', 'SW161P07', 1, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(6, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-006', 'SW161P06', 2, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(7, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-007', 'SW161P05', 2, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(8, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-008', 'SW161P07', 2, 3, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(9, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-009', 'SW161P22', 2, 4, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(10, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'ATL-010', 'SW161P21', 2, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(11, 'CUBICLE-SPACE', 'BGPC000101', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-001', 'NATGEN-BU', 1, 1, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(12, 'CUBICLE-SPACE', 'SW161P35', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-002', 'ln uz', 1, 2, 'Repair', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(13, 'CUBICLE-SPACE', 'BGPC000103', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-003', 'NATGEN-BU', 1, 3, 'Repair', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(14, 'CUBICLE-SPACE', 'BGPC000104', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-004', 'NATGEN-BU', 2, 1, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(15, 'CUBICLE-SPACE', 'BGPC-NAT01', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-01', 'NATGEN-AU', 1, 1, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(16, 'CUBICLE-SPACE', 'BGPC-NAT02', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-02', '', 1, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(17, 'CUBICLE-SPACE', 'BGPC-NAT03', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-03', 'NATGEN-AU', 1, 3, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(18, 'CUBICLE-SPACE', 'BGPC-NAT04', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-04', 'NATGEN-AU', 1, 4, 'Repair', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(19, 'CUBICLE-SPACE', 'BGPC-NAT05', NULL, 'Station', 'Onsite', 'NATGEN', 'NAT-05', '', 2, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(20, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-001', 'SW161P20', 1, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(21, 'CUBICLE-SPACE', 'SOPLAGWK033860', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-002', 'SW161P19', 1, 2, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(22, 'CUBICLE-SPACE', 'SOPLAGWK035231', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-003', 'SW161P18', 1, 3, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(23, 'CUBICLE-SPACE', 'SOPLAGWK035378', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-004', 'SW161P16', 1, 4, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(24, 'CUBICLE-SPACE', 'SOPLAGW60020671', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-005', 'SW161P35', 1, 5, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(25, 'CUBICLE-SPACE', 'SOPLAGWK034806', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-006', 'SW161P34', 1, 6, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(26, 'CUBICLE-SPACE', 'SOPLAGW60020667', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-007', 'SW161P33', 1, 7, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(27, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-008', 'SW161P32', 2, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(28, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-009', 'SW161P31', 2, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(29, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-010', 'SW161P29', 2, 3, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(30, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-011', 'SW161P29', 2, 4, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(31, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-012', 'SW162P46', 2, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(32, 'CUBICLE-SPACE', 'SA-PC-13', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-013', 'SW161P46', 2, 6, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(33, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-014', 'SW162P09', 2, 7, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(34, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-015', 'SW161P44', 3, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(35, 'CUBICLE-SPACE', 'SA-PC-16', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-016', 'SW161P43', 3, 2, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(36, 'CUBICLE-SPACE', 'SOPLAGWK033554', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-017', 'SW162P23', 3, 3, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(37, 'CUBICLE-SPACE', 'SOPLAGWK033557', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-018', 'SW162P21', 3, 4, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(38, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-019', 'SW162P20', 3, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(39, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-020', 'SW162P19', 3, 6, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(40, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-021', 'SW162P23', 3, 7, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(41, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-022', 'SW162P18', 4, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(42, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-023', 'SW162P17', 4, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(43, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-024', 'SW162P06', 4, 3, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(44, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-025', 'SW162P05', 4, 4, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(45, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-026', 'SW162P23', 4, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(46, 'CUBICLE-SPACE', 'SA-PC-27', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-027', 'SW162P21', 4, 6, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(47, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-028', 'SW162P20', 4, 7, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(48, 'CUBICLE-SPACE', 'SA-PC-29', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-029', 'SW162P19', 5, 1, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(49, 'CUBICLE-SPACE', 'SA-PC-30', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-030', 'SW162P23', 5, 2, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(50, 'CUBICLE-SPACE', 'SOPLAGL60019819', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-031', 'SW162P18', 5, 3, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(51, 'CUBICLE-SPACE', 'SA-PC-32', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-032', 'SW162P17', 5, 4, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(52, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-033', 'SW162P35', 5, 5, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(53, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-034', 'SW162P34', 5, 6, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(54, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-035', 'SW162P33', 5, 7, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(55, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-036', 'SW162P32', 6, 1, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(56, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-037', 'SW162P31', 6, 2, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(57, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-038', 'SW162P30', 6, 3, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(58, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'San Antonio', 'SA-039', 'SW162P29', 6, 4, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(59, 'CUBICLE-SPACE', 'SOPLAGW60019682', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(60, 'CUBICLE-SPACE', 'SOPLAGW60019685', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(61, 'CUBICLE-SPACE', 'SOPLAGW60019674', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(62, 'CUBICLE-SPACE', 'SOPLAGW60019673', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(63, 'CUBICLE-SPACE', 'SOPLAGW60019672', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(64, 'CUBICLE-SPACE', 'SOPLAGW60019671', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(65, 'CUBICLE-SPACE', 'SOPLAGW60019670', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(66, 'CUBICLE-SPACE', 'SOPLAGW60019680', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(67, 'CUBICLE-SPACE', 'SOPLAGW60019679', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(68, 'CUBICLE-SPACE', 'SOPLAGW60019678', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(69, 'CUBICLE-SPACE', 'SOPLAGW60019677', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(70, 'CUBICLE-SPACE', 'SOPLAGW60019676', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(71, 'CUBICLE-SPACE', 'SOPLAGW60019675', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(72, 'CUBICLE-SPACE', 'SOPLAGW60019690', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(73, 'CUBICLE-SPACE', 'SOPLAGWK032888', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(74, 'CUBICLE-SPACE', 'SOPLAGWK032337', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(75, 'CUBICLE-SPACE', 'SOPLAGWK032675', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(76, 'CUBICLE-SPACE', 'SOPLAGWK033255', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(77, 'CUBICLE-SPACE', 'SOPLAGWK033260', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(78, 'CUBICLE-SPACE', 'SOPLAGWK032334', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(79, 'CUBICLE-SPACE', 'SOPLAGWK032673', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(80, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(81, 'CUBICLE-SPACE', 'SOPLAGW60023139', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(82, 'CUBICLE-SPACE', 'SOPLAGW60023138', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(83, 'CUBICLE-SPACE', 'SOPLAGW60023137', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(84, 'CUBICLE-SPACE', 'SOPLAGW60023136', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(85, 'CUBICLE-SPACE', 'SOPLAGW60023135', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(86, 'CUBICLE-SPACE', 'SOPLAGW60023134', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(87, 'CUBICLE-SPACE', 'SOPLAGW60020695', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(88, 'CUBICLE-SPACE', 'SOPLAGW60023146', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(89, 'CUBICLE-SPACE', 'SOPLAGW60023145', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(90, 'CUBICLE-SPACE', 'SOPLAGW60023144', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(91, 'CUBICLE-SPACE', 'SOPLAGW60023143', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(92, 'CUBICLE-SPACE', 'SOPLAGW60023142', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(93, 'CUBICLE-SPACE', 'SOPLAGW60023141', NULL, 'Station', 'Onsite', 'Chicago', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(94, 'CUBICLE-SPACE', 'SOPLAGW60019770', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(95, 'CUBICLE-SPACE', 'SOPLAGW60020690', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(96, 'CUBICLE-SPACE', 'SOPLAGW60020899', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(97, 'CUBICLE-SPACE', 'SOPLAGW60021784', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(98, 'CUBICLE-SPACE', 'SOPLAGW60021783', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(99, 'CUBICLE-SPACE', 'SOPLAGW60021782', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(100, 'CUBICLE-SPACE', 'SOPLAGW60021781', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(101, 'CUBICLE-SPACE', 'SOPLAGW60020692', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(102, 'CUBICLE-SPACE', 'SOPLAGW60020688', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(103, 'CUBICLE-SPACE', 'SOPLAGW60020693', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(104, 'CUBICLE-SPACE', 'SOPLAGW60019695', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(105, 'CUBICLE-SPACE', 'BGPC000032827P', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(106, 'CUBICLE-SPACE', 'SOPLAGWK035394', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(107, 'CUBICLE-SPACE', 'SOPLAGWK035381', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(108, 'CUBICLE-SPACE', 'SOPLAGWK035241', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(109, 'CUBICLE-SPACE', 'SOPLAGW60020694', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(110, 'CUBICLE-SPACE', 'BGPC000032834P', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(111, 'CUBICLE-SPACE', 'BGPC000032837P', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(112, 'CUBICLE-SPACE', 'BGPC000032836P', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(113, 'CUBICLE-SPACE', 'BGPC000032838P', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(114, 'CUBICLE-SPACE', 'SOPLAGW60019745', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(115, 'CUBICLE-SPACE', 'SOPLAGWK035906', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(116, 'CUBICLE-SPACE', 'SOPLAGW60020900', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(117, 'CUBICLE-SPACE', 'SOPLAGW60020691', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(118, 'CUBICLE-SPACE', 'SOPLAGW60020689', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(119, 'CUBICLE-SPACE', 'SOPLAGW60020898', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(120, 'CUBICLE-SPACE', 'SOPLAGW60020897', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(121, 'CUBICLE-SPACE', 'SOPLAGW60020687', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(122, 'CUBICLE-SPACE', 'BGPC000032842 / BGPC000032838', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(123, 'CUBICLE-SPACE', 'SOPLAGWK032828 / SOPLAGW60020647', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(124, 'CUBICLE-SPACE', 'BGPC000032840P / SOPLAGWK032827', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(125, 'CUBICLE-SPACE', 'SOPLAGWK032839 / SOPLAGWK032823', NULL, 'Station', 'Onsite', 'Miami', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(126, 'CUBICLE-SPACE', 'SOPLAGWK035378', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', 'SW161P16', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(127, 'CUBICLE-SPACE', 'SOPLAGWK035378', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', 'SOPLAGWK035378', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(128, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(129, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(130, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(131, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(132, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(133, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(134, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(135, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(136, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(137, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(138, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(139, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(140, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(141, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(142, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(143, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Gray Room', 'GR-HOST', '', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(144, 'CUBICLE-SPACE', 'SOPLAGW60020702', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0001', 'SW161P04', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(145, 'CUBICLE-SPACE', 'SOPLAGWK032848', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0002', 'SW161P03', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(146, 'CUBICLE-SPACE', 'SOPLAGWK032671', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0003', 'SW161P02', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(147, 'CUBICLE-SPACE', 'SOPLAGWK034823', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0004', 'SW161P01', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(148, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0005', 'Available', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(149, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0006', 'SW161P16', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(150, 'CUBICLE-SPACE', 'SOPLAGW60020703', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0007', 'SW161P15', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(151, 'CUBICLE-SPACE', 'SOPLAGW60021422', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0008', 'SW161P14', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(152, 'CUBICLE-SPACE', 'SOPLAGW60021423', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0009', 'SW161P13', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(153, 'CUBICLE-SPACE', 'SOPLAGW60021424', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0010', 'SW161P12', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(154, 'CUBICLE-SPACE', NULL, NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0011', 'SW161P28', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(155, 'CUBICLE-SPACE', NULL, NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0012', 'SW161P27', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(156, 'CUBICLE-SPACE', NULL, NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0013', 'SW161P26', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(157, 'CUBICLE-SPACE', 'SOPLAGW60019699', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0014', 'SW161P25', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(158, 'CUBICLE-SPACE', 'SOPLAGWK034821', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0015', 'SW161P24', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(159, 'CUBICLE-SPACE', 'SOPLAGWK032816', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0016', 'SW161P40', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(160, 'CUBICLE-SPACE', 'SOPLAGW60020661', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0017', 'SW161P39', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(161, 'CUBICLE-SPACE', 'SOPLAGW60020658', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0018', 'SW161P38', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(162, 'CUBICLE-SPACE', 'SOPLAGWK032821', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0019', 'SW161P37', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(163, 'CUBICLE-SPACE', 'SOPLAGWK031608', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0020', 'SW161P36', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(164, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0021', 'SW161P04', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(165, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0022', 'SW161P03', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(166, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0023', 'SW161P02', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(167, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0024', 'SW161P21', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(168, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0025', 'SW161P45', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(169, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0026', 'SW162P16', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(170, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0027', 'SW162P15', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(171, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0028', 'SW162P14', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(172, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0029', 'SW162P13', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(173, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0030', 'SW162P12', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(174, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0031', 'SW162P28', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(175, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0032', 'SW162P27', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(176, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0033', 'SW162P26', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(177, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0034', 'SW162P25', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(178, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Phoenix', 'PHX-0035', 'SW162P24', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(179, 'CUBICLE-SPACE', 'SOPLAGW60019681', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(180, 'CUBICLE-SPACE', 'SOPLAGW60019686', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(181, 'CUBICLE-SPACE', 'SOPLAGW60019684', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(182, 'CUBICLE-SPACE', 'SOPLAGW60018683', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(183, 'CUBICLE-SPACE', 'SOPLAGW60019688', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(184, 'CUBICLE-SPACE', 'SOPLAGW60018689', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(185, 'CUBICLE-SPACE', 'SOPLAGW60019687', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(186, 'CUBICLE-SPACE', 'SOPLAGWK033560', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(187, 'CUBICLE-SPACE', 'SOPLAGWK032665', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(188, 'CUBICLE-SPACE', 'SOPLAGWK032849', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(189, 'CUBICLE-SPACE', 'SOPLAGWK031606', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(190, 'CUBICLE-SPACE', 'SOPLAGWK031605', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(191, 'CUBICLE-SPACE', 'SOPLAGW60023151', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(192, 'CUBICLE-SPACE', 'SOPLAGW60023150', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(193, 'CUBICLE-SPACE', 'SOPLAGW60023149', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(194, 'CUBICLE-SPACE', 'SOPLAGW60023148', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(195, 'CUBICLE-SPACE', 'SOPLAGW60023154', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(196, 'CUBICLE-SPACE', 'SOPLAGW60023153', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(197, 'CUBICLE-SPACE', 'SOPLAGW60023152', NULL, 'Station', 'Onsite', 'Orlando', '', 'S163P33', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(198, 'CUBICLE-SPACE', 'SOPLAGW60023147', NULL, 'Station', 'Onsite', 'Orlando', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(199, 'CUBICLE-SPACE', 'SOPLAGWK035395', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(200, 'CUBICLE-SPACE', 'SOPLAGWK035245', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P37', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(201, 'CUBICLE-SPACE', '35227', NULL, 'Station', 'Onsite', 'Denver', '', 'SW137P22', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(202, 'CUBICLE-SPACE', 'SOPLAGWK035246', NULL, 'Station', 'Onsite', 'Denver', '', 'SW137P23', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(203, 'CUBICLE-SPACE', 'SOPLAGWK035238', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P32', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(204, 'CUBICLE-SPACE', '35252', NULL, 'Station', 'Onsite', 'Denver', '', 'SW171P31', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(205, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P34', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(206, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P38', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(207, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW137P24', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(208, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P39', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(209, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P19', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(210, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P23', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(211, 'CUBICLE-SPACE', 'SOPLAGWK036161', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P29', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(212, 'CUBICLE-SPACE', 'SOPLAGWK036159', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P23', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(213, 'CUBICLE-SPACE', 'SOPLAGWK032733', NULL, 'Station', 'Onsite', 'Denver', '', 'SW137P20', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(214, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW145P13', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(215, 'CUBICLE-SPACE', 'SOPLAGWK035240', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P28', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(216, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P18', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(217, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P30', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(218, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P20', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(219, 'CUBICLE-SPACE', 'SOPLAGW60020432', NULL, 'Station', 'Onsite', 'Denver', '', 'SW137P36', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(220, 'CUBICLE-SPACE', 'SOPLAGWK033275', NULL, 'Station', 'Onsite', 'Denver', '', 'SW144P42', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(221, 'CUBICLE-SPACE', 'SOPLAGWK036164', NULL, 'Station', 'Onsite', 'Denver', '', 'SW140P31', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(222, 'CUBICLE-SPACE', 'SOPLAGWK036427', NULL, 'Station', 'Onsite', 'Denver', '', 'SW145P22', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(223, 'CUBICLE-SPACE', 'SOPLAGWK036162', NULL, 'Station', 'Onsite', 'Denver', '', 'SW145P21', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(224, 'CUBICLE-SPACE', 'SOPLAGWK036167', NULL, 'Station', 'Onsite', 'Denver', '', 'SW145P22', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(225, 'CUBICLE-SPACE', 'SOPLAGWK036430', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P14', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(226, 'CUBICLE-SPACE', 'SOPLAGWK036166', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P20', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(227, 'CUBICLE-SPACE', 'SOPLAGWK036158', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(228, 'CUBICLE-SPACE', 'SOPLAGW60019709', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(229, 'CUBICLE-SPACE', 'SOPLAGWK036429', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(230, 'CUBICLE-SPACE', 'SOPLAGWK036428', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(231, 'CUBICLE-SPACE', 'SOPLAGW60020431', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(232, 'CUBICLE-SPACE', 'SOPLAGWK032740', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(233, 'CUBICLE-SPACE', 'SOPLAGW60019767', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(234, 'CUBICLE-SPACE', 'SOPLAGWK033388', NULL, 'Station', 'Onsite', 'Denver', '', '', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(235, 'CUBICLE-SPACE', 'SOPLAGW60021427', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P10', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(236, 'CUBICLE-SPACE', 'SOPLAGW60021425', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P11', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(237, 'CUBICLE-SPACE', 'SOPLAGWK036424', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P08', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(238, 'CUBICLE-SPACE', 'SOPLAGW60021426', NULL, 'Station', 'Onsite', 'Denver', '', 'SW143P20', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(239, 'CUBICLE-SPACE', 'SOPLAGW60021421', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P09', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(240, 'CUBICLE-SPACE', 'SOPLAGW60021418', NULL, 'Station', 'Onsite', 'Denver', '', 'SW138P12', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(241, 'CUBICLE-SPACE', 'SOPLAGWK035894', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(242, 'CUBICLE-SPACE', 'SOPLAGWK032738', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(243, 'CUBICLE-SPACE', 'SOPLAGW60019796', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(244, 'CUBICLE-SPACE', 'SOPLAGW60019773', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(245, 'CUBICLE-SPACE', 'SOPLAGW60021730', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(246, 'CUBICLE-SPACE', 'SOPLAGWK032729', NULL, 'Station', 'Onsite', 'Dallas', '', 'S137P29', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(247, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'S137P18', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(248, 'CUBICLE-SPACE', 'BGPC000033549P', NULL, 'Station', 'Onsite', 'Dallas', '', 'S137P26', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(249, 'CUBICLE-SPACE', 'SOPLAGWK031607', NULL, 'Station', 'Onsite', 'Dallas', '', 'S137P20', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(250, 'CUBICLE-SPACE', 'SOPLAGWK031607', NULL, 'Station', 'Onsite', 'Dallas', '', 'S137P19', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(251, 'CUBICLE-SPACE', 'SOPLAGWK033561', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P37', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(252, 'CUBICLE-SPACE', 'SOPLAGWK032344', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P38', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(253, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P39', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(254, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P40', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(255, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P30', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(256, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P01', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(257, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P04', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(258, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P03', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(259, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P43', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(260, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P42', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(261, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW144P26', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(262, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P04', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(263, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW144P25', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(264, 'CUBICLE-SPACE', 'Laptop use', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW137P43', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(265, 'CUBICLE-SPACE', 'SOPLAGWK032815', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P05', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53');
INSERT INTO `all_assets_master` (`id`, `asset_name`, `host_name`, `serial_num`, `device_type`, `location`, `department`, `cubicle_no`, `switch_port`, `grid_row`, `grid_col`, `status`, `user_assigned`, `date_returned`, `remarks`, `dispose_date`, `dispose_time`, `updated_at`, `created_at`) VALUES
(266, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(267, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P06/P15', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(268, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P18', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(269, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P17', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(270, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'SW138P16', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(271, 'CUBICLE-SPACE', '', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(272, 'CUBICLE-SPACE', 'SOPLAGWK032847', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(273, 'CUBICLE-SPACE', 'SOPLAGWK032850', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(274, 'CUBICLE-SPACE', 'SOPLAGWK032819', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(275, 'CUBICLE-SPACE', 'SOPLAGW60019797', NULL, 'Station', 'Onsite', 'Dallas', '', 'Not Set', NULL, NULL, 'Occupied', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(276, 'CUBICLE-SPACE', NULL, NULL, 'Station', 'Onsite', 'San Antonio', '', NULL, NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-11 13:24:53', '2026-03-11 13:24:53'),
(512, 'aaa2', 'sss2', '123', 'Laptop', 'WFH', NULL, NULL, NULL, NULL, NULL, 'Vacant', NULL, NULL, NULL, NULL, NULL, '2026-03-06 11:42:02', '2026-03-10 13:32:10'),
(513, '45345', 'tgtrh', '7868', 'Laptop', 'WFH', NULL, NULL, NULL, NULL, NULL, 'Vacant', NULL, NULL, '', NULL, NULL, '2026-03-06 11:48:02', '2026-03-10 13:32:10'),
(514, 'fghfgh', '-08890789', 'vvxcv', 'Laptop', 'WFH', NULL, NULL, NULL, NULL, NULL, 'Vacant', NULL, NULL, '', NULL, NULL, '2026-03-06 11:48:44', '2026-03-10 13:32:10'),
(515, 'jjjjjj', '99999', '777777', 'Laptop', 'WFH', NULL, NULL, NULL, NULL, NULL, 'Dispose', NULL, NULL, 'ffffff', NULL, NULL, '2026-03-08 23:09:09', '2026-03-10 13:32:10'),
(516, 'jjjjjj8', '999997', '777777', 'Laptop', 'Onsite', 'SPRINGER', 'ATL-010', NULL, NULL, NULL, 'Vacant', NULL, NULL, '', NULL, NULL, '2026-03-10 12:18:49', '2026-03-10 13:32:10'),
(517, 'SPH10000211', 'SOPLAGW600021314', 'CN23234', 'Desktop', 'WFH', '', '', NULL, NULL, NULL, 'Active', NULL, NULL, '', NULL, NULL, '2026-03-10 15:02:29', '2026-03-10 13:38:13'),
(518, 'SPH10000213', '21321', 'CN32435', 'Laptop', 'Onsite', 'NATGEN', 'GR-HOST', NULL, NULL, NULL, 'Active', NULL, NULL, '', NULL, NULL, '2026-03-10 15:03:10', '2026-03-10 15:03:10'),
(519, 'Laptop Dell Latitude', 'SOPLAGLAVANU', 'NJIAB ', 'Desktop', 'Onsite', 'NATGEN', '', NULL, NULL, NULL, 'Active', 'Juan Dela Cruz', '2023-10-25', 'Complete with charger', NULL, NULL, '2026-03-11 14:08:16', '2026-03-11 13:25:57'),
(520, 'USB-C Hub', NULL, NULL, 'Returned Item', 'WFH', NULL, NULL, NULL, NULL, NULL, 'Pending', 'Maria Clara', '2023-10-26', 'Waiting for inspection', NULL, NULL, '2026-03-11 13:25:57', '2026-03-11 13:25:57'),
(521, 'SOPLAGL600', 'HSS', 'MKBB', 'Desktop', 'Onsite', 'NATGEN', '', NULL, NULL, NULL, 'Active', 'Simoun Ibarra', '2023-10-27', 'Left click not working', NULL, NULL, '2026-03-11 14:53:49', '2026-03-11 13:25:57'),
(522, 'Dond do dat', 'JJJ', 'jahab', 'Desktop', 'Onsite', 'DPD', 'ATL-010', NULL, NULL, NULL, 'Active', NULL, NULL, '', NULL, NULL, '2026-03-12 13:14:48', '2026-03-11 15:09:49'),
(523, 'SPH10000211iiy', 'AEIOU', 'WWWWWWW', 'Laptop', 'Onsite', 'NATGEN', 'ATL-008', NULL, NULL, NULL, 'Active', NULL, NULL, '', NULL, NULL, '2026-03-12 14:48:04', '2026-03-12 14:48:04');

-- --------------------------------------------------------

--
-- Table structure for table `hdn_files`
--

CREATE TABLE `hdn_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `hdn_files`
--

INSERT INTO `hdn_files` (`id`, `file_name`, `file_path`, `uploaded_at`, `updated_at`) VALUES
(1, 'Hi ken labyu.pdf', 'uploads/1773012614_Hi_ken_labyu.pdf', '2026-03-08 23:30:14', '2026-03-08 23:30:14'),
(5, 'Hi ren.pdf', 'uploads/1773036913_Hi_ren.pdf', '2026-03-09 06:15:13', '2026-03-09 06:15:13');

-- --------------------------------------------------------

--
-- Table structure for table `hdn_records`
--

CREATE TABLE `hdn_records` (
  `id` int(11) NOT NULL,
  `Date` varchar(255) NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `last_update` date DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_description` text NOT NULL,
  `assigned_to` varchar(100) NOT NULL,
  `status` enum('Pending','In-Progress','Done') DEFAULT 'Pending',
  `date_given` date NOT NULL,
  `date_completed` date DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `task_description`, `assigned_to`, `status`, `date_given`, `date_completed`, `comment`, `created_at`) VALUES
(5, 'dasdada', '3242423', 'Pending', '2026-03-03', NULL, 'mb_strlen($comment): I used mb_strlen (multi-byte string length) to safely check the comment length. It checks if the text exceeds 250 characters before even ', '2026-03-06 10:32:03'),
(6, 'sdfgthyujkilo;p', 'jashdknasdkj', 'Pending', '2026-03-08', '2026-03-10', '', '2026-03-08 23:12:06');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('euc_admin','euc') NOT NULL DEFAULT 'euc',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'EUC Administrator', 'euc_admin', 'admin@ojtbox.com', '$2y$10$NRLDeVtFDR8laqFYxivcQ.uGqiyYVbnrbgHIS/QbVCUSx0ajIUC3i', 'euc_admin', '2026-03-09 12:52:40'),
(2, 'Regular EUC User', 'euc_user', 'user@ojtbox.com', '$2y$10$/6BNr8/zpsuNmiSlSZ/nXubwqxgEvuZ9WGSBySfpyyEPZfKZoPuCq', 'euc', '2026-03-09 12:52:40'),
(4, 'System Admin', 'admin', 'admin@ojtbox1.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'euc_admin', '2026-03-09 13:40:19'),
(5, '', 'admin2', 'admin@gmail.com', '$2y$10$YilsHlLw9k0vZNI5oSlVjesg.aIo17NzKxlyG1YX1BXpBn2tFYjyq', 'euc_admin', '2026-03-09 15:03:31');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `all_assets_master`
--
ALTER TABLE `all_assets_master`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hdn_files`
--
ALTER TABLE `hdn_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hdn_records`
--
ALTER TABLE `hdn_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `all_assets_master`
--
ALTER TABLE `all_assets_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=524;

--
-- AUTO_INCREMENT for table `hdn_files`
--
ALTER TABLE `hdn_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `hdn_records`
--
ALTER TABLE `hdn_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
