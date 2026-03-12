-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 09, 2026 at 05:55 PM
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
(1, 'ATL-001', 'SOPLAGL60019819', 'DPD', 'San Antonio', 'Repair', 1, 1),
(2, 'ATL-002', 'BGPC000032734', 'DPD', 'San Antonio', 'Occupied', 1, 2),
(3, 'ATL-003', 'SIR PONTS', '-', 'San Antonio', 'Repair', 1, 3),
(4, 'ATL-004', 'SOPLAGWK035318', 'DPD', 'San Antonio', 'Vacant', 1, 4),
(5, 'ATL-005', 'SOPLAGWK035722', 'DPD', 'San Antonio', 'Occupied', 1, 5),
(6, 'ATL-006', 'SOPLAGWK035254', 'DPD', 'San Antonio', '', 2, 1),
(7, 'ATL-007', 'SOPLAGW60019765', 'DPD', 'San Antonio', 'Occupied', 2, 2),
(8, 'ATL-008', 'SOPLAGW60019764', 'DPD', 'San Antonio', 'Occupied', 2, 3),
(9, 'ATL-009', 'soplag', '-', 'San Antonio', NULL, 2, 4),
(10, 'ATL-010', 'SOPLAGWK036431', 'HINDAWI', 'San Antonio', 'Occupied', 2, 5),
(11, 'NAT-001', 'BGPC000101', 'NATGEN-BU', 'NATGEN', 'Occupied', 1, 1),
(12, 'NAT-002', 'SW161P35', 'ln uz', 'NATGEN', 'Repair', 1, 2),
(13, 'NAT-003', 'BGPC000103', 'NATGEN-BU', 'NATGEN', 'Repair', 1, 3),
(14, 'NAT-004', 'BGPC000104', 'NATGEN-BU', 'NATGEN', 'Occupied', 2, 1),
(15, 'NAT-01', 'BGPC-NAT01', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 1),
(16, 'NAT-02', 'BGPC-NAT02', '', 'NATGEN', 'Vacant', 1, 2),
(17, 'NAT-03', 'BGPC-NAT03', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 3),
(18, 'NAT-04', 'BGPC-NAT04', 'NATGEN-AU', 'NATGEN', 'Repair', 1, 4),
(19, 'NAT-05', 'BGPC-NAT05', '', 'NATGEN', 'Vacant', 2, 1),
(20, 'SA-001', 'SA-PC-01', 'Campaign Alpha', 'San Antonio', 'Occupied', 1, 1),
(21, 'SA-002', 'SA-PC-02', 'Campaign Alpha', 'San Antonio', 'Occupied', 1, 2),
(22, 'SA-003', '', '', 'San Antonio', 'Vacant', 1, 3),
(23, 'SA-004', 'SA-PC-04', 'Campaign Beta', 'San Antonio', 'Occupied', 1, 4),
(24, 'SA-005', '', 'Under Maintenance', 'San Antonio', 'Repair', 1, 5),
(25, 'SA-006', 'SA-PC-06', 'Campaign Beta', 'San Antonio', 'Occupied', 1, 6),
(26, 'SA-007', '', '', 'San Antonio', 'Vacant', 1, 7),
(27, 'SA-008', 'SA-PC-08', 'Campaign Gamma', 'San Antonio', 'Occupied', 2, 1),
(28, 'SA-009', 'SA-PC-09', 'Campaign Gamma', 'San Antonio', 'Occupied', 2, 2),
(29, 'SA-010', '', '', 'San Antonio', 'Vacant', 2, 3),
(30, 'SA-011', 'SA-PC-11', 'Campaign Delta', 'San Antonio', 'Occupied', 2, 4),
(31, 'SA-012', '', '', 'San Antonio', 'Vacant', 2, 5),
(32, 'SA-013', 'SA-PC-13', 'Campaign Delta', 'San Antonio', 'Occupied', 2, 6),
(33, 'SA-014', '', '', 'San Antonio', 'Vacant', 2, 7),
(34, 'SA-015', 'SA-PC-15', 'Campaign Epsilon', 'San Antonio', 'Occupied', 3, 1),
(35, 'SA-016', 'SA-PC-16', 'Campaign Epsilon', 'San Antonio', 'Occupied', 3, 2),
(36, 'SA-017', '', '', 'San Antonio', 'Vacant', 3, 3),
(37, 'SA-018', 'SA-PC-18', 'Campaign Zeta', 'San Antonio', 'Occupied', 3, 4),
(38, 'SA-019', '', 'No Network Connection', 'San Antonio', 'Repair', 3, 5),
(39, 'SA-020', 'SA-PC-20', 'Campaign Zeta', 'San Antonio', 'Occupied', 3, 6),
(40, 'SA-021', '', '', 'San Antonio', 'Vacant', 3, 7),
(41, 'SA-022', 'SA-PC-22', 'Campaign Alpha', 'San Antonio', 'Occupied', 4, 1),
(42, 'SA-023', 'SA-PC-23', 'Campaign Alpha', 'San Antonio', 'Occupied', 4, 2),
(43, 'SA-024', '', '', 'San Antonio', 'Vacant', 4, 3),
(44, 'SA-025', 'SA-PC-25', 'Campaign Beta', 'San Antonio', 'Occupied', 4, 4),
(45, 'SA-026', '', '', 'San Antonio', 'Vacant', 4, 5),
(46, 'SA-027', 'SA-PC-27', 'Campaign Beta', 'San Antonio', 'Occupied', 4, 6),
(47, 'SA-028', '', '', 'San Antonio', 'Vacant', 4, 7),
(48, 'SA-029', 'SA-PC-29', 'Campaign Gamma', 'San Antonio', 'Occupied', 5, 1),
(49, 'SA-030', 'SA-PC-30', 'Campaign Gamma', 'San Antonio', 'Occupied', 5, 2),
(50, 'SA-031', '', '', 'San Antonio', 'Vacant', 5, 3),
(51, 'SA-032', 'SA-PC-32', 'Campaign Delta', 'San Antonio', 'Occupied', 5, 4),
(52, 'SA-033', '', '', 'San Antonio', 'Vacant', 5, 5),
(53, 'SA-034', 'SA-PC-34', 'Campaign Delta', 'San Antonio', 'Occupied', 5, 6),
(54, 'SA-035', '', '', 'San Antonio', 'Vacant', 5, 7),
(55, 'SA-036', 'SA-PC-36', 'Campaign Epsilon', 'San Antonio', 'Occupied', 6, 1),
(56, 'SA-037', 'SA-PC-37', 'Campaign Epsilon', 'San Antonio', 'Occupied', 6, 2),
(57, 'SA-038', '', '', 'San Antonio', 'Vacant', 6, 3),
(58, 'SA-039', 'SA-PC-39', 'Campaign Zeta', 'San Antonio', 'Occupied', 6, 4),
(59, 'SA-040', '', '', 'San Antonio', 'Vacant', 6, 5),
(60, 'SA-041', 'SA-PC-41', 'Campaign Zeta', 'San Antonio', 'Occupied', 6, 6),
(61, 'SA-042', '', '', 'San Antonio', 'Vacant', 6, 7),
(62, 'SA-043', 'SA-PC-43', 'Campaign Alpha', 'San Antonio', 'Occupied', 7, 1),
(63, 'SA-044', 'SA-PC-44', 'Campaign Alpha', 'San Antonio', 'Occupied', 7, 2),
(64, 'SA-045', '', '', 'San Antonio', 'Vacant', 7, 3),
(65, 'SA-046', 'SA-PC-46', 'Campaign Beta', 'San Antonio', 'Occupied', 7, 4),
(66, 'SA-047', '', 'Broken Monitor', 'San Antonio', 'Repair', 7, 5),
(67, 'SA-048', 'SA-PC-48', 'Campaign Beta', 'San Antonio', 'Occupied', 7, 6),
(68, 'SA-049', '', '', 'San Antonio', 'Vacant', 7, 7);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
