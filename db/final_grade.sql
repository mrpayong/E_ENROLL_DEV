-- phpMyAdmin SQL Dump
-- version 5.1.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 17, 2026 at 01:04 AM
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
-- Database: `e_e_enrollment`
--

-- --------------------------------------------------------

--
-- Table structure for table `final_grade`
--

CREATE TABLE `final_grade` (
  `final_id` int(11) NOT NULL,
  `teacher_class_id` int(11) NOT NULL DEFAULT 0 COMMENT 'class schedule',
  `student_id` int(11) NOT NULL DEFAULT 0,
  `student_name` varchar(255) NOT NULL DEFAULT '',
  `student_id_text` varchar(100) NOT NULL DEFAULT '',
  `program_id` int(11) NOT NULL DEFAULT 0,
  `program_code` varchar(100) NOT NULL DEFAULT '',
  `major` varchar(100) NOT NULL DEFAULT '',
  `yr_level` varchar(100) NOT NULL DEFAULT '0',
  `section_name` varchar(100) NOT NULL DEFAULT '',
  `subject_code` varchar(100) NOT NULL DEFAULT '',
  `course_desc` varchar(255) NOT NULL DEFAULT '',
  `units` int(11) NOT NULL DEFAULT 0,
  `prelimterm_grade` double NOT NULL DEFAULT 0,
  `midterm_grade` double NOT NULL DEFAULT 0,
  `finalterm_grade` double NOT NULL DEFAULT 0,
  `final_grade` double NOT NULL DEFAULT 0,
  `final_grade_text` varchar(10) NOT NULL DEFAULT '' COMMENT 'for text grades',
  `converted_grade` varchar(10) NOT NULL DEFAULT '',
  `completion` varchar(10) NOT NULL DEFAULT '',
  `remarks` varchar(255) NOT NULL DEFAULT '',
  `school_year_id` int(11) NOT NULL DEFAULT 0,
  `school_year` varchar(50) NOT NULL,
  `sem` varchar(50) NOT NULL,
  `flag_fixed` int(11) NOT NULL DEFAULT 0 COMMENT '0-computed, 1-set by registrar',
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0-lms, 1-manual',
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime NOT NULL DEFAULT current_timestamp(),
  `school_name` varchar(255) NOT NULL DEFAULT '',
  `credit_code` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `final_grade`
--
ALTER TABLE `final_grade`
  ADD PRIMARY KEY (`final_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `final_grade`
--
ALTER TABLE `final_grade`
  MODIFY `final_id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
