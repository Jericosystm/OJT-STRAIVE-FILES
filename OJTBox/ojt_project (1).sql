-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2026 at 10:08 AM
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
-- Table structure for table `inventory_items`
--

CREATE TABLE `inventory_items` (
  `id` int(11) NOT NULL,
  `asset_name` varchar(100) NOT NULL,
  `host_name` varchar(100) NOT NULL,
  `serial_num` varchar(100) NOT NULL,
  `device_type` varchar(50) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'Active',
  `dispose_date` date DEFAULT NULL,
  `dispose_time` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `asset_name`, `host_name`, `serial_num`, `device_type`, `status`, `dispose_date`, `dispose_time`, `remarks`, `updated_at`) VALUES
(15, 'aaa', 'sss', '123', 'Laptop', 'Active', NULL, NULL, NULL, '2026-03-06 11:42:02'),
(16, 'aaa2', 'sss2', '123', 'Laptop', 'Vacant', NULL, NULL, NULL, '2026-03-06 11:42:02'),
(17, '45345', 'tgtrh', '7868', 'Laptop', 'Vacant', NULL, NULL, '', '2026-03-06 11:48:02'),
(19, 'fghfgh', '-08890789', 'vvxcv', 'Laptop', 'Vacant', NULL, NULL, '', '2026-03-06 11:48:44'),
(20, 'jjjjjj', '99999', '777777', 'Laptop', 'Dispose', NULL, NULL, 'ffffff', '2026-03-08 23:09:09'),
(21, 'jjjjjj8', '999997', '777777', 'Laptop', 'Vacant', NULL, NULL, '', '2026-03-09 01:20:47');

-- --------------------------------------------------------

--
-- Table structure for table `production_floor_map`
--

