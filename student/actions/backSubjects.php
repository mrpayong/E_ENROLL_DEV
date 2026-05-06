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

    function semester_order_value($semesterName) {
        return strcasecmp(trim((string)$semesterName), '1st Semester') === 0 ? 1 : 2;
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

            $day = $parts[0];
            $timeRange = $parts[1];
            $timeParts = array_map('trim', explode('-', $timeRange));
            if (count($timeParts) !== 2) continue;

            $start = time_to_minutes($timeParts[0]);
            $end = time_to_minutes($timeParts[1]);

            if ($start === null || $end === null) continue;

            $entries[] = [
                'day' => strtoupper($day),
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

    $student = [];
    $sql_student = "
        SELECT student_id_no, year_level, curriculum_id, program_id
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
            'msg_status' => false,
            'msg_response' => 'student not found',
        ]);
    }

    $student_id_no = trim($student['student_id_no']);
    $stored_year_level = intVal($student['year_level']);
    $curriculum_id = intVal($student['curriculum_id']);
    $program_id = intVal($student['program_id']);
    $selected_class_id = isset($_GET['selected_class_id']) ? intVal($_GET['selected_class_id']) : 0;

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
            'msg_status' => false,
            'msg_response' => 'No default Fiscal Year set.',
        ]);
    }

    $school_year_id = intVal($schoolYear['school_year_id']);
    $active_term_semester = trim($schoolYear['sem']);
    $active_school_year = trim($schoolYear['school_year']);
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
    $failedCodes = [];

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
            } elseif ($remarks === 'FAILED') {
                $failedCodes[$code] = true;
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

    $missing_required_subjects = [];
    for ($level = 1; $level <= $planning_year_level; $level++) {
        if (empty($curriculumByYearSem[$level])) {
            continue;
        }

        foreach ($semester_order as $semester_name) {
            $semester_bucket = $curriculumByYearSem[$level][$semester_name] ?? null;
            if (empty($semester_bucket)) {
                continue;
            }

            $is_after_planning_point =
                ($level > $planning_year_level) ||
                ($level === $planning_year_level && $planning_semester === '1st Semester' && strcasecmp($semester_name, '2nd Semester') === 0);

            if ($is_after_planning_point) {
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
            'required_units' => 0,
            'current_units' => 0,
            'missing_units' => 0,
            'target_year_level' => $planning_year_level,
            'target_semester' => $active_term_semester,
        ]);
    }

    $target_year_level = $planning_year_level;
    if (strcasecmp($active_term_semester, '1st Semester') === 0 && strcasecmp($planning_semester, '2nd Semester') === 0) {
        $target_year_level = min($planning_year_level + 1, $maxCurriculumYearLevel);
    }

    $target_bucket = $curriculumByYearSem[$target_year_level][$active_term_semester] ?? null;

    if (empty($target_bucket)) {
        json_exit([
            'last_page' => 1,
            'data' => [],
            'msg_status' => true,
            'mode' => 'irregular',
            'required_units' => 0,
            'current_units' => 0,
            'missing_units' => 0,
            'target_year_level' => $target_year_level,
            'target_semester' => $active_term_semester,
        ]);
    }

    $normalEligibleSubjects = [];
    $current_units = 0;

    foreach (($target_bucket['courses'] ?? []) as $subject_code => $courseData) {
        $row = $courseData['row'];
        if (isset($passedCodes[$subject_code])) {
            continue;
        }

        if (!prereq_satisfied($row, $passedCodes)) {
            continue;
        }

        $normalEligibleSubjects[$subject_code] = $row;
        $current_units += intVal($courseData['unit'] ?? 0);
    }

    $target_required_units = intVal($target_bucket['required_units'] ?? 0);
    $missing_units = max(0, $target_required_units - $current_units);

    if ($missing_units <= 0) {
        json_exit([
            'last_page' => 1,
            'data' => [],
            'msg_status' => true,
            'mode' => 'irregular',
            'required_units' => $target_required_units,
            'current_units' => $current_units,
            'missing_units' => 0,
            'target_year_level' => $target_year_level,
            'target_semester' => $active_term_semester,
        ]);
    }

    $candidateRows = [];
    foreach ($curriculumRows as $row) {
        $code = trim($row['subject_code'] ?? '');
        $rowYear = intVal($row['year_level'] ?? 0);
        $rowSemester = trim($row['semester'] ?? '');
        $unit = intVal($row['unit'] ?? 0);
        $preReq = trim($row['pre_req'] ?? '');

        if ($code === '' || $rowYear === 0 || $unit <= 0) {
            continue;
        }

        if (isset($passedCodes[$code]) || isset($normalEligibleSubjects[$code])) {
            continue;
        }

        if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
            continue;
        }

        if ($rowYear <= $target_year_level) {
            continue;
        }

        if ($preReq !== '') {
            continue;
        }

        $candidateRows[$code] = $row;
    }

    uasort($candidateRows, function ($a, $b) {
        $aYear = intVal($a['year_level'] ?? 0);
        $bYear = intVal($b['year_level'] ?? 0);
        if ($aYear !== $bYear) {
            return $aYear <=> $bYear;
        }

        $aUnit = intVal($a['unit'] ?? 0);
        $bUnit = intVal($b['unit'] ?? 0);
        if ($aUnit !== $bUnit) {
            return $aUnit <=> $bUnit;
        }

        return strcasecmp(trim($a['subject_code'] ?? ''), trim($b['subject_code'] ?? ''));
    });

    $offeringsByCode = [];
    $studentOfferingsByClass = [];
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
            p.short_name AS program_short_name
        FROM teacher_class tc
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        LEFT JOIN programs p ON p.program_id = tc.program_id
        WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
        AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
        AND tc.status = 0
        AND cs.status = 0
    ";

    if ($query = call_mysql_query($sql_offerings)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim($row['subject_code'] ?? '');
            if ($code === '') {
                continue;
            }

            if (!isset($offeringsByCode[$code])) {
                $offeringsByCode[$code] = [];
            }

            $offeringsByCode[$code][] = $row;

            if (intVal($row['program_id'] ?? 0) === $program_id) {
                $classId = intVal($row['class_id'] ?? 0);
                if ($classId > 0) {
                    if (!isset($studentOfferingsByClass[$classId])) {
                        $studentOfferingsByClass[$classId] = [];
                    }
                    $studentOfferingsByClass[$classId][] = $row;
                }
            }
        }
    }

    if ($selected_class_id <= 0) {
        foreach ($studentOfferingsByClass as $classId => $rows) {
            $matchedCount = 0;
            foreach ($rows as $row) {
                $code = trim($row['subject_code'] ?? '');
                if ($code !== '' && isset($normalEligibleSubjects[$code])) {
                    $matchedCount++;
                }
            }

            if ($matchedCount > 0) {
                $selected_class_id = $classId;
                break;
            }
        }
    }

    $lockedScheduleEntries = [];
    foreach (($studentOfferingsByClass[$selected_class_id] ?? []) as $row) {
        $code = trim($row['subject_code'] ?? '');
        if ($code === '' || !isset($normalEligibleSubjects[$code])) {
            continue;
        }

        $lockedScheduleEntries = array_merge($lockedScheduleEntries, parse_schedule_entries($row['schedule'] ?? ''));
    }

    $offeredBackSubjects = [];
    $remaining_units = $missing_units;

    foreach ($candidateRows as $code => $row) {
        if ($remaining_units <= 0) {
            break;
        }

        $unit = intVal($row['unit'] ?? 0);
        if ($unit <= 0 || $unit > $remaining_units) {
            continue;
        }

        $compatibleOfferings = $offeringsByCode[$code] ?? [];
        if (empty($compatibleOfferings)) {
            continue;
        }

        $chosenOffering = null;
        foreach ($compatibleOfferings as $offeringRow) {
            $candidateScheduleEntries = parse_schedule_entries($offeringRow['schedule'] ?? '');
            if (schedules_overlap($lockedScheduleEntries, $candidateScheduleEntries)) {
                continue;
            }

            $chosenOffering = $offeringRow;
            break;
        }

        if ($chosenOffering === null) {
            continue;
        }

        $sectionTextParts = array_filter([
            trim((string)($chosenOffering['program_short_name'] ?? '')),
            trim((string)($chosenOffering['class_name'] ?? '')),
        ]);
        $sectionText = !empty($sectionTextParts) ? implode(' ', $sectionTextParts) : 'No compatible section';

        $offeredBackSubjects[] = [
            'teacher_class_id' => intVal($chosenOffering['teacher_class_id'] ?? 0),
            'class_id' => intVal($chosenOffering['class_id'] ?? 0),
            'class_name' => $chosenOffering['class_name'] ?? '',
            'subject_code' => $code,
            'subject_title' => $chosenOffering['subject_title'] ?? ($row['subject_title'] ?? ''),
            'unit' => $unit,
            'pre_req' => $row['pre_req'] ?? '',
            'schedule' => $chosenOffering['schedule'] ?? '',
            'section_text' => $sectionText,
            'subject_id' => intVal($chosenOffering['subject_id'] ?? 0),
            'curriculum_year_level' => intVal($row['year_level'] ?? 0),
            'curriculum_semester' => $row['semester'] ?? '',
            'offered_program' => $chosenOffering['program_short_name'] ?? '',
        ];

        $remaining_units -= $unit;
    }

    json_exit([
        'last_page' => 1,
        'data' => $offeredBackSubjects,
        'msg_status' => true,
        'mode' => 'irregular',
        'school_year' => $active_school_year,
        'semester' => $active_term_semester,
        'required_units' => $target_required_units,
        'current_units' => $current_units,
        'missing_units' => $missing_units,
        'filled_units' => $missing_units - $remaining_units,
        'remaining_units' => $remaining_units,
        'target_year_level' => $target_year_level,
        'target_semester' => $active_term_semester,
    ]);
} catch (Throwable $th) {
    json_exit([
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
