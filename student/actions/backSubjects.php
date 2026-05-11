<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$session_class->session_close();

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
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if ($g_user_role !== "STUDENT") {
        include HTTP_401;
        exit();
    }

    $selected_class_id = isset($_GET['selected_class_id']) ? intVal($_GET['selected_class_id']) : 0;
    $offered_class_id = isset($_GET['offered_class_id']) ? intVal($_GET['offered_class_id']) : 0;

    $student = [];
    $sql_student = "
        SELECT student_id_no, year_level, curriculum_id, program_id
        FROM student
        WHERE student_id_no = '" . escape($db_connect, $g_general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_student)) {
        if ($data = call_mysql_fetch_array($query)) {
            $student = $data;
        }
    }

    if (empty($student)) {
        json_exit([
            'msg_status' => false,
            'msg_response' => 'Student not found.',
        ]);
    }

    $student_id_no = trim((string)($student['student_id_no'] ?? ''));
    $stored_year_level = intVal($student['year_level'] ?? 0);
    $curriculum_id = intVal($student['curriculum_id'] ?? 0);
    $program_id = intVal($student['program_id'] ?? 0);

    $schoolYear = null;
    $sql_sy = "
        SELECT school_year_id, school_year, sem
        FROM school_year
        WHERE flag_used != 0
        ORDER BY createdAt DESC
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_sy)) {
        if ($data = call_mysql_fetch_array($query)) {
            $schoolYear = $data;
        }
    }

    if ($schoolYear === null) {
        json_exit([
            'msg_status' => false,
            'msg_response' => 'No default Fiscal Year set.',
        ]);
    }

    $school_year_id = intVal($schoolYear['school_year_id'] ?? 0);
    $active_term_semester = trim((string)($schoolYear['sem'] ?? ''));
    $active_school_year = trim((string)($schoolYear['school_year'] ?? ''));
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
    $progressed_semester = $active_term_semester;
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

    if ($student_academic_status !== 'Irregular') {
        json_exit([
            'last_page' => 1,
            'data' => [],
            'msg_status' => true,
            'mode' => 'regular',
            'missing_units' => 0,
            'selected_units' => 0,
            'offered_sections' => [],
            'selected_offered_class_id' => 0,
        ]);
    }

    if ($selected_class_id <= 0) {
        json_exit([
            'last_page' => 1,
            'data' => [],
            'msg_status' => true,
            'mode' => 'irregular',
            'missing_units' => 0,
            'selected_units' => 0,
            'offered_sections' => [],
            'selected_offered_class_id' => 0,
            'msg_response' => 'Please select a section first.',
        ]);
    }

    $eligibleCandidatesByCode = [];
    $incoming_required_units = intVal($curriculumByYearSem[$incoming_year_level][$active_term_semester]['required_units'] ?? 0);
    $selected_fixed_schedule_entries = [];
    $fixed_units_for_selected_section = 0;

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

        if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
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

    $eligibleCourseCodesSql = "''";
    if (!empty($eligibleCandidatesByCode)) {
        $eligibleCourseCodesSql = "'" . implode("','", array_map(function($code) use ($db_connect) {
            return escape($db_connect, $code);
        }, array_keys($eligibleCandidatesByCode))) . "'";
    }

    $sql_offered = "
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
            bse.status,
            bse.dean_name,
            p.short_name AS program_short_name
        FROM backsubject_enroll bse
        LEFT JOIN programs p ON bse.program_id = p.program_id
        WHERE bse.school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(bse.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
          AND UPPER(bse.status) = 'ACTIVE'
          AND bse.course_code IN ($eligibleCourseCodesSql)
        ORDER BY bse.course_code ASC, bse.class_name ASC, bse.course_title ASC
    ";

    $offeredRows = [];
    $offeredByCode = [];
    if ($query = call_mysql_query($sql_offered)) {
        while ($row = call_mysql_fetch_array($query)) {
            $offeredRows[] = $row;
            $courseCode = trim((string)($row['course_code'] ?? ''));
            if ($courseCode !== '' && !isset($offeredByCode[$courseCode])) {
                $offeredByCode[$courseCode] = $row;
            }
        }
    }

    $teacherClassRows = [];
    if (!empty($offeredByCode)) {
        $offeredCourseCodesSql = "'" . implode("','", array_map(function($code) use ($db_connect) {
            return escape($db_connect, $code);
        }, array_keys($offeredByCode))) . "'";

        $sql_teacher_class_offers = "
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
                tc.year_level,
                cs.class_name,
                cs.sec_limit,
                s.subject_code,
                s.subject_title,
                s.limit AS course_limit,
                p.short_name AS program_short_name
            FROM teacher_class tc
            INNER JOIN subject s ON s.subject_id = tc.subject_id
            INNER JOIN class_section cs ON cs.class_id = tc.class_id
            LEFT JOIN programs p ON p.program_id = tc.program_id
            WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
              AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
              AND tc.status = 0
              AND cs.status = 0
              AND tc.class_id > 0
              AND tc.subject_id > 0
              AND TRIM(tc.schedule) <> ''
              AND tc.schedule <> '[]'
              AND s.subject_code IN ($offeredCourseCodesSql)
            ORDER BY s.subject_code ASC, p.short_name ASC, cs.class_name ASC
        ";

        if ($query = call_mysql_query($sql_teacher_class_offers)) {
            while ($row = call_mysql_fetch_array($query)) {
                $teacherClassRows[] = $row;
            }
        }
    }

    $courseEnrolledMap = [];
    $sectionEnrolledMap = [];
    $sql_capacity_counts = "
        SELECT class_id, subject_id, COUNT(DISTINCT student_id_no) AS enrolled_count
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, strtoupper($active_term_semester)) . "')
          AND status = 'Enrolled'
        GROUP BY class_id, subject_id
    ";

    if ($query = call_mysql_query($sql_capacity_counts)) {
        while ($row = call_mysql_fetch_array($query)) {
            $classId = intVal($row['class_id'] ?? 0);
            $subjectId = intVal($row['subject_id'] ?? 0);
            $enrolledCount = intVal($row['enrolled_count'] ?? 0);

            if (!isset($courseEnrolledMap[$classId])) {
                $courseEnrolledMap[$classId] = [];
            }
            $courseEnrolledMap[$classId][$subjectId] = $enrolledCount;
        }
    }

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
            $sectionEnrolledMap[intVal($row['class_id'] ?? 0)] = intVal($row['section_enrolled'] ?? 0);
        }
    }

    if ($selected_class_id > 0) {
        $fixedCandidateCodes = [];
        foreach ($curriculumRows as $row) {
            $code = trim((string)($row['subject_code'] ?? ''));
            $rowSemester = trim((string)($row['semester'] ?? ''));
            $rowYear = intVal($row['year_level'] ?? 0);

            if ($code === '' || $rowYear <= 0 || isset($passedCodes[$code])) {
                continue;
            }

            if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
                continue;
            }

            if ($rowYear !== $incoming_year_level) {
                continue;
            }

            if (prereq_satisfied($row, $passedCodes)) {
                $fixedCandidateCodes[$code] = $row;
            }
        }

        $sql_selected_section_fixed = "
            SELECT tc.subject_id, tc.schedule, s.subject_code, tc.unit
            FROM teacher_class tc
            INNER JOIN subject s ON s.subject_id = tc.subject_id
            WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
              AND tc.class_id = '" . escape($db_connect, $selected_class_id) . "'
              AND tc.program_id = '" . escape($db_connect, $program_id) . "'
              AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
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

                $selected_fixed_schedule_entries = array_merge(
                    $selected_fixed_schedule_entries,
                    parse_schedule_entries($row['schedule'] ?? '')
                );
                $fixed_units_for_selected_section += intVal($row['unit'] ?? ($fixedCandidateCodes[$subjectCode]['unit'] ?? 0));
            }
        }
    }

    $offeredCourses = [];
    $offeredSectionsById = [];
    $missing_units = max(0, $incoming_required_units - $fixed_units_for_selected_section);
    $available_offered_units = 0;
    $availableOfferedUnitCodes = [];

    foreach ($teacherClassRows as $teacherClassRow) {
        $course_code = trim((string)($teacherClassRow['subject_code'] ?? ''));
        $offeredRow = $offeredByCode[$course_code] ?? null;
        $course_title = trim((string)($teacherClassRow['subject_title'] ?? ''));
        $curriculumRow = !empty($eligibleCandidatesByCode[$course_code]) ? $eligibleCandidatesByCode[$course_code][0] : null;

        if ($offeredRow === null || $curriculumRow === null) {
            continue;
        }

        $class_id = intVal($teacherClassRow['class_id'] ?? 0);
        $subject_id = intVal($teacherClassRow['subject_id'] ?? 0);
        $course_limit = intVal($teacherClassRow['course_limit'] ?? 0);
        $section_limit = intVal($teacherClassRow['section_limit'] ?? 0) > 0 ? intVal($teacherClassRow['section_limit'] ?? 0) : intVal($teacherClassRow['sec_limit'] ?? 0);
        $section_enrolled = intVal($sectionEnrolledMap[$class_id] ?? 0);
        $course_enrolled = intVal($courseEnrolledMap[$class_id][$subject_id] ?? 0);

        if ($section_limit > 0 && $section_enrolled >= $section_limit) {
            continue;
        }

        if ($course_limit > 0 && $course_enrolled >= $course_limit) {
            continue;
        }

        $offeredScheduleEntries = parse_schedule_entries($teacherClassRow['schedule'] ?? '');
        if (!empty($selected_fixed_schedule_entries) && schedules_overlap($selected_fixed_schedule_entries, $offeredScheduleEntries)) {
            continue;
        }

        $unit = intVal($curriculumRow['unit'] ?? 0);
        if (!isset($availableOfferedUnitCodes[$course_code])) {
            $availableOfferedUnitCodes[$course_code] = true;
            $available_offered_units += $unit;
        }

        if (!isset($offeredSectionsById[$class_id])) {
            $offeredSectionsById[$class_id] = [
                'class_id' => $class_id,
                'class_name' => trim((string)($teacherClassRow['class_name'] ?? '')),
                'matched_subject_count' => 0,
                'class_enrolled_count' => $section_enrolled,
                'class_section_limit' => $section_limit,
                'remaining_slots' => $section_limit > 0 ? max(0, $section_limit - $section_enrolled) : null,
            ];
        }
        $offeredSectionsById[$class_id]['matched_subject_count']++;

        if ($offered_class_id > 0 && $class_id !== $offered_class_id) {
            continue;
        }

        $offeredCourses[] = [
            'offered_subject_id' => intVal($offeredRow['offered_subject_id'] ?? 0),
            'teacher_class_id' => intVal($teacherClassRow['teacher_class_id'] ?? 0),
            'class_id' => $class_id,
            'year_level' => intVal($teacherClassRow['year_level'] ?? 0),
            'class_name' => trim((string)($teacherClassRow['class_name'] ?? '')),
            'subject_code' => $course_code,
            'subject_title' => trim((string)($curriculumRow['subject_title'] ?? $course_title)),
            'unit' => $unit,
            'pre_req' => trim((string)($curriculumRow['pre_req'] ?? '')),
            'schedule' => $teacherClassRow['schedule'] ?? '',
            'section_text' => trim((string)($teacherClassRow['class_name'] ?? '')),
            'subject_id' => $subject_id,
            'curriculum_year_level' => intVal($curriculumRow['year_level'] ?? 0),
            'curriculum_semester' => trim((string)($curriculumRow['semester'] ?? '')),
            'offered_program' => trim((string)($teacherClassRow['program_short_name'] ?? '')),
            'dean_name' => trim((string)($offeredRow['dean_name'] ?? '')),
            'section_limit' => $section_limit,
            'section_enrolled' => $section_enrolled,
            'course_limit' => $course_limit,
            'course_enrolled' => $course_enrolled,
        ];
    }

    $offeredSections = array_values($offeredSectionsById);
    usort($offeredSections, function($a, $b) {
        return strcasecmp($a['class_name'] ?? '', $b['class_name'] ?? '');
    });

    json_exit([
        'last_page' => 1,
        'data' => ($offered_class_id <= 0) ? [] : $offeredCourses,
        'msg_status' => true,
        'mode' => 'irregular',
        'school_year' => $active_school_year,
        'semester' => $active_term_semester,
        'missing_units' => $missing_units,
        'selected_class_id' => $selected_class_id,
        'selected_offered_class_id' => $offered_class_id,
        'selected_section' => '',
        'offered_sections' => $offeredSections,
        'stored_year_level' => $stored_year_level,
        'planning_year_level' => $planning_year_level,
        'planning_semester' => $planning_semester,
        'incoming_year_level' => $incoming_year_level,
        'incoming_semester' => $incoming_semester,
        'incoming_required_units' => $incoming_required_units,
        'fixed_units_for_selected_section' => $fixed_units_for_selected_section,
        'available_offered_units' => $available_offered_units,
        'can_underload' => ($available_offered_units < $missing_units),
        'selected_units' => 0,
    ]);
} catch (Throwable $th) {
    json_exit([
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