CREATE TABLE `production_floor_map` (
  `id` int(11) NOT NULL,
  `cubicle_no` varchar(20) NOT NULL,
  `hostname` varchar(100) DEFAULT NULL,
  `campaign` varchar(100) DEFAULT NULL,
  `department` varchar(100) DEFAULT 'San Antonio',
  `status` enum('Occupied','Vacant','Repair') DEFAULT 'Vacant',
  `grid_row` int(11) DEFAULT NULL,
  `grid_col` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_floor_map`
--

INSERT INTO `production_floor_map` (`id`, `cubicle_no`, `hostname`, `campaign`, `department`, `status`, `grid_row`, `grid_col`) VALUES
(1, 'ATL-001', 'soplagen', 'DPD', 'San Antonio', 'Occupied', 1, 1),
(2, 'ATL-002', 'SOPLAGL60019819', 'DPD', 'San Antonio', 'Occupied', 1, 2),
(3, 'ATL-003', 'SOPLAGWK036431', 'HINDAWI', 'San Antonio', 'Occupied', 1, 3),
(4, 'ATL-004', 'SA-PC-02', 'Campaign Alpha', 'San Antonio', 'Occupied', 1, 4),
(5, 'ATL-005', '', '', 'San Antonio', 'Vacant', 1, 5),
(6, 'ATL-006', '', '', 'San Antonio', 'Vacant', 2, 1),
(7, 'ATL-007', '', '', 'San Antonio', 'Vacant', 2, 2),
(8, 'ATL-008', 'BGPC-NAT02', '', 'San Antonio', 'Vacant', 2, 3),
(9, 'ATL-009', '', 'DPD', 'San Antonio', 'Vacant', 2, 4),
(10, 'ATL-010', '', '-', 'San Antonio', 'Vacant', 2, 5),
(11, 'NAT-001', 'BGPC000101', 'NATGEN-BU', 'NATGEN', 'Occupied', 1, 1),
(12, 'NAT-002', 'SW161P35', 'ln uz', 'NATGEN', 'Repair', 1, 2),
(13, 'NAT-003', 'BGPC000103', 'NATGEN-BU', 'NATGEN', 'Repair', 1, 3),
(14, 'NAT-004', 'BGPC000104', 'NATGEN-BU', 'NATGEN', 'Occupied', 2, 1),
(15, 'NAT-01', 'BGPC-NAT01', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 1),
(16, 'NAT-02', 'raptors', 'toronto man', 'NATGEN', 'Occupied', 1, 2),
(17, 'NAT-03', 'BGPC-NAT03', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 3),
(18, 'NAT-04', 'BGPC-NAT04', 'NATGEN-AU', 'NATGEN', 'Repair', 1, 4),
(19, 'NAT-05', 'BGPC-NAT05', '', 'NATGEN', 'Vacant', 2, 1),
(20, 'SA-001', '', 'DPD', 'San Antonio', 'Vacant', 1, 1),
(21, 'SA-002', 'SOPLAGWK035722', 'DPD', 'San Antonio', 'Occupied', 1, 2),
(22, 'SA-003', 'torotor', 'DPD', 'San Antonio', 'Occupied', 1, 3),
(23, 'SA-004', 'renyl pc', 'DPD', 'San Antonio', 'Occupied', 1, 4),
(24, 'SA-005', '', 'Under Maintenance', 'San Antonio', 'Repair', 1, 5),
(25, 'SA-006', 'SA-PC-06', 'Campaign Beta', 'San Antonio', 'Occupied', 1, 6),
(26, 'SA-007', 'hihgh', '-', 'San Antonio', 'Occupied', 1, 7),
(27, 'SA-008', 'SA-PC-08', 'Campaign Gamma', 'San Antonio', 'Occupied', 2, 1),
(28, 'SA-009', 'SOPLAGL60019819', 'DPD', 'San Antonio', 'Occupied', 2, 2),
(29, 'SA-010', 'raprtos', 'test', 'San Antonio', 'Occupied', 2, 3),
(30, 'SA-011', '', '', 'San Antonio', 'Vacant', 2, 4),
(31, 'SA-012', 'laptop', 'dsdssd', 'San Antonio', 'Occupied', 2, 5),
(32, 'SA-013', 'SA-PC-13', 'Campaign Delta', 'San Antonio', 'Occupied', 2, 6),
(33, 'SA-014', 'SA-PC-18', 'Campaign Zeta', 'San Antonio', 'Occupied', 2, 7),
(34, 'SA-015', 'SA-PC-15', 'Campaign Epsilon', 'San Antonio', 'Occupied', 3, 1),
(35, 'SA-016', 'SA-PC-39', 'Campaign Zeta', 'San Antonio', 'Occupied', 3, 2),
(36, 'SA-017', 'SA-PC-16', 'Campaign Epsilon', 'San Antonio', 'Occupied', 3, 3),
(37, 'SA-018', '', '', 'San Antonio', 'Vacant', 3, 4),
(38, 'SA-019', '', 'No Network Connection', 'San Antonio', 'Repair', 3, 5),
(39, 'SA-020', 'SA-PC-20', 'Campaign Zeta', 'San Antonio', 'Occupied', 3, 6),
(40, 'SA-021', 'SA-PC-11', 'Campaign Delta', 'San Antonio', 'Occupied', 3, 7),
(41, 'SA-022', 'SA-PC-22', 'Campaign Alpha', 'San Antonio', 'Occupied', 4, 1),
(42, 'SA-023', 'SA-PC-23', 'Campaign Alpha', 'San Antonio', 'Occupied', 4, 2),
(43, 'SA-024', 'laptop', 's', 'San Antonio', 'Occupied', 4, 3),
(44, 'SA-025', '', '', 'San Antonio', 'Vacant', 4, 4),
(45, 'SA-026', 'SOPLAGL6001967', 'DPD', 'San Antonio', 'Occupied', 4, 5),
(46, 'SA-027', 'SA-PC-27', 'Campaign Beta', 'San Antonio', 'Occupied', 4, 6),
(47, 'SA-028', 'SA-PC-04', 'Campaign Beta', 'San Antonio', 'Occupied', 4, 7),
(48, 'SA-029', 'SA-PC-29', 'Campaign Gamma', 'San Antonio', 'Occupied', 5, 1),
(49, 'SA-030', 'SA-PC-30', 'Campaign Gamma', 'San Antonio', 'Occupied', 5, 2),
(50, 'SA-031', 'SOPLAGL60019819', 'DPD', 'San Antonio', 'Occupied', 5, 3),
(51, 'SA-032', '', '', 'San Antonio', 'Vacant', 5, 4),
(52, 'SA-033', '', '', 'San Antonio', 'Vacant', 5, 5),
(53, 'SA-034', 'SA-PC-34', 'Campaign Delta', 'San Antonio', 'Occupied', 5, 6),
(54, 'SA-035', 'SOPLAGW60019765', 'DPD', 'San Antonio', 'Occupied', 5, 7),
(55, 'SA-036', 'SA-PC-36', 'Campaign Epsilon', 'San Antonio', 'Occupied', 6, 1),
(56, 'SA-037', 'SA-PC-37', 'Campaign Epsilon', 'San Antonio', 'Occupied', 6, 2),
(57, 'SA-038', 'SA-PC-32', 'Campaign Delta', 'San Antonio', 'Occupied', 6, 3),
(58, 'SA-039', '', '', 'San Antonio', 'Vacant', 6, 4),
(69, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(70, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(71, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(72, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(73, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(74, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(75, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(76, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(77, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(78, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(79, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(80, '', 'SOPLAGL6001969', 'DPD', 'Chicago', 'Occupied', NULL, NULL),
(81, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(82, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(83, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(84, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(85, '', 'renyl pc', 'DPD', 'Chicago', 'Occupied', NULL, NULL),
(86, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(87, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(88, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(89, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(90, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(91, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(92, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(93, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(94, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(95, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(96, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(97, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(98, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(99, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(100, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(101, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(102, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(103, '', '', '', 'Chicago', 'Vacant', NULL, NULL),
(104, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(105, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(106, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(107, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(108, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(109, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(110, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(111, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(112, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(113, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(114, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(115, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(116, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(117, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(118, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(119, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(120, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(121, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(122, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(123, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(124, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(125, '', 'soplaglag', 'dasasd', 'Miami', 'Occupied', NULL, NULL),
(126, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(127, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(128, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(129, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(130, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(131, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(132, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(133, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(134, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(135, '', '', '', 'Miami', 'Vacant', NULL, NULL),
(172, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(173, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(174, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(175, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(176, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(177, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(178, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(179, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(180, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(181, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(182, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(183, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(184, 'GR-HOST', 'hahahaa', '', 'Gray Room', 'Occupied', NULL, NULL),
(185, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(186, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(187, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(188, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(189, 'GR-HOST', '', '', 'Gray Room', 'Vacant', NULL, NULL),
(190, 'PHX-0001', '', 'DPD', 'Phoenix', 'Vacant', NULL, NULL),
(191, 'PHX-0002', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(192, 'PHX-0003', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(193, 'PHX-0004', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(194, 'PHX-0005', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(195, 'PHX-0006', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(196, 'PHX-0007', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(197, 'PHX-0008', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(198, 'PHX-0009', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(199, 'PHX-0010', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(200, 'PHX-0011', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(201, 'PHX-0012', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(202, 'PHX-0013', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(203, 'PHX-0014', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(204, 'PHX-0015', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(205, 'PHX-0016', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(206, 'PHX-0017', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(207, 'PHX-0018', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(208, 'PHX-0019', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(209, 'PHX-0020', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(210, 'PHX-0021', 'ken pc haha', 'dasasd', 'Phoenix', 'Occupied', NULL, NULL),
(211, 'PHX-0022', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(212, 'PHX-0023', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(213, 'PHX-0024', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(214, 'PHX-0025', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(215, 'PHX-0026', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(216, 'PHX-0027', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(217, 'PHX-0028', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(218, 'PHX-0029', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(219, 'PHX-0030', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(220, 'PHX-0031', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(221, 'PHX-0032', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(222, 'PHX-0033', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(223, 'PHX-0034', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(224, 'PHX-0035', '', '', 'Phoenix', 'Vacant', NULL, NULL),
(225, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(226, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(227, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(228, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(229, '', 'halimbawa', '', 'Orlando', 'Occupied', NULL, NULL),
(230, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(231, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(232, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(233, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(234, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(235, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(236, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(237, '', 'halimaw', '', 'Orlando', 'Occupied', NULL, NULL),
(238, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(239, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(240, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(241, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(242, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(243, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(244, '', '', 'Not Set', 'Orlando', 'Vacant', NULL, NULL),
(245, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(246, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(247, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(248, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(249, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(250, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(251, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(252, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(253, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(254, '', 'reanto', '', 'Denver', 'Occupied', NULL, NULL),
(255, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(256, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(257, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(258, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(259, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(260, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(261, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(262, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(263, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(264, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(265, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(266, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(267, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(268, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(269, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(270, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(271, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(272, '', 'pomting', '', 'Denver', 'Occupied', NULL, NULL),
(273, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(274, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(275, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(276, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(277, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(278, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(279, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(280, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(281, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(282, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(283, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(284, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(285, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(286, '', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(287, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(288, '', 'SOPLAGL60019819', 'dasasd', 'Dallas', 'Occupied', NULL, NULL),
(289, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(290, '', 'labyu ken', '', 'Dallas', 'Occupied', NULL, NULL),
(291, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(292, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(293, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(294, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(295, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(296, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(297, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(298, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(299, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(300, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(301, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(302, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(303, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(304, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(305, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(306, '', 'eco man', '', 'Dallas', 'Occupied', NULL, NULL),
(307, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(308, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(309, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(310, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(311, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(312, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(313, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(314, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(315, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(316, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(317, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(318, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(319, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(320, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(321, '', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(322, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(323, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(324, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(325, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(326, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(327, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(328, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(329, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(330, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(331, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(332, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(333, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(334, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(335, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(336, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(337, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(338, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(339, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(340, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(341, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(342, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(343, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(344, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(345, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(346, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(347, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(348, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(349, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(350, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(351, '', 'TITE', '', 'Atlanta', 'Occupied', NULL, NULL),
(352, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(353, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(354, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(355, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(356, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(357, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(358, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(359, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(360, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(361, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(362, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(363, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(364, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(365, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(366, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(367, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(368, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(369, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(370, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(371, '', 'PAHIRAP', '', 'Atlanta', 'Occupied', NULL, NULL),
(372, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(373, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(374, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(375, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(376, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(377, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(378, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(379, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(380, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(381, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(382, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(383, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(384, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(385, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(386, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(387, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(388, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(389, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(390, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(391, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(392, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(393, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(394, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(395, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(396, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(397, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(398, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(399, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(400, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(401, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(402, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(403, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(404, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(405, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(406, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(407, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(408, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(409, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(410, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(411, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(412, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(413, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(414, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(415, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(416, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(417, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(418, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(419, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(420, '', '', 'Not Set', 'Atlanta', 'Vacant', NULL, NULL),
(421, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(422, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(423, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(424, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(425, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(426, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(427, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(428, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(429, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(430, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(431, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(432, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(433, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(434, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(435, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(436, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(437, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(438, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(439, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(440, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(441, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(442, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(443, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(444, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(445, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(446, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(447, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(448, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(449, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(450, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(451, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(452, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(453, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(454, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(455, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(456, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(457, '', '', 'Not Set', 'Indiana', 'Vacant', NULL, NULL),
(458, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(459, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(460, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(461, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(462, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(463, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(464, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(465, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(466, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(467, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(468, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(469, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(470, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(471, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(472, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(473, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(474, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(475, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(476, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(477, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(478, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(479, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(480, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(481, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(482, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(483, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(484, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(485, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(486, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(487, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(488, '', 'SOPLAGL60019819', 'SD', 'Los Angeles', 'Occupied', NULL, NULL),
(489, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(490, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(491, '', 'LAPTOP', 'dazes', 'Los Angeles', 'Occupied', NULL, NULL),
(492, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(493, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(494, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(495, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(496, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(497, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(498, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(499, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(500, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(501, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(502, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(503, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(504, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(505, '', 'LAPTOP', 'dasasd', 'Los Angeles', 'Occupied', NULL, NULL),
(506, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(507, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(508, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(509, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(510, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(511, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(512, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(513, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(514, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(515, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(516, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(517, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(518, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(519, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(520, '', '', 'Not Set', 'Los Angeles', 'Vacant', NULL, NULL),
(521, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(522, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(523, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(524, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(525, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(526, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(527, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(528, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(529, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(530, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(531, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(532, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(533, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(534, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(535, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(536, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(537, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(538, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(539, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(540, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(541, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(542, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(543, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(544, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(545, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(546, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(547, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(548, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(549, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(550, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(551, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(552, '', 'LAPTOP', 'DPD', 'Boston', 'Occupied', NULL, NULL),
(553, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(554, '', 'sir ryan', 'halikmaw', 'Boston', 'Occupied', NULL, NULL),
(555, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(556, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(557, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(558, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(559, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(560, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(561, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(562, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(563, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(564, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(565, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(566, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(567, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(568, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(569, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(570, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(571, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(572, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(573, '', '', 'Not Set', 'Boston', 'Vacant', NULL, NULL),
(629, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(630, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(631, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(632, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(633, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(634, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(635, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(636, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(637, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(638, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(639, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(640, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(641, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(642, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(643, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(644, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(645, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(646, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(647, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(648, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(649, '', 'GRIZZLES', 'DPD', 'Toronto', 'Occupied', NULL, NULL),
(650, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(651, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(652, '', 'raprtos', 'test', 'Toronto', 'Occupied', NULL, NULL),
(653, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(654, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(655, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(656, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(657, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(658, '', 'TRREX', 'DPD', 'Toronto', 'Occupied', NULL, NULL),
(659, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(660, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(661, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(662, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(663, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(664, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(665, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(666, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(667, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(668, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(669, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(670, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(671, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(672, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(673, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(674, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(675, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(676, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(677, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(678, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(679, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(680, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(681, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(682, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL),
(683, '', '', 'Not Set', 'Toronto', 'Vacant', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `returned_items`
--

CREATE TABLE `returned_items` (
  `id` int(11) NOT NULL,
  `item_name` varchar(255) NOT NULL,
  `returned_by` varchar(100) NOT NULL,
  `date_returned` date NOT NULL,
  `status` enum('Pending','Verified','Damaged') DEFAULT 'Pending',
  `remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `returned_items`
--

INSERT INTO `returned_items` (`id`, `item_name`, `returned_by`, `date_returned`, `status`, `remarks`) VALUES
(1, 'Laptop Dell Latitude', 'Juan Dela Cruz', '2023-10-25', 'Verified', 'Complete with charger'),
(2, 'USB-C Hub', 'Maria Clara', '2023-10-26', 'Pending', 'Waiting for inspection'),
(3, 'Wireless Mouse', 'Simoun Ibarra', '2023-10-27', 'Damaged', 'Left click not working');

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
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `role`, `created_at`) VALUES
(3, 'admin1', 'admin@example.com', '$2y$10$8K1p/a06vLOv9eW9.S6Sdu.TfP0vO5uH5uV9eN3mK6Z1f2g3h4i5j', 'Test User', 'user', '2026-03-08 08:42:24'),
(6, 'admin', 'admin@ojtbox.com', '$2y$10$.j5PRcLaQaujYqzhhlCJheQjI5sLCYljv6Drn1jGUABt6mtA43jCG', 'System Admin', 'user', '2026-03-08 08:48:53'),
(7, 'trial1', 'trial@gmail.com', 'trial123', 'Try Once', 'admin', '2026-03-08 09:00:43'),
(16, 'admin12', 'admin@ojtbox12.com', '$2y$10$8S8GkX9N/9L5.V9WdG6uO.8vG5Q7pZ4w6f6K7j8L9M0N1P2Q3R4S5', 'System Administrator', 'admin', '2026-03-09 13:52:59');

--
-- Indexes for dumped tables
--

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
-- Indexes for table `inventory_items`
--
ALTER TABLE `inventory_items`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `production_floor_map`
--
ALTER TABLE `production_floor_map`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `returned_items`
--
ALTER TABLE `returned_items`
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
-- AUTO_INCREMENT for table `inventory_items`
--
ALTER TABLE `inventory_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `production_floor_map`
--
ALTER TABLE `production_floor_map`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=684;

--
-- AUTO_INCREMENT for table `returned_items`
--
ALTER TABLE `returned_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
