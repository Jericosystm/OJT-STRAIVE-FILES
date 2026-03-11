-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 11, 2026 at 08:00 AM
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
  `location_type` enum('WFH','Onsite') DEFAULT 'WFH',
  `cubicle_num` varchar(50) DEFAULT NULL,
  `station` varchar(100) DEFAULT NULL,
  `dispose_date` date DEFAULT NULL,
  `dispose_time` time DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `location` varchar(20) DEFAULT 'WFH',
  `cubicle_no` varchar(50) DEFAULT NULL,
  `department` varchar(100) DEFAULT NULL,
  `cubicle_number` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory_items`
--

INSERT INTO `inventory_items` (`id`, `asset_name`, `host_name`, `serial_num`, `device_type`, `status`, `location_type`, `cubicle_num`, `station`, `dispose_date`, `dispose_time`, `remarks`, `updated_at`, `location`, `cubicle_no`, `department`, `cubicle_number`) VALUES
(15, 'aaa', 'sss', '123', 'Laptop', 'Active', 'WFH', NULL, NULL, NULL, NULL, NULL, '2026-03-06 11:42:02', 'WFH', NULL, NULL, NULL),
(16, 'aaa2', 'sss2', '123', 'Laptop', 'Vacant', 'WFH', NULL, NULL, NULL, NULL, NULL, '2026-03-06 11:42:02', 'WFH', NULL, NULL, NULL),
(17, '45345', 'tgtrh', '7868', 'Laptop', 'Vacant', 'WFH', NULL, NULL, NULL, NULL, '', '2026-03-06 11:48:02', 'WFH', NULL, NULL, NULL),
(19, 'fghfgh', '-08890789', 'vvxcv', 'Laptop', 'Vacant', 'WFH', NULL, NULL, NULL, NULL, '', '2026-03-06 11:48:44', 'WFH', NULL, NULL, NULL),
(20, 'jjjjjj', '99999', '777777', 'Laptop', 'Active', 'WFH', NULL, NULL, NULL, NULL, 'ffffff', '2026-03-11 02:37:07', 'Onsite', NULL, 'DPD', 'LAL-0003'),
(21, 'jjjjjj8', 'renyl', '67676767', 'Desktop', 'Active', 'WFH', NULL, NULL, NULL, NULL, '', '2026-03-11 06:11:37', 'Onsite', NULL, 'DPD', 'PHX-0006');

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
(11, 'NAT-001', 'BGPC000101', 'NATGEN-BU', 'NATGEN', 'Occupied', 1, 1),
(12, 'NAT-002', 'SW161P35', 'ln uz', 'NATGEN', 'Repair', 1, 2),
(13, 'NAT-003', 'BGPC000103', 'NATGEN-BU', 'NATGEN', 'Repair', 1, 3),
(14, 'NAT-004', 'BGPC000104', 'NATGEN-BU', 'NATGEN', 'Occupied', 2, 1),
(15, 'NAT-01', 'BGPC-NAT01', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 1),
(16, 'NAT-02', 'BGPC-NAT02', '', 'NATGEN', 'Vacant', 1, 2),
(17, 'NAT-03', 'BGPC-NAT03', 'NATGEN-AU', 'NATGEN', 'Occupied', 1, 3),
(18, 'NAT-04', 'BGPC-NAT04', 'NATGEN-AU', 'NATGEN', 'Repair', 1, 4),
(19, 'NAT-05', 'BGPC-NAT05', '', 'NATGEN', 'Vacant', 2, 1),
(524, 'SAN-0001', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(525, 'SAN-0002', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(526, 'SAN-0003', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(527, 'SAN-0004', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(528, 'SAN-0005', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(529, 'SAN-0006', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(530, 'SAN-0007', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(531, 'SAN-0008', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(532, 'SAN-0009', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(533, 'SAN-0010', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(534, 'SAN-0011', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(535, 'SAN-0012', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(536, 'SAN-0013', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(537, 'SAN-0014', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(538, 'SAN-0015', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(539, 'SAN-0016', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(540, 'SAN-0017', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(541, 'SAN-0018', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(542, 'SAN-0019', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(543, 'SAN-0020', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(544, 'SAN-0021', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(545, 'SAN-0022', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(546, 'SAN-0023', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(547, 'SAN-0024', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(548, 'SAN-0025', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(549, 'SAN-0026', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(550, 'SAN-0027', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(551, 'SAN-0028', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(552, 'SAN-0029', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(553, 'SAN-0030', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(554, 'SAN-0031', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(555, 'SAN-0032', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(556, 'SAN-0033', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(557, 'SAN-0034', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(558, 'SAN-0035', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(559, 'SAN-0036', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(560, 'SAN-0037', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(561, 'SAN-0038', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(562, 'SAN-0039', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(563, 'SAN-0040', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(564, 'SAN-0041', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(565, 'SAN-0042', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(566, 'SAN-0043', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(567, 'SAN-0044', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(568, 'SAN-0045', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(569, 'SAN-0046', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(570, 'SAN-0047', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(571, 'SAN-0048', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(572, 'SAN-0049', '', 'Not Set', 'San Antonio', 'Vacant', NULL, NULL),
(608, 'PHX-0001', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(609, 'PHX-0002', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(610, 'PHX-0003', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(611, 'PHX-0004', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(612, 'PHX-0005', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(613, 'PHX-0006', 'renyl', 'Not Set', 'Phoenix', 'Occupied', NULL, NULL),
(614, 'PHX-0007', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(615, 'PHX-0008', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(616, 'PHX-0009', '', 'Not Set', 'Phoenix', '', NULL, NULL),
(617, 'PHX-0010', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(618, 'PHX-0011', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(619, 'PHX-0012', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(620, 'PHX-0013', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(621, 'PHX-0014', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(622, 'PHX-0015', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(623, 'PHX-0016', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(624, 'PHX-0017', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(625, 'PHX-0018', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(626, 'PHX-0019', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(627, 'PHX-0020', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(628, 'PHX-0021', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(629, 'PHX-0022', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(630, 'PHX-0023', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(631, 'PHX-0024', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(632, 'PHX-0025', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(633, 'PHX-0026', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(634, 'PHX-0027', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(635, 'PHX-0028', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(636, 'PHX-0029', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(637, 'PHX-0030', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(638, 'PHX-0031', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(639, 'PHX-0032', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(640, 'PHX-0033', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(641, 'PHX-0034', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(642, 'PHX-0035', '', 'Not Set', 'Phoenix', 'Vacant', NULL, NULL),
(643, 'DEN-0001', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(644, 'DEN-0002', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(645, 'DEN-0003', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(646, 'DEN-0004', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(647, 'DEN-0005', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(648, 'DEN-0006', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(649, 'DEN-0007', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(650, 'DEN-0008', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(651, 'DEN-0009', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(652, 'DEN-0010', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(653, 'DEN-0011', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(654, 'DEN-0012', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(655, 'DEN-0013', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(656, 'DEN-0014', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(657, 'DEN-0015', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(658, 'DEN-0016', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(659, 'DEN-0017', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(660, 'DEN-0018', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(661, 'DEN-0019', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(662, 'DEN-0020', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(663, 'DEN-0021', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(664, 'DEN-0022', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(665, 'DEN-0023', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(666, 'DEN-0024', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(667, 'DEN-0025', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(668, 'DEN-0026', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(669, 'DEN-0027', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(670, 'DEN-0028', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(671, 'DEN-0029', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(672, 'DEN-0030', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(673, 'DEN-0031', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(674, 'DEN-0032', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(675, 'DEN-0033', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(676, 'DEN-0034', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(677, 'DEN-0035', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(678, 'DEN-0036', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(679, 'DEN-0037', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(680, 'DEN-0038', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(681, 'DEN-0039', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(682, 'DEN-0040', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(683, 'DEN-0041', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(684, 'DEN-0042', '', 'Not Set', 'Denver', 'Vacant', NULL, NULL),
(685, 'DAL-0001', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(686, 'DAL-0002', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(687, 'DAL-0003', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(688, 'DAL-0004', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(689, 'DAL-0005', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(690, 'DAL-0006', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(691, 'DAL-0007', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(692, 'DAL-0008', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(693, 'DAL-0009', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(694, 'DAL-0010', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(695, 'DAL-0011', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(696, 'DAL-0012', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(697, 'DAL-0013', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(698, 'DAL-0014', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(699, 'DAL-0015', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(700, 'DAL-0016', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(701, 'DAL-0017', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(702, 'DAL-0018', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(703, 'DAL-0019', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(704, 'DAL-0020', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(705, 'DAL-0021', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(706, 'DAL-0022', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(707, 'DAL-0023', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(708, 'DAL-0024', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(709, 'DAL-0025', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(710, 'DAL-0026', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(711, 'DAL-0027', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(712, 'DAL-0028', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(713, 'DAL-0029', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(714, 'DAL-0030', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(715, 'DAL-0031', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(716, 'DAL-0032', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(717, 'DAL-0033', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(718, 'DAL-0034', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL),
(719, 'DAL-0035', '', 'Not Set', 'Dallas', 'Vacant', NULL, NULL);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=720;

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
