<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

function json_fail($payload) {
    echo json_encode($payload);
    exit();
}

function dataEmptyCheck($val) {
    return ($val === null || $val === '');
}

function split_prereq_codes($text) {
    $text = trim((string)$text);
    if ($text === '') return [];
    $parts = preg_split('/\s*,\s*|\s*;\s*|\s*\/\s*/', $text);
    return array_values(array_filter(array_map('trim', $parts)));
}

function prereq_satisfied($curriculumRow, $passedCodes) {
    $preReqCodes = split_prereq_codes($curriculumRow['pre_req'] ?? '');
    foreach ($preReqCodes as $code) {
        if (!isset($passedCodes[$code])) {
            return false;
        }
    }
    return true;
}

function time_to_minutes($timeText) {
    $parts = explode(':', trim((string)$timeText));
    if (count($parts) < 2) return null;
    return (int)$parts[0] * 60 + (int)$parts[1];
}

function parse_schedule_entries($scheduleValue) {
    if (!$scheduleValue) return [];

    $parsed = $scheduleValue;
    if (is_string($parsed)) {
        $decoded = json_decode($parsed, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $parsed = $decoded;
        } else {
            $parsed = [$parsed];
        }
    }

    if (!is_array($parsed)) {
        return [];
    }

    $entries = [];
    foreach ($parsed as $item) {
        $text = trim((string)$item);
        if ($text === '') continue;

        $parts = array_map('trim', explode('::', $text));
        if (count($parts) < 2) continue;

        $day = strtoupper($parts[0]);
        $timeRange = $parts[1];
        $timeParts = array_map('trim', explode('-', $timeRange));
        if (count($timeParts) !== 2) continue;

        $start = time_to_minutes($timeParts[0]);
        $end = time_to_minutes($timeParts[1]);

        if ($start === null || $end === null) continue;

        $entries[] = [
            'day' => $day,
            'start' => $start,
            'end' => $end,
        ];
    }

    return $entries;
}

function schedules_overlap($scheduleA, $scheduleB) {
    foreach ($scheduleA as $a) {
        foreach ($scheduleB as $b) {
            if (($a['day'] ?? '') !== ($b['day'] ?? '')) {
                continue;
            }

            if (max($a['start'], $b['start']) < min($a['end'], $b['end'])) {
                return true;
            }
        }
    }

    return false;
}

