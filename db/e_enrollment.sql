-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 01, 2026 at 02:23 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_enrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `activity_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp(),
  `action` longtext NOT NULL DEFAULT '',
  `session_id` varchar(255) NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '0',
  `system_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `class_section`
--

CREATE TABLE `class_section` (
  `class_id` int(11) NOT NULL,
  `class_name` varchar(100) NOT NULL DEFAULT '',
  `sem_limit` longtext NOT NULL DEFAULT '{"0":30}' COMMENT 'actual representation: [{school_year_id:"0", default value:"30"}]',
  `program_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0,
  `date_modified` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `class_section`
--

INSERT INTO `class_section` (`class_id`, `class_name`, `sem_limit`, `program_id`, `status`, `date_modified`) VALUES
(1, '4-IT2', '{\"0\":30}', 11, 0, '2026-01-20 11:30:34.000000'),
(2, '4-IT3', '{\"0\":30,\"1\":48,\"3\":21,\"2\":39}', 11, 0, '2026-01-20 11:34:04.000000'),
(3, '2-A1', '{\"0\":30,\"1\":34,\"3\":22,\"2\":40}', 12, 0, '2026-01-20 09:29:49.000000'),
(161, '2-A2', '{\"0\":26,\"1\":35}', 12, 0, '2026-01-20 11:33:42.000000'),
(162, '4-IT4', '{\"2\":48}', 11, 0, '2026-01-20 11:34:33.000000'),
(163, 'Section_1', '{\"0\":52}', 11, 0, '2026-01-20 11:48:12.000000'),
(164, 'Section_2', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(165, 'Section_3', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(166, 'Section_4', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(167, 'Section_5', '{\"0\":38}', 11, 0, '2026-01-20 13:57:34.000000'),
(168, 'Section_6', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(169, 'Section_7', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(170, 'Section_8', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(171, 'Section_9', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(172, 'Section_10', '{\"0\":38,\"2\":40}', 12, 0, '2026-01-20 13:58:21.000000'),
(173, 'Section_11', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(174, 'Section_12', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(175, 'Section_13', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(176, 'Section_14', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(177, 'Section_15', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(178, 'Section_16', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(179, 'Section_17', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(180, 'Section_18', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(181, 'Section_19', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(182, 'Section_20', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(183, 'Section_21', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(184, 'Section_22', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(185, 'Section_23', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(186, 'Section_24', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(187, 'Section_25', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(188, 'Section_26', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(189, 'Section_27', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(190, 'Section_28', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(191, 'Section_29', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(192, 'Section_30', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(193, 'Section_31', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(194, 'Section_32', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(195, 'Section_33', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(196, 'Section_34', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(197, 'Section_35', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(198, 'Section_36', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(199, 'Section_37', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(200, 'Section_38', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(201, 'Section_39', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(202, 'Section_40', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(203, 'Section_41', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(204, 'Section_42', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(205, 'Section_43', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(206, 'Section_44', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(207, 'Section_45', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(208, 'Section_46', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(209, 'Section_47', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(210, 'Section_48', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(211, 'Section_49', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(212, 'Section_50', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(213, 'Section_51', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(214, 'Section_52', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(215, 'Section_53', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(216, 'Section_54', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(217, 'Section_55', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(218, 'Section_56', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(219, 'Section_57', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(220, 'Section_58', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(221, 'Section_59', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(222, 'Section_60', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(223, 'Section_61', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(224, 'Section_62', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(225, 'Section_63', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(226, 'Section_64', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(227, 'Section_65', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(228, 'Section_66', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(229, 'Section_67', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(230, 'Section_68', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(231, 'Section_69', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(232, 'Section_70', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(233, 'Section_71', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(234, 'Section_72', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(235, 'Section_73', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(236, 'Section_74', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(237, 'Section_75', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(238, 'Section_76', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(239, 'Section_77', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(240, 'Section_78', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(241, 'Section_79', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(242, 'Section_80', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(243, 'Section_81', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(244, 'Section_82', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(245, 'Section_83', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(246, 'Section_84', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(247, 'Section_85', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(248, 'Section_86', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(249, 'Section_87', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(250, 'Section_88', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(251, 'Section_89', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(252, 'Section_90', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(253, 'Section_91', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(254, 'Section_92', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(255, 'Section_93', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(256, 'Section_94', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(257, 'Section_95', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(258, 'Section_96', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(259, 'Section_97', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(260, 'Section_98', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(261, 'Section_99', '{\"0\":30}', 11, 0, '2026-01-20 11:48:12.000000'),
(262, 'Section_100', '{\"0\":30}', 12, 0, '2026-01-20 11:48:12.000000'),
(263, '1-IT1', '{\"0\":22}', 11, 0, '0000-00-00 00:00:00.000000');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum`
--

CREATE TABLE `curriculum` (
  `curriculum_id` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `program_id` int(11) DEFAULT NULL,
  `curriculum_title` int(11) DEFAULT NULL,
  `createdAt` date NOT NULL DEFAULT current_timestamp(),
  `updatedAt` date DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 0,
  `school_year_id` int(11) NOT NULL,
  `curriculum_code` varchar(255) NOT NULL,
  `cur_subj_id` int(11) NOT NULL,
  `subject_id` int(11) DEFAULT NULL,
  `subject_code` varchar(100) NOT NULL DEFAULT '',
  `subject_title` varchar(100) NOT NULL DEFAULT '',
  `category` varchar(100) NOT NULL DEFAULT '',
  `description` longtext NOT NULL DEFAULT '',
  `unit` int(11) NOT NULL DEFAULT 0,
  `pre_req` varchar(255) NOT NULL,
  `semester` varchar(100) NOT NULL DEFAULT '',
  `lec_lab` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[0,0]',
  `year_level` int(11) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `curriculum`
--

INSERT INTO `curriculum` (`curriculum_id`, `department_id`, `program_id`, `curriculum_title`, `createdAt`, `updatedAt`, `status`, `school_year_id`, `curriculum_code`, `cur_subj_id`, `subject_id`, `subject_code`, `subject_title`, `category`, `description`, `unit`, `pre_req`, `semester`, `lec_lab`, `year_level`, `date_created`) VALUES
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10914, 9001, 'GEC 1', 'Understanding the Self', 'GE', '', 3, '', '1st Semester', '[3,0]', 1, '2026-01-23 11:16:04'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10915, 9002, 'CC 100', 'Introduction to Computing', 'Major', '', 3, '', '1st Semester', '[3,0]', 1, '2026-01-23 11:16:04'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10916, 9003, 'GEC 2', 'Readings in Philippine History', 'GE', '', 3, 'GEC 1', '2nd Semester', '[3,0]', 1, '2026-01-23 16:02:11'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10917, 9004, 'CC 101', 'Computer Programming 1', 'Major', '', 3, 'CC 100', '2nd Semester', '[3,0]', 1, '2026-01-23 16:02:11'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10918, 9005, 'CC 200', 'Data Structures and Algorithms', 'Major', '', 3, 'CC 101', '1st Semester', '[3,0]', 2, '2026-01-23 16:02:11'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10919, 9006, 'IT 200', 'Discrete Structures', 'Major', '', 3, 'GEC 2', '1st Semester', '[3,0]', 2, '2026-01-23 16:02:11'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10920, 9007, 'IT 201', 'Database Systems', 'Major', '', 3, 'CC 101', '2nd Semester', '[3,0]', 2, '2026-01-28 09:19:20'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10921, 9008, 'IT 202', 'Computer Networks 1', 'Major', '', 3, 'CC 101', '2nd Semester', '[3,0]', 2, '2026-01-28 09:19:20'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10922, 9009, 'CC 300', 'Operating Systems', 'Major', '', 3, 'CC 200', '1st Semester', '[3,0]', 3, '2026-01-28 09:19:20'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10923, 9010, 'IT 300', 'Human Computer Interaction', 'Major', '', 3, 'IT 200', '1st Semester', '[3,0]', 3, '2026-01-28 09:19:20'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10924, 9011, 'IT 301', 'Computer Networks 2', 'Major', '', 3, 'IT 202', '2nd Semester', '[3,0]', 3, '2026-01-28 09:19:20'),
(18, NULL, NULL, NULL, '2026-02-27', NULL, 1, 0, '', 10925, 9012, 'CC 301', 'Software Engineering', 'Major', '', 3, 'CC 300', '2nd Semester', '[3,0]', 3, '2026-01-28 09:19:20');

-- --------------------------------------------------------

--
-- Table structure for table `curriculum_master`
--

CREATE TABLE `curriculum_master` (
  `curriculum_id` int(11) NOT NULL,
  `curriculum_code` varchar(255) NOT NULL DEFAULT '',
  `program_id` int(11) DEFAULT NULL,
  `header` longtext NOT NULL DEFAULT '',
  `units` int(11) NOT NULL DEFAULT 0,
  `flag_default` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `curriculum_master`
--

INSERT INTO `curriculum_master` (`curriculum_id`, `curriculum_code`, `program_id`, `header`, `units`, `flag_default`, `date_created`) VALUES
(18, '2020', 1, 'BSIT Prospectus 2020', 0, 1, '2026-01-23 11:16:04');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `department_id` int(11) NOT NULL,
  `department` varchar(255) NOT NULL,
  `code_name` text NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `user_id` int(50) NOT NULL,
  `updatedAt` datetime(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`department_id`, `department`, `code_name`, `status`, `user_id`, `updatedAt`) VALUES
(5, 'Department of Computing Informatic', 'DCI', 1, 3, '2026-01-15 18:05:14.000000'),
(6, 'Department of Business and Accounting', 'DBA', 1, 4, NULL),
(7, 'Department of teaching and education', 'DTE', 1, 11, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `enrollments`
--

CREATE TABLE `enrollments` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `teacher_class_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `section_name` varchar(255) NOT NULL,
  `schedule` varchar(255) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `sem` tinyint(2) NOT NULL,
  `dean_allowed_id` int(11) DEFAULT NULL,
  `dean_approved_by` int(11) DEFAULT NULL,
  `dean_approved_at` datetime DEFAULT NULL,
  `date_enrolled` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(50) NOT NULL DEFAULT 'Enrolled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `enrollments`
--

INSERT INTO `enrollments` (`enrollment_id`, `student_id`, `teacher_class_id`, `subject_id`, `class_id`, `section_name`, `schedule`, `school_year_id`, `sem`, `dean_allowed_id`, `dean_approved_by`, `dean_approved_at`, `date_enrolled`, `status`) VALUES
(1, 25, 9102, 9009, 165, 'BSCS 3-A', 'TTH 1:00-2:30 PM', 2, 1, 17, 23, '2026-01-28 09:25:15', '2026-01-28 11:28:14', 'Enrolled'),
(2, 25, 9104, 9010, 168, 'BSBA 2-A', 'TTH 8:00-9:30 AM', 2, 1, 18, 23, '2026-01-28 09:25:15', '2026-01-28 11:28:14', 'Enrolled'),
(3, 25, 9101, 9009, 161, 'BSIT 3-A', 'MWF 9:00-10:30 AM', 2, 1, 17, 23, '2026-02-04 16:34:07', '2026-02-04 10:23:06', 'Pending'),
(4, 25, 9103, 9010, 161, 'BSIT 3-A', 'MWF 1:00-2:30 PM', 2, 1, 18, 23, '2026-02-04 16:34:07', '2026-02-04 10:23:06', 'Pending'),
(21, 25, 9001, 9001, 9001, 'BSIT 3-B', 'MWF 8:00-9:00 AM', 2, 1, NULL, NULL, NULL, '2026-02-10 09:57:45', 'Pending'),
(22, 25, 9003, 9003, 9003, 'BSIT 3-C', 'MWF 1:00-2:00 PM', 2, 1, NULL, NULL, NULL, '2026-02-10 09:57:50', 'Pending'),
(24, 32, 9101, 9009, 161, 'BSIT 3-A', 'MWF 9:00-10:30 AM', 2, 1, NULL, NULL, NULL, '2026-02-11 13:38:43', 'Enrolled'),
(25, 32, 9103, 9010, 161, 'BSIT 3-A', 'MWF 1:00-2:30 PM', 2, 1, NULL, NULL, NULL, '2026-02-11 13:38:58', 'Enrolled');

-- --------------------------------------------------------

--
-- Table structure for table `final_grade`
--

CREATE TABLE `final_grade` (
  `final_id` int(11) NOT NULL,
  `teacher_class_id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL DEFAULT 0,
  `student_name` varchar(255) NOT NULL DEFAULT '',
  `student_id_text` varchar(100) NOT NULL DEFAULT '',
  `section_name` longtext NOT NULL DEFAULT '',
  `subject_code` varchar(255) NOT NULL DEFAULT '',
  `course_desc` text NOT NULL DEFAULT '',
  `program_id` int(11) NOT NULL DEFAULT 0,
  `program_code` varchar(100) NOT NULL DEFAULT '',
  `units` int(11) NOT NULL DEFAULT 0,
  `prelim_grade` double NOT NULL DEFAULT 0,
  `midterm_grade` double NOT NULL DEFAULT 0,
  `finalterm_grade` double NOT NULL DEFAULT 0,
  `final_grade` double NOT NULL DEFAULT 0,
  `converted_grade` varchar(10) NOT NULL DEFAULT '',
  `completion` varchar(10) NOT NULL DEFAULT '',
  `school_year_id` int(11) NOT NULL DEFAULT 0,
  `school_year` varchar(50) NOT NULL DEFAULT '',
  `sem` varchar(50) NOT NULL DEFAULT '',
  `remarks` varchar(255) NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `teacher_name` varchar(255) NOT NULL DEFAULT '',
  `schedule` varchar(255) NOT NULL DEFAULT '',
  `yr_level` varchar(100) NOT NULL DEFAULT '',
  `flag_fixed` int(11) NOT NULL DEFAULT 0 COMMENT '1 -set by registrar, 0 -computed',
  `date_updated` datetime DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0-lms, 1- manual',
  `major` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `final_grade`
--

INSERT INTO `final_grade` (`final_id`, `teacher_class_id`, `student_id`, `student_name`, `student_id_text`, `section_name`, `subject_code`, `course_desc`, `program_id`, `program_code`, `units`, `prelim_grade`, `midterm_grade`, `finalterm_grade`, `final_grade`, `converted_grade`, `completion`, `school_year_id`, `school_year`, `sem`, `remarks`, `date_added`, `teacher_id`, `teacher_name`, `schedule`, `yr_level`, `flag_fixed`, `date_updated`, `status`, `major`) VALUES
(3, 10, 10, 'LOPEZ, CARLOS ANTONIO', 'STU-2025004', 'BSIT 1-A', 'GEC 1', 'Understanding the Self', 1, 'BSIT', 3, 84, 85.5, 87, 85.5, '2.00', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:18:40', 17, 'Dr. Elena Santos Rodriguez', 'MWF 8:00-9:00 AM', '1', 0, NULL, 1, 'Information Technology'),
(4, 11, 10, 'LOPEZ, CARLOS ANTONIO', 'STU-2025004', 'BSIT 1-A', 'CC 100', 'Introduction to Computing', 1, 'BSIT', 3, 86, 88, 90, 88, '1.75', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:18:40', 17, 'Dr. Elena Santos Rodriguez', 'TTH 10:00-12:00 PM', '1', 0, NULL, 1, 'Information Technology'),
(5, 10, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 1-A', 'GEC 1', 'Understanding the Self', 1, 'BSIT', 3, 82, 84, 86, 84, '2.00', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:19:20', 17, 'Dr. Elena Santos Rodriguez', 'MWF 8:00-9:00 AM', '1', 0, NULL, 1, 'Information Technology'),
(6, 11, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 1-A', 'CC 100', 'Introduction to Computing', 1, 'BSIT', 3, 85, 87, 89, 87, '1.75', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:19:20', 17, 'Dr. Elena Santos Rodriguez', 'TTH 10:00-12:00 PM', '1', 0, NULL, 1, 'Information Technology'),
(7, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 1-A', 'GEC 2', 'Readings in Philippine History', 1, 'BSIT', 3, 84, 86, 88, 86, '1.75', 'COMPLETED', 2, '2025-2026', '2nd Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '1', 0, NULL, 1, 'Information Technology'),
(8, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 1-A', 'CC 101', 'Computer Programming 1', 1, 'BSIT', 3, 86, 88, 90, 88, '1.75', 'COMPLETED', 2, '2025-2026', '2nd Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '1', 0, NULL, 1, 'Information Technology'),
(9, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 2-A', 'CC 200', 'Data Structures and Algorithms', 1, 'BSIT', 3, 85, 87, 89, 87, '1.75', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '2', 0, NULL, 1, 'Information Technology'),
(10, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 2-A', 'IT 200', 'Discrete Structures', 1, 'BSIT', 3, 84, 86, 88, 86, '1.75', 'COMPLETED', 2, '2025-2026', '1st Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '2', 0, NULL, 1, 'Information Technology'),
(11, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 2-A', 'IT 201', 'Database Systems', 1, 'BSIT', 3, 86, 88, 90, 88, '1.75', 'COMPLETED', 2, '2025-2026', '2nd Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '2', 0, NULL, 1, 'Information Technology'),
(12, 0, 25, 'JOHNSON, SUYOU PALOMINA', '2021-10023', 'BSIT 2-A', 'IT 202', 'Computer Networks 1', 1, 'BSIT', 3, 82, 84, 86, 84, '2.00', 'COMPLETED', 2, '2025-2026', '2nd Semester', 'PASSED', '2026-01-28 09:19:20', 26, 'FACULTY F. FACULTY JR.', 'TBA', '2', 0, NULL, 1, 'Information Technology');

-- --------------------------------------------------------

--
-- Table structure for table `instructor`
--

CREATE TABLE `instructor` (
  `instructor_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `department` int(11) NOT NULL DEFAULT 0,
  `cluster_id` int(11) NOT NULL DEFAULT 0,
  `account_status` int(11) NOT NULL DEFAULT 0,
  `contact_number` varchar(100) DEFAULT NULL,
  `brgy` text DEFAULT NULL,
  `home_address` text DEFAULT NULL,
  `date_modified` datetime NOT NULL DEFAULT current_timestamp(),
  `flag_access` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `notif_id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `content` longtext NOT NULL,
  `created_At` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `unread` tinyint(5) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `programs`
--

CREATE TABLE `programs` (
  `program_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL DEFAULT 0,
  `program` varchar(255) NOT NULL DEFAULT '',
  `short_name` varchar(100) NOT NULL DEFAULT '',
  `major` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '\'\\\'["xxx-ccc-xxx"]\\\'\'',
  `status` int(11) NOT NULL DEFAULT 0,
  `duration` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `programs`
--

INSERT INTO `programs` (`program_id`, `department_id`, `program`, `short_name`, `major`, `status`, `duration`) VALUES
(11, 5, 'Bachelor of Science in Information Technology', 'BSIT', '[\"NEW MAJOR\",\"NEW MAJOR2\"]', 0, 4),
(12, 6, 'Bachelor of Science in Accounting', 'DBA', '[\"TEST\"]', 0, 4),
(13, 7, 'Bachelor of scinece in education', 'BSED', '[\"MAJOR IN FILIPINO\"]', 0, 4),
(27, 5, 'information technology', 'BSIT', '\'\\\'[\"xxx-ccc-xxx\"]\\\'\'', 0, 5);

-- --------------------------------------------------------

--
-- Table structure for table `program_cluster`
--

CREATE TABLE `program_cluster` (
  `cluster_id` int(11) NOT NULL,
  `cluster_name` varchar(255) NOT NULL DEFAULT '',
  `cluster_code` varchar(255) NOT NULL DEFAULT '',
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `teacher_name` varchar(255) NOT NULL DEFAULT '' COMMENT 'display name',
  `subject_id` longtext NOT NULL DEFAULT '[]',
  `department_id` int(11) NOT NULL DEFAULT 0,
  `schoolyear_id` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `prospectus`
--

CREATE TABLE `prospectus` (
  `prospectus_id` int(11) NOT NULL,
  `year_level` int(11) NOT NULL,
  `Lec` int(11) NOT NULL DEFAULT 0,
  `Lab` int(11) NOT NULL DEFAULT 0,
  `Grade` int(11) NOT NULL DEFAULT 0,
  `Required_Total_Units` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `school_year_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `student_id` varchar(255) CHARACTER SET latin1 NOT NULL,
  `status` int(11) NOT NULL DEFAULT 0,
  `createdAt` datetime(6) NOT NULL DEFAULT current_timestamp(6),
  `updatedAt` datetime(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- Table structure for table `school_year`
--

CREATE TABLE `school_year` (
  `school_year_id` int(11) NOT NULL,
  `school_year` varchar(50) NOT NULL DEFAULT '',
  `sem` varchar(50) NOT NULL DEFAULT '',
  `date_from` date DEFAULT NULL,
  `date_to` date DEFAULT NULL,
  `flag_used` int(11) NOT NULL DEFAULT 1,
  `createdAt` timestamp(6) NOT NULL DEFAULT current_timestamp(6),
  `updatedAt` datetime(6) DEFAULT NULL,
  `isDefault` tinyint(2) NOT NULL DEFAULT 0,
  `limit_perSem` int(50) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `school_year`
--

INSERT INTO `school_year` (`school_year_id`, `school_year`, `sem`, `date_from`, `date_to`, `flag_used`, `createdAt`, `updatedAt`, `isDefault`, `limit_perSem`) VALUES
(1, '2024-2025', '1st Semester', '2025-05-05', '2025-06-30', 1, '2026-01-15 06:59:54.394983', '2026-02-26 13:18:56.000000', 0, 0),
(2, '2025-2026', '1st Semester', '2025-09-05', '2025-09-30', 1, '2026-01-15 06:59:54.394983', '2026-02-26 15:11:17.000000', 0, 0),
(3, '2025-2026', '1st Semester', '2026-01-04', '2026-05-26', 1, '2026-01-15 09:54:30.181024', '2026-02-26 15:10:42.000000', 1, 0),
(4, '2026-2027', '1st Semester', '2026-07-25', '2026-12-28', 1, '2026-02-24 07:19:49.778435', '2026-02-26 14:58:46.000000', 0, 0),
(5, '2026-2027', '2nd Semester', '2027-12-05', '2027-05-24', 1, '2026-02-24 07:59:13.851308', '2026-02-26 15:51:33.000000', 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `module` varchar(255) NOT NULL DEFAULT '',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`settings`)),
  `school_year_id` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`setting_id`, `module`, `settings`, `school_year_id`, `date_added`) VALUES
(1, 'subject_exceptions', '[156,88,89,174,86]', 0, '2024-08-14 08:49:33'),
(2, 'grade_system', '[{\"rating_id\":1,\"range_from\":55,\"range_to\":59,\"grade\":4,\"text\":\"FAILED\"},{\"rating_id\":2,\"range_from\":60,\"range_to\":64,\"grade\":3,\"text\":\"PASSED\"},{\"rating_id\":3,\"range_from\":65,\"range_to\":69,\"grade\":2.75,\"text\":\"PASSED\"},{\"rating_id\":4,\"range_from\":70,\"range_to\":74,\"grade\":2.5,\"text\":\"PASSED\"},{\"rating_id\":5,\"range_from\":75,\"range_to\":79,\"grade\":2.25,\"text\":\"PASSED\"},{\"rating_id\":6,\"range_from\":80,\"range_to\":83,\"grade\":2,\"text\":\"PASSED\"},{\"rating_id\":7,\"range_from\":84,\"range_to\":87,\"grade\":1.75,\"text\":\"PASSED\"},{\"rating_id\":8,\"range_from\":88,\"range_to\":91,\"grade\":1.5,\"text\":\"PASSED\"},{\"rating_id\":9,\"range_from\":92,\"range_to\":95,\"grade\":1.25,\"text\":\"PASSED\"},{\"rating_id\":10,\"range_from\":96,\"range_to\":100,\"grade\":1,\"text\":\"PASSED\"},{\"rating_id\":11,\"range_from\":0,\"range_to\":54,\"grade\":5,\"text\":\"FAILED\"}]', 0, '2023-01-23 16:33:52'),
(3, 'additional', '[{\"field\":\"FAILED_UNIT\",\"value\":6,\"remarks\":\"(Greater than) - Number of unit to be consider as Failed \\n(Equal)- Number of unit to be probationary\"}]', 0, '2023-01-23 16:51:03');

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` varchar(255) NOT NULL,
  `firstname` varchar(50) CHARACTER SET latin1 COLLATE latin1_general_ci DEFAULT NULL,
  `lastname` varchar(50) DEFAULT NULL,
  `email_address` varchar(50) DEFAULT NULL,
  `ccc_email` varchar(255) DEFAULT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `suffix_name` varchar(10) DEFAULT NULL,
  `contact` varchar(255) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `gender` varchar(10) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `year_level` int(11) NOT NULL DEFAULT 0,
  `major` varchar(50) NOT NULL DEFAULT '',
  `class_id` int(11) NOT NULL DEFAULT 0,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) NOT NULL DEFAULT '',
  `course_id` int(11) NOT NULL DEFAULT 0,
  `status` varchar(100) NOT NULL DEFAULT '0',
  `curriculum_id` int(11) NOT NULL DEFAULT 0,
  `emergency_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `graduated_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`graduated_data`)),
  `additional_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '\'{}\'',
  `flag_update` datetime(6) NOT NULL,
  `program_id` int(11) DEFAULT NULL,
  `department_id` int(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `firstname`, `lastname`, `email_address`, `ccc_email`, `middle_name`, `suffix_name`, `contact`, `barangay`, `address`, `gender`, `dob`, `year_level`, `major`, `class_id`, `username`, `password`, `course_id`, `status`, `curriculum_id`, `emergency_data`, `graduated_data`, `additional_data`, `flag_update`, `program_id`, `department_id`) VALUES
('2021-01111', 'Tristan', 'Payong', 'tristan17@email.com', 'tapayaong@ccc.edu.ph', 'Abreu', NULL, '23333222222', 'Brgy. Canlubang', 'adasdasdada', NULL, NULL, 4, 'eqeqeq', 0, NULL, '', 0, '0', 27, 'eqweqwe asdadasd', '[]', 'eqweqeqweqwe', '2026-02-27 16:36:52.000000', 27, 5),
('2021-10023', 'Suyou', 'Johnson', 'suyou@email.com', 'suyou.johnson@ccc.edu.ph', 'Palomina', NULL, '', '', '', 'Male', '2026-02-03', 2, 'Information Technology', 0, 'suyou.johnson', '', 1, '0', 0, '', '[]', '\'{}\'', '0000-00-00 00:00:00.000000', 2, 2);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_id` int(11) NOT NULL,
  `subject_code` varchar(100) NOT NULL DEFAULT '',
  `subject_title` varchar(100) NOT NULL DEFAULT '',
  `lec_lab` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '\'[0,0]\'',
  `unit` int(11) NOT NULL DEFAULT 0,
  `description` longtext NOT NULL DEFAULT '',
  `flag_manual_enroll` int(11) NOT NULL DEFAULT 0 COMMENT '0 - false\r\n1 - true',
  `status` int(11) NOT NULL,
  `date_modified` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_id`, `subject_code`, `subject_title`, `lec_lab`, `unit`, `description`, `flag_manual_enroll`, `status`, `date_modified`) VALUES
(265, 'TEST3', 'TEST', '[3,2]', 5, '', 1, 0, '2026-01-21 13:22:10'),
(268, 'TEST3', 'TEST23', '[3,2]', 5, '', 1, 0, '2026-01-21 13:51:31'),
(269, 'MATH101', 'College Algebra', '[2,1]', 3, 'Basic algebra course', 1, 0, '2026-01-23 09:04:45'),
(270, 'MATH101', 'English Composition', '[2,1]', 3, 'Intro to writing', 1, 0, '2026-01-23 09:04:45'),
(271, 'MATH101', 'Intro to Computing', '[2,1]', 3, 'Computing basics', 1, 0, '2026-01-23 09:04:45'),
(272, 'SCI104', 'General Science', '[0,2]', 4, 'Science overview', 1, 0, '2026-01-23 09:04:45'),
(273, 'SCI104', 'Physical Education', '[0,2]', 4, 'PE class', 1, 0, '2026-01-23 09:04:45'),
(274, 'SCI104', 'World History', '[0,2]', 4, 'History of the world', 1, 0, '2026-01-23 09:04:45'),
(287, 'CS104', 'College Algebra 2', '[4,2]', 3, 'Basic algebra course', 1, 0, '2026-01-23 14:00:18'),
(290, 'HIST105', 'LIFE AND WORDS OF RIZAL', '[3,2]', 2, 'Science overview', 1, 0, '2026-01-23 16:36:08'),
(291, 'HIST105', 'READING IN PHILIPPINE HISTORY', '[3,2]', 2, 'PE class', 1, 0, '2026-01-23 16:36:08'),
(292, 'HIST105', 'SIBIKA', '[3,2]', 2, 'History of the world', 1, 0, '2026-01-23 16:36:08'),
(293, 'IT201', 'DATA STRUCTURES AND ALGORITHMS WITH LABORATORY', '[2,3]', 3, '', 0, 0, '2026-01-23 10:49:47'),
(307, 'PE202', 'TEAM SPORTS', '[2,0]', 2, '', 1, 0, '2026-01-23 12:22:23'),
(308, 'CEEL302', 'MULTIMEDIA', '[2,3]', 3, '', 1, 0, '2026-01-23 12:24:41'),
(309, 'CEEL302', 'GRAPHIC DESIGN', '[2,3]', 3, '', 1, 0, '2026-01-23 16:18:21'),
(310, 'IT231', 'OPERATING SYSTEM', '[2,3]', 3, '', 0, 0, '2026-01-27 12:56:14'),
(319, 'ENG101', 'ENGLISH 1', '[4,2]', 3, '', 1, 0, '2026-02-27 14:30:09'),
(320, 'ENG101', 'ENLGISH 2', '[4,2]', 3, '', 1, 0, '2026-02-27 14:30:09'),
(321, 'ENG101', 'ENLGISH 3', '[4,2]', 3, '', 1, 0, '2026-02-27 14:30:09'),
(323, 'PSYM102', 'PSYCHOLOGY ASSESSMENT', '[1,2]', 3, '', 0, 0, '2026-01-27 14:07:51'),
(342, 'ASD', 'ASDDSA', '[2,2]', 2, '', 0, 0, '2026-02-03 08:34:37'),
(343, 'ASD1', 'ASD', '[2,2]', 2, '', 0, 0, '2026-02-03 08:35:27'),
(344, 'ASD2', 'ASDASDSA2', '[2,2]', 2, '', 0, 0, '2026-02-03 08:49:18'),
(345, 'ASD3', 'ASDAD3', '[2,2]', 2, '', 0, 0, '2026-02-03 08:50:39'),
(346, 'QWE', 'QWEWQ', '[2,2]', 2, '', 0, 0, '2026-02-03 09:38:45'),
(347, 'QWE1', 'QWE1QWE', '[2,2]', 2, '', 0, 0, '2026-02-03 09:43:45');

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class`
--

CREATE TABLE `teacher_class` (
  `teacher_class_id` int(11) NOT NULL,
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `class_id` int(11) NOT NULL DEFAULT 0,
  `subject_id` int(11) NOT NULL DEFAULT 0,
  `subject_text` varchar(255) NOT NULL DEFAULT '''''',
  `thumbnails` text NOT NULL DEFAULT '',
  `schedule` longtext NOT NULL,
  `sem` varchar(20) NOT NULL DEFAULT '',
  `schoolyear_id` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime DEFAULT current_timestamp(),
  `program_id` int(11) NOT NULL,
  `room` varchar(255) NOT NULL,
  `year_level` int(50) NOT NULL,
  `unit` int(55) NOT NULL,
  `lec_lab` longtext NOT NULL,
  `section_limit` int(50) NOT NULL,
  `status` tinyint(11) NOT NULL DEFAULT 0 COMMENT 'not archived = 0, archived = 1',
  `total_hours` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `teacher_class`
--

INSERT INTO `teacher_class` (`teacher_class_id`, `teacher_id`, `class_id`, `subject_id`, `subject_text`, `thumbnails`, `schedule`, `sem`, `schoolyear_id`, `date_added`, `program_id`, `room`, `year_level`, `unit`, `lec_lab`, `section_limit`, `status`, `total_hours`) VALUES
(109, 5, 201, 265, 'TEST', '', '[\"Monday:: 08:00-09:00:: R-1:: lec\"]', '2nd Semester', 1, '2026-02-20 13:35:08', 11, '{\"R-1_Monday\":{\"0\":\"08:00-09:00\"}}', 1, 3, '[2,2]', 34, 0, 2),
(110, 5, 201, 268, 'TEST23', '', '[\"Wednesday:: 12:00-13:00:: R-7:: lec\",\"Thursday:: 12:00-13:00:: R-7:: lec\",\"Friday:: 09:00-10:00:: R-7:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"R-7_Wednesday\":{\"0\":\"12:00-13:00\"},\"R-7_Thursday\":{\"0\":\"12:00-13:00\"},\"R-7_Friday\":{\"0\":\"09:00-10:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(111, 5, 203, 269, 'College Algebra', '', '[\"Saturday:: 08:00-09:00:: R-4:: lec\",\"Friday:: 08:00-09:00:: R-4:: lec\",\"Tuesday:: 08:00-09:00:: R-4:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 11, '{\"R-4_Saturday\":{\"0\":\"08:00-09:00\"},\"R-4_Friday\":{\"0\":\"08:00-09:00\"},\"R-4_Tuesday\":{\"0\":\"08:00-09:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(112, 7, 201, 270, 'English Composition', '', '[\"Tuesday:: 10:00-11:00:: R-2:: lec\",\"Thursday:: 10:00-11:00:: R-2:: lec\",\"Saturday:: 10:00-11:00:: R-2:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"R-2_Tuesday\":{\"0\":\"10:00-11:00\"},\"R-2_Thursday\":{\"0\":\"10:00-11:00\"},\"R-2_Saturday\":{\"0\":\"10:00-11:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(113, 5, 201, 271, 'Intro to Computing', '', '[\"Monday:: 09:00-10:00:: R-3:: lec\",\"Tuesday:: 09:00-10:00:: R-3:: lec\",\"Wednesday:: 09:00-10:00:: R-3:: lec\",\"Saturday:: 09:00-10:00:: R-1:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 11, '{\"R-3_Monday\":{\"0\":\"09:00-10:00\"},\"R-3_Tuesday\":{\"0\":\"09:00-10:00\"},\"R-3_Wednesday\":{\"0\":\"09:00-10:00\"},\"R-1_Saturday\":{\"0\":\"09:00-10:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(114, 5, 201, 272, 'General Science', '', '[\"Monday:: 15:00-16:00:: AB-3:: lec\",\"Tuesday:: 15:00-16:00:: AB-3:: lec\",\"Wednesday:: 15:00-16:00:: AB-3:: lec\",\"Saturday:: 14:30-15:30:: AB-3:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"AB-3_Monday\":{\"0\":\"15:00-16:00\"},\"AB-3_Tuesday\":{\"0\":\"15:00-16:00\"},\"AB-3_Wednesday\":{\"0\":\"15:00-16:00\"},\"AB-3_Saturday\":{\"0\":\"14:30-15:30\"}}', 1, 3, '[2,2]', 30, 0, 2),
(115, 5, 207, 273, 'Physical Education', '', '[\"Monday:: 14:00-15:00:: R-7:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 11, '{\"R-7_Monday\":{\"0\":\"14:00-15:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(116, 6, 208, 274, 'World History', '', '[\"Tuesday:: 15:00-16:00:: R-8:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"R-8_Tuesday\":{\"0\":\"15:00-16:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(117, 6, 209, 287, 'College Algebra 2', '', '[\"Wednesday:: 16:00-17:00:: R-9:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 11, '{\"R-9_Wednesday\":{\"0\":\"16:00-17:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(118, 6, 210, 290, 'LIFE AND WORDS OF RIZAL', '', '[\"Thursday:: 17:00-18:00:: R-10:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"R-10_Thursday\":{\"0\":\"17:00-18:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(119, 6, 211, 291, 'READING IN PHILIPPINE HISTORY', '', '[\"Friday:: 18:00-19:00:: R-11:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 11, '{\"R-11_Friday\":{\"0\":\"18:00-19:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(120, 6, 212, 292, 'SIBIKA', '', '[\"Saturday:: 19:00-20:00:: R-12:: lec\"]', '1st Semester', 2, '2026-02-20 13:35:08', 12, '{\"R-12_Saturday\":{\"0\":\"19:00-20:00\"}}', 1, 3, '[2,2]', 30, 0, 2),
(121, 8, 3, 319, 'ENLGISH 1', '', '[\"Thursday:: 12:00-13:00:: asda:: lec\",\"Wednesday:: 12:00-14:00:: r-7:: lec\"]', '1st Semester', 2, '2026-02-20 15:34:59', 11, '{\"asda_Thursday\":{\"0\":\"12:00-13:00\"},\"r-7_Wednesday\":{\"0\":\"12:00-14:00\"}}', 2, 2, '[2,2]', 30, 0, 2),
(122, 8, 161, 343, 'ASD', '', '[\"Thursday:: 09:00-13:00:: asda:: lab\"]', '2nd Semester', 3, '2026-02-20 15:54:54', 11, '{\"asda_Thursday\":{\"0\":\"09:00-13:00\"}}', 2, 3, '[2,2]', 26, 0, 3),
(123, 7, 161, 287, 'College Algebra 2', '', '[\"Thursday:: 13:00-14:00:: asda:: lec\"]', '2nd Semester', 3, '2026-02-20 15:59:35', 11, '{\"asda_Thursday\":{\"0\":\"13:00-14:00\"}}', 2, 1, '[2,2]', 26, 0, 3),
(124, 6, 2, 272, 'General Science', '', '[\"Tuesday:: 12:00-13:00:: AB-1:: lec\",\"Tuesday:: 14:00-15:00:: R-4:: lec\"]', '2nd Semester', 3, '2026-02-23 15:35:32', 11, '{\"AB-1_Tuesday\":{\"0\":\"12:00-13:00\"},\"R-4_Tuesday\":{\"0\":\"14:00-15:00\"}}', 3, 2, '[2,2]', 30, 0, 2),
(125, 6, 1, 319, 'ENLGISH 1', '', '[\"Friday:: 12:00-13:00:: R-4:: lec\",\"Monday:: 12:00-13:00:: AB-7:: lec\"]', '2nd Semester', 3, '2026-02-23 15:37:00', 11, '{\"R-4_Friday\":{\"0\":\"12:00-13:00\"},\"AB-7_Monday\":{\"0\":\"12:00-13:00\"}}', 3, 2, '[1,2]', 30, 0, 2);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_class_student`
--

CREATE TABLE `teacher_class_student` (
  `teacher_class_student_id` int(11) NOT NULL,
  `teacher_class_id` int(11) NOT NULL DEFAULT 0,
  `student_id` int(11) NOT NULL DEFAULT 0,
  `teacher_id` int(11) NOT NULL DEFAULT 0,
  `program_id` int(11) NOT NULL DEFAULT 0,
  `year_level` int(11) NOT NULL DEFAULT 0,
  `date_added` date DEFAULT curdate(),
  `status` varchar(50) DEFAULT '',
  `room` varchar(50) NOT NULL,
  `time_from` time NOT NULL,
  `time_to` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `general_id` varchar(255) NOT NULL,
  `img` varchar(255) NOT NULL,
  `f_name` varchar(255) NOT NULL,
  `m_name` varchar(255) NOT NULL,
  `l_name` varchar(255) NOT NULL,
  `suffix` varchar(255) NOT NULL,
  `sex` varchar(100) NOT NULL,
  `birth_date` varchar(255) NOT NULL,
  `user_role` text DEFAULT '\'[""]\'' COMMENT '''1'' => ''ADMIN'', ''2'' => ''REGISTRAR'', ''3'' => ''VPAA'', ''4'' => ''INSTRUCTOR'', ''5'' => ''FACULTY'', ''6'' => ''STUDENT''',
  `username` varchar(255) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `email_address` varchar(255) NOT NULL DEFAULT '',
  `recovery_email` varchar(255) NOT NULL,
  `position` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL,
  `locked` int(11) NOT NULL,
  `last_signin` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `general_id`, `img`, `f_name`, `m_name`, `l_name`, `suffix`, `sex`, `birth_date`, `user_role`, `username`, `password`, `email_address`, `recovery_email`, `position`, `status`, `locked`, `last_signin`) VALUES
(1, 'CGC-08626', '', 'Marlon', 'Llanes', 'Reolo', '', 'male', '2000-11-04', '[\"1\",\"2\"]', 'mlreolo@ccc.edu.ph', '779a8d6c3c12a58398e76f3146ae3874454e97c5', 'mlreolo@ccc.edu.ph', 'mlreolo@ccc.edu.ph', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(2, '2021-01111', '', 'TRISTAN', 'RAPHAEL', 'PAYONG', '', 'male', '2010-08-02', '[\"2\"]', '2021-01111', 'c2cea4ac862d8ac050a82b40643dae872a74841e', 'tristanpayong@gmail.com', 'recover@email.com', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(3, '999-999', '', 'MISTER', 'D.', 'DEAN', '', 'male', '2011-01-06', '[\"3\"]', 'mr.dean', '91337c91163ccf14faf052a6ec176c884a13989a', 'mr_dean@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(4, '2021-01111CGC', '', 'MISS', 'D.', 'DEAN', '', 'female', '2011-01-05', '[\"3\"]', 'ms.dean', 'afa7ae4d2cea9a27c63c7f1ece85e4aa7d7b0245', 'ms_dean@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(5, '999-teacher', '', 'MISTER', 'SIR', 'PROF', '', 'male', '2011-01-19', '[\"4\"]', 'userprof', '2023e83c4e009ecee12c7da312e9d1cade1d085b', 'MisterProf@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(6, '999-222', '', 'MISS', 'MISS', 'PROF', '', 'female', '2011-02-01', '[\"4\"]', 'prof2', '15fcdf43fc10e4420833b054e845b16b533ad5db', 'MissProf@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(7, '2021-prof', '', 'SIR PROF', 'A', 'PROFESSOR', 'JR.', 'male', '2011-02-01', '[\"4\"]', '2021-prof', 'aaa92d0bbe09a95dabef644d8d73f6c7ccaa50b0', 'ProfessorProf@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(8, '999-maam', '', 'ALIN', 'D', 'OG', '', 'male', '2011-01-11', '[\"4\"]', '999-maam', 'c5130f76b1418e092689c46d2258622d8d746a08', 'AlinD_og@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(9, '2020-2020', '', 'TRISTAN', 'MARIVEL', 'AZARAP', '', 'male', '2010-12-15', '[\"4\"]', '2020-2020', 'c5a2813c2714d22e63b306a5a5ff80ac90cac4fd', 'tristanma@email.com', '', 'Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(10, '2021-10023', '', 'SUYOU', 'PALOMINA', 'JOHNSON', '', 'male', '2011-02-07', '[\"5\"]', '2021-0000', 'c59fd8d27c9a4434b52daafcb3c8dd90d781c5f7', 'suyou.johnson@ccc.edu.ph', '', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(11, '2021-dean', '', 'JOHN', 'JOHN', 'JOHN', '', 'male', '2011-02-01', '[\"3\"]', '2021-dean', '1b5bbf0b42b786d4f0366e646517ca1fd79b5ec7', 'john3@email.com', '', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(12, 'student-1', '', 'MIKE', 'MIKE', 'MIKE', '', 'male', '2011-02-21', '[\"5\"]', 'student-1', '013fa1d373576aab4282a962a9a3fe7b025b39f0', 'mike3@email.com', '', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00'),
(41, '2021-01111C', '', 'JOSEPH', 'JOSEPH', 'JOSEPH', '', 'male', '2011-02-23', '[\"5\"]', '2021-01111C', 'e298d9a27d2e4b617e88f6d8474335ecd3154f53', '2021-01111C@email.com', '', 'Non-Teaching Personnel', 0, 0, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `user_log`
--

CREATE TABLE `user_log` (
  `user_log_id` int(11) NOT NULL,
  `login_date` datetime NOT NULL,
  `logout_date` datetime NOT NULL,
  `action` varchar(20) NOT NULL,
  `user_id` text NOT NULL,
  `session_id` text NOT NULL,
  `ip_address` varchar(20) NOT NULL,
  `device` varchar(255) NOT NULL,
  `system_id` int(11) NOT NULL DEFAULT 0,
  `token_id` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[]' CHECK (json_valid(`token_id`)),
  `login_flag` int(11) NOT NULL DEFAULT 0,
  `user_level` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `user_log`
--

INSERT INTO `user_log` (`user_log_id`, `login_date`, `logout_date`, `action`, `user_id`, `session_id`, `ip_address`, `device`, `system_id`, `token_id`, `login_flag`, `user_level`) VALUES
(1, '2025-09-08 12:43:20', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2025-09-08 12:43:20\", \"LOGIN\", \"::1\"], [\"2025-09-08 13:02:22\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"139.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 139.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b1823a1ba1c6a33da356437e44036ff816ffdda681481153c23afebeabae3478\", \"1f48e1aa5310c52ec704243ffee61685edcbbe4ddc1b6999fcad60ed6191ca2f\"]', 1, 1),
(2, '2026-01-15 14:37:17', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-15 14:37:17\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"425dc0ad10a3c190077ce499945db1641c35dcc4fafae4db2b0ff9ff693edbaa\"]', 1, 1),
(3, '2026-01-15 14:58:09', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-15 14:58:09\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f3efacbe69a98704666cb7e2e194c584b54d327af4e127f9148b1ca252f62cde\"]', 1, 2),
(4, '2026-01-16 08:12:39', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-16 08:12:39\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"e4e3f9b307ef2fb6652867a2da9d7561e0e760b9c6af2bb37500e9d7a337e5fa\"]', 1, 2),
(5, '2026-01-17 08:50:14', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-17 08:50:14\", \"LOGIN\", \"::1\"], [\"2026-01-17 11:39:31\", \"LOGIN\", \"::1\"], [\"2026-01-17 14:41:19\", \"LOGIN\", \"::1\"], [\"2026-01-17 21:46:00\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"1d61fef2eb4d348ab1a5f24e6ad81862b8ad19ca2739b78830af2551d7933d38\", \"14005dc9fb3267aae7ef34e0fb5f66b352bd328a82efb0617f51fd7ff9329e61\", \"c091fc90cfe97b0b0485111bba15403756c9d865385f19dda067de4810ea5509\", \"fbd02d3d751d8c96095c06bec46eb504c86d3a42f034876a19d53cbc29e6de1e\"]', 1, 2),
(6, '2026-01-18 13:59:51', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-18 13:59:51\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"22474135c5b1d742d7015faadd2fe6ca35b2290a3670571758a3eb3fa5b41718\"]', 1, 2),
(7, '2026-01-19 08:16:38', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-19 08:16:38\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"aebe750b1997d25c7a2b2be78b3b768d3e9d6bf58f629f9a098c8dbae9bf0f88\"]', 1, 2),
(8, '2026-01-19 13:22:35', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-01-19 13:22:35\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"7be68cbfedd3e4d64612369e5988743906ad6a7c70da753d8fc3eea51a9237f5\"]', 1, 1),
(9, '2026-01-20 07:27:42', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-20 07:27:42\", \"LOGIN\", \"::1\"], [\"2026-01-20 16:57:39\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"09c9094babe836979db2190c8fb9d9502621a64bcdb1cb369e1cb4c1014441f2\", \"022447730b390a3e58c0cd66d104b6d9493e593ca4f34a9074dfb33752f7b2ec\"]', 1, 2),
(10, '2026-01-21 07:52:07', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-21 07:52:07\", \"LOGIN\", \"::1\"], [\"2026-01-21 10:31:12\", \"LOGIN\", \"::1\"], [\"2026-01-21 13:21:50\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"143.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 143.0.0.0 on Windows 10 64-bit\"}', 0, '[\"52d727d068944ca16a8ab9c83c5ed6f6c01c6173b51e3957b8ec2554e25000e2\", \"0b8749e9ffd2a31fc100a390ad2b87b9505f5bd6060ae62871410ae14cc3fb23\", \"8f390eb0719e8cdf14ea030b9fbf93b81fbde7e757e9bed0cc2ec9f49e22d71f\"]', 1, 2),
(11, '2026-01-22 07:43:24', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-22 07:43:24\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"dfb06daee5fbb42a51e3dc2aacaaadd65313e9470a7cc846826443732e3ec131\"]', 1, 2),
(12, '2026-01-23 08:13:23', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-23 08:13:23\", \"LOGIN\", \"::1\"], [\"2026-01-23 08:17:53\", \"LOGIN\", \"::1\"], [\"2026-01-23 11:40:41\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"a98f13759c789b89c25029455252b82afa4a758b38af2d20bdb8d91265b33dfb\", \"9d847b84136ab4081cb261f47a593f538d89dec4d955fdfae40e42b315fbe74a\", \"4ac957ff956e67826d12d6b8ad4127df2e1b09933fe5fd3e231974d0a4c586ce\"]', 1, 2),
(13, '2026-01-26 08:05:44', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-26 08:05:44\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"d7ec160a5aa70779a455509497952dbd935301718634b852c912357de6ff49af\"]', 1, 2),
(14, '2026-01-27 07:50:46', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-27 07:50:46\", \"LOGIN\", \"::1\"], [\"2026-01-27 10:23:19\", \"LOGIN\", \"::1\"], [\"2026-01-27 10:40:54\", \"LOGIN\", \"::1\"], [\"2026-01-27 10:41:48\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"bf22ad45e3d3fdf732e19eb39a9a6e0c9409f26c93af15b96b2be122465431cd\", \"5b297a4ebd49db3d35a0747b458c50d7fdc6c3e42190034b649c3b89f1931660\", \"2ac621f0d2ab140ed6aa3e564b698e7b4fac689a32e837f54a753fb7c7e9183d\", \"bce63295e9a2b446ebe10fe214679527aa86bf24c699bb7251c3b5da807ba4fa\"]', 1, 2),
(15, '2026-01-28 08:22:54', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-28 08:22:54\", \"LOGIN\", \"::1\"], [\"2026-01-28 10:59:50\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b5243fb432ad3503b3823860d3b5624aa9619674e816bebc77fda88c585e5e34\", \"8475e7d749d8a083e316ce834633d40988412d152eaff0deae808eb2c1273e05\"]', 1, 2),
(16, '2026-01-29 08:26:15', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-29 08:26:15\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"d39d10fa8588bec1d443419951077e7b9fab9819e851090b9b54f4efad10ed76\"]', 1, 2),
(17, '2026-01-30 09:02:30', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-01-30 09:02:30\", \"LOGIN\", \"::1\"], [\"2026-01-30 14:36:20\", \"LOGIN\", \"::1\"], [\"2026-01-30 15:39:15\", \"LOGIN\", \"::1\"], [\"2026-01-30 16:30:53\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"96f4e4593dbf753f84152500f4aaaa33e845c4d81b13a34c36e6411d1d360c1a\", \"70e52893ea8454f49bbc807489c87d91d19bd53f3027ddfd0645075b3cece666\", \"36b6516ebca6cd1367217a213dda8cc90888b460f7bad756917f3fc2d4f8ee74\", \"6d66cbcbfbb7112399f18a4db387e2925102107de38e2681e32a63bc5548574a\"]', 1, 2),
(18, '2026-02-01 10:24:15', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-01 10:24:15\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"d2b03f50c0c1c8508367ed6c5ab14017be2e2b73b2953ce7cb937d408eecdd6e\"]', 1, 2),
(19, '2026-02-02 09:26:20', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-02 09:26:20\", \"LOGIN\", \"::1\"], [\"2026-02-02 10:02:38\", \"LOGIN\", \"::1\"], [\"2026-02-02 11:28:07\", \"LOGIN\", \"::1\"], [\"2026-02-02 14:53:07\", \"LOGIN\", \"::1\"], [\"2026-02-02 14:57:01\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"839931ab57bc69d96cd547d9fd44f1f3ef911146ad062d11318659ec1ce2501d\", \"7056949431d9aff5cff641ad1caab296abbaab88746ee87360b30dbbaaf896f3\", \"64fcc929229ee4ecca520890d2077d0d7938271f21e1deec274a9920a4ff0bb4\", \"5f7847d89528997462e232ba1b32f2816cdea48d8790e3393decf3a1c48ccaa8\", \"1105ad2b218975d5e7ba859a2b0b79bd5896ff18f4fdd6c1875ca40531f84145\"]', 1, 2),
(20, '2026-02-03 07:46:27', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-03 07:46:27\", \"LOGIN\", \"::1\"], [\"2026-02-03 09:50:54\", \"LOGIN\", \"::1\"], [\"2026-02-03 11:24:34\", \"LOGIN\", \"::1\"], [\"2026-02-03 11:32:58\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"3590deb024ba5c31f01686584a6dc9f102458d3cba976de9d5fb81bbeae27e6c\", \"83a424c1759fe88318891b8584f0fe6d4136db090134439d65e3d93c33c4fd01\", \"c4e1a9a6b4334bdee03e70b782f38f46bdc20bcec92c6bc2506835220ad4db13\", \"79ba72e0dace5c57d53cfc40efc112321af7b5bf7b41c7ac1f4987262dc78601\"]', 1, 2),
(21, '2026-02-04 07:56:13', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-04 07:56:13\", \"LOGIN\", \"::1\"], [\"2026-02-04 11:22:13\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"164408994a796d61db6c2d9af55340939e3f6b9126a18e35caf14cb2248a8207\", \"ab02ec39de3b6302220b24f8d328d8120fe5a9ed776f83328249b86430c3fca0\"]', 1, 2),
(22, '2026-02-05 08:33:49', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-05 08:33:49\", \"LOGIN\", \"::1\"], [\"2026-02-05 14:31:43\", \"LOGIN\", \"::1\"], [\"2026-02-05 16:42:55\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"70ba4db304ddf2d2270a07ed390ada6938e3a222bc67010068e4cd7d97ec06af\", \"c28d8515d3aece8796a5a60cbb8d2875f63a1770fc0ecb4112811f912cbd1468\", \"28542c80723cfdaed97fc68749c2edff6c3aed99cb3aa0907abc6c534675923f\"]', 1, 2),
(23, '2026-02-06 12:27:43', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-06 12:27:43\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"53976a51ff3a8620d7925ba07afa736f77ccc9760f89588b0e669d2f29b1e6fa\"]', 1, 2),
(24, '2026-02-08 06:24:23', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-08 06:24:23\", \"LOGIN\", \"::1\"], [\"2026-02-08 06:51:14\", \"LOGIN\", \"::1\"], [\"2026-02-08 07:16:02\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b211ae9c0234f70a4582c228d53b7f6a9d0eacc5affd0789241904cdaa34cffa\", \"354c8017a305c01858150789bdebea84661546114f100c412fb794ad095bcdf5\", \"c068fffb9d570b47e60f863f25c1aa6df1395b0002a8b096ec7ef14768068a55\"]', 1, 2),
(25, '2026-02-09 08:03:25', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-09 08:03:25\", \"LOGIN\", \"::1\"], [\"2026-02-09 09:56:57\", \"LOGIN\", \"::1\"], [\"2026-02-09 13:33:24\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"50cf6c60b142860c6163f072744657b28507b1bb553d0eca438b87568fe7ee66\", \"6fe9b09a7f25f578856a4845e55ef73d98e49408501515dd37f50399ac7fc69b\", \"90b7cc47704ce2df8f5526c215425c75b3fcc1b97634f64b74df90d30879a6a3\"]', 1, 2),
(26, '2026-02-10 08:01:29', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-10 08:01:29\", \"LOGIN\", \"::1\"], [\"2026-02-10 14:23:48\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"bbf5c07c65b7a2d2db29fce52cad246f60a99fb27b879250a4c04042d1936e5e\", \"12a1526cb857a45a3efff258541c8a2c2993b6f1447994210765dd8ca49515a8\"]', 1, 2),
(27, '2026-02-11 08:52:44', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-11 08:52:44\", \"LOGIN\", \"::1\"], [\"2026-02-11 15:34:57\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"1fe106a687975f600ce7d015f233880927d4a9e093c71ef59ab1d26c645c2b6e\", \"75254da16ef652bafbf8b23bea5930d2da63a455ee28219356cb08c9040bd961\"]', 1, 2),
(28, '2026-02-12 08:53:18', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-12 08:53:18\", \"LOGIN\", \"::1\"], [\"2026-02-12 11:14:12\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"e69e7cc19bb9c8a098e20fc71707c2498fa2290156c200c6a975a7b3e8435065\", \"44258f68cac6d8b5f1b9cf0b1349247d751d4237c696041a44f4f76c84182575\"]', 1, 2),
(29, '2026-02-13 09:10:34', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-13 09:10:34\", \"LOGIN\", \"::1\"], [\"2026-02-13 14:25:36\", \"LOGIN\", \"::1\"], [\"2026-02-13 15:52:20\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"2e95f4e647b9fa15a13ab53d45f0962cfdc78686f57cfd7d029043ed4ad56513\", \"f42a05a02486164f6c2c0136136c315080885fed4ab9fa2fb4a2b536532c9259\", \"7b158935e84ef118f8f33604a5d6026f41b821ec3f9d3f790ec5e489c5a376bf\"]', 1, 2),
(30, '2026-02-16 11:39:10', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-16 11:39:10\", \"LOGIN\", \"::1\"], [\"2026-02-16 13:25:22\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"a68e452c65f5aec6b1eb655a96c927d174cdef571dfe1222118cd6fccfaa05d6\", \"75c50bd480ff48c76119b2feced504de24ee0f107135bf11ab0097acc36ec0af\"]', 1, 2),
(31, '2026-02-17 08:59:40', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-17 08:59:40\", \"LOGIN\", \"::1\"], [\"2026-02-17 10:28:18\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"1ccccba5bc2112ee2edbb79cc56615d2e2d7a8e3d551aa1c135a026809a5a92c\", \"51106de262ae941b8c6a336c072b64b5b16d5681ebc8e91c29075a883ad531fa\"]', 1, 2),
(32, '2026-02-18 09:54:59', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-18 09:54:59\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"52d6fd22ea1b52243a874016467e60aba91a66a095b474e48765212345c0b096\"]', 1, 2),
(33, '2026-02-19 08:26:12', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-19 08:26:12\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"144.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 144.0.0.0 on Windows 10 64-bit\"}', 0, '[\"0cebeb86fe5098c35304d7a2f7768d2f29af2eeaf5133adb879a24bea9c3385b\"]', 1, 2),
(34, '2026-02-20 08:58:15', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-20 08:58:15\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b5b3c7764055db9c207607b09b487b328d96ad33f4c5c4b328e5144c6b4de22b\"]', 1, 2),
(35, '2026-02-20 15:36:37', '0000-00-00 00:00:00', 'LOGIN', '10', '[[\"2026-02-20 15:36:37\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"82cb8bd0b864c25a3315eb6f4abe3fdc968ebdb75ceca3a30fbf99154ec86dfa\"]', 1, 2),
(36, '2026-02-23 11:56:50', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-23 11:56:50\", \"LOGIN\", \"::1\"], [\"2026-02-23 15:25:44\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"483b1cd626e9a2f062707e3e462fd41fa356de715e97bf9bd141865727d7fd46\", \"6adc8cbaaeccf3a86702aabc804ff6162266caf2331265cfefe3d4313d9275a6\"]', 1, 2),
(37, '2026-02-24 10:15:31', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-24 10:15:31\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"9f2cbe604ff292d6d19535519a2655ac1507d3e48aba789019859655461e0bb7\"]', 1, 2),
(38, '2026-02-26 11:07:42', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-26 11:07:42\", \"LOGIN\", \"::1\"], [\"2026-02-26 18:22:44\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"64ee2fa7cccce904d600ad474fed432de1e5c6f45cd07e6138c0009f7bf40c5e\", \"59290d8014189c4b93aed1c2c4327f831f15bb35f96dea2f1dd1ca765e04c5d5\"]', 1, 2),
(39, '2026-02-26 18:23:26', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-26 18:23:26\", \"LOGIN\", \"::1\"], [\"2026-02-26 18:47:51\", \"LOGIN\", \"::1\"], [\"2026-02-26 18:52:07\", \"LOGIN\", \"::1\"], [\"2026-02-26 18:52:34\", \"LOGIN\", \"::1\"], [\"2026-02-26 21:56:59\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:14:46\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:19:41\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:20:19\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:20:49\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:21:51\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:33:51\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:34:17\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:41:49\", \"LOGIN\", \"::1\"], [\"2026-02-26 22:44:58\", \"LOGIN\", \"::1\"], [\"2026-02-26 23:10:23\", \"LOGIN\", \"::1\"], [\"2026-02-26 23:11:24\", \"LOGIN\", \"::1\"], [\"2026-02-26 23:16:01\", \"LOGIN\", \"::1\"], [\"2026-02-26 23:16:26\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"00f0a3f32acb59511896151ba00604c9e654b3253e856ab830445390df852e49\", \"5131e23a854b74c9a2bccf23c6ebc7b283cc982d30b44e2a83c7161d18d36570\", \"3dc9d7ee400bcb7e0a0ea31fc7a8f75cfa9c38987f601175e3fb2243e62cd6d1\", \"42a314731c63c4e3fbb774298e5b6cefb1569fd67d7aa4428305d9abbdeb709c\", \"67b99079b07130eb5ccd06efbcf27dfbe8e69a10abc12c80d3698282258bf045\", \"19bdb5d49b9bae97d3f88a673ce9d3a553f1f7d9b840bc486c478e4487cbad91\", \"2bab338f2fe5ea2f4618ca8dd9213d4246cd3c518b20fe514e253d9b4498ba79\", \"499783f9580b0fa2e617a047ba43e6517f5286acf9cf8fd533403a70bb164d3b\", \"8702837d93f3fa061988aeebd51dcb08e261f48eb85a2f6fefed7eb0b9a125cd\", \"2bf98538630236c213e3efd8da252e2aa8f1d7345363705522f3f0ad9b8ebfdd\", \"1c134a1785222b7ce50b749c09e1f6beb81bc22cc0fb7c0501809a8591b3d24f\", \"151f7eb22719a742f3cc4ef282dcd43ba7b66869c88f2fabc33440c31f8e54ad\", \"e3498b1e71717af2f028514e523ae0b290f6479302b8f84d1e707c3accdda969\", \"c18a12e2ed028966395338a823cda759a861a60ae9a4a239b2da075eb1ae1013\", \"a0f5407379d6568ef5c4a6e5d65bf0fd718bb69cae6944e5c2546105b27b788c\", \"57dbad779d5c3ff137ecdf74bc183f86e4e7e91ad86430f7d701f11e849df917\", \"23578942a6925c6925b0620ddb5d7e633db01f562b9466eefaca5adacc7d1f58\", \"7368bea7ad4b9ef10dd46d51168bd66ec7ff6e4987648d13401fd1d3f8e56d16\"]', 1, 1),
(40, '2026-02-27 01:02:34', '0000-00-00 00:00:00', 'LOGIN', '1', '[[\"2026-02-27 01:02:34\", \"LOGIN\", \"::1\"], [\"2026-02-27 01:05:36\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:48:58\", \"LOGIN\", \"::1\"], [\"2026-02-27 05:44:09\", \"LOGIN\", \"::1\"], [\"2026-02-27 13:08:14\", \"LOGIN\", \"::1\"], [\"2026-02-27 13:58:01\", \"LOGIN\", \"::1\"], [\"2026-02-27 16:20:42\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f4fd074d0ce1b48f904b6eb3505f52d97f7cff73cbb30ad3fe1bfa59a1a477b0\", \"ad01f84831b5abe28997d6ea38918b8b1d2c8cd488c9e188f94161e03f6f0a10\", \"a172d081a006f49c19721af8a85895540f85ad72f4d283b76f908a3a4b9eb468\", \"2f1a9e72e7e5ae6d56e6205e8c7a1f3b8b9d1f573e62d67930d294d2cfc2cb18\", \"5cf3b4c336c4267185400b9b5d9dfe5d7fe03028dffb639d32fdb9745b099463\", \"40aff4105e6e2a58c801c03090443ec6d482bbbc70d0f2193c53408cb55fdd58\", \"01a94aca8c3b63dc7791872360911c47bde1dae513c7c4e4e24b0fb5b8c3c61b\"]', 1, 1),
(41, '2026-02-27 02:03:03', '0000-00-00 00:00:00', 'LOGIN', '3', '[[\"2026-02-27 02:03:03\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:03:21\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:03:26\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:04:32\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"288d3550ef09c88b5892905faecd0abedb123c06340e7ab93df627fb4c551bd3\", \"3741a261e73a7e386f5e9d6d990da3f6c0b46562f3b8510ef08b773ef1dd25ac\", \"d1d4d766109a78f56493ffa7e167b0922c13e8f89cca45f001018a6d8ec1431f\", \"255311386ab63608f837edbe7a83163c826a1fc1e34a732a38bd7b04a0c16b5f\"]', 1, 3),
(42, '2026-02-27 02:06:49', '0000-00-00 00:00:00', 'LOGIN', '11', '[[\"2026-02-27 02:06:49\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:10:56\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:12:26\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:13:50\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:20:51\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:29:40\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:37:42\", \"LOGIN\", \"::1\"], [\"2026-02-27 02:40:10\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:58:34\", \"LOGIN\", \"::1\"], [\"2026-02-27 05:35:59\", \"LOGIN\", \"::1\"], [\"2026-02-27 14:03:58\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"cca5971bf27f9d78be1468d6dad69849b0398d62aea03b997b5b0c8e5e52ca3d\", \"fd88b09964cb0c3f70229cb67691aec147252e12314eb9b698707624227dd4ec\", \"9a7204c6a350a539f3464d487e2e38a56068109db169c5b1810ed6aebb915296\", \"cc5962f5c3c32a49ad0ef9b3d2410391876a711d7cdd77199f681e8daf33804b\", \"708623b764713dc02f5cda17827c75d1bac2f72cb84862fac22e733a91d9684e\", \"d8d3f64d813a1a8e068a1210f9cb19d565ee3b6998dc0a4969e2a0e330a9adf6\", \"999de82c3ed11c7b74b5b00be6bf1030aa1bbed03e25a137bc2cc687d493f7f4\", \"6673fc3cd3bea31fcf4244ec9d1f178e42c25ac65c68534d02c1a571ec4272fb\", \"366290f3d30760a4bd95ca5922e201fc487a27a8e5bce1b7531989e58639c363\", \"431cdd34a301f36e9580aa2a90632aa71d551d87d4342183c0d6b932a0d3f8ab\", \"80ca7bc6bcfc6389e79ec9261431dc26a2edfe603427c04a6549c78360fb1ef3\"]', 1, 3),
(43, '2026-02-27 02:51:18', '0000-00-00 00:00:00', 'LOGIN', '2', '[[\"2026-02-27 02:51:18\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:36:46\", \"LOGIN\", \"::1\"], [\"2026-02-27 14:21:15\", \"LOGIN\", \"::1\"], [\"2026-02-27 16:21:06\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"b619fafcb7bd56563011a8b1924615b92aa7a2ca8dfd56df75a2de8cafb7e1ab\", \"667a4a4380d3f130ebc68967fe17e2f08618437eac7d3795133efb289be2c798\", \"1e5d38c7b38ae76d617b994ea68b563ecde56703908fa97fc435f056ea5f5328\", \"7cb1bf397b054a5a6c6d79d8deb68a7879c017014ec2a094ed519a3892ee6060\"]', 1, 2),
(44, '2026-02-27 03:25:43', '0000-00-00 00:00:00', 'LOGIN', '12', '[[\"2026-02-27 03:25:43\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:26:38\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:30:25\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:30:51\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:31:12\", \"LOGIN\", \"::1\"], [\"2026-02-27 03:32:46\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:38:55\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:39:07\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:39:48\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:40:48\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:42:42\", \"LOGIN\", \"::1\"], [\"2026-02-27 04:42:49\", \"LOGIN\", \"::1\"], [\"2026-02-27 05:36:49\", \"LOGIN\", \"::1\"], [\"2026-02-27 11:34:26\", \"LOGIN\", \"::1\"], [\"2026-02-27 11:42:25\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"5db1442ef40f8bf8ff2bafe3be51f131f825ce14388fc9c5ee4896f655852464\", \"79de30c8ee7ebe24b14a65f6e35e91a6b54d6d2ea61fd263f990d52d61e1ce69\", \"bc94126218a3923108cf8df46ddf960f9f6bbb2e761214bcb39c14ba457de6f0\", \"d99c7ce79c1c5c80c92c59092b6d1f6375b56da9b42fd3f43627042349a9c61d\", \"4f9ff41cf5dc915a5bb231db2ca9461a1d1ae5972701a78b6b5034956d768a68\", \"8f8dd0d8c453e9a1d9639b818dc56146c4b5e3a739a50e997fb3342c1074f790\", \"8c34857bc25c14f6a2feb47f6dcbc351d19441dc0a8b47ab46a4653468667981\", \"1197ebdd333cdb5dbc19f972150f0d1b4282bf5e30990bd7f4a85263887d3618\", \"589eb3e29e3a6632326929a86fbe15876cd0ab233499f246e011d1c6d552c86a\", \"e1331383946c6a60e3ae934aa871711444f462a3bdd7642bf23bcdd0d46e0e7d\", \"1484799dbe2bab16cf22c170a092c039d151e11a2666f1c5a3cc617952d31a0a\", \"39e4e198f5c10d33dd69ed49351d379f819c073d3b6bb7fd72677747880260c0\", \"c4006bf2e395d347427aacd0b1d03852ebd2ffa497c3032b5d235fcc4ee586d0\", \"9c80c7b69d0837000a31278a6de5762df0161171056707e9c85bdf2c8f8ff82e\", \"a8f1c177227a65aa8af6e272ebb1b03353c7e05a7b4418af957909dacdbf7a88\"]', 1, 5),
(45, '2026-02-27 12:22:31', '0000-00-00 00:00:00', 'LOGIN', '13', '[[\"2026-02-27 12:22:31\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"5e3589a45bf3f346f6ec0464fa6a4085a68e414467896d010aa0a401e7b0b6fe\"]', 1, 5),
(46, '2026-02-27 13:10:20', '0000-00-00 00:00:00', 'LOGIN', '41', '[[\"2026-02-27 13:10:20\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"f5699378da48d3d118288aea2adec595a5386ed5133779d06f8d9bac11644157\"]', 1, 5),
(47, '2026-03-01 16:08:46', '2026-03-01 16:16:28', 'LOGIN', '1', '[[\"2026-03-01 16:08:46\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:16:28\", \"LOGOUT\", \"::1\"], [\"2026-03-01 16:17:01\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:23:05\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:28:48\", \"LOGIN\", \"::1\"], [\"2026-03-01 16:28:57\", \"LOGIN\", \"::1\"], [\"2026-03-01 21:00:47\", \"LOGIN\", \"::1\"]]', '::1', '{\"device\":\"Chrome\",\"version\":\"145.0.0.0\",\"layout\":\"Blink\",\"os\":{\"architecture\":64,\"family\":\"Windows\",\"version\":\"10\"},\"description\":\"Chrome 145.0.0.0 on Windows 10 64-bit\"}', 0, '[\"4aea341fe3eac4a3fb92bdb32af42be7eef3da240839af97390ed3afe35cd1a3\", \"99cb98885849e3465f86d948df86b83daf9102898ff84c9f02d6829c350a64da\", \"e27ebfd994c5c5ba95b42a36f05465cc5e2c4613485b155cab37b5837f17b45c\", \"682ab8cebd0a874820e7e56cbd9928c1cfffaa39f16545d146c7f2e258c84a85\", \"c47cb49093f77e54eafc7105bfec1db0c8535fedf4b17911391f041b7c7e4ddb\", \"ba123e7c9ec6ddc2c3f242c232414fb219b79681f63cc9a50a99fee1c31915c2\"]', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `user_logs`
--

CREATE TABLE `user_logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `login_time` datetime NOT NULL DEFAULT current_timestamp(),
  `logout_time` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `user_logs`
--

INSERT INTO `user_logs` (`log_id`, `user_id`, `login_time`, `logout_time`, `ip_address`) VALUES
(1, 1, '2026-02-26 18:23:34', NULL, '::1');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`activity_log_id`);

--
-- Indexes for table `class_section`
--
ALTER TABLE `class_section`
  ADD PRIMARY KEY (`class_id`);

--
-- Indexes for table `curriculum`
--
ALTER TABLE `curriculum`
  ADD PRIMARY KEY (`cur_subj_id`),
  ADD UNIQUE KEY `subject_title` (`subject_title`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `program_id` (`program_id`),
  ADD KEY `school_year_id` (`school_year_id`),
  ADD KEY `curriculum_id` (`curriculum_id`) USING BTREE;

--
-- Indexes for table `curriculum_master`
--
ALTER TABLE `curriculum_master`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD UNIQUE KEY `curriculum_code` (`curriculum_code`,`program_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`department_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `instructor`
--
ALTER TABLE `instructor`
  ADD PRIMARY KEY (`instructor_id`);

--
-- Indexes for table `programs`
--
ALTER TABLE `programs`
  ADD PRIMARY KEY (`program_id`);

--
-- Indexes for table `program_cluster`
--
ALTER TABLE `program_cluster`
  ADD PRIMARY KEY (`cluster_id`);

--
-- Indexes for table `prospectus`
--
ALTER TABLE `prospectus`
  ADD PRIMARY KEY (`prospectus_id`);

--
-- Indexes for table `school_year`
--
ALTER TABLE `school_year`
  ADD PRIMARY KEY (`school_year_id`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `school_year` (`school_year_id`,`module`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD UNIQUE KEY `student_id` (`student_id`) USING BTREE;

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_id`),
  ADD KEY `status` (`status`) USING BTREE;

--
-- Indexes for table `teacher_class`
--
ALTER TABLE `teacher_class`
  ADD PRIMARY KEY (`teacher_class_id`);

--
-- Indexes for table `teacher_class_student`
--
ALTER TABLE `teacher_class_student`
  ADD PRIMARY KEY (`teacher_class_student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `user_log`
--
ALTER TABLE `user_log`
  ADD PRIMARY KEY (`user_log_id`);

--
-- Indexes for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `activity_log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `class_section`
--
ALTER TABLE `class_section`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=264;

--
-- AUTO_INCREMENT for table `curriculum`
--
ALTER TABLE `curriculum`
  MODIFY `cur_subj_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10926;

--
-- AUTO_INCREMENT for table `curriculum_master`
--
ALTER TABLE `curriculum_master`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `instructor`
--
ALTER TABLE `instructor`
  MODIFY `instructor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `programs`
--
ALTER TABLE `programs`
  MODIFY `program_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `program_cluster`
--
ALTER TABLE `program_cluster`
  MODIFY `cluster_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `prospectus`
--
ALTER TABLE `prospectus`
  MODIFY `prospectus_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `school_year`
--
ALTER TABLE `school_year`
  MODIFY `school_year_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1943;

--
-- AUTO_INCREMENT for table `subject`
--
ALTER TABLE `subject`
  MODIFY `subject_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=348;

--
-- AUTO_INCREMENT for table `teacher_class`
--
ALTER TABLE `teacher_class`
  MODIFY `teacher_class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT for table `teacher_class_student`
--
ALTER TABLE `teacher_class_student`
  MODIFY `teacher_class_student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `user_log`
--
ALTER TABLE `user_log`
  MODIFY `user_log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user_logs`
--
ALTER TABLE `user_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `user_logs`
--
ALTER TABLE `user_logs`
  ADD CONSTRAINT `user_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
