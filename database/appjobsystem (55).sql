-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 19, 2025 at 03:31 AM
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
-- Database: `appjobsystem`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `application_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `application_letter` varchar(255) NOT NULL,
  `personal_data_sheet` varchar(255) NOT NULL,
  `transcript_of_records` varchar(255) NOT NULL,
  `proof_of_eligibility` varchar(255) NOT NULL,
  `other_documents` varchar(255) DEFAULT NULL,
  `work_experience` text DEFAULT NULL,
  `education` text DEFAULT NULL,
  `status` enum('Pending','Applied','Under Review','Interview Scheduled','Under Interviews','Interviewed','Hired','Not Selected','Exam Completed','Exam Scheduled','Hired Not Shortlisted','For Requirements','Not Shortlisted') DEFAULT 'Pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `interview_date` datetime DEFAULT NULL,
  `exam_date` datetime DEFAULT NULL,
  `last_notified_status` varchar(50) DEFAULT NULL,
  `evaluation_status` enum('Pending','In Progress','Completed') DEFAULT 'Pending',
  `total_score` decimal(5,2) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `application_number`, `user_id`, `position_id`, `resume`, `application_letter`, `personal_data_sheet`, `transcript_of_records`, `proof_of_eligibility`, `other_documents`, `work_experience`, `education`, `status`, `submitted_at`, `interview_date`, `exam_date`, `last_notified_status`, `evaluation_status`, `total_score`, `phone`) VALUES
(28, 'APP-6423B9C5', 30, 4, NULL, 'uploads/68327806cf870-Application Letter.pdf', 'uploads/68327806d091f-Resume.pdf', 'uploads/68327806d116d-TOR.pdf', 'uploads/68327806d1f90-Proof of Eligibility.pdf', 'uploads/68327806d28e6-Other Documents.pdf', NULL, NULL, 'Hired', '2025-05-25 01:53:10', '2025-05-27 12:00:00', NULL, NULL, 'Pending', NULL, '091234567890'),
(31, 'APP-8ECD351A', 30, 2, NULL, 'uploads/68463ac05484f-Application Letter.pdf', 'uploads/68463ac055127-Resume.pdf', 'uploads/68463ac055788-TOR.pdf', 'uploads/68463ac055d32-Proof of Eligibility.pdf', 'uploads/68463ac0562d2-Other Documents.pdf', NULL, NULL, 'Under Review', '2025-06-09 01:37:04', NULL, NULL, NULL, 'Completed', NULL, '091234567890'),
(44, 'APP-FAB53CA6', 9, 1, NULL, 'uploads/687ed3051c1f4-Application Letter.pdf', 'uploads/687ed3051c7f1-Resume.pdf', 'uploads/687ed3051de32-TOR.pdf', 'uploads/687ed3051ed1f-Proof of Eligibility.pdf', 'uploads/687ed3051f21f-Other Documents.pdf', '[{\"position\":\"Freelance Web Developer\",\"company\":\"Freelance\",\"start_date\":\"2022-07-20\",\"end_date\":\"2025-07-10\",\"salary\":\"40000\",\"status\":\"contractual\",\"govt_service\":\"N\"}]', '[{\"level\":\"COLLEGE\",\"school\":\"City College of Calamba\",\"degree\":\"Bachelor of Information techonology\",\"start_date\":\"2023-07-06\",\"end_date\":\"2025-07-04\",\"highest_level\":\"\",\"year_graduated\":\"2025\",\"honors\":\"Cumlaude\"}]', 'Under Review', '2025-07-21 23:53:41', NULL, NULL, NULL, 'Completed', NULL, '09106835257'),
(45, 'APP-F33A9D6E', 9, 2, NULL, 'uploads/687f060faacd9-Application Letter.pdf', 'uploads/687f060fab25a-Resume.pdf', 'uploads/687f060fab6e8-TOR.pdf', 'uploads/687f060fabbc0-Proof of Eligibility.pdf', 'uploads/687f060fac1d7-Other Documents.pdf', '[{\"position\":\"Instructor\",\"company\":\"Deped\",\"start_date\":\"2022-07-13\",\"end_date\":\"2024-07-18\",\"salary\":\"30000\",\"status\":\"contractual\",\"govt_service\":\"Y\"}]', '[{\"level\":\"SECONDARY\",\"school\":\"Palo Alto Integrated School\",\"degree\":\"Highschool\",\"start_date\":\"2019-07-11\",\"end_date\":\"2020-07-23\",\"highest_level\":\"\",\"year_graduated\":\"2020\",\"honors\":\"honor\"}]', 'Not Selected', '2025-07-22 03:31:27', NULL, NULL, NULL, 'Pending', NULL, '09106835257'),
(46, 'APP-CB6FFC41', 9, 3, NULL, 'uploads/688393ebc0846-Application Letter.pdf', 'uploads/688393ebc0ef9-Resume.pdf', 'uploads/688393ebc148f-TOR.pdf', 'uploads/688393ebc19cf-Proof of Eligibility.pdf', 'uploads/688393ebc1fd4-Other Documents.pdf', '[{\"position\":\"testing\",\"company\":\"testing\",\"start_date\":\"2020-07-22\",\"end_date\":\"2022-07-07\",\"salary\":\"30000\",\"salary_grade\":\"N\\/A\",\"status\":\"test\",\"govt_service\":\"N\"}]', '[{\"level\":\"SECONDARY\",\"school\":\"asdawq\",\"degree\":\"asdqdq\",\"start_date\":\"2022-07-20\",\"end_date\":\"2023-07-26\",\"highest_level\":\"\",\"year_graduated\":\"testing\",\"honors\":\"testing\"}]', 'Under Review', '2025-07-25 14:25:47', NULL, NULL, NULL, 'Completed', NULL, '09106835257'),
(47, 'APP-C50974C2', 9, 11, 'uploads/68a3a6f3771f1-Resume.pdf', 'uploads/68a3a6f377c71-Application Letter.pdf', 'uploads/68a3a6f3784da-PDS_Form.xlsx', 'uploads/68a3a6f378ded-TOR.pdf', 'uploads/68a3a6f37943c-Proof of Eligibility.pdf', 'uploads/68a3a6f379a52-Other Documents.pdf', '[{\"position\":\"testing\",\"company\":\"testing\",\"start_date\":\"2019-08-15\",\"end_date\":\"2020-08-18\",\"salary\":\"30000\",\"salary_grade\":\"N\\/A\",\"status\":\"test\",\"govt_service\":\"Y\"}]', '[{\"level\":\"VOCATIONAL \\/ TRADE COURSE\",\"school\":\"asdawq\",\"degree\":\"asdqdq\",\"start_date\":\"2021-08-25\",\"end_date\":\"2023-08-16\",\"highest_level\":\"testying\",\"year_graduated\":\"testing\",\"honors\":\"testing\"}]', 'Under Review', '2025-08-18 22:19:31', NULL, NULL, NULL, 'Completed', NULL, '09106835257');

-- --------------------------------------------------------

--
-- Table structure for table `application_education`
--

CREATE TABLE `application_education` (
  `education_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `level` varchar(100) NOT NULL,
  `school` varchar(255) NOT NULL,
  `degree` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `highest_level` varchar(255) DEFAULT NULL,
  `year_graduated` varchar(20) NOT NULL,
  `honors` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_education`
--

INSERT INTO `application_education` (`education_id`, `application_id`, `level`, `school`, `degree`, `start_date`, `end_date`, `highest_level`, `year_graduated`, `honors`) VALUES
(8, 44, 'COLLEGE', 'City College of Calamba', 'Bachelor of Information techonology', '2023-07-06', '2025-07-04', '', '2025', 'Cumlaude'),
(9, 45, 'SECONDARY', 'Palo Alto Integrated School', 'Highschool', '2019-07-11', '2020-07-23', '', '2020', 'honor'),
(10, 46, 'SECONDARY', 'asdawq', 'asdqdq', '2022-07-20', '2023-07-26', 'Graduated', 'testing', 'testing'),
(11, 47, 'VOCATIONAL / TRADE COURSE', 'asdawq', 'asdqdq', '2021-08-25', '2023-08-16', 'testying', 'testing', 'testing');

-- --------------------------------------------------------

--
-- Table structure for table `application_work_experience`
--

CREATE TABLE `application_work_experience` (
  `experience_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `position` varchar(255) NOT NULL,
  `company` varchar(255) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `salary` varchar(50) NOT NULL,
  `salary_grade` varchar(20) DEFAULT NULL,
  `status_of_appointment` varchar(100) NOT NULL,
  `govt_service` enum('Y','N') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_work_experience`
--

INSERT INTO `application_work_experience` (`experience_id`, `application_id`, `position`, `company`, `start_date`, `end_date`, `salary`, `salary_grade`, `status_of_appointment`, `govt_service`) VALUES
(11, 44, 'Freelance Web Developer', 'Freelance', '2022-07-20', '2025-07-10', '40000', NULL, 'contractual', 'N'),
(12, 45, 'Instructor', 'Deped', '2022-07-13', '2024-07-18', '30000', NULL, 'contractual', 'Y'),
(13, 46, 'testing', 'testing', '2020-07-22', '2022-07-07', '30000', 'N/A', 'test', 'N'),
(14, 47, 'testing', 'testing', '2019-08-15', '2020-08-18', '30000', 'N/A', 'test', 'Y');

-- --------------------------------------------------------

--
-- Table structure for table `auth_tokens`
--

CREATE TABLE `auth_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL,
  `last_used_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `name`, `description`, `created_at`, `updated_at`) VALUES
(1, 'IT Department', 'Information Technology Department', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(2, 'Secondary Education', 'Secondary Education Department', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(3, 'Health Services', 'Health and Medical Services', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(4, 'College of Computer Studies', 'Computer Science and IT Programs', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(5, 'College of Hospitality Management and Tourism', 'Hospitality and Tourism Programs', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(6, 'test', NULL, '2025-07-22 05:14:42', '2025-07-22 05:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `evaluations`
--

CREATE TABLE `evaluations` (
  `evaluation_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `personality` int(11) NOT NULL,
  `communication` int(11) NOT NULL,
  `analytical` int(11) NOT NULL,
  `achievement` int(11) NOT NULL,
  `leadership` int(11) NOT NULL,
  `relationship` int(11) NOT NULL,
  `jobfit` int(11) NOT NULL,
  `aptitude` int(11) NOT NULL,
  `education_rating` int(11) NOT NULL,
  `education_units` int(11) NOT NULL,
  `experience_rating` int(11) NOT NULL,
  `additional_experience` int(11) NOT NULL,
  `training_rating` int(11) NOT NULL,
  `eligibility_rating` int(11) NOT NULL,
  `accomplishment_rating` int(11) NOT NULL,
  `total_score` decimal(5,2) NOT NULL,
  `evaluator_id` int(11) NOT NULL,
  `evaluated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `evaluations`
--

INSERT INTO `evaluations` (`evaluation_id`, `application_id`, `personality`, `communication`, `analytical`, `achievement`, `leadership`, `relationship`, `jobfit`, `aptitude`, `education_rating`, `education_units`, `experience_rating`, `additional_experience`, `training_rating`, `eligibility_rating`, `accomplishment_rating`, `total_score`, `evaluator_id`, `evaluated_at`) VALUES
(2, 44, 10, 10, 10, 10, 10, 10, 10, 5, 30, 6, 15, 0, 15, 10, 5, 96.00, 34, '2025-07-27 15:44:43'),
(3, 31, 10, 10, 10, 10, 10, 10, 10, 5, 30, 0, 15, 5, 10, 10, 5, 90.00, 34, '2025-07-27 16:35:42'),
(5, 46, 10, 10, 10, 10, 10, 10, 10, 5, 30, 10, 15, 0, 15, 10, 5, 100.00, 34, '2025-08-18 01:01:31'),
(6, 47, 8, 8, 8, 8, 8, 8, 8, 4, 30, 0, 15, 0, 5, 10, 0, 72.00, 34, '2025-08-19 00:45:43');

-- --------------------------------------------------------

--
-- Table structure for table `job_positions`
--

CREATE TABLE `job_positions` (
  `position_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `type` enum('Full-Time','Part-Time') NOT NULL,
  `category` enum('Teaching','Non-Teaching') NOT NULL,
  `location_id` int(11) NOT NULL,
  `date_posted` date NOT NULL,
  `place_of_assignment` varchar(255) NOT NULL,
  `status` enum('Open','Closed') DEFAULT 'Open',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `description` text DEFAULT NULL,
  `salary_range` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_positions`
--

INSERT INTO `job_positions` (`position_id`, `title`, `department_id`, `type`, `category`, `location_id`, `date_posted`, `place_of_assignment`, `status`, `created_at`, `updated_at`, `description`, `salary_range`) VALUES
(1, 'Senior Web Developer', 1, 'Full-Time', 'Non-Teaching', 1, '2025-04-22', 'Main Office - IT Division', 'Open', '2025-04-22 00:00:00', '2025-04-22 00:00:00', 'Responsible for developing and maintaining web applications', 'PHP 50,000 - 70,000'),
(2, 'English Language Teacher', 2, 'Full-Time', 'Teaching', 2, '2025-04-20', 'San Pablo High School', 'Open', '2025-04-20 00:00:00', '2025-04-20 00:00:00', 'Teaching English subjects for high school students', 'PHP 25,000 - 35,000'),
(3, 'School Nurse', 3, 'Part-Time', 'Non-Teaching', 3, '2025-04-18', 'Calamba Campus Clinic', 'Open', '2025-04-18 00:00:00', '2025-04-18 00:00:00', 'Providing basic healthcare services to students', 'PHP 15,000 - 20,000'),
(4, 'WEB DESIGNER', 1, 'Full-Time', 'Non-Teaching', 4, '2025-05-02', 'IT Department - Web Team', 'Open', '2025-05-02 00:00:00', '2025-05-02 00:00:00', 'Designing user interfaces for web applications', 'PHP 30,000 - 45,000'),
(5, 'Filipino Language Teacher', 2, 'Full-Time', 'Teaching', 5, '2025-05-02', 'Sta. Cruz National High School', 'Open', '2025-05-02 00:00:00', '2025-05-02 00:00:00', 'Teaching Filipino subjects for high school students', 'PHP 25,000 - 35,000'),
(7, 'Science Language Teacher', 2, 'Part-Time', 'Teaching', 6, '2025-05-13', 'Los Baños Science High School', 'Open', '2025-05-13 00:00:00', '2025-05-13 00:00:00', 'Teaching science subjects for high school students', 'PHP 20,000 - 25,000'),
(11, 'Multimedia and Arts', 4, 'Full-Time', 'Teaching', 8, '2025-05-19', 'LSPU - CCS Building', 'Open', '2025-05-19 00:00:00', '2025-05-19 00:00:00', 'Teaching multimedia and arts courses', 'PHP 35,000 - 45,000'),
(12, 'Operations management', 5, 'Full-Time', 'Teaching', 6, '2025-05-22', 'LSPU - CHMT Building', 'Open', '2025-05-22 00:00:00', '2025-05-22 00:00:00', 'Teaching operations management courses', 'PHP 35,000 - 50,000'),
(16, 'test', 6, 'Full-Time', 'Teaching', 3, '2025-07-22', 'test', 'Open', '2025-07-22 05:14:42', '2025-07-22 05:14:42', 'test', '25,000 to 40,000');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `location_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`location_id`, `name`, `address`, `created_at`, `updated_at`) VALUES
(1, 'DXC Technology', 'Main Office - IT Division', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(2, 'San Pablo Campus', 'San Pablo High School', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(3, 'Calamba Medical Center', 'Calamba Campus Clinic', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(4, 'Fujitsu Philippines', 'IT Department - Web Team', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(5, 'Sta. Cruz Main Campus', 'Sta. Cruz National High School', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(6, 'Los Baños Campus', 'Los Baños Science High School', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(7, 'Nextvas Bagong Kalsada', 'Computer of Studies', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(8, 'LSPU - CCS Building', 'College of Computer Studies', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(9, 'Los Baños', NULL, '2025-07-22 05:41:27', '2025-07-22 05:41:27');

-- --------------------------------------------------------

--
-- Table structure for table `login_attempts`
--

CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `attempt_time` datetime NOT NULL DEFAULT current_timestamp(),
  `success` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `login_attempts`
--

INSERT INTO `login_attempts` (`id`, `ip_address`, `email`, `attempt_time`, `success`) VALUES
(66, '::1', 'newadmin@lspu.edu.ph', '2025-07-28 01:13:30', 1),
(67, '::1', 'superadmin@lspu.edu.ph', '2025-07-28 11:03:38', 1),
(68, '::1', 'newadmin@lspu.edu.ph', '2025-07-28 11:12:38', 1),
(69, '::1', 'aceattacker028@gmail.com', '2025-07-28 13:10:32', 1),
(70, '::1', 'superadmin@lspu.edu.ph', '2025-07-28 13:25:09', 1),
(71, '::1', 'aceattacker028@gmail.com', '2025-07-29 13:44:24', 1),
(72, '::1', 'newadmin@lspu.edu.ph', '2025-07-29 14:00:36', 1),
(73, '::1', 'aceattacker028@gmail.com', '2025-08-03 04:40:57', 1),
(74, '::1', 'superadmin@lspu.edu.ph', '2025-08-03 04:51:37', 1),
(75, '::1', 'aceattacker028@gmail.com', '2025-08-03 05:28:34', 0),
(76, '::1', 'aceattacker028@gmail.com', '2025-08-03 05:28:40', 1),
(77, '::1', 'aceattacker028@gmail.com', '2025-08-03 05:31:19', 0),
(78, '::1', 'aceattacker028@gmail.com', '2025-08-03 05:31:25', 1),
(79, '::1', 'aceattacker028@gmail.com', '2025-08-03 06:47:51', 1),
(80, '::1', 'newadmin@lspu.edu.ph', '2025-08-15 07:02:12', 1),
(81, '::1', 'newadmin@lspu.edu.ph', '2025-08-18 10:06:34', 1),
(82, '::1', 'newadmin@lspu.edu.ph', '2025-08-18 10:11:07', 1),
(83, '::1', 'aceattacker028@gmail.com', '2025-08-19 05:26:08', 1),
(84, '::1', 'newadmin@lspu.edu.ph', '2025-08-19 06:28:00', 1),
(85, '::1', 'superadmin@lspu.edu.ph', '2025-08-19 07:24:22', 1),
(86, '::1', 'aceattacker028@gmail.com', '2025-08-19 08:18:40', 1),
(87, '::1', 'newadmin@lspu.edu.ph', '2025-08-19 08:33:15', 1),
(88, '::1', 'superadmin@lspu.edu.ph', '2025-08-19 08:55:27', 1),
(89, '::1', 'aceattacker028@gmail.com', '2025-08-19 09:19:09', 1),
(90, '::1', 'newadmin@lspu.edu.ph', '2025-08-19 09:22:30', 0),
(91, '::1', 'newadmin@lspu.edu.ph', '2025-08-19 09:22:35', 1),
(92, '::1', 'superadmin@lspu.edu.ph', '2025-08-19 09:24:22', 0),
(93, '::1', 'superadmin@lspu.edu.ph', '2025-08-19 09:24:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `position_requirements`
--

CREATE TABLE `position_requirements` (
  `requirement_id` int(11) NOT NULL,
  `position_id` int(11) NOT NULL,
  `requirement_type` enum('eligibility','qualification','experience','training') NOT NULL,
  `description` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `position_requirements`
--

INSERT INTO `position_requirements` (`requirement_id`, `position_id`, `requirement_type`, `description`, `created_at`, `updated_at`) VALUES
(1, 1, 'eligibility', 'CS Professional', '2025-04-22 00:00:00', '2025-04-22 00:00:00'),
(2, 1, 'qualification', 'Bachelor\'s in Computer Science', '2025-04-22 00:00:00', '2025-04-22 00:00:00'),
(3, 1, 'experience', 'None required', '2025-04-22 00:00:00', '2025-04-22 00:00:00'),
(4, 1, 'training', 'None required', '2025-04-22 00:00:00', '2025-04-22 00:00:00'),
(5, 2, 'eligibility', 'LET Passer', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(6, 2, 'qualification', 'Bachelor\'s in English Education', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(7, 2, 'experience', '2 years teaching experience', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(8, 2, 'training', 'None required', '2025-04-20 00:00:00', '2025-04-20 00:00:00'),
(9, 3, 'eligibility', 'RA 1080 (Nursing Board)', '2025-04-18 00:00:00', '2025-04-18 00:00:00'),
(10, 3, 'qualification', 'BS in Nursing', '2025-04-18 00:00:00', '2025-04-18 00:00:00'),
(11, 3, 'experience', '1 year clinical experience', '2025-04-18 00:00:00', '2025-04-18 00:00:00'),
(12, 3, 'training', 'First Aid & Emergency Care', '2025-04-18 00:00:00', '2025-04-18 00:00:00'),
(13, 4, 'eligibility', 'CS Professional', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(14, 4, 'qualification', 'Bachelor\'s in Multimedia Arts or similar', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(15, 4, 'experience', '3 years in UI/UX design', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(16, 4, 'training', 'Photoshop, HTML/CSS training', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(17, 5, 'eligibility', 'LET Passer', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(18, 5, 'qualification', 'Bachelor\'s in Filipino Education', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(19, 5, 'experience', '2 years teaching experience', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(20, 5, 'training', 'Training in Language Teaching', '2025-05-02 00:00:00', '2025-05-02 00:00:00'),
(21, 7, 'eligibility', 'LET Passer', '2025-05-13 00:00:00', '2025-05-13 00:00:00'),
(22, 7, 'qualification', 'Bachelor\'s in Science Education', '2025-05-13 00:00:00', '2025-05-13 00:00:00'),
(23, 7, 'experience', '1 year teaching experience', '2025-05-13 00:00:00', '2025-05-13 00:00:00'),
(24, 7, 'training', 'Training in Science Instruction', '2025-05-13 00:00:00', '2025-05-13 00:00:00'),
(25, 11, 'eligibility', 'CS Professional or LET (as applicable)', '2025-05-19 00:00:00', '2025-05-19 00:00:00'),
(26, 11, 'qualification', 'Bachelor\'s in Multimedia Arts', '2025-05-19 00:00:00', '2025-05-19 00:00:00'),
(27, 11, 'experience', '2 years teaching/design experience', '2025-05-19 00:00:00', '2025-05-19 00:00:00'),
(28, 11, 'training', 'Workshops in Creative Software', '2025-05-19 00:00:00', '2025-05-19 00:00:00'),
(29, 12, 'eligibility', 'Must possess at least a Master\'s Degree in a related field.', '2025-05-22 00:00:00', '2025-05-22 00:00:00'),
(30, 12, 'qualification', 'Preferably with academic background in Operations Management, Hospitality, or Tourism.\r\n\r\nStrong communication and organizational skills.', '2025-05-22 00:00:00', '2025-05-22 00:00:00'),
(31, 12, 'experience', 'At least 2 years of relevant teaching or industry experience in operations or hospitality management.', '2025-05-22 00:00:00', '2025-05-22 00:00:00'),
(32, 12, 'training', 'Must have completed relevant training or seminars in academic instruction or hospitality industry standards (minimum of 8 hours).', '2025-05-22 00:00:00', '2025-05-22 00:00:00'),
(33, 16, 'eligibility', 'testt', '2025-07-22 05:14:42', '2025-07-22 05:14:42'),
(34, 16, 'qualification', 'test', '2025-07-22 05:14:42', '2025-07-22 05:14:42'),
(35, 16, 'experience', 'test', '2025-07-22 05:14:42', '2025-07-22 05:14:42'),
(36, 16, 'training', 'test', '2025-07-22 05:14:42', '2025-07-22 05:14:42');

-- --------------------------------------------------------

--
-- Table structure for table `system_logs`
--

CREATE TABLE `system_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `affected_table` varchar(100) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_logs`
--

INSERT INTO `system_logs` (`log_id`, `user_id`, `action`, `details`, `ip_address`, `log_time`, `affected_table`, `record_id`) VALUES
(134, 33, 'Page View', 'Superadmin dashboard accessed by user ID 33', '::1', '2025-07-18 08:49:11', NULL, NULL),
(135, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:03:39', NULL, NULL),
(136, 33, 'Database Backup Failed', 'Error creating backup', NULL, '2025-07-28 03:04:04', NULL, NULL),
(137, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:04:04', NULL, NULL),
(138, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:04:04', NULL, NULL),
(139, 33, 'Database Backup Failed', 'Error creating backup', NULL, '2025-07-28 03:04:40', NULL, NULL),
(140, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:04:40', NULL, NULL),
(141, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:04:40', NULL, NULL),
(142, 33, 'Database Backup Failed', 'Error creating backup', NULL, '2025-07-28 03:04:40', NULL, NULL),
(143, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 03:51:30', NULL, NULL),
(144, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 04:25:09', NULL, NULL),
(145, 33, 'Admin Added', 'Superadmin created new admin account for adminjobonline@gmail.com', NULL, '2025-07-28 04:46:00', NULL, NULL),
(146, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 04:48:45', NULL, NULL),
(147, 33, 'Admin Updated', 'Superadmin updated account ID 36', NULL, '2025-07-28 04:48:52', NULL, NULL),
(148, 33, 'Admin Deleted', 'Superadmin deleted account ID 36', NULL, '2025-07-28 04:48:55', NULL, NULL),
(149, 33, 'Admin Added', 'Superadmin created new admin account for jbdmnnln@gmail.com', NULL, '2025-07-28 04:49:25', NULL, NULL),
(150, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 05:07:42', NULL, NULL),
(151, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 05:25:09', NULL, NULL),
(152, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 06:02:35', NULL, NULL),
(153, 33, 'Database Cleanup: Removed 17 login attempts', 'Cleanup performed by superadmin user ID 33. Days threshold: 1. Force delete: No', '::1', '2025-07-28 06:55:10', NULL, NULL),
(154, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 06:55:18', NULL, NULL),
(155, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-07-28 06:55:45', NULL, NULL),
(156, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-02 20:51:37', NULL, NULL),
(157, 33, 'Admin Deleted', 'Superadmin deleted account ID 37', NULL, '2025-08-02 20:51:50', NULL, NULL),
(158, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-18 23:24:22', NULL, NULL),
(159, 33, 'Admin Added', 'Superadmin created new admin account for jbdmnnln@gmail.com', NULL, '2025-08-18 23:25:10', NULL, NULL),
(160, 33, 'Admin Deleted', 'Superadmin deleted account ID 38', NULL, '2025-08-18 23:27:30', NULL, NULL),
(161, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-18 23:36:00', NULL, NULL),
(162, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-18 23:36:03', NULL, NULL),
(163, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-19 00:55:27', NULL, NULL),
(164, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-19 00:58:49', NULL, NULL),
(165, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-19 00:59:50', NULL, NULL),
(166, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-19 01:24:27', NULL, NULL),
(167, 33, 'Page View', 'Superadmin dashboard accessed', '::1', '2025-08-19 01:24:30', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `birthdate` date DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `profile_pic` varchar(255) DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `role` enum('applicant','admin','superadmin') NOT NULL DEFAULT 'applicant',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `email_verified` tinyint(1) DEFAULT 0,
  `verification_token` varchar(255) DEFAULT NULL,
  `verification_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `first_name`, `middle_name`, `last_name`, `email`, `birthdate`, `phone`, `username`, `password`, `profile_pic`, `reset_token`, `token_expiry`, `reset_expires`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`, `email_verified`, `verification_token`, `verification_expires`) VALUES
(9, 'John Andrei', 'Cadacio', 'Guzman', 'aceattacker028@gmail.com', '2001-07-28', '09106835257', 'Deyi028', '$2y$10$WGXlK6auS8M1WOE1CeSmGua7WEzr5omhuVg6yPkst88RkcmOFZfgO', '684612bae0531.jpg', NULL, NULL, NULL, 'applicant', 1, '2025-08-19 09:19:09', '2025-05-24 21:40:55', '2025-08-19 01:19:09', 1, NULL, NULL),
(30, 'Dey', 'Cadacio', 'Guzman', 'markdreicadacio@gmail.com', '2001-07-28', '091234567890', 'deydey056', '$2y$10$TENm45Qnl7P6zqTzXKlEHeA1dN8MA5AGBvAxqwmtk8P4FT12qDrYC', '682aabb2d0b6a-images.jpg', '252498', NULL, '2025-05-21 23:45:31', 'applicant', 1, NULL, '2025-05-24 17:53:10', '2025-05-24 15:45:31', 1, NULL, NULL),
(33, 'Super', 'Admin', 'Account', 'superadmin@lspu.edu.ph', NULL, NULL, 'superadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '6877c7b6ef463.jpg', NULL, NULL, NULL, 'superadmin', 1, '2025-08-19 09:24:27', '2025-07-16 13:49:13', '2025-08-19 01:24:27', 1, NULL, NULL),
(34, 'A', 'Admin', 'Account', 'newadmin@lspu.edu.ph', '0000-00-00', '', 'newadmin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '6877b785e07f3.jpg', NULL, NULL, NULL, 'admin', 1, '2025-08-19 09:22:35', '2025-07-16 14:27:56', '2025-08-19 01:22:35', 1, NULL, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD UNIQUE KEY `application_number` (`application_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `application_education`
--
ALTER TABLE `application_education`
  ADD PRIMARY KEY (`education_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  ADD PRIMARY KEY (`experience_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`);

--
-- Indexes for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD PRIMARY KEY (`evaluation_id`),
  ADD UNIQUE KEY `application_id` (`application_id`),
  ADD KEY `evaluator_id` (`evaluator_id`);

--
-- Indexes for table `job_positions`
--
ALTER TABLE `job_positions`
  ADD PRIMARY KEY (`position_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`location_id`);

--
-- Indexes for table `login_attempts`
--
ALTER TABLE `login_attempts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ip_address` (`ip_address`),
  ADD KEY `email` (`email`),
  ADD KEY `attempt_time` (`attempt_time`);

--
-- Indexes for table `position_requirements`
--
ALTER TABLE `position_requirements`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `position_id` (`position_id`);

--
-- Indexes for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `application_education`
--
ALTER TABLE `application_education`
  MODIFY `education_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  MODIFY `experience_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `evaluations`
--
ALTER TABLE `evaluations`
  MODIFY `evaluation_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_positions`
--
ALTER TABLE `job_positions`
  MODIFY `position_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `location_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `login_attempts`
--
ALTER TABLE `login_attempts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=94;

--
-- AUTO_INCREMENT for table `position_requirements`
--
ALTER TABLE `position_requirements`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `system_logs`
--
ALTER TABLE `system_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=168;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`position_id`) REFERENCES `job_positions` (`position_id`) ON DELETE CASCADE;

--
-- Constraints for table `application_education`
--
ALTER TABLE `application_education`
  ADD CONSTRAINT `application_education_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `application_work_experience`
--
ALTER TABLE `application_work_experience`
  ADD CONSTRAINT `application_work_experience_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `auth_tokens`
--
ALTER TABLE `auth_tokens`
  ADD CONSTRAINT `auth_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `evaluations`
--
ALTER TABLE `evaluations`
  ADD CONSTRAINT `evaluations_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `evaluations_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_positions`
--
ALTER TABLE `job_positions`
  ADD CONSTRAINT `job_positions_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`department_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `job_positions_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `locations` (`location_id`) ON DELETE CASCADE;

--
-- Constraints for table `position_requirements`
--
ALTER TABLE `position_requirements`
  ADD CONSTRAINT `position_requirements_ibfk_1` FOREIGN KEY (`position_id`) REFERENCES `job_positions` (`position_id`) ON DELETE CASCADE;

--
-- Constraints for table `system_logs`
--
ALTER TABLE `system_logs`
  ADD CONSTRAINT `system_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