try {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== "POST" || !isset($_POST['submitBackSubjectEnrollment']) || $_POST['submitBackSubjectEnrollment'] !== "createIrregularEnrollment") {
        json_fail([
            'code' => 400,
            'msg_status' => false,
            'msg_response' => 'Unsupported request.',
            'msg_span' => '_system',
        ]);
    }

    $student_id_no = isset($_POST['idNumber']) ? trim((string)$_POST['idNumber']) : '';
    $school_year_id = isset($_POST['school_year_id']) ? intVal(trim((string)$_POST['school_year_id'])) : 0;
    $curriculum_id = isset($_POST['curriculum_id']) ? intVal(trim((string)$_POST['curriculum_id'])) : 0;
    $program_id = isset($_POST['program_id']) ? intVal(trim((string)$_POST['program_id'])) : 0;
    $selected_class_id = isset($_POST['selected_class_id']) ? intVal(trim((string)$_POST['selected_class_id'])) : 0;
    $sem = isset($_POST['semester']) ? strtoupper(trim((string)$_POST['semester'])) : '';
    $enrollData = isset($_POST['backSubjectCourses']) ? json_decode(trim((string)$_POST['backSubjectCourses'])) : '';
    $fixedEnrollData = isset($_POST['fixedCourses']) ? json_decode(trim((string)$_POST['fixedCourses'])) : [];

    $output = [
        'code' => 0,
        'msg_status' => false,
        'msg_response' => 'Request error, please try again.',
        'msg_span' => '_system',
    ];

    if ($g_user_role !== "STUDENT") {
        $output['code'] = 401;
        $output['msg_response'] = "Unauthorized request.";
        json_fail($output);
    }

    if (dataEmptyCheck($student_id_no) || empty($school_year_id) || empty($curriculum_id) || empty($program_id) || empty($selected_class_id) || dataEmptyCheck($sem) || empty($enrollData)) {
        $output['code'] = 501;
        $output['msg_response'] = "Please select a valid offered course and section.";
        json_fail($output);
    }

    if (!is_array($enrollData) || count($enrollData) === 0) {
        $output['code'] = 502;
        $output['msg_response'] = "No offered courses found in request payload.";
        json_fail($output);
    }

    if (!is_array($fixedEnrollData)) {
        $fixedEnrollData = [];
    }

    $student = [];
    $sql_student = "
        SELECT student_id_no, class_id, year_level, curriculum_id, program_id
        FROM student
        WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_student)) {
        if ($data = call_mysql_fetch_array($query)) {
            $student = $data;
        }
    }

    if (empty($student)) {
        $output['code'] = 503;
        $output['msg_response'] = "Student record not found.";
        json_fail($output);
    }

    $stored_year_level = intVal($student['year_level'] ?? 0);
    $student_curriculum_id = intVal($student['curriculum_id'] ?? 0);
    $student_program_id = intVal($student['program_id'] ?? 0);

    if ($student_curriculum_id !== $curriculum_id || $student_program_id !== $program_id) {
        $output['code'] = 504;
        $output['msg_response'] = "Student enrollment context no longer matches the active record.";
        json_fail($output);
    }

    $offeredSubjectIds = [];
    $requestedTeacherClassIds = [];
    $requestedRows = [];
    $seenOfferedSubjectIds = [];
    foreach ($enrollData as $course) {
        $offered_subject_id = isset($course->offered_subject_id) ? intVal($course->offered_subject_id) : 0;
        $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
        if ($offered_subject_id <= 0 || $teacher_class_id <= 0) {
            $output['code'] = 505;
            $output['msg_response'] = "Invalid offered course data found in request payload.";
            json_fail($output);
        }

        $request_key = $offered_subject_id . '|' . $teacher_class_id;
        if (isset($seenOfferedSubjectIds[$request_key])) {
            $output['code'] = 506;
            $output['msg_response'] = "Duplicate offered course found in request payload.";
            json_fail($output);
        }

        $seenOfferedSubjectIds[$request_key] = true;
        $offeredSubjectIds[] = $offered_subject_id;
        $requestedTeacherClassIds[] = $teacher_class_id;
        $requestedRows[] = [
            'offered_subject_id' => $offered_subject_id,
            'teacher_class_id' => $teacher_class_id,
        ];
    }

    $offeredSubjectIds = array_values(array_unique(array_map('intval', $offeredSubjectIds)));
    $requestedTeacherClassIds = array_values(array_unique(array_map('intval', $requestedTeacherClassIds)));

    $fixedRows = [];
    $fixedTeacherClassIds = [];
    $seenFixedTeacherClassIds = [];
    foreach ($fixedEnrollData as $course) {
        $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
        if ($teacher_class_id <= 0) {
            $output['code'] = 506;
            $output['msg_response'] = "Invalid fixed subject data found in request payload.";
            json_fail($output);
        }

        if (isset($seenFixedTeacherClassIds[$teacher_class_id])) {
            $output['code'] = 506;
            $output['msg_response'] = "Duplicate fixed subject found in request payload.";
            json_fail($output);
        }

        $seenFixedTeacherClassIds[$teacher_class_id] = true;
        $fixedTeacherClassIds[] = $teacher_class_id;
        $fixedRows[] = [
            'teacher_class_id' => $teacher_class_id,
        ];
    }

    $semester_order = ['1st Semester', '2nd Semester'];
    $curriculumRows = [];
    $curriculumByYearSem = [];
    $curriculumCourseMap = [];
    $maxCurriculumYearLevel = 0;

    $sql_curriculum = "
        SELECT subject_code, subject_title, unit, pre_req, year_level, semester
        FROM curriculum
        WHERE curriculum_id = '" . escape($db_connect, $student_curriculum_id) . "'
        ORDER BY year_level ASC, semester ASC, subject_code ASC
    ";

    if ($query = call_mysql_query($sql_curriculum)) {
        while ($row = call_mysql_fetch_array($query)) {
            $curriculumRows[] = $row;

            $year_level = intVal($row['year_level'] ?? 0);
            $subject_code = trim((string)($row['subject_code'] ?? ''));
            $row_semester = trim((string)($row['semester'] ?? ''));
            $unit = intVal($row['unit'] ?? 0);

            if ($year_level <= 0 || $row_semester === '' || $subject_code === '') {
                continue;
            }

            if (!isset($curriculumByYearSem[$year_level])) {
                $curriculumByYearSem[$year_level] = [];
            }

            if (!isset($curriculumByYearSem[$year_level][$row_semester])) {
                $curriculumByYearSem[$year_level][$row_semester] = [
                    'required_units' => 0,
                    'earned_units' => 0,
                    'courses' => [],
                ];
            }

            $curriculumByYearSem[$year_level][$row_semester]['required_units'] += $unit;
            $curriculumByYearSem[$year_level][$row_semester]['courses'][$subject_code] = [
                'unit' => $unit,
                'row' => $row,
            ];

            $curriculumCourseMap[$subject_code] = [
                'year_level' => $year_level,
                'semester' => $row_semester,
                'unit' => $unit,
                'row' => $row,
            ];

            if ($year_level > $maxCurriculumYearLevel) {
                $maxCurriculumYearLevel = $year_level;
            }
        }
    }

    $passedCodes = [];
    $sql_passed = "
        SELECT subject_code, remarks
        FROM final_grade
        WHERE student_id_text = '" . escape($db_connect, $student_id_no) . "'
    ";

    if ($query = call_mysql_query($sql_passed)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim((string)($row['subject_code'] ?? ''));
            $remarks = strtoupper(trim((string)($row['remarks'] ?? '')));

            if ($code === '' || !isset($curriculumCourseMap[$code])) {
                continue;
            }

            if ($remarks === 'PASSED') {
                $courseMeta = $curriculumCourseMap[$code];
                $courseYear = intVal($courseMeta['year_level'] ?? 0);
                $courseSemester = trim((string)($courseMeta['semester'] ?? ''));
                $courseUnit = intVal($courseMeta['unit'] ?? 0);
                $passedCodes[$code] = true;
                $curriculumByYearSem[$courseYear][$courseSemester]['earned_units'] += $courseUnit;
            }
        }
    }

    $curriculumProgressionOrder = [];
    for ($level = 1; $level <= $maxCurriculumYearLevel; $level++) {
        foreach ($semester_order as $semester_name) {
            if (empty($curriculumByYearSem[$level][$semester_name])) {
                continue;
            }

            $bucket = $curriculumByYearSem[$level][$semester_name];
            $curriculumProgressionOrder[] = [
                'year_level' => $level,
                'semester' => $semester_name,
                'required_units' => intVal($bucket['required_units']),
                'earned_units' => intVal($bucket['earned_units']),
            ];
        }
    }

    $progressed_year_level = $stored_year_level;
    $progressed_semester = $sem;
    foreach ($curriculumProgressionOrder as $bucket) {
        if (intVal($bucket['earned_units']) < intVal($bucket['required_units'])) {
            $progressed_year_level = intVal($bucket['year_level']);
            $progressed_semester = $bucket['semester'];
            break;
        }
    }

    $planning_year_level = $progressed_year_level;
    $planning_semester = $progressed_semester;
    $incoming_year_level = $stored_year_level > 0 ? $stored_year_level : $planning_year_level;
    $incoming_semester = trim((string)$sem);

    if (strcasecmp($incoming_semester, '1st Semester') === 0 && $maxCurriculumYearLevel > 0) {
        $incoming_year_level = min($incoming_year_level + 1, $maxCurriculumYearLevel);
    } elseif ($maxCurriculumYearLevel > 0) {
        $incoming_year_level = min(max($incoming_year_level, 1), $maxCurriculumYearLevel);
    }

    $missing_required_subjects = [];
    for ($level = 1; $level <= $incoming_year_level; $level++) {
        if (empty($curriculumByYearSem[$level])) {
            continue;
        }

        foreach ($semester_order as $semester_name) {
            $semester_bucket = $curriculumByYearSem[$level][$semester_name] ?? null;
            if (empty($semester_bucket)) {
                continue;
            }

            $is_target_or_future =
                ($level > $incoming_year_level) ||
                ($level === $incoming_year_level && strcasecmp($semester_name, $incoming_semester) === 0) ||
                ($level === $incoming_year_level && strcasecmp($incoming_semester, '1st Semester') === 0 && strcasecmp($semester_name, '2nd Semester') === 0);

            if ($is_target_or_future) {
                continue;
            }

            foreach (($semester_bucket['courses'] ?? []) as $subject_code => $courseData) {
                if (!isset($passedCodes[$subject_code])) {
                    $missing_required_subjects[$subject_code] = $courseData['row'];
                }
            }
        }
    }

    if (empty($missing_required_subjects)) {
        $output['code'] = 507;
        $output['msg_response'] = "This irregular enrollment path is only available to irregular students.";
        json_fail($output);
    }

    $eligibleCandidatesByCode = [];
    $incoming_required_units = 0;
    foreach (($curriculumByYearSem[$incoming_year_level] ?? []) as $bucketSemester => $bucket) {
        if (strcasecmp($bucketSemester, $sem) === 0) {
            $incoming_required_units = intVal($bucket['required_units'] ?? 0);
            break;
        }
    }
    foreach ($curriculumRows as $row) {
        $code = trim((string)($row['subject_code'] ?? ''));
        $rowYear = intVal($row['year_level'] ?? 0);
        $rowSemester = trim((string)($row['semester'] ?? ''));
        $unit = intVal($row['unit'] ?? 0);

        if ($code === '' || $rowYear <= 0 || $unit <= 0) {
            continue;
        }

        if (isset($passedCodes[$code])) {
            continue;
        }

        if (strcasecmp($rowSemester, $sem) !== 0) {
            continue;
        }

        if ($rowYear <= $incoming_year_level) {
            continue;
        }

        if (!prereq_satisfied($row, $passedCodes)) {
            continue;
        }

        if (!isset($eligibleCandidatesByCode[$code])) {
            $eligibleCandidatesByCode[$code] = [];
        }
        $eligibleCandidatesByCode[$code][] = $row;
    }

    $offeredSubjectIdsSql = implode(',', array_map('intval', $offeredSubjectIds));
    $offeringMap = [];
    $sql_offerings = "
        SELECT
            bse.offered_subject_id,
            bse.school_year_id,
            bse.sem,
            bse.program_id,
            bse.year_level,
            bse.class_id,
            bse.class_name,
            bse.teacher_class_id,
            bse.subject_id,
            bse.course_code,
            bse.course_title,
            bse.schedule,
            bse.section_limit,
            bse.course_limit,
            bse.status
        FROM backsubject_enroll bse
        WHERE bse.offered_subject_id IN ($offeredSubjectIdsSql)
          AND bse.school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(bse.sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND UPPER(bse.status) = 'ACTIVE'
    ";

    if ($query = call_mysql_query($sql_offerings)) {
        while ($data = call_mysql_fetch_array($query)) {
            $offeringMap[intVal($data['offered_subject_id'] ?? 0)] = $data;
        }
    }

    $allTeacherClassIds = array_values(array_unique(array_merge($requestedTeacherClassIds, $fixedTeacherClassIds)));
    $teacherClassIdsSql = implode(',', array_map('intval', $allTeacherClassIds));
    $teacherClassMap = [];
    $sql_teacher_classes = "
        SELECT
            tc.teacher_class_id,
            tc.class_id,
            tc.subject_id,
            tc.schedule,
            tc.sem,
            tc.schoolyear_id,
            tc.program_id,
            tc.year_level,
            tc.unit,
            tc.section_limit,
            cs.class_name,
            cs.sec_limit,
            s.subject_code,
            s.subject_title,
            s.limit AS course_limit
        FROM teacher_class tc
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        WHERE tc.teacher_class_id IN ($teacherClassIdsSql)
          AND tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND tc.status = 0
          AND cs.status = 0
          AND tc.class_id > 0
          AND tc.subject_id > 0
          AND TRIM(tc.schedule) <> ''
          AND tc.schedule <> '[]'
    ";

    if ($query = call_mysql_query($sql_teacher_classes)) {
        while ($data = call_mysql_fetch_array($query)) {
            $teacherClassMap[intVal($data['teacher_class_id'] ?? 0)] = $data;
        }
    }

    foreach ($offeredSubjectIds as $offered_subject_id) {
        if (empty($offeringMap[$offered_subject_id])) {
            $output['code'] = 508;
            $output['msg_response'] = "One or more offered courses are no longer available.";
            json_fail($output);
        }

        $offering = $offeringMap[$offered_subject_id];
        $course_code = trim((string)($offering['course_code'] ?? ''));
        $curriculumRow = !empty($eligibleCandidatesByCode[$course_code]) ? $eligibleCandidatesByCode[$course_code][0] : null;

        if ($curriculumRow === null) {
            $output['code'] = 509;
            $output['msg_response'] = "One or more selected offered courses are not applicable to your curriculum.";
            json_fail($output);
        }
    }

    foreach ($requestedRows as $requestRow) {
        $offered_subject_id = intVal($requestRow['offered_subject_id'] ?? 0);
        $teacher_class_id = intVal($requestRow['teacher_class_id'] ?? 0);
        $offering = $offeringMap[$offered_subject_id] ?? null;
        $teacherClass = $teacherClassMap[$teacher_class_id] ?? null;

        if (empty($offering) || empty($teacherClass)) {
            $output['code'] = 509;
            $output['msg_response'] = "One or more selected schedules are no longer available.";
            json_fail($output);
        }

        if (strcasecmp(trim((string)($offering['course_code'] ?? '')), trim((string)($teacherClass['subject_code'] ?? ''))) !== 0) {
            $output['code'] = 509;
            $output['msg_response'] = "One or more selected schedules do not match the dean-offered course list.";
            json_fail($output);
        }
    }

    $fixedCandidateCodes = [];
    foreach ($curriculumRows as $row) {
        $code = trim((string)($row['subject_code'] ?? ''));
        $rowSemester = trim((string)($row['semester'] ?? ''));
        $rowYear = intVal($row['year_level'] ?? 0);

        if ($code === '' || $rowYear <= 0 || isset($passedCodes[$code])) {
            continue;
        }

        if (strcasecmp($rowSemester, $sem) !== 0) {
            continue;
        }

        if ($rowYear !== $incoming_year_level) {
            continue;
        }

        if (prereq_satisfied($row, $passedCodes)) {
            $fixedCandidateCodes[$code] = $row;
        }
    }

    foreach ($fixedRows as $fixedRow) {
        $teacher_class_id = intVal($fixedRow['teacher_class_id'] ?? 0);
        $teacherClass = $teacherClassMap[$teacher_class_id] ?? null;

        if (empty($teacherClass)) {
            $output['code'] = 509;
            $output['msg_response'] = "One or more fixed subjects are no longer available.";
            json_fail($output);
        }

        $course_code = trim((string)($teacherClass['subject_code'] ?? ''));
        $class_id = intVal($teacherClass['class_id'] ?? 0);
        $teacher_program_id = intVal($teacherClass['program_id'] ?? 0);

        if ($course_code === '' || !isset($fixedCandidateCodes[$course_code]) || $class_id !== $selected_class_id || $teacher_program_id !== $student_program_id) {
            $output['code'] = 509;
            $output['msg_response'] = "One or more fixed subjects do not match your selected section and curriculum.";
            json_fail($output);
        }
    }

    $selectedFixedScheduleEntries = [];
    $fixed_units_for_selected_section = 0;
    $sql_selected_section_fixed = "
        SELECT tc.subject_id, tc.schedule, s.subject_code, tc.unit
        FROM teacher_class tc
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
          AND tc.class_id = '" . escape($db_connect, $selected_class_id) . "'
          AND tc.program_id = '" . escape($db_connect, $student_program_id) . "'
          AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND tc.status = 0
          AND TRIM(tc.schedule) <> ''
          AND tc.schedule <> '[]'
    ";

    if ($query = call_mysql_query($sql_selected_section_fixed)) {
        while ($row = call_mysql_fetch_array($query)) {
            $subjectCode = trim((string)($row['subject_code'] ?? ''));
            if ($subjectCode === '' || !isset($fixedCandidateCodes[$subjectCode])) {
                continue;
            }

            $selectedFixedScheduleEntries = array_merge(
                $selectedFixedScheduleEntries,
                parse_schedule_entries($row['schedule'] ?? '')
            );
            $fixed_units_for_selected_section += intVal($row['unit'] ?? ($fixedCandidateCodes[$subjectCode]['unit'] ?? 0));
        }
    }

    $missing_units = max(0, $incoming_required_units - $fixed_units_for_selected_section);
    $selected_offered_units = 0;
    $available_offered_units = 0;
    $teacherClassIds = [];
    $selectedCourseCodes = [];
    $existingScheduleEntries = [];
    $newScheduleEntries = [];
    $sectionEnrolledMap = [];
    $courseEnrolledMap = [];

    $capacitySql = "
        SELECT class_id, subject_id, COUNT(DISTINCT student_id_no) AS course_enrolled
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND status = 'Enrolled'
        GROUP BY class_id, subject_id
    ";

    if ($query = call_mysql_query($capacitySql)) {
        while ($data = call_mysql_fetch_array($query)) {
            $class_id = intVal($data['class_id'] ?? 0);
            $subject_id = intVal($data['subject_id'] ?? 0);

            if (!isset($courseEnrolledMap[$class_id])) {
                $courseEnrolledMap[$class_id] = [];
            }

            $courseEnrolledMap[$class_id][$subject_id] = intVal($data['course_enrolled'] ?? 0);
        }
    }

    $sectionCountSql = "
        SELECT class_id, COUNT(DISTINCT student_id_no) AS section_enrolled
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND status = 'Enrolled'
        GROUP BY class_id
    ";

    if ($query = call_mysql_query($sectionCountSql)) {
        while ($data = call_mysql_fetch_array($query)) {
            $sectionEnrolledMap[intVal($data['class_id'] ?? 0)] = intVal($data['section_enrolled'] ?? 0);
        }
    }

    foreach ($requestedRows as $requestRow) {
        $offered_subject_id = intVal($requestRow['offered_subject_id'] ?? 0);
        $teacher_class_id = intVal($requestRow['teacher_class_id'] ?? 0);
        $offering = $offeringMap[$offered_subject_id];
        $teacherClass = $teacherClassMap[$teacher_class_id];
        $class_id = intVal($teacherClass['class_id'] ?? 0);
        $subject_id = intVal($teacherClass['subject_id'] ?? 0);
        $course_limit = intVal($teacherClass['course_limit'] ?? 0);
        $section_limit = intVal($teacherClass['section_limit'] ?? 0) > 0 ? intVal($teacherClass['section_limit'] ?? 0) : intVal($teacherClass['sec_limit'] ?? 0);
        $scheduleEntries = parse_schedule_entries($teacherClass['schedule'] ?? '');
        $course_code = trim((string)($teacherClass['subject_code'] ?? ''));
        $curriculumRow = !empty($eligibleCandidatesByCode[$course_code]) ? $eligibleCandidatesByCode[$course_code][0] : null;

        if ($teacher_class_id <= 0 || $class_id <= 0 || $subject_id <= 0 || $curriculumRow === null || empty($scheduleEntries)) {
            $output['code'] = 510;
            $output['msg_response'] = "One or more offered courses have incomplete schedule data.";
            json_fail($output);
        }

        $teacherClassIds[] = $teacher_class_id;
        if (isset($selectedCourseCodes[$course_code])) {
            $output['code'] = 510;
            $output['msg_response'] = "Please select only one section for each offered course.";
            json_fail($output);
        }
        $selectedCourseCodes[$course_code] = true;
        $selected_offered_units += intVal($curriculumRow['unit'] ?? 0);

        if ($section_limit > 0 && intVal($sectionEnrolledMap[$class_id] ?? 0) >= $section_limit) {
            $output['code'] = 511;
            $output['msg_response'] = "One or more offered courses already reached the section limit.";
            json_fail($output);
        }

        if ($course_limit > 0 && intVal($courseEnrolledMap[$class_id][$subject_id] ?? 0) >= $course_limit) {
            $output['code'] = 512;
            $output['msg_response'] = "One or more offered courses already reached the course limit.";
            json_fail($output);
        }

        if (schedules_overlap($selectedFixedScheduleEntries, $scheduleEntries)) {
            $output['code'] = 513;
            $output['msg_response'] = "One or more offered courses conflict with the fixed subjects in your selected section.";
            json_fail($output);
        }

        if (schedules_overlap($newScheduleEntries, $scheduleEntries)) {
            $output['code'] = 514;
            $output['msg_response'] = "Schedule conflict found within the selected offered courses.";
            json_fail($output);
        }

        $newScheduleEntries = array_merge($newScheduleEntries, $scheduleEntries);
    }

    $allEligibleOfferCodes = [];
    $sql_all_possible_offers = "
        SELECT DISTINCT bse.course_code
        FROM backsubject_enroll bse
        INNER JOIN teacher_class tc ON tc.schoolyear_id = bse.school_year_id
        INNER JOIN subject s ON s.subject_id = tc.subject_id AND s.subject_code = bse.course_code
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        WHERE bse.school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(bse.sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND UPPER(bse.status) = 'ACTIVE'
          AND tc.status = 0
          AND cs.status = 0
          AND TRIM(tc.schedule) <> ''
          AND tc.schedule <> '[]'
    ";

    if ($query = call_mysql_query($sql_all_possible_offers)) {
        while ($data = call_mysql_fetch_array($query)) {
            $course_code = trim((string)($data['course_code'] ?? ''));
            if ($course_code !== '' && !empty($eligibleCandidatesByCode[$course_code])) {
                $allEligibleOfferCodes[$course_code] = true;
            }
        }
    }

    foreach ($allEligibleOfferCodes as $course_code => $flag) {
        $curriculumRow = !empty($eligibleCandidatesByCode[$course_code]) ? $eligibleCandidatesByCode[$course_code][0] : null;
        if ($curriculumRow !== null) {
            $available_offered_units += intVal($curriculumRow['unit'] ?? 0);
        }
    }

    if ($missing_units <= 0) {
        $output['code'] = 515;
        $output['msg_response'] = "Your selected fixed subjects already satisfy the required units for this term.";
        json_fail($output);
    }

    $underload_allowed = ($available_offered_units < $missing_units);
    $units_valid = ($selected_offered_units === $missing_units) || ($underload_allowed && $selected_offered_units === $available_offered_units);

    if (!$units_valid) {
        $output['code'] = 516;
        $output['msg_response'] = $underload_allowed
            ? "All available offered-course units must be selected when underloading is unavoidable."
            : "Selected offered-course units must exactly match your missing units for this term.";
        json_fail($output);
    }

    foreach (array_values(array_unique(array_merge($teacherClassIds, $fixedTeacherClassIds))) as $teacher_class_id) {
        $duplicate_sql = "
            SELECT enrollment_id
            FROM enrollments
            WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
              AND teacher_class_id = '" . escape($db_connect, $teacher_class_id) . "'
              AND school_year_id = '" . escape($db_connect, $school_year_id) . "'
              AND UPPER(sem) = UPPER('" . escape($db_connect, $sem) . "')
            LIMIT 1
        ";

        if ($query = call_mysql_query($duplicate_sql)) {
            if ($data = call_mysql_fetch_array($query)) {
                $output['code'] = 517;
                $output['msg_response'] = "Some courses are already enrolled for this term.";
                json_fail($output);
            }
        }
    }

    $sql_existing_schedule = "
        SELECT schedule
        FROM enrollments
        WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
          AND school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, $sem) . "')
          AND status = 'Enrolled'
    ";

    if ($query = call_mysql_query($sql_existing_schedule)) {
        while ($data = call_mysql_fetch_array($query)) {
            $existingScheduleEntries = array_merge($existingScheduleEntries, parse_schedule_entries($data['schedule'] ?? ''));
        }
    }

    foreach ($requestedRows as $requestRow) {
        $teacher_class_id = intVal($requestRow['teacher_class_id'] ?? 0);
        $scheduleEntries = parse_schedule_entries($teacherClassMap[$teacher_class_id]['schedule'] ?? '');
        if (schedules_overlap($existingScheduleEntries, $scheduleEntries)) {
            $output['code'] = 518;
            $output['msg_response'] = "Schedule conflict found with your currently enrolled subjects.";
            json_fail($output);
        }
    }

    $db_connect->begin_transaction();

    foreach ($fixedRows as $fixedRow) {
        $teacher_class_id = intVal($fixedRow['teacher_class_id'] ?? 0);
        $teacherClass = $teacherClassMap[$teacher_class_id] ?? [];
        $subject_id = intVal($teacherClass['subject_id'] ?? 0);
        $class_id = intVal($teacherClass['class_id'] ?? 0);
        $class_name = trim((string)($teacherClass['class_name'] ?? ''));
        $schedule = trim((string)($teacherClass['schedule'] ?? ''));

        if ($teacher_class_id <= 0 || $subject_id <= 0 || $class_id <= 0 || $class_name === '' || $schedule === '') {
            throw new Exception("Invalid fixed subject data found during enrollment.");
        }

        $insert_sql = "
            INSERT INTO enrollments (
                student_id_no,
                teacher_class_id,
                subject_id,
                class_id,
                program_id,
                curriculum_id,
                section_name,
                schedule,
                school_year_id,
                sem,
                status
            ) VALUES (
                '" . escape($db_connect, $student_id_no) . "',
                '" . escape($db_connect, $teacher_class_id) . "',
                '" . escape($db_connect, $subject_id) . "',
                '" . escape($db_connect, $class_id) . "',
                '" . escape($db_connect, $student_program_id) . "',
                '" . escape($db_connect, $student_curriculum_id) . "',
                '" . escape($db_connect, $class_name) . "',
                '" . escape($db_connect, $schedule) . "',
                '" . escape($db_connect, $school_year_id) . "',
                '" . escape($db_connect, $sem) . "',
                'Enrolled'
            )
        ";

        call_mysql_query($insert_sql);
    }

    foreach ($requestedRows as $requestRow) {
        $teacher_class_id = intVal($requestRow['teacher_class_id'] ?? 0);
        $teacherClass = $teacherClassMap[$teacher_class_id] ?? [];
        $subject_id = intVal($teacherClass['subject_id'] ?? 0);
        $class_id = intVal($teacherClass['class_id'] ?? 0);
        $class_name = trim((string)($teacherClass['class_name'] ?? ''));
        $schedule = trim((string)($teacherClass['schedule'] ?? ''));

        if ($teacher_class_id <= 0 || $subject_id <= 0 || $class_id <= 0 || $class_name === '' || $schedule === '') {
            throw new Exception("Invalid offered course data found during enrollment.");
        }

        $insert_sql = "
            INSERT INTO enrollments (
                student_id_no,
                teacher_class_id,
                subject_id,
                class_id,
                program_id,
                curriculum_id,
                section_name,
                schedule,
                school_year_id,
                sem,
                status
            ) VALUES (
                '" . escape($db_connect, $student_id_no) . "',
                '" . escape($db_connect, $teacher_class_id) . "',
                '" . escape($db_connect, $subject_id) . "',
                '" . escape($db_connect, $class_id) . "',
                '" . escape($db_connect, $student_program_id) . "',
                '" . escape($db_connect, $student_curriculum_id) . "',
                '" . escape($db_connect, $class_name) . "',
                '" . escape($db_connect, $schedule) . "',
                '" . escape($db_connect, $school_year_id) . "',
                '" . escape($db_connect, $sem) . "',
                'Enrolled'
            )
        ";

        call_mysql_query($insert_sql);
    }

    $update_student_section_sql = "
        UPDATE student SET
        class_id = '" . escape($db_connect, $selected_class_id) . "'
        WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
    ";
    call_mysql_query($update_student_section_sql);

    $db_connect->commit();

    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_response'] = "Offered courses enrolled successfully.";
    $output['msg_span'] = '';
    echo json_encode($output);
    exit();
} catch (Throwable $th) {
    $db_connect->rollback();
    json_fail([
        'code' => 500,
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
        'msg_span' => '_system',
    ]);
}
?>
