-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 06, 2025 at 09:13 AM
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
-- Database: `employeedatabase`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password`) VALUES
(1, 'ZFF@gmail.com', '$2y$10$stwllqAZaTT0EfAmAFOKheFw8GiAYvjEf5UvIqCH6w7iIHp8B/MA6');

-- --------------------------------------------------------

--
-- Table structure for table `employee_db`
--

CREATE TABLE `employee_db` (
  `employee_id` int(11) NOT NULL,
  `firstName` varchar(100) NOT NULL,
  `lastName` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_db`
--

INSERT INTO `employee_db` (`employee_id`, `firstName`, `lastName`) VALUES
(1, 'ABEGALE', 'ESCOLANO'),
(2, 'AIRA JEAN', 'AUZA'),
(3, 'ALPHONSE RAPHAEL', 'GUEVARRA'),
(4, 'ANGELI', 'COMIA'),
(5, 'ANJELICA JOY', 'NACNAC'),
(6, 'ANTHONY ROSENDO', 'FARAON'),
(7, 'AUSTERE', 'PANADERO'),
(8, 'BARBARA', 'JAMILI'),
(9, 'CARL MYSON', 'DULLA'),
(10, 'CATHERINE', 'CHUNG'),
(11, 'CATHERINE RAISA KIMBERLY', 'MANDIGMA'),
(12, 'CATHYRAIN', 'RAMIREZ'),
(13, 'CHANTAL', 'CHICANO'),
(14, 'CHARMAINE', 'CARATING'),
(15, 'CHERRY', 'RENEGADO'),
(16, 'CHRISTIAN', 'DENIEGA'),
(17, 'CINDY GRACE', 'GUERBO'),
(18, 'DANIELLE ANNE', 'CADA'),
(19, 'DORIE LYN', 'BALANOBA'),
(20, 'ELAINE JOYCE', 'DIAZ'),
(21, 'ELIZABETH', 'LAMADRID');

-- --------------------------------------------------------

--
-- Table structure for table `health_records`
--

