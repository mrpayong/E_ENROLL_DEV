
START TRANSACTION;

-- final grade
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

ALTER TABLE `final_grade`
  ADD PRIMARY KEY (`final_id`);


ALTER TABLE `final_grade`
  MODIFY `final_id` int(11) NOT NULL AUTO_INCREMENT;



-- setting
CREATE TABLE `settings` (
  `setting_id` int(11) NOT NULL,
  `module` varchar(255) NOT NULL DEFAULT '',
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`settings`)),
  `school_year_id` int(11) NOT NULL DEFAULT 0,
  `date_added` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;  

INSERT INTO `settings` (`setting_id`, `module`, `settings`, `school_year_id`, `date_added`) VALUES
(1, 'subject_exceptions', '[]', 0, '2026-03-17 00:00:00'),
(2, 'grade_system', '[{\"rating_id\":1,\"range_from\":55,\"range_to\":59,\"grade\":4,\"text\":\"FAILED\"},{\"rating_id\":2,\"range_from\":60,\"range_to\":64,\"grade\":3,\"text\":\"PASSED\"},{\"rating_id\":3,\"range_from\":65,\"range_to\":69,\"grade\":2.75,\"text\":\"PASSED\"},{\"rating_id\":4,\"range_from\":70,\"range_to\":74,\"grade\":2.5,\"text\":\"PASSED\"},{\"rating_id\":5,\"range_from\":75,\"range_to\":79,\"grade\":2.25,\"text\":\"PASSED\"},{\"rating_id\":6,\"range_from\":80,\"range_to\":83,\"grade\":2,\"text\":\"PASSED\"},{\"rating_id\":7,\"range_from\":84,\"range_to\":87,\"grade\":1.75,\"text\":\"PASSED\"},{\"rating_id\":8,\"range_from\":88,\"range_to\":91,\"grade\":1.5,\"text\":\"PASSED\"},{\"rating_id\":9,\"range_from\":92,\"range_to\":95,\"grade\":1.25,\"text\":\"PASSED\"},{\"rating_id\":10,\"range_from\":96,\"range_to\":100,\"grade\":1,\"text\":\"PASSED\"},{\"rating_id\":11,\"range_from\":0,\"range_to\":54,\"grade\":5,\"text\":\"FAILED\"}]', 0, '2026-03-17 00:00:00'),
(3, 'additional', '[{\"field\":\"FAILED_UNIT\",\"value\":6,\"remarks\":\"(Greater than) - Number of unit to be consider as Failed \\n(Equal)- Number of unit to be probationary\"}]', 0, '2026-03-17 00:00:00');

ALTER TABLE `settings`
  ADD PRIMARY KEY (`setting_id`),
  ADD UNIQUE KEY `school_year` (`school_year_id`,`module`);

ALTER TABLE `settings`
  MODIFY `setting_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1944;


--  activity logs
ALTER TABLE `activity_log` CHANGE `session_id` `session_id` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '';

CREATE TABLE `activity_log_student` (
  `activity_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp(),
  `action` longtext NOT NULL DEFAULT '',
  `session_id` longtext NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '0',
  `system_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `activity_log_student`
  ADD PRIMARY KEY (`activity_log_id`);

ALTER TABLE `activity_log_student`
  MODIFY `activity_log_id` int(11) NOT NULL AUTO_INCREMENT;


CREATE TABLE `activity_log_dean` (
  `activity_log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL DEFAULT 0,
  `date_log` datetime NOT NULL DEFAULT current_timestamp(),
  `action` longtext NOT NULL DEFAULT '',
  `session_id` longtext NOT NULL DEFAULT '',
  `user_level` varchar(100) NOT NULL DEFAULT '0',
  `system_id` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `activity_log_dean`
  ADD PRIMARY KEY (`activity_log_id`);

ALTER TABLE `activity_log_dean`
  MODIFY `activity_log_id` int(11) NOT NULL AUTO_INCREMENT;


-- grade tracker
CREATE TABLE `grade_tracker` (
  `tracker_id` int(11) NOT NULL,
  `data_changed` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`data_changed`)),
  `upload_file` text NOT NULL DEFAULT '',
  `reason` text NOT NULL DEFAULT '',
  `date_added` datetime NOT NULL DEFAULT current_timestamp(),
  `added_by` text NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


ALTER TABLE `grade_tracker`
  ADD PRIMARY KEY (`tracker_id`);

ALTER TABLE `grade_tracker`
  MODIFY `tracker_id` int(11) NOT NULL AUTO_INCREMENT;



-- student info
CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `ref_user_id` int(11) NOT NULL DEFAULT 0,
  `student_id_no` varchar(255) NOT NULL DEFAULT '',
  `firstname` varchar(255) NOT NULL DEFAULT '',
  `middle_name` varchar(255) NOT NULL DEFAULT '',
  `lastname` varchar(255) NOT NULL DEFAULT '',
  `suffix_name` varchar(100) NOT NULL DEFAULT '',
  `gender` varchar(10) NOT NULL DEFAULT '',
  `dob` date DEFAULT NULL,
  `ccc_email` varchar(100) NOT NULL DEFAULT '',
  `contact` varchar(11) NOT NULL DEFAULT '',
  `barangay` varchar(100) NOT NULL DEFAULT '',
  `address` longtext NOT NULL DEFAULT '',
  `birth_place` varchar(255) NOT NULL DEFAULT '',
  `curriculum_id` int(11) NOT NULL DEFAULT 0,
  `program_id` int(11) NOT NULL DEFAULT 0,
  `major` varchar(100) NOT NULL DEFAULT '',
  `year_level` int(11) NOT NULL DEFAULT 0,
  `flag_update` int(11) NOT NULL DEFAULT 0,
  `date_update` datetime NOT NULL DEFAULT current_timestamp(),
  `status` int(11) NOT NULL DEFAULT 0 COMMENT '0 - active\r\n1 - not active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`);

ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT;


COMMIT;