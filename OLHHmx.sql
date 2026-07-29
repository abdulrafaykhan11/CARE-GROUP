-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 25, 2026 at 08:41 AM
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
-- Database: `care`
--

-- --------------------------------------------------------

--
-- Table structure for table `appointments`
--

CREATE TABLE `appointments` (
  `appointment_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `appointment_date` date NOT NULL,
  `appointment_time` time NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('Pending','Confirmed','Completed','Cancelled','NoShow') DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `appointments`
--

INSERT INTO `appointments` (`appointment_id`, `doctor_id`, `patient_id`, `clinic_id`, `appointment_date`, `appointment_time`, `reason`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(4, 3, 3, 1, '2026-07-24', '03:45:00', 'uihoij', 'Completed', 'hoi0p9jsoijriovaedoij', '2026-07-19 10:20:16', '2026-07-20 07:14:42'),
(6, 4, 5, 1, '2026-07-23', '05:00:00', 'uihoij', 'Pending', '', '2026-07-20 06:11:58', '2026-07-20 06:11:58'),
(7, 5, 4, 3, '2026-07-24', '11:00:00', 'Headache', 'Cancelled', '', '2026-07-20 06:42:17', '2026-07-20 07:09:23');

-- --------------------------------------------------------

--
-- Table structure for table `cities`
--

CREATE TABLE `cities` (
  `city_id` int(11) NOT NULL,
  `city_name` varchar(100) NOT NULL,
  `state` varchar(100) DEFAULT NULL,
  `country` varchar(100) DEFAULT 'Pakistan',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cities`
--

INSERT INTO `cities` (`city_id`, `city_name`, `state`, `country`, `status`, `created_at`, `updated_at`) VALUES
(1, 'karachi', 'Sindh', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(2, 'Lahore', 'Punjab', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(3, 'Sukkur', 'Sindh', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(4, 'Larkana', 'Sindh', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(5, 'Faisalabad', 'Punjab', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(6, 'Peshawar', 'KPK', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(7, 'Quetta', 'KPK', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43'),
(8, 'Multan', 'Punjab', 'Pakistan', 'Active', '2026-07-17 08:53:43', '2026-07-17 08:53:43');

-- --------------------------------------------------------

--
-- Table structure for table `clinics`
--

CREATE TABLE `clinics` (
  `clinic_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `clinic_name` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text NOT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `clinics`
--

INSERT INTO `clinics` (`clinic_id`, `city_id`, `clinic_name`, `phone`, `email`, `address`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Aga Khan University Hospital', '021-111-911-911', 'info@aku.edu', 'National Stadium Road, Karachi', 'Active', '2026-07-19 09:56:17', '2026-07-19 09:56:17'),
(2, 4, 'The Indus Hospital', '021-111-111-123', 'info@indushospital.org.pk', 'Korangi Crossing, Karachi', 'Active', '2026-07-19 09:56:17', '2026-07-19 09:56:17'),
(3, 7, 'Shifa Medical Clinic', '021-34567890', 'contact@shifaclinic.com', 'Block 13-C, Gulshan-e-Iqbal, Karachi', 'Active', '2026-07-19 09:56:17', '2026-07-19 09:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `diseases`
--

CREATE TABLE `diseases` (
  `disease_id` int(11) NOT NULL,
  `disease_name` varchar(150) NOT NULL,
  `symptoms` text DEFAULT NULL,
  `prevention` text DEFAULT NULL,
  `treatment` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `doctors`
--

CREATE TABLE `doctors` (
  `doctor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `specialization_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `full_address` text DEFAULT NULL,
  `experience_years` int(11) DEFAULT NULL CHECK (`experience_years` >= 0),
  `qualification` varchar(255) NOT NULL,
  `pmdc_registration_number` varchar(50) NOT NULL,
  `cnic` varchar(20) NOT NULL,
  `license_certificate` varchar(255) DEFAULT NULL,
  `degree_certificate` varchar(255) DEFAULT NULL,
  `consultation_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `verification_status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctors`
--

INSERT INTO `doctors` (`doctor_id`, `user_id`, `specialization_id`, `city_id`, `full_address`, `experience_years`, `qualification`, `pmdc_registration_number`, `cnic`, `license_certificate`, `degree_certificate`, `consultation_fee`, `gender`, `date_of_birth`, `profile_image`, `bio`, `verification_status`, `verified_by`, `verified_at`, `created_at`, `updated_at`) VALUES
(3, 12, 7, 1, 'power house chorangi', 4, 'MBBS', '52656-D', '42101-6555545-6', 'assets/uploads/doctor/license/license_1784353539_2763.jpg', 'assets/uploads/doctor/degrees/degree_1784353539_6111.jpg', 4500.00, 'Male', '1992-02-27', 'doctor_profile_1784534431_7688.jpg', 'general doctor ', 'Pending', NULL, NULL, '2026-07-18 05:45:39', '2026-07-20 08:00:31'),
(4, 13, 2, 2, 'near bahria town lahore', 8, 'FCPS Dermatology', '56427-P', '42101-5111343-7', 'assets/uploads/doctor/license/license_1784353827_6260.jpg', 'assets/uploads/doctor/degrees/degree_1784353827_3716.jpg', 3400.00, 'Male', '1999-10-13', 'doctor_profile_1784353827_2265.png', 'a pecialized dermatology.Have worked for 8 years in this field.', 'Pending', NULL, NULL, '2026-07-18 05:50:27', '2026-07-18 05:50:27'),
(5, 14, 4, 7, 'ma jinnah road', 6, 'FCPS Neurology', '72666-P', '42101-6665675-7', 'assets/uploads/doctor/license/license_1784357656_5511.jpg', 'assets/uploads/doctor/degrees/degree_1784357656_2064.jpg', 2500.00, 'Female', '1998-05-13', 'doctor_profile_1784534500_3988.jpg', 'best neurology doctor in town', 'Pending', NULL, NULL, '2026-07-18 06:54:16', '2026-07-20 08:01:40');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_availability`
--

CREATE TABLE `doctor_availability` (
  `availability_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) DEFAULT NULL,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `start_time` time NOT NULL,
  `end_time` time NOT NULL,
  `slot_duration` int(11) DEFAULT 15 COMMENT 'Duration in minutes',
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_availability`
--

INSERT INTO `doctor_availability` (`availability_id`, `doctor_id`, `day`, `start_time`, `end_time`, `slot_duration`, `status`, `created_at`, `updated_at`) VALUES
(1, 3, 'Wednesday', '15:30:00', '16:30:00', 15, 'Active', '2026-07-19 09:33:33', '2026-07-19 09:33:33'),
(4, 4, 'Friday', '16:40:00', '18:50:00', 20, 'Active', '2026-07-19 09:36:40', '2026-07-19 09:36:40'),
(5, 5, 'Sunday', '09:30:00', '11:40:00', 15, 'Active', '2026-07-19 09:37:30', '2026-07-19 09:37:30');

-- --------------------------------------------------------

--
-- Table structure for table `doctor_clinic`
--

CREATE TABLE `doctor_clinic` (
  `doctor_clinic_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `clinic_id` int(11) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `doctor_clinic`
--

INSERT INTO `doctor_clinic` (`doctor_clinic_id`, `doctor_id`, `clinic_id`, `is_primary`, `created_at`) VALUES
(1, 4, 1, 1, '2026-07-19 10:08:02'),
(2, 3, 1, 0, '2026-07-19 10:08:23'),
(3, 5, 3, 1, '2026-07-20 06:40:54');

-- --------------------------------------------------------

--
-- Table structure for table `medical_news`
--

CREATE TABLE `medical_news` (
  `news_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` longtext NOT NULL,
  `image` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `status` enum('Draft','Published','Archived') DEFAULT 'Draft',
  `published_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'General',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `patients`
--

CREATE TABLE `patients` (
  `patient_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `city_id` int(11) NOT NULL,
  `full_address` text DEFAULT NULL,
  `gender` enum('Male','Female','Other') DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `blood_group` enum('A+','A-','B+','B-','AB+','AB-','O+','O-') DEFAULT NULL,
  `emergency_contact_name` varchar(100) DEFAULT NULL,
  `emergency_contact_phone` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `patients`
--

INSERT INTO `patients` (`patient_id`, `user_id`, `city_id`, `full_address`, `gender`, `date_of_birth`, `blood_group`, `emergency_contact_name`, `emergency_contact_phone`, `profile_image`, `created_at`, `updated_at`) VALUES
(3, 2, 3, 'power house choranig', 'Male', '1997-02-13', 'AB-', 'saud', '03472645987', 'assets/uploads/patients/patien_1784283615_9364.png', '2026-07-17 10:20:15', '2026-07-17 10:20:15'),
(4, 5, 2, 'saddar lahore', 'Male', '2003-06-12', 'B+', 'majid', '03436675676', 'assets/uploads/patients/patien_1784307628_9471.jpg', '2026-07-17 17:00:28', '2026-07-17 17:00:28'),
(5, 11, 1, 'bufferzone house no 23', 'Male', '1990-06-06', 'A-', 'Muhammad Amir', '03565554567', 'assets/uploads/patients/patien_1784308744_6180.jpg', '2026-07-17 17:19:04', '2026-07-17 17:19:04');

-- --------------------------------------------------------

--
-- Table structure for table `reviews`
--

CREATE TABLE `reviews` (
  `review_id` int(11) NOT NULL,
  `doctor_id` int(11) NOT NULL,
  `patient_id` int(11) NOT NULL,
  `appointment_id` int(11) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL CHECK (`rating` between 1 and 5),
  `review` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `specializations`
--

CREATE TABLE `specializations` (
  `specialization_id` int(11) NOT NULL,
  `specialization_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Active','Inactive') DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `specializations`
--

INSERT INTO `specializations` (`specialization_id`, `specialization_name`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Cardiology', 'Heart and cardiovascular system diseases, blood pressure, and heart failure care.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(2, 'Dermatology', 'Skin, hair, nails conditions, acne treatment, and cosmetic skin care.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(3, 'Pediatrics', 'Complete medical care, growth tracking, and vaccinations for infants, children, and teens.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(4, 'Neurology', 'Brain, spinal cord, nervous system disorders, and stroke management.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(5, 'Orthopedics', 'Bone, joint, ligament, muscle disorders, fractures, and joint replacements.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(6, 'Gynecology', 'Female reproductive health, pregnancy care, and childbirth management.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31'),
(7, 'General Medicine', 'Diagnosis and non-surgical treatment of primary healthcare conditions and adult illnesses.', 'Active', '2026-07-17 10:43:31', '2026-07-17 10:43:31');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('Admin','Doctor','Patient') NOT NULL DEFAULT 'Patient',
  `status` enum('Active','Inactive','Suspended') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `phone`, `password`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@gmail.com', '03408714704', '$2y$10$lFBwUgat0rEAEun7bms7O.W373irf3RvjT4c3aBmjBwWCn0.5ZfU.', 'Admin', 'Active', '2026-07-17 07:59:39', '2026-07-17 07:59:39'),
(2, 'Zubair', 'zubair@gmail.com', '03566283652', '$2y$10$XDlhj1XaSU07wNecw.AWTut9YK49qrK98gZ/w.aRNyu7ZNvfCZzIe', 'Patient', 'Active', '2026-07-17 08:11:57', '2026-07-17 08:11:57'),
(3, 'Sami', 'sami@gmail.com', '03675472575', '$2y$10$F2fMgMyuqQlxo8EL2KEvGOsxURnnynejXX3UXKykoId6RxE1D43bW', 'Doctor', 'Active', '2026-07-17 11:38:45', '2026-07-17 11:38:45'),
(4, 'Zahid Ali', 'zahid@gmail.com', '03653712809', '$2y$10$Z45ulgBYLnMJHMXlQg8DWOet/cU7hW3e4.52e51GxdWY3sZ2JWmKG', 'Doctor', 'Active', '2026-07-17 16:51:58', '2026-07-17 16:51:58'),
(5, 'saad yaqoob', 'saad@gmail.com', '03516425444', '$2y$10$viGQFfmZU8gXT.3xl.nJ8.Md7eS2c0tOu.sr43pwO0AkgcWmMI//.', 'Patient', 'Active', '2026-07-17 16:58:52', '2026-07-17 16:58:52'),
(11, 'Sarfaraz', 'saifi@gmail.com', '03564777878', '$2y$10$0mbXfH7rkYMMUa/iszdlquANu7MJ61iGHomp2sjrLhg5pzdiJIbMK', 'Patient', 'Active', '2026-07-17 17:18:18', '2026-07-17 17:18:18'),
(12, 'Usman Khalids', 'usman@gmail.com', '03562755654', '$2y$10$T4xIneuiXoSwirP0LZp4X.kFF3dGyaNQ70VGagdewL5cRbEbLpmM2', 'Doctor', 'Active', '2026-07-18 05:43:55', '2026-07-20 07:58:20'),
(13, 'Shahmeer Abbas', 'shahmeer@gmail.com', '03411232567', '$2y$10$QnAYbiMnzsWOieb6wdRt8eOZxih5isH4xg9B58IzlMixXT9J9ePjO', 'Doctor', 'Active', '2026-07-18 05:47:43', '2026-07-18 05:47:43'),
(14, 'Samia Mashkoor Khan', 'samia@gmail.com', '03112345312', '$2y$10$ercA39t/YCqNVEe52kbNDepu3Emm3Lz33HU2UYc0p2uXwTiB8Tyqe', 'Doctor', 'Active', '2026-07-18 06:52:44', '2026-07-20 08:01:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `appointments`
--
ALTER TABLE `appointments`
  ADD PRIMARY KEY (`appointment_id`),
  ADD KEY `clinic_id` (`clinic_id`),
  ADD KEY `idx_appointment_search` (`doctor_id`,`appointment_date`,`status`),
  ADD KEY `idx_patient_appointments` (`patient_id`);

--
-- Indexes for table `cities`
--
ALTER TABLE `cities`
  ADD PRIMARY KEY (`city_id`);

--
-- Indexes for table `clinics`
--
ALTER TABLE `clinics`
  ADD PRIMARY KEY (`clinic_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `diseases`
--
ALTER TABLE `diseases`
  ADD PRIMARY KEY (`disease_id`),
  ADD UNIQUE KEY `disease_name` (`disease_name`);

--
-- Indexes for table `doctors`
--
ALTER TABLE `doctors`
  ADD PRIMARY KEY (`doctor_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD UNIQUE KEY `pmdc_registration_number` (`pmdc_registration_number`),
  ADD UNIQUE KEY `cnic` (`cnic`),
  ADD KEY `specialization_id` (`specialization_id`),
  ADD KEY `verified_by` (`verified_by`),
  ADD KEY `idx_doctor_search` (`city_id`,`specialization_id`,`verification_status`);

--
-- Indexes for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD PRIMARY KEY (`availability_id`),
  ADD KEY `doctor_id` (`doctor_id`);

--
-- Indexes for table `doctor_clinic`
--
ALTER TABLE `doctor_clinic`
  ADD PRIMARY KEY (`doctor_clinic_id`),
  ADD UNIQUE KEY `doctor_id` (`doctor_id`,`clinic_id`),
  ADD KEY `clinic_id` (`clinic_id`);

--
-- Indexes for table `medical_news`
--
ALTER TABLE `medical_news`
  ADD PRIMARY KEY (`news_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `patients`
--
ALTER TABLE `patients`
  ADD PRIMARY KEY (`patient_id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `city_id` (`city_id`);

--
-- Indexes for table `reviews`
--
ALTER TABLE `reviews`
  ADD PRIMARY KEY (`review_id`),
  ADD UNIQUE KEY `appointment_id` (`appointment_id`),
  ADD KEY `doctor_id` (`doctor_id`),
  ADD KEY `patient_id` (`patient_id`);

--
-- Indexes for table `specializations`
--
ALTER TABLE `specializations`
  ADD PRIMARY KEY (`specialization_id`),
  ADD UNIQUE KEY `specialization_name` (`specialization_name`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `phone` (`phone`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `appointments`
--
ALTER TABLE `appointments`
  MODIFY `appointment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `cities`
--
ALTER TABLE `cities`
  MODIFY `city_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `clinics`
--
ALTER TABLE `clinics`
  MODIFY `clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `diseases`
--
ALTER TABLE `diseases`
  MODIFY `disease_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `doctors`
--
ALTER TABLE `doctors`
  MODIFY `doctor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  MODIFY `availability_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `doctor_clinic`
--
ALTER TABLE `doctor_clinic`
  MODIFY `doctor_clinic_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `medical_news`
--
ALTER TABLE `medical_news`
  MODIFY `news_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `patients`
--
ALTER TABLE `patients`
  MODIFY `patient_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `reviews`
--
ALTER TABLE `reviews`
  MODIFY `review_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `specializations`
--
ALTER TABLE `specializations`
  MODIFY `specialization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `appointments`
--
ALTER TABLE `appointments`
  ADD CONSTRAINT `appointments_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`),
  ADD CONSTRAINT `appointments_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`),
  ADD CONSTRAINT `appointments_ibfk_3` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`);

--
-- Constraints for table `clinics`
--
ALTER TABLE `clinics`
  ADD CONSTRAINT `clinics_ibfk_1` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`);

--
-- Constraints for table `doctors`
--
ALTER TABLE `doctors`
  ADD CONSTRAINT `doctors_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctors_ibfk_2` FOREIGN KEY (`specialization_id`) REFERENCES `specializations` (`specialization_id`),
  ADD CONSTRAINT `doctors_ibfk_3` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`),
  ADD CONSTRAINT `doctors_ibfk_4` FOREIGN KEY (`verified_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `doctor_availability`
--
ALTER TABLE `doctor_availability`
  ADD CONSTRAINT `doctor_availability_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE;

--
-- Constraints for table `doctor_clinic`
--
ALTER TABLE `doctor_clinic`
  ADD CONSTRAINT `doctor_clinic_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `doctor_clinic_ibfk_2` FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`clinic_id`) ON DELETE CASCADE;

--
-- Constraints for table `medical_news`
--
ALTER TABLE `medical_news`
  ADD CONSTRAINT `medical_news_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `patients`
--
ALTER TABLE `patients`
  ADD CONSTRAINT `patients_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `patients_ibfk_2` FOREIGN KEY (`city_id`) REFERENCES `cities` (`city_id`);

--
-- Constraints for table `reviews`
--
ALTER TABLE `reviews`
  ADD CONSTRAINT `reviews_ibfk_1` FOREIGN KEY (`doctor_id`) REFERENCES `doctors` (`doctor_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_2` FOREIGN KEY (`patient_id`) REFERENCES `patients` (`patient_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reviews_ibfk_3` FOREIGN KEY (`appointment_id`) REFERENCES `appointments` (`appointment_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