CREATE TABLE `health_records` (
  `id` int(11) NOT NULL,
  `employee_name` varchar(255) NOT NULL,
  `unit` varchar(255) NOT NULL,
  `wellness_status` varchar(3) NOT NULL,
  `symptoms` text DEFAULT NULL,
  `symptoms_management` text DEFAULT NULL,
  `household_symptoms` varchar(3) NOT NULL,
  `household_symptoms_details` text DEFAULT NULL,
  `environmental_check` varchar(255) NOT NULL,
  `environmental_issues` text DEFAULT NULL,
  `mental_health_check` varchar(255) NOT NULL,
  `mental_health_support` text DEFAULT NULL,
  `current_status` varchar(255) NOT NULL,
  `submission_date` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `health_records`
--

INSERT INTO `health_records` (`id`, `employee_name`, `unit`, `wellness_status`, `symptoms`, `symptoms_management`, `household_symptoms`, `household_symptoms_details`, `environmental_check`, `environmental_issues`, `mental_health_check`, `mental_health_support`, `current_status`, `submission_date`) VALUES
(22, 'AIRA JEAN AUZA', 'UNFPA', 'no', 'Fever, Cough, Difficulty in breathing or Shortness of breath', 'consultProfessional', 'yes', 'Fever, Cough, Difficulty in breathing', 'yes', 'Natural disasters – flooding, Natural disasters – earthquake, Natural disasters – volcanic eruption', 'yes', 'mentalhealthsupport - need ZFF support', 'rto', '2025-01-31 06:54:25'),
(23, 'DANIELLE ANNE CADA', 'Corpcomm', 'no', 'Fever, Cough, Other, lagnat', 'consultProfessional', 'yes', 'Fever, Cough, Other, lagnat', 'yes', 'Natural disasters – flooding', 'yes', 'mentalhealthsupport - can manage the situation', 'rto', '2025-01-31 06:55:25'),
(24, 'ABEGALE ESCOLANO', 'Office of the Executive Director', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'rto', '2025-01-31 07:23:46'),
(25, 'ANGELI COMIA', 'Corpcomm', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'remote', '2025-01-31 07:24:30'),
(26, 'ANTHONY ROSENDO FARAON', 'Finance', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'remote', '2025-01-31 07:24:47'),
(27, 'CHRISTIAN DENIEGA', 'Corpcomm', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'remote', '2025-01-31 07:25:21'),
(29, 'AIRA JEAN AUZA', 'ZFFI', 'no', 'Fever, Cough, Difficulty in breathing or Shortness of breath, Other, lagnat', 'consultProfessional', 'yes', 'Fever, Cough, Difficulty in breathing, Other, lagnat', 'yes', 'Natural disasters – severe weather disturbance', 'yes', 'mentalhealthsupport - need ZFF support', 'remote', '2025-02-03 07:02:17'),
(30, 'ABEGALE ESCOLANO', 'Office of the Executive Director', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'rto', '2025-02-03 07:14:31'),
(31, 'ALPHONSE RAPHAEL GUEVARRA', 'Corpcomm', 'yes', '', '', 'no', '', 'none', '', 'none', '', 'remote', '2025-02-03 07:15:41'),
(37, 'ABEGALE ESCOLANO', 'Corpcomm', 'no', 'Chills, Colds or congestion or runny nose, ', 'manageAtHome', 'yes', 'Chills, Colds or congestion or runny nose, Sore Throat, Other, lagnat', 'none', '', 'none', '', 'remote', '2025-02-04 04:53:52'),
(38, 'AIRA JEAN AUZA', 'Corpcomm', 'no', 'pagtatae', 'manageAtHome', 'no', '', 'none', '', 'none', '', 'remote', '2025-02-04 04:57:10'),
(39, 'ALPHONSE RAPHAEL GUEVARRA', 'ZFFI', 'no', 'Chills, Colds or congestion or runny nose, lagnat', 'manageAtHome', 'yes', 'Chills', 'none', '', 'none', '', 'remote', '2025-02-04 04:57:54'),
(40, 'AIRA JEAN AUZA', 'Finance', 'yes', '', '', 'yes', '', 'none', '', 'none', '', 'remote', '2025-02-05 01:24:54'),
(41, 'ANGELI COMIA', 'Corpcomm', 'no', 'lagnat', 'consultProfessional', 'yes', 'Other, lagnat', 'none', '', 'none', '', 'remote', '2025-02-05 01:25:45'),
(42, 'ANTHONY ROSENDO FARAON', 'Corpcomm', 'yes', '', '', 'yes', '', 'none', '', 'none', '', 'remote', '2025-02-05 01:26:15'),
(43, 'DORIE LYN BALANOBA', 'ZFFI', 'yes', '', '', 'yes', 'Other, lagnat', 'none', '', 'none', '', 'remote', '2025-02-05 01:29:34'),
(44, 'CHRISTIAN DENIEGA', 'ZFFI', 'no', 'Chills, Colds or congestion or runny nose, Cough', 'consultProfessional', 'yes', 'Chills, Colds or congestion or runny nose, Cough, Other, lagnat', 'none', '', 'none', '', 'remote', '2025-02-05 09:28:05');

-- --------------------------------------------------------

--
-- Table structure for table `symptoms`
--

CREATE TABLE `symptoms` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` enum('employee','household') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `symptoms`
--

INSERT INTO `symptoms` (`id`, `name`, `category`) VALUES
(1, 'Fever', 'employee'),
(2, 'Cough', 'employee'),
(3, 'Difficulty in breathing or Shortness of breath', 'employee'),
(4, 'Chills', 'employee'),
(5, 'Sore Throat', 'employee'),
(6, 'Diarrhea', 'employee'),
(7, 'Headache', 'employee'),
(8, 'Colds or congestion or runny nose', 'employee'),
(9, 'Nausea or vomiting', 'employee'),
(10, 'Muscle pain', 'employee'),
(11, 'Recent loss of taste and smell', 'employee'),
(12, 'Fever', 'household'),
(13, 'Cough', 'household'),
(14, 'Difficulty in breathing', 'household'),
(15, 'Shortness of breath', 'household'),
(16, 'Chills', 'household'),
(17, 'Sore Throat', 'household'),
(18, 'Diarrhea', 'household'),
(19, 'Headache', 'household'),
(20, 'Colds or congestion or runny nose', 'household'),
(21, 'Nausea or vomiting', 'household'),
(22, 'Muscle pain', 'household'),
(23, 'Recent loss of taste and smell', 'household');

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `units`
--

INSERT INTO `units` (`id`, `name`) VALUES
(2, 'Corpcomm'),
(3, 'Finance'),
(4, 'HRIMSA'),
(5, 'KGJF'),
(6, 'LHS'),
(7, 'Nutrition Portfolio'),
(1, 'Office of the Executive Director'),
(8, 'S&P'),
(9, 'TCI'),
(10, 'UNFPA'),
(11, 'ZFFI');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `employee_db`
--
ALTER TABLE `employee_db`
  ADD PRIMARY KEY (`employee_id`);

--
-- Indexes for table `health_records`
--
ALTER TABLE `health_records`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `symptoms`
--
ALTER TABLE `symptoms`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_db`
--
ALTER TABLE `employee_db`
  MODIFY `employee_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `health_records`
--
ALTER TABLE `health_records`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `symptoms`
--
ALTER TABLE `symptoms`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `units`
--
ALTER TABLE `units`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
