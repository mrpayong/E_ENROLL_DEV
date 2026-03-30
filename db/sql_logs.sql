-- jan 15 2026
ALTER TABLE `notification` ADD `created_At` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) AFTER `content`;
ALTER TABLE `notification` CHANGE `receipient_id` `recipient_id` INT(11) NOT NULL;
ALTER TABLE `notification` ADD `unread` TINYINT(5) NOT NULL DEFAULT '0' AFTER `created_At`;

ALTER TABLE `school_year` ADD `createdAt` TIMESTAMP(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) AFTER `flag_used`;
ALTER TABLE `school_year` ADD `updatedAt` DATETIME(6) NULL AFTER `createdAt`;
ALTER TABLE `school_year` ADD `isDefault` TINYINT(2) NOT NULL DEFAULT '0' AFTER `updatedAt`;

ALTER TABLE `departments` ADD `user_id` INT(50) NOT NULL AFTER `status`;
ALTER TABLE `departments` ADD FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `departments` ADD UNIQUE(`user_id`);

ALTER TABLE `curriculum` ADD FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`) ON DELETE NO ACTION ON UPDATE NO ACTION; 
ALTER TABLE `curriculum` ADD FOREIGN KEY (`program_id`) REFERENCES `programs`(`program_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `curriculum` ADD `status` TINYINT(1) NOT NULL DEFAULT '0' AFTER `updatedAt`;
ALTER TABLE `curriculum` ADD `school_year_id` INT(11) NOT NULL AFTER `status`;
ALTER TABLE `curriculum` ADD FOREIGN KEY (`school_year_id`) REFERENCES `school_year`(`school_year_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `class_section`
    ADD `updated_At` DATETIME NULL AFTER `class_name`,
    ADD `student_limit` INT(11) NULL AFTER `updated_At`,
    ADD `school_year_id` VARCHAR(255) NULL AFTER `student_limit`;
ALTER TABLE `class_section` CHANGE `school_year_id` `school_year_id` INT(50) NOT NULL;
ALTER TABLE `class_section` CHANGE `student_limit` `student_limit` INT(50) NOT NULL DEFAULT '30';
ALTER TABLE `class_section` CHANGE `school_year_id` `school_year_id` LONGTEXT NOT NULL DEFAULT '[]';

ALTER TABLE subject
    ADD `program_id` INT(11) NULL AFTER `subject_title`,
    ADD `school_year_id` INT(11) NULL AFTER `program_id`,
    ADD `createAt` DATETIME NULL AFTER `excepted`,
    ADD `updatedAt` DATETIME NULL AFTER `createAt`;
ALTER TABLE `subject` CHANGE `createAt` `createAt` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP;


ALTER TABLE `student` CHANGE `middle_name` `middle_name` VARCHAR(50) NULL;
ALTER TABLE `student` CHANGE `lastname` `lastname` VARCHAR(50) NULL;
ALTER TABLE `student` CHANGE `suffix_name` `suffix_name` VARCHAR(10) NULL;
ALTER TABLE `student` CHANGE `gender` `gender` VARCHAR(10) NULL;
ALTER TABLE `student` CHANGE `username` `username` VARCHAR(50) NULL;
ALTER TABLE `student` ADD `program_id` INT(11) NULL AFTER `flag_update`;
ALTER TABLE `student` CHANGE `student_id` `student_id` INT(11) NOT NULL; -- off Auto increment
ALTER TABLE `student` CHANGE `student_id` `student_id` VARCHAR(255) NOT NULL;
ALTER TABLE `student` CHANGE `email_address` `email_address` VARCHAR(50) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `student` CHANGE `ccc_email` `ccc_email` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL;
ALTER TABLE `student` CHANGE `emergency_data` `emergency_data` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL; -- removed default data
ALTER TABLE `student` CHANGE `flag_update` `flag_update` TIMESTAMP(6) NOT NULL;
ALTER TABLE `student` CHANGE `flag_update` `flag_update` DATETIME(6) NOT NULL; -- to datetime

ALTER TABLE `subject` ADD UNIQUE(`subject_code`);
ALTER TABLE `subject` ADD UNIQUE(`subject_title`);

ALTER TABLE `programs` ADD `duration` INT(50) NOT NULL AFTER `status`;

-- jan 16, 2026
ALTER TABLE `departments` ADD `updatedAt` DATETIME(6) NULL AFTER `user_id`;

ALTER TABLE `prospectus` CHANGE `student_id` `student_id` VARCHAR(255) NOT NULL;

ALTER TABLE `prospectus` ADD FOREIGN KEY (`subject_id`) REFERENCES `subject`(`subject_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `prospectus` ADD FOREIGN KEY (`program_id`) REFERENCES `programs`(`program_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `prospectus` ADD FOREIGN KEY (`school_year_id`) REFERENCES `school_year`(`school_year_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
ALTER TABLE `prospectus` ADD FOREIGN KEY (`student_id`) REFERENCES `student`(`student_id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

ALTER TABLE `curriculum` ADD `curriculum_code` VARCHAR(255) NOT NULL AFTER `school_year_id`;

ALTER TABLE prospectus DROP FOREIGN KEY prospectus_ibfk_5;
ALTER TABLE prospectus DROP FOREIGN KEY prospectus_ibfk_4;
ALTER TABLE prospectus DROP FOREIGN KEY prospectus_ibfk_3;
ALTER TABLE prospectus DROP FOREIGN KEY prospectus_ibfk_2;

ALTER TABLE `prospectus` DROP INDEX `student_id`;
ALTER TABLE `prospectus` DROP INDEX `program_id`;
ALTER TABLE `prospectus` DROP INDEX `department_id`;
ALTER TABLE `prospectus` DROP INDEX `school_year_id`;
ALTER TABLE `prospectus` DROP INDEX `subject_id`;

ALTER TABLE `class_section` CHANGE `student_limit` `sem_limit` VARCHAR(50) NOT NULL DEFAULT '[{key:\"0\", value:\"30\"}]';
ALTER TABLE `class_section` CHANGE `sem_limit` `sem_limit` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '[{key:\"0\", value:\"30\"}]';
ALTER TABLE `class_section` DROP `program_name`;
ALTER TABLE `class_section` CHANGE `sem_limit` `sem_limit` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '[{key:\"0\", value:\"30\"}]' COMMENT 'actual representation: [{school_year_id:\"0\", default value:\"30\"}]';
ALTER TABLE `class_section` CHANGE `updated_At` `date_modified` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `class_section` DROP `school_year_id`;
ALTER TABLE `class_section` CHANGE `date_modified` `date_modified` DATETIME(6) NOT NULL AFTER `status`;

-- jan 17, 2026
ALTER TABLE `class_section` CHANGE `sem_limit` `sem_limit` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '[{\"key\":\"0\", \"value\":\"30\"}]' COMMENT 'actual representation: [{school_year_id:\"0\", default value:\"30\"}]';

-- Jan 19, 2026
ALTER TABLE `class_section` CHANGE `sem_limit` `sem_limit` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '[{\"0\":30}]' COMMENT 'actual representation: [{school_year_id:\"0\", default value:\"30\"}]';
ALTER TABLE `class_section` CHANGE `sem_limit` `sem_limit` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '{\"0\":30}' COMMENT 'actual representation: [{school_year_id:\"0\", default value:\"30\"}]';

-- jan 20, 2026
ALTER TABLE `curriculum` 
ADD `cur_subj_id` INT(11) NOT NULL AFTER `curriculum_code`;
ADD `cur_subj_id` int(11) NOT NULL,
ADD `subject_id` int(11) DEFAULT NULL,
ADD `subject_code` varchar(100) NOT NULL DEFAULT '',
ADD `subject_title` varchar(100) NOT NULL DEFAULT '',
ADD `category` varchar(100) NOT NULL DEFAULT '',
ADD `description` longtext NOT NULL DEFAULT '',
ADD `unit` int(11) NOT NULL DEFAULT 0,
ADD `pre_req` varchar(255) NOT NULL,
ADD `semester` varchar(100) NOT NULL DEFAULT '',
ADD `lec_lab` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[0,0]',
-- CHECK (json_valid(`lec_lab`)), remove
ADD `year_level` int(11) DEFAULT NULL,
ADD `date_created` datetime NOT NULL DEFAULT current_timestamp()

ALTER TABLE `curriculum`
  MODIFY `cur_subj_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10914;

ALTER TABLE `subject` 
    ADD `lec_lab` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT '[0,0]' CHECK (json_valid(`lec_lab`)),
    ADD `year_level` int(11) DEFAULT NULL,
    ADD `fee` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '[]' CHECK (json_valid(`fee`)),
    ADD `flag_manual_enroll` int(11) NOT NULL DEFAULT 0 COMMENT '0 - false\r\n1 - true',
    ADD `date_created` datetime NOT NULL DEFAULT current_timestamp()

ALTER TABLE `subject` CHANGE `status` `status` INT(11) NOT NULL; -- removed default value
ALTER TABLE `subject` ADD UNIQUE(`status`);

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
-- Indexes for table `curriculum_master`
--
ALTER TABLE `curriculum_master`
  ADD PRIMARY KEY (`curriculum_id`),
  ADD UNIQUE KEY `curriculum_code` (`curriculum_code`,`program_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `curriculum_master`
--
ALTER TABLE `curriculum_master`
  MODIFY `curriculum_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;


-- JAN20,2026
ALTER TABLE `users` CHANGE `user_role` `user_role` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL DEFAULT '\'[\"\"]\'' COMMENT '\'1\' => \'ADMIN\', \'2\' => \'REGISTRAR\', \'3\' => \'VPAA\', \'4\' => \'INSTRUCTOR\', \'5\' => \'FACULTY\', \'6\' => \'STUDENT\'';

ALTER TABLE `teacher_class_student` 
    ADD time_from TIME NOT NULL,
    ADD time_to TIME NOT NULL

ALTER TABLE `teacher_class` ADD `program_id` INT(11) NOT NULL AFTER `date_added`;
ALTER TABLE `teacher_class` ADD `room` VARCHAR(255) NOT NULL AFTER `program_id`;
ALTER TABLE `teacher_class` ADD `year_level` INT(50) NOT NULL AFTER `room`;

-- jan 22, 2026
ALTER TABLE `subject`
  DROP `program_id`,
  DROP `school_year_id`,
  DROP `category`,
  DROP `Pre_req`,
  DROP `semester`,
  DROP `excepted`,
  DROP `updatedAt`,
  DROP `year_level`,
  DROP `fee`;

ALTER TABLE `subject` CHANGE `lec_lab` `lec_lab` INT(11) NOT NULL DEFAULT '0' AFTER `subject_title`;
ALTER TABLE `subject` CHANGE `unit` `unit` INT(11) NOT NULL DEFAULT '0' AFTER `lec_lab`;
ALTER TABLE `subject` CHANGE `flag_manual_enroll` `flag_manual_enroll` INT(11) NOT NULL DEFAULT '0' COMMENT '0 - false\r\n1 - true' AFTER `description`;
ALTER TABLE `subject` CHANGE `date_created` `date_modified` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `subject` DROP INDEX `subject_code`; -- make not unique

-- jan 23, 2026
ALTER TABLE `subject` DROP INDEX `subject_title`;

-- jan28,2026
ALTER TABLE `teacher_class` CHANGE `schedule` `schedule` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;
LTER TABLE `teacher_class` CHANGE `room` `room` LONGTEXT CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '[]';
ALTER TABLE `teacher_class` CHANGE `room` `room` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;

INSERT INTO `teacher_class` (`teacher_class_id`, `teacher_id`, `class_id`, `subject_id`, `subject_text`, `thumbnails`, `schedule`, `sem`, `schoolyear_id`, `date_added`, `program_id`, `room`, `year_level`) VALUES ('1', '5', '3', '320', 'ENLGISH 2', '', '\"Thursday:: 09:00-11:00:: adsa:: lec\"', '1st Semester', '2', '2026-01-28', '12', '[{\"AB-1\":\"7:00-9:00\"},{\"AB-2\":\"14:00-15:00\"}]', '2');
ALTER TABLE `teacher_class` ADD `unit` INT(55) NOT NULL AFTER `year_level`, ADD `lec_lab` INT(55) NOT NULL AFTER `unit`;

-- jan 29, 2026
ALTER TABLE `teacher_class` CHANGE `date_added` `date_added` DATE NULL DEFAULT CURRENT_TIMESTAMP;
ALTER TABLE `teacher_class` ADD `section_limit` INT(50) NOT NULL AFTER `lec_lab`;
ALTER TABLE `teacher_class` CHANGE `lec_lab` `lec_lab` LONGTEXT NOT NULL;

-- jan30, 2026
ALTER TABLE `teacher_class` ADD `statud` TINYINT(11) NOT NULL DEFAULT '0' COMMENT 'not archived = 0, archived = 1' AFTER `section_limit`;
ALTER TABLE `teacher_class` CHANGE `statud` `status` TINYINT(11) NOT NULL DEFAULT '0' COMMENT 'not archived = 0, archived = 1';

-- feb 12, 2026
ALTER TABLE `teacher_class` ADD `total_hours` INT(11) NOT NULL AFTER `status`;

-- feb 13, 2026
ALTER TABLE `student` ADD `department_id` INT(50) NOT NULL AFTER `program_id`;

-- mar 11, 2026
ALTER TABLE `curriculum`
  DROP `school_year_id`,
  DROP `cur_subj_id`,
  DROP `category`,
  DROP `semester`,
  DROP `year_level`,
  DROP `date_created`;

ALTER TABLE `curriculum` ADD `pre_req_id` INT(50) NOT NULL AFTER `lec_lab`;
ALTER TABLE `curriculum` CHANGE `curriculum_code` `curriculum_code` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL AFTER `curriculum_id`;
ALTER TABLE `curriculum` CHANGE `pre_req_id` `pre_req_id` INT(50) NOT NULL AFTER `pre_req`;
ALTER TABLE `curriculum` CHANGE `lec_lab` `lec_lab` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT '\'[0,0]\'' AFTER `unit`;
ALTER TABLE `curriculum` CHANGE `createdAt` `createdAt` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `pre_req_id`;
ALTER TABLE `curriculum` CHANGE `updatedAt` `updatedAt` DATETIME NULL DEFAULT NULL AFTER `createdAt`, CHANGE `status` `status` TINYINT(1) NOT NULL DEFAULT '0' AFTER `pre_req_id`;
ALTER TABLE `curriculum` DROP `department_id`;

ALTER TABLE `curriculum` ADD `school_year_id` INT(50) NOT NULL AFTER `updatedAt`, ADD `year_level` INT(50) NOT NULL AFTER `school_year_id`;
ALTER TABLE `curriculum` CHANGE `school_year_id` `school_year_id` INT(50) NOT NULL AFTER `curriculum_title`, CHANGE `year_level` `year_level` INT(50) NOT NULL AFTER `curriculum_title`;
ALTER TABLE `curriculum` CHANGE `school_year_id` `semester` VARCHAR(50) NOT NULL;
ALTER TABLE `curriculum` ADD `prospectus_id` INT(50) NOT NULL AUTO_INCREMENT AFTER `updatedAt`, ADD PRIMARY KEY (`prospectus_id`);

-- mar 12, 2026
ALTER TABLE `curriculum` CHANGE `curriculum_title` `curriculum_title` VARCHAR(50) NULL DEFAULT NULL;

-- mar 17, 2026
ALTER TABLE `student` CHANGE `student_id` `student_id_no` VARCHAR(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '';

-- mar 19, 2026
ALTER TABLE `enrollments` CHANGE `student_id` `student_id_no` VARCHAR(50) NOT NULL;


-- mar 24, 2026
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

ALTER TABLE `final_grade` CHANGE `remarks` `remarks` VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL COMMENT 'PASSED, FAILED, INC, LOA, DRP';

-- mar 25, 2026
CREATE TABLE `e_enrollment`.`modify_units_students` (
  `mus_key` INT(100) NOT NULL , 
  `student_id_no` VARCHAR(100) NOT NULL , 
  `year_level` INT(100) NOT NULL , 
  `ol_ul_flag` BOOLEAN NOT NULL , 
  `modified_unit` INT(100) NOT NULL , 
  `school_year_id` INT(100) NOT NULL , 
  `semester` VARCHAR(100) NOT NULL , 
  `curriculum_id` INT(100) NOT NULL , 
  `created_At` DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP , 
  `updated_At` DATETIME(6) NULL , 
  PRIMARY KEY (`mus_key`)
  ) 
  ENGINE = InnoDB;

-- mar 26, 2026
ALTER TABLE `modify_units_students` CHANGE `ol_ul_flag` `ol_ul_flag` TINYINT(1) NOT NULL COMMENT '0 = overload 1 = underload';
ALTER TABLE `modify_units_students` CHANGE `mus_key` `mus_key` INT(100) NOT NULL AUTO_INCREMENT;

-- mar 30, 2026
ALTER TABLE `subject` ADD `limit` INT(50) NOT NULL DEFAULT '0' AFTER `description`;
ALTER TABLE `class_section` ADD `school_year_id` INT(50) NOT NULL AFTER `program_id`;