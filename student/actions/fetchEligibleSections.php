<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$session_class->session_close();

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if ($g_user_role !== "STUDENT") {
        include HTTP_401;
        exit();
    }

    function json_exit($payload) {
        echo json_encode($payload);
        exit();
    }

    function split_prereq_codes($text) {
        $text = trim((string)$text);
        if ($text === '') return [];
        $parts = preg_split('/\s*,\s*|\s*;\s*|\s*\/\s*/', $text);
        return array_values(array_filter(array_map('trim', $parts)));
    }

    function prereq_satisfied($curriculumRow, $passedCodes) {
        $reqs = split_prereq_codes($curriculumRow['pre_req'] ?? '');
        foreach ($reqs as $code) {
            if (!isset($passedCodes[$code])) return false;
        }
        return true;
    }

    $selected_class_id = isset($_GET['selected_class_id']) ? intVal($_GET['selected_class_id']) : 0;

    $student = [];
    $sql_student = "
        SELECT student_id_no, year_level, curriculum_id, program_id, class_id
        FROM student
        WHERE student_id_no = '" . escape($db_connect, $g_general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_student)) {
        while ($data = call_mysql_fetch_array($query)) {
            $student = $data;
        }
    }

    if (empty($student)) {
        json_exit([
            'code' => 401,
            'msg_response' => 'Student not found.',
            'msg_status' => false,
        ]);
    }

    $student_id_no = trim($student['student_id_no']);
    $stored_year_level = intVal($student['year_level']);
    $curriculum_id = intVal($student['curriculum_id']);
    $program_id = intVal($student['program_id']);
    $student_class_id = intVal($student['class_id'] ?? 0);

    $schoolYear = null;
    $sql_sy = "
        SELECT school_year_id, school_year, sem
        FROM school_year
        WHERE flag_used != 0
        ORDER BY createdAt DESC
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_sy)) {
        while ($data = call_mysql_fetch_array($query)) {
            $schoolYear = $data;
        }
    }

    if ($schoolYear === null) {
        json_exit([
            'code' => 401,
            'msg_response' => 'No default Fiscal Year set.',
            'msg_status' => false,
        ]);
    }

    $school_year_id = intVal($schoolYear['school_year_id']);
    $active_term_semester = trim($schoolYear['sem']);
    $semester_order = ['1st Semester', '2nd Semester'];

    $curriculumRows = [];
    $curriculumByYearSem = [];
    $curriculumCourseMap = [];
    $maxCurriculumYearLevel = 0;

    $sql_curriculum = "
        SELECT subject_code, subject_title, unit, pre_req, year_level, semester
        FROM curriculum
        WHERE curriculum_id = '" . escape($db_connect, $curriculum_id) . "'
        ORDER BY year_level ASC, semester ASC, subject_code ASC
    ";

    if ($query = call_mysql_query($sql_curriculum)) {
        while ($row = call_mysql_fetch_array($query)) {
            $curriculumRows[] = $row;

            $year_level = intVal($row['year_level'] ?? 0);
            $subject_code = trim($row['subject_code'] ?? '');
            $row_semester = trim($row['semester'] ?? '');
            $unit = intVal($row['unit'] ?? 0);

            if ($year_level === 0 || $row_semester === '' || $subject_code === '') {
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
    $enrolledCodes = [];
    $enrolledClassId = 0;

    $sql_passed = "
        SELECT subject_code, remarks
        FROM final_grade
        WHERE student_id_text = '" . escape($db_connect, $student_id_no) . "'
    ";

    if ($query = call_mysql_query($sql_passed)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim($row['subject_code'] ?? '');
            $remarks = strtoupper(trim($row['remarks'] ?? ''));

            if ($code === '' || !isset($curriculumCourseMap[$code])) {
                continue;
            }

            $courseMeta = $curriculumCourseMap[$code];
            $courseYear = intVal($courseMeta['year_level']);
            $courseSemester = trim($courseMeta['semester']);
            $courseUnit = intVal($courseMeta['unit']);

            if ($remarks === 'PASSED') {
                $passedCodes[$code] = true;
                $curriculumByYearSem[$courseYear][$courseSemester]['earned_units'] += $courseUnit;
            }
        }
    }

    $sql_enrolled_subjects = "
        SELECT e.class_id, e.teacher_class_id, e.subject_id, e.section_name, e.schedule, s.subject_code, s.subject_title
        FROM enrollments e
        INNER JOIN subject s ON s.subject_id = e.subject_id
        WHERE e.student_id_no = '" . escape($db_connect, $student_id_no) . "'
          AND e.school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(e.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
          AND e.status = 'Enrolled'
    ";

    if ($query = call_mysql_query($sql_enrolled_subjects)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim($row['subject_code'] ?? '');
            if ($code === '') {
                continue;
            }

            $enrolledCodes[$code] = $row;
            if ($enrolledClassId <= 0) {
                $enrolledClassId = intVal($row['class_id'] ?? 0);
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
    $progressed_semester = $active_term_semester;
    $first_incomplete_found = false;

    foreach ($curriculumProgressionOrder as $bucket) {
        if (intVal($bucket['earned_units']) < intVal($bucket['required_units'])) {
            $progressed_year_level = intVal($bucket['year_level']);
            $progressed_semester = $bucket['semester'];
            $first_incomplete_found = true;
            break;
        }
    }

    if (!$first_incomplete_found && !empty($curriculumProgressionOrder)) {
        $lastBucket = end($curriculumProgressionOrder);
        $lastYear = intVal($lastBucket['year_level']);
        $lastSem = trim($lastBucket['semester']);

        if ($lastSem === '1st Semester') {
            $progressed_year_level = $lastYear;
            $progressed_semester = '2nd Semester';
        } else {
            $progressed_year_level = min($lastYear + 1, $maxCurriculumYearLevel);
            $progressed_semester = '1st Semester';
        }
    }

    $planning_year_level = $progressed_year_level;
    $planning_semester = $progressed_semester;

    $incoming_year_level = $stored_year_level > 0 ? $stored_year_level : $progressed_year_level;
    if (strcasecmp($active_term_semester, '1st Semester') === 0 && $maxCurriculumYearLevel > 0) {
        $incoming_year_level = min($incoming_year_level + 1, $maxCurriculumYearLevel);
    } elseif ($maxCurriculumYearLevel > 0) {
        $incoming_year_level = min(max($incoming_year_level, 1), $maxCurriculumYearLevel);
    }

    $incoming_semester = $active_term_semester;

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

    $student_academic_status = empty($missing_required_subjects) ? 'Regular' : 'Irregular';

    $fixedSubjects = [];

    foreach ($curriculumRows as $row) {
        $rowSemester = trim($row['semester'] ?? '');
        $rowYear = intVal($row['year_level'] ?? 0);
        $code = trim($row['subject_code'] ?? '');

        if ($code === '' || $rowYear === 0 || $rowSemester === '') {
            continue;
        }

        if (isset($passedCodes[$code])) {
            continue;
        }

        if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
            continue;
        }

        if ($rowYear !== $incoming_year_level) {
            continue;
        }

        if (prereq_satisfied($row, $passedCodes)) {
            $fixedSubjects[$code] = $row;
        }
    }

    $offeringsByClass = [];
    $classMetaById = [];

    $sql_offerings = "
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
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
        AND tc.program_id = '" . escape($db_connect, $program_id) . "'
        AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
        AND tc.status = 0
        AND cs.status = 0
        AND tc.class_id > 0
        AND tc.subject_id > 0
        AND TRIM(tc.schedule) <> ''
        AND tc.schedule <> '[]'
    ";

    if ($query = call_mysql_query($sql_offerings)) {
        while ($row = call_mysql_fetch_array($query)) {
            $classId = intVal($row['class_id'] ?? 0);
            $code = trim($row['subject_code'] ?? '');
            $subjectId = intVal($row['subject_id'] ?? 0);

            if ($classId <= 0 || $subjectId <= 0 || $code === '') {
                continue;
            }

            if (!isset($offeringsByClass[$classId])) {
                $offeringsByClass[$classId] = [];
            }

            $offeringsByClass[$classId][] = $row;

            if (!isset($classMetaById[$classId])) {
                $classMetaById[$classId] = [
                    'class_id' => $classId,
                    'class_name' => trim($row['class_name'] ?? ''),
                    'section_limit' => intVal($row['section_limit'] ?? 0) > 0 ? intVal($row['section_limit'] ?? 0) : intVal($row['sec_limit'] ?? 0),
                    'section_enrolled' => 0,
                    'subjects' => [],
                ];
            }

            $classMetaById[$classId]['subjects'][$code] = [
                'teacher_class_id' => intVal($row['teacher_class_id'] ?? 0),
                'subject_id' => $subjectId,
                'course_limit' => intVal($row['course_limit'] ?? 0),
                'enrolled' => 0,
            ];
        }
    }

    $sectionEnrolledMap = [];
    $sql_section_counts = "
        SELECT class_id, COUNT(DISTINCT student_id_no) AS section_enrolled
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, strtoupper($active_term_semester)) . "')
          AND status = 'Enrolled'
        GROUP BY class_id
    ";

    if ($query = call_mysql_query($sql_section_counts)) {
        while ($row = call_mysql_fetch_array($query)) {
            $classId = intVal($row['class_id'] ?? 0);
            $sectionEnrolledMap[$classId] = intVal($row['section_enrolled'] ?? 0);
            if (isset($classMetaById[$classId])) {
                $classMetaById[$classId]['section_enrolled'] = intVal($row['section_enrolled'] ?? 0);
            }
        }
    }

    $sql_course_counts = "
        SELECT class_id, subject_id, COUNT(DISTINCT student_id_no) AS course_enrolled
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, strtoupper($active_term_semester)) . "')
          AND status = 'Enrolled'
        GROUP BY class_id, subject_id
    ";

    if ($query = call_mysql_query($sql_course_counts)) {
        while ($row = call_mysql_fetch_array($query)) {
            $classId = intVal($row['class_id'] ?? 0);
            $subjectId = intVal($row['subject_id'] ?? 0);
            $courseEnrolled = intVal($row['course_enrolled'] ?? 0);

            if (!isset($classMetaById[$classId]['subjects'])) {
                continue;
            }

            foreach ($classMetaById[$classId]['subjects'] as $subjectCode => $subjectMeta) {
                if (intVal($subjectMeta['subject_id']) === $subjectId) {
                    $classMetaById[$classId]['subjects'][$subjectCode]['enrolled'] = $courseEnrolled;
                    break;
                }
            }
        }
    }

    $sections = [];
    foreach ($classMetaById as $classId => $classMeta) {
        $sectionLimit = intVal($classMeta['section_limit'] ?? 0);
        $sectionEnrolled = intVal($classMeta['section_enrolled'] ?? 0);
        $isClassFull = ($sectionLimit > 0 && $sectionEnrolled >= $sectionLimit);

        if ($isClassFull && intVal($classId) !== intVal($enrolledClassId)) {
            continue;
        }

        $matched_subject_count = 0;

        foreach ($fixedSubjects as $code => $fixedRow) {
            if (empty($classMeta['subjects'][$code])) {
                continue;
            }

            $courseMeta = $classMeta['subjects'][$code];
            $courseLimit = intVal($courseMeta['course_limit'] ?? 0);
            $courseEnrolled = intVal($courseMeta['enrolled'] ?? 0);
            if ($courseLimit > 0 && $courseEnrolled >= $courseLimit && empty($enrolledCodes[$code])) {
                continue;
            }

            $matched_subject_count++;
        }

        if ($student_academic_status === 'Regular' && $matched_subject_count < count($fixedSubjects)) {
            continue;
        }

        if ($student_academic_status === 'Irregular') {
            if ($matched_subject_count <= 0) {
                continue;
            }
        }

        $sections[] = [
            'class_id' => $classId,
            'class_name' => $classMeta['class_name'] ?? '',
            'matched_subject_count' => $matched_subject_count,
            'class_enrolled_count' => $sectionEnrolled,
            'class_section_limit' => $sectionLimit,
            'remaining_slots' => $sectionLimit > 0 ? max(0, $sectionLimit - $sectionEnrolled) : null,
        ];
    }

    if ($selected_class_id <= 0 && $enrolledClassId > 0) {
        $selected_class_id = $enrolledClassId;
    } elseif ($selected_class_id <= 0 && $student_class_id > 0 && !empty($enrolledCodes)) {
        $selected_class_id = $student_class_id;
    }

    if ($student_academic_status === 'Irregular' && $selected_class_id > 0 && !isset($classMetaById[$selected_class_id])) {
        $selected_class_id = 0;
    }

    $fixedResponse = [];
    if ($selected_class_id > 0) {
        foreach (($offeringsByClass[$selected_class_id] ?? []) as $row) {
            $subject_code = trim($row['subject_code'] ?? '');

            if ($subject_code === '' || !isset($fixedSubjects[$subject_code])) {
                continue;
            }

            $courseMeta = $classMetaById[$selected_class_id]['subjects'][$subject_code] ?? null;
            $courseLimit = intVal($courseMeta['course_limit'] ?? 0);
            $courseEnrolled = intVal($courseMeta['enrolled'] ?? 0);

            if ($courseLimit > 0 && $courseEnrolled >= $courseLimit && empty($enrolledCodes[$subject_code])) {
                continue;
            }

            $curriculumRow = $fixedSubjects[$subject_code];
            $fixedResponse[] = [
                'teacher_class_id' => intVal(($enrolledCodes[$subject_code]['teacher_class_id'] ?? 0) ?: ($row['teacher_class_id'] ?? 0)),
                'class_id' => intVal($row['class_id'] ?? 0),
                'class_name' => $row['class_name'] ?? '',
                'subject_code' => $subject_code,
                'subject_title' => $row['subject_title'] ?? ($curriculumRow['subject_title'] ?? ''),
                'unit' => intVal($row['unit'] ?? ($curriculumRow['unit'] ?? 0)),
                'pre_req' => $curriculumRow['pre_req'] ?? '',
                'schedule' => ($enrolledCodes[$subject_code]['schedule'] ?? '') ?: ($row['schedule'] ?? ''),
                'section_text' => !empty($enrolledCodes[$subject_code]) ? 'Enrolled' : ($row['class_name'] ?? ''),
                'subject_id' => intVal(($enrolledCodes[$subject_code]['subject_id'] ?? 0) ?: ($row['subject_id'] ?? 0)),
                'curriculum_year_level' => intVal($curriculumRow['year_level'] ?? 0),
                'curriculum_semester' => $curriculumRow['semester'] ?? '',
            ];
        }
    }

    $base_section_label = '';
    foreach ($sections as $sectionRow) {
        if (intVal($sectionRow['class_id']) === intVal($selected_class_id)) {
            $base_section_label = $sectionRow['class_name'] ?? '';
            break;
        }
    }

    $required_units = intVal($curriculumByYearSem[$incoming_year_level][$incoming_semester]['required_units'] ?? 0);
    $fixed_subject_units = 0;
    foreach ($fixedSubjects as $fixedRow) {
        $fixed_subject_units += intVal($fixedRow['unit'] ?? 0);
    }

    json_exit([
        'last_page' => 1,
        'data' => ($selected_class_id <= 0) ? [] : $fixedResponse,
        'msg_status' => true,
        'mode' => strtolower($student_academic_status),
        'stored_year_level' => $stored_year_level,
        'progressed_year_level' => $progressed_year_level,
        'progressed_semester' => $progressed_semester,
        'planning_year_level' => $planning_year_level,
        'planning_semester' => $planning_semester,
        'incoming_year_level' => $incoming_year_level,
        'incoming_semester' => $incoming_semester,
        'selected_class_id' => $selected_class_id,
        'base_section_label' => $base_section_label,
        'required_units' => $required_units,
        'fixed_subject_units' => $fixed_subject_units,
        'sections' => $sections,
        'fixed_subjects' => $fixedResponse,
        'requires_section_selection' => true,
    ]);
} catch (Throwable $th) {
    json_exit([
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
