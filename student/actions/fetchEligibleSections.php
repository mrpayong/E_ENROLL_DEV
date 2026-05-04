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
    function json_exit($payload){
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
    $output = array(
        'code' => 0,
        'msg_response' => 'Failed to Request',
        'msg_status' => false,
        'msg_span' => '_system'
    );
    $selected_class_id = isset($_GET['selected_class_id']) ? intVal($_GET['selected_class_id']) : 0;

    /*
    |--------------------------------------------------------------------------
    | Student
    |--------------------------------------------------------------------------
    */
    $student = array();
    $sql_student = "
        SELECT student_id_no, year_level, curriculum_id, program_id
        FROM student
        WHERE student_id_no = '" . escape($db_connect, $g_general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_student)) {
        while($data = call_mysql_fetch_array($query)){
            $student = $data;
        }
    }

    if ($student === null) {
        $output['code'] = 401;
        $output['msg_response'] = 'student not found';
        echo json_encode($output);
        exit();
    }


    $student_id_no = trim($student['student_id_no']);
    $stored_year_level = intVal($student['year_level']);
    $curriculum_id = intVal($student['curriculum_id']);
    $program_id = intVal($student['program_id']);

    /*
    |--------------------------------------------------------------------------
    | Active school year
    |--------------------------------------------------------------------------
    */
    $schoolYear = null;
    $sql_sy = "
        SELECT school_year_id, school_year, sem
        FROM school_year
        WHERE isDefault = '1'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_sy)) {
        while($data = call_mysql_fetch_array($query)){
            $schoolYear = $data;
        }
    }

    if ($schoolYear === null) {
        $output['code'] = 401;
        $output['msg_response'] = 'fiscal year not found';
        echo json_encode($output);
        exit();
    }

    $school_year_id = intVal($schoolYear['school_year_id']);
    $active_term_semester = trim($schoolYear['sem']);

    /*
    |--------------------------------------------------------------------------
    | Passed subjects
    |--------------------------------------------------------------------------
    */
    $passedCodes = [];
    $sql_passed = "
        SELECT DISTINCT subject_code
        FROM final_grade
        WHERE student_id_text = '" . escape($db_connect, $student_id_no) . "'
          AND UPPER(remarks) = 'PASSED'
    ";

    if ($query = call_mysql_query($sql_passed)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim($row['subject_code']);
            if ($code !== '') {
                // get existing final grades of the student for
                // curriculum progression tracking through comparison
                $passedCodes[$code] = true;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Curriculum
    |--------------------------------------------------------------------------
    */
    $curriculumRows = [];
    $curriculumByYearSem= [];
    $maxCurriculumYearLevel = 0;

    $sql_curriculum = "
        SELECT subject_code, subject_title, unit, pre_req, year_level, semester
        FROM curriculum
        WHERE curriculum_id = '" . escape($db_connect, $curriculum_id) . "'
        ORDER BY year_level ASC, semester ASC
    ";

    if ($query = call_mysql_query($sql_curriculum)) {
        while ($row = call_mysql_fetch_array($query)) {
            $curriculumRows[] = $row;

            $year_level = intVal($row['year_level']);
            $subject_code = trim($row['subject_code']);
            $row_semester = trim($row['semester']);

            if ($year_level !== 0 && $row_semester !== '' && $subject_code !== '') {

                if (!isset($curriculumByYearSem[$year_level][$row_semester])) {
                    $curriculumByYearSem[$year_level][$row_semester] = [];
                }

                // structure curriculum by year level and semester
                // to be compared with final grades later for progression tracking through comparison
                $curriculumByYearSem[$year_level][$row_semester][] = $row;

                if ($year_level > $maxCurriculumYearLevel) {
                    $maxCurriculumYearLevel = $year_level;
                }
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Compute progressed/planning year level
    |--------------------------------------------------------------------------
    */
    $progressed_year_level = 1;
    $progressed_semester = '1st Semester';
    $semester_order = ['1st Semester', '2nd Semester'];

    for ($level = 1; $level <= $maxCurriculumYearLevel; $level++) {
        // "$level <= $maxCurriculumYearLevel" means:
        // continue looping as long as $level is less than or equal to the highest curriculum year level
        // "$level++" means:
        // after each loop, add 1 to $level
        if (empty($curriculumByYearSem[$level])) {
            break;
        }


        foreach ($semester_order as $semester_name) {
            $semester_rows = $curriculumByYearSem[$level][$semester_name] ?? [];

            if (empty($semester_rows)) {
                continue;
            }

            $all_passed_for_semester = true;

            foreach ($semester_rows as $row) {
                $subject_code = trim($row['subject_code'] ?? '');
                if ($subject_code === '') {
                    continue;
                }

                // track passed subjects by indexing
                // $passedCodes array with the $subejct_code values 
                // from $curriculumByYearSem array
                if (!isset($passedCodes[$subject_code])) {
                    $all_passed_for_semester = false;
                    break;
                }
            }

            if ($all_passed_for_semester) {
                if ($semester_name === '1st Semester') {
                    $progressed_year_level = $level;
                    $progressed_semester = '2nd Semester';
                } else {
                    $progressed_year_level = $level + 1;
                    $progressed_semester = '1st Semester';
                }
            } else {
                $progressed_year_level = $level;
                $progressed_semester = $semester_name;
                break 2;
            }
        }
    }

    if ($maxCurriculumYearLevel > 0 && $progressed_year_level > $maxCurriculumYearLevel) {
        $progressed_year_level = $maxCurriculumYearLevel;
        $progressed_semester = '2nd Semester';
    }

    $planning_year_level = $progressed_year_level;
    $planning_semester = $progressed_semester;
    /*
    |--------------------------------------------------------------------------
    | Academic status
    |--------------------------------------------------------------------------
    */
    $missing_lower_year_subjects = [];

    for ($level = 1; $level <= $maxCurriculumYearLevel; $level++) {
        if (empty($curriculumByYearSem[$level])) {
            continue;
        }

        foreach ($semester_order as $semester_name) {
            $semester_rows = $curriculumByYearSem[$level][$semester_name] ?? [];

            if (empty($semester_rows)) {
                continue;
            }

            $is_before_planning_point =
                ($level < $planning_year_level) ||
                ($level === $planning_year_level && $semester_name !== $planning_semester && $planning_semester === '2nd Semester');

            if (!$is_before_planning_point) {
                continue;
            }

            foreach ($semester_rows as $row) {
                $subject_code = trim($row['subject_code'] ?? '');
                if ($subject_code === '') {
                    continue;
                }

                if (!isset($passedCodes[$subject_code])) {
                    $missing_lower_year_subjects[] = $subject_code;
                }
            }
        }
    }

    $student_academic_status = empty($missing_lower_year_subjects) ? 'Regular' : 'Irregular';

    /*
    |--------------------------------------------------------------------------
    | Current-term curriculum buckets
    |--------------------------------------------------------------------------
    */
    $fixedSubjects = [];
    $backlogSubjects = [];
    $higherSubjects = [];

foreach ($curriculumRows as $row) {
    $rowSemester = trim($row['semester'] ?? '');
    $rowYear = intVal($row['year_level'] ?? 0);
    $code = trim($row['subject_code'] ?? '');

    if ($code === '' || $rowYear === 0 || $rowSemester === '') {
        continue;
    }

    // Only subjects belonging to the currently open term semester
    // should be considered for available offerings.
    if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
        continue;
    }

    // Already passed subjects should not appear again.
    if (isset($passedCodes[$code])) {
        continue;
    }

    $eligible = prereq_satisfied($row, $passedCodes);

    // Fixed subjects:
    // same planning year level, same currently open semester, prereqs satisfied
    if ($rowYear === $planning_year_level && $eligible) {
        $fixedSubjects[$code] = $row;
        continue;
    }

    // Backlog subjects:
    // lower year level subjects that are offered in this current semester
    if ($rowYear < $planning_year_level) {
        $backlogSubjects[$code] = $row;
        continue;
    }

    // Higher-year subjects:
    // only for irregular students, and only if prereqs are already satisfied
    if ($student_academic_status === 'Irregular' && $rowYear > $planning_year_level && $eligible) {
        $higherSubjects[$code] = $row;
        continue;
    }
}

    /*
    |--------------------------------------------------------------------------
    | Teacher class offerings
    |--------------------------------------------------------------------------
    */
    $offeringsByClass = [];
    $offeringsByCode = [];
    $enrollmentCountsByTeacherClass = [];
    $enrollmentCountsByClass = [];
    
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
            s.subject_title
        FROM teacher_class tc
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
        AND tc.program_id = '" . escape($db_connect, $program_id) . "'
        AND tc.sem = '" . escape($db_connect, $active_term_semester) . "'
        AND tc.status = 0
        AND cs.status = 0
    ";

    if ($query = call_mysql_query($sql_offerings)) {
        while ($row = call_mysql_fetch_array($query)) {
            $classId = intVal($row['class_id'] ?? 0);
            $code = trim($row['subject_code'] ?? '');
            $teacherClassId = intVal($row['teacher_class_id']);

            if ($classId > 0 && $teacherClassId > 0 && $code !== '') {
                if (!isset($offeringsByClass[$classId])) {
                    $offeringsByClass[$classId] = [];
                }
                if (!isset($offeringsByCode[$code])) {
                    $offeringsByCode[$code] = [];
                }

                $offeringsByClass[$classId][] = $row;
                $offeringsByCode[$code][] = $row;
            }
        }
    }

    $sql_enrollment_counts = "
        SELECT
            teacher_class_id,
            class_id,
            COUNT(*) AS enrolled_count
        FROM enrollments
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
        AND sem = '" . escape($db_connect, $active_term_semester === '1st Semester' ? 1 : 2) . "'
        AND status = 'Enrolled'
        GROUP BY teacher_class_id, class_id
    ";

    if ($query = call_mysql_query($sql_enrollment_counts)) {
        while ($row = call_mysql_fetch_array($query)) {
            $teacherClassId = intVal($row['teacher_class_id'] ?? 0);
            $classId = intVal($row['class_id'] ?? 0);
            $enrolledCount = intVal($row['enrolled_count'] ?? 0);

            if ($teacherClassId > 0) {
                $enrollmentCountsByTeacherClass[$teacherClassId] = $enrolledCount;
            }

            if ($classId > 0) {
                if (!isset($enrollmentCountsByClass[$classId])) {
                    $enrollmentCountsByClass[$classId] = 0;
                }

                $enrollmentCountsByClass[$classId] += $enrolledCount;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Sections that fully cover fixed subjects
    |--------------------------------------------------------------------------
    */

$sections = [];

if (!empty($fixedSubjects)) {
    foreach ($offeringsByClass as $classId => $rows) {
        $offeredCodes = [];
        $missingFixedCodes = [];
        $hasFullTeacherClass = false;

        $className = $rows[0]['class_name'] ?? '';
        $classSectionLimit = intVal($rows[0]['class_section_limit'] ?? 0);
        $classEnrolledCount = intVal($enrollmentCountsByClass[$classId] ?? 0);

        foreach ($rows as $r) {
            $offeredCode = trim($r['subject_code'] ?? '');
            $teacherClassId = intVal($r['teacher_class_id'] ?? 0);
            $teacherClassLimit = intVal($r['section_limit'] ?? 0);
            $teacherClassEnrolledCount = intVal($enrollmentCountsByTeacherClass[$teacherClassId] ?? 0);

            if ($offeredCode !== '') {
                $offeredCodes[$offeredCode] = true;
            }

            if ($teacherClassLimit > 0 && $teacherClassEnrolledCount >= $teacherClassLimit) {
                $hasFullTeacherClass = true;
            }
        }


        foreach ($fixedSubjects as $code => $fixedRow) {
            if (!isset($offeredCodes[$code])) {
                $missingFixedCodes[] = $code;
            }
        }

        $isClassFull = ($classSectionLimit > 0 && $classEnrolledCount >= $classSectionLimit);

        if (empty($missingFixedCodes) && !$hasFullTeacherClass && !$isClassFull) {
            $sections[] = [
                'class_id' => $classId,
                'class_name' => $className,
                'fixed_subject_count' => count($fixedSubjects),
                'class_enrolled_count' => $classEnrolledCount,
                'class_section_limit' => $classSectionLimit,
                'remaining_slots' => $classSectionLimit > 0 ? max(0, $classSectionLimit - $classEnrolledCount) : null,
            ];
        }
    }
}

if ($selected_class_id <= 0 && !empty($sections)) {
    $selected_class_id = intVal($sections[0]['class_id']);
}
    /*
    |--------------------------------------------------------------------------
    | Fixed subjects for selected/base section
    |--------------------------------------------------------------------------
    */
    $fixedResponse = [];

    foreach ($offeringsByClass[$selected_class_id] ?? [] as $row) {
        $subject_code = trim($row['subject_code'] ?? '');

        if ($subject_code === '' || !isset($fixedSubjects[$subject_code])) {
            continue;
        }

        $curriculumRow = $fixedSubjects[$subject_code];

        $fixedResponse[] = [
            'teacher_class_id' => intVal($row['teacher_class_id'] ?? 0),
            'class_id' => intVal($row['class_id'] ?? 0),
            'class_name' => $row['class_name'] ?? '',
            'subject_code' => $subject_code,
            'subject_title' => $row['subject_title'] ?? ($curriculumRow['subject_title'] ?? ''),
            'unit' => intVal($row['unit'] ?? ($curriculumRow['unit'] ?? 0)),
            'pre_req' => $curriculumRow['pre_req'] ?? '',
            'schedule' => $row['schedule'] ?? '',
            'section_text' => $row['class_name'] ?? '',
            'subject_id' => intVal($row['subject_id'] ?? 0),
            'curriculum_year_level' => intVal($curriculumRow['year_level'] ?? 0),
            'curriculum_semester' => $curriculumRow['semester'] ?? '',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Optional subjects
    |--------------------------------------------------------------------------
    */
    $mapOptional = function($rows) use ($offeringsByCode) {
        $result = [];

        foreach ($rows as $code => $row) {
            $compatible = $offeringsByCode[$code] ?? [];
            $sectionText = count($compatible)
                ? implode(', ', array_unique(array_column($compatible, 'class_name')))
                : 'No compatible section';

            $teacherClassId = count($compatible)
                ? intVal($compatible[0]['teacher_class_id'] ?? 0)
                : 0;

            $result[] = [
                'teacher_class_id' => $teacherClassId,
                'subject_code' => $code,
                'subject_title' => $row['subject_title'] ?? '',
                'unit' => intVal($row['unit'] ?? 0),
                'pre_req' => $row['pre_req'] ?? '',
                'section_text' => $sectionText,
            ];
        }

        return $result;
    };

$base_section_label = '';
foreach ($sections as $sectionRow) {
    if (intVal($sectionRow['class_id']) === intVal($selected_class_id)) {
        $base_section_label = $sectionRow['class_name'] ?? '';
        break;
    }
}

json_exit([
    'last_page' => 1,
    'data' => $fixedResponse,
    'msg_status' => true,
    'mode' => strtolower($student_academic_status),
    'stored_year_level' => $stored_year_level,
    'progressed_year_level' => $progressed_year_level,
    'progressed_semester' => $progressed_semester,
    'planning_year_level' => $planning_year_level,
    'planning_semester' => $planning_semester,
    'selected_class_id' => $selected_class_id,
    'base_section_label' => $base_section_label,
    'required_units' => array_sum(array_map(function($r){ return intVal($r['unit']); }, $fixedResponse)),
    'sections' => $sections,
    'fixed_subjects' => $fixedResponse,
    'backlog_subjects' => $mapOptional($backlogSubjects),
    'higher_year_subjects' => $mapOptional($higherSubjects),
]);
} catch (Throwable $th) {
    json_exit([
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
