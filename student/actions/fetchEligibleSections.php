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

    if (empty($student)) {
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
        WHERE flag_used != 0
        ORDER BY createdAt DESC
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_sy)) {
        while($data = call_mysql_fetch_array($query)){
            $schoolYear = $data;
        }
    }

    if ($schoolYear === null) {
        $output['code'] = 401;
        $output['msg_response'] = 'No default Fiscal Year set.';
        echo json_encode($output);
        exit();
    }

    $school_year_id = intVal($schoolYear['school_year_id']);
    $active_term_semester = trim($schoolYear['sem']);
    $active_school_year = trim($schoolYear['school_year']);





    /*
    |--------------------------------------------------------------------------
    | Curriculum
    |--------------------------------------------------------------------------
    */
$curriculumRows = [];
$curriculumByYearSem = [];
$curriculumCourseMap = [];
$curriculumProgressionOrder = [];
$maxCurriculumYearLevel = 0;
$semester_order = ['1st Semester', '2nd Semester'];

$sql_curriculum = "
    SELECT subject_code, subject_title, unit, pre_req, year_level, semester
    FROM curriculum
    WHERE curriculum_id = '" . escape($db_connect, $curriculum_id) . "'
    ORDER BY year_level ASC, semester ASC
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
                'passed_codes' => []
            ];
        }

        $curriculumByYearSem[$year_level][$row_semester]['courses'][$subject_code] = [
            'unit' => $unit,
            'row' => $row
        ];
        $curriculumByYearSem[$year_level][$row_semester]['required_units'] += $unit;

        $curriculumCourseMap[$subject_code] = [
            'year_level' => $year_level,
            'semester' => $row_semester,
            'unit' => $unit,
            'row' => $row
        ];

        if ($year_level > $maxCurriculumYearLevel) {
            $maxCurriculumYearLevel = $year_level;
        }
    }
}


    /*
    |--------------------------------------------------------------------------
    | Passed subjects
    |--------------------------------------------------------------------------
    */
$passedCodes = [];
$failedCodes = [];

$sql_passed = "
    SELECT subject_code, units, remarks
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
            $curriculumByYearSem[$courseYear][$courseSemester]['passed_codes'][$code] = true;
        } elseif ($remarks === 'FAILED') {
            $failedCodes[$code] = true;
        }
    }
}
/*
|--------------------------------------------------------------------------
| Flatten curriculum into ordered semester checkpoints
|--------------------------------------------------------------------------
*/
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
            'courses' => $bucket['courses'],
            'passed_codes' => $bucket['passed_codes']
        ];
    }
}

    /*
    |--------------------------------------------------------------------------
    | Progression logic: stop at first incomplete semester
    |--------------------------------------------------------------------------
    */
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
        $lastSem = $lastBucket['semester'];

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

    /*
    |--------------------------------------------------------------------------
    | Academic status
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Current-term curriculum buckets
    |--------------------------------------------------------------------------
    */
//     $fixedSubjects = [];
//     $backlogSubjects = [];
//     $higherSubjects = [];

// // foreach ($curriculumRows as $row) {
// //     $rowSemester = trim($row['semester'] ?? '');
// //     $rowYear = intVal($row['year_level'] ?? 0);
// //     $code = trim($row['subject_code'] ?? '');

// //     if ($code === '' || $rowYear === 0 || $rowSemester === '') {
// //         continue;
// //     }

// //     // Only subjects belonging to the currently open term semester
// //     // should be considered for available offerings.
// //     if (strcasecmp($rowSemester, $active_term_semester) !== 0) {
// //         continue;
// //     }

// //     // Already passed subjects should not appear again.
// //     if (isset($passedCodes[$code])) {
// //         continue;
// //     }

// //     $eligible = prereq_satisfied($row, $passedCodes);

// //     // Fixed subjects:
// //     // same planning year level, same currently open semester, prereqs satisfied
// //     if ($rowYear === $planning_year_level && $eligible) {
// //         $fixedSubjects[$code] = $row;
// //         continue;
// //     }

// //     // Backlog subjects:
// //     // lower year level subjects that are offered in this current semester
// //     if ($rowYear < $planning_year_level) {
// //         $backlogSubjects[$code] = $row;
// //         continue;
// //     }

// //     // Higher-year subjects:
// //     // only for irregular students, and only if prereqs are already satisfied
// //     if ($student_academic_status === 'Irregular' && $rowYear > $planning_year_level && $eligible) {
// //         $higherSubjects[$code] = $row;
// //         continue;
// //     }
// // }

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

    if (isset($passedCodes[$code])) {
        continue;
    }

    $eligible = prereq_satisfied($row, $passedCodes);
    $isActiveSemester = (strcasecmp($rowSemester, $active_term_semester) === 0);

    $isBacklog =
        ($rowYear < $planning_year_level) ||
        (
            $rowYear === $planning_year_level &&
            $planning_semester === '2nd Semester' &&
            strcasecmp($rowSemester, '1st Semester') === 0
        );

    if ($isBacklog && $isActiveSemester) {
        $backlogSubjects[$code] = $row;
        continue;
    }
    /*
    Offer next active-semester subjects not affected by the failed subject.
    If prereqs are satisfied, they should appear even if the student has
    another failed subject elsewhere.
    */
    if ($isActiveSemester && $eligible) {
        $fixedSubjects[$code] = $row;
        continue;
    }

    if (
        $student_academic_status === 'Irregular' &&
        $rowYear > $planning_year_level &&
        $isActiveSemester &&
        $eligible
    ) {
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
        AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_term_semester) . "')
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
        AND sem = '" . escape($db_connect, stripos($active_term_semester, '1ST') !== false ? 1 : 2) . "'
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

// $sections = [];

// if (!empty($fixedSubjects)) {
//     foreach ($offeringsByClass as $classId => $rows) {
//         $offeredCodes = [];
//         $missingFixedCodes = [];
//         $hasFullTeacherClass = false;

//         $className = $rows[0]['class_name'] ?? '';
//         $classSectionLimit = intVal($rows[0]['class_section_limit'] ?? 0);
//         $classEnrolledCount = intVal($enrollmentCountsByClass[$classId] ?? 0);

//         foreach ($rows as $r) {
//             $offeredCode = trim($r['subject_code'] ?? '');
//             $teacherClassId = intVal($r['teacher_class_id'] ?? 0);
//             $teacherClassLimit = intVal($r['section_limit'] ?? 0);
//             $teacherClassEnrolledCount = intVal($enrollmentCountsByTeacherClass[$teacherClassId] ?? 0);

//             if ($offeredCode !== '') {
//                 $offeredCodes[$offeredCode] = true;
//             }

//             if ($teacherClassLimit > 0 && $teacherClassEnrolledCount >= $teacherClassLimit) {
//                 $hasFullTeacherClass = true;
//             }
//         }


//         foreach ($fixedSubjects as $code => $fixedRow) {
//             if (!isset($offeredCodes[$code])) {
//                 $missingFixedCodes[] = $code;
//             }
//         }

//         $isClassFull = ($classSectionLimit > 0 && $classEnrolledCount >= $classSectionLimit);

//         if (empty($missingFixedCodes) && !$hasFullTeacherClass && !$isClassFull) {
//             $sections[] = [
//                 'class_id' => $classId,
//                 'class_name' => $className,
//                 'fixed_subject_count' => count($fixedSubjects),
//                 'class_enrolled_count' => $classEnrolledCount,
//                 'class_section_limit' => $classSectionLimit,
//                 'remaining_slots' => $classSectionLimit > 0 ? max(0, $classSectionLimit - $classEnrolledCount) : null,
//             ];
//         }
//     }
// }

$sections = [];
$availableSubjects = $student_academic_status === 'Irregular'
    ? ($fixedSubjects + $higherSubjects)
    : $fixedSubjects;

if (!empty($availableSubjects)) {
    foreach ($offeringsByClass as $classId => $rows) {
        $offeredCodes = [];
        $matchedOfferableCodes = [];
        $missingFixedCodes = [];
        $hasFullTeacherClass = false;

        $className = $rows[0]['class_name'] ?? '';
        $classSectionLimit = intVal($rows[0]['sec_limit'] ?? 0);
        $classEnrolledCount = intVal($enrollmentCountsByClass[$classId] ?? 0);

        foreach ($rows as $r) {
            $offeredCode = trim($r['subject_code'] ?? '');
            $teacherClassId = intVal($r['teacher_class_id'] ?? 0);
            $teacherClassLimit = intVal($r['section_limit'] ?? 0);
            $teacherClassEnrolledCount = intVal($enrollmentCountsByTeacherClass[$teacherClassId] ?? 0);

            if ($offeredCode !== '') {
                $offeredCodes[$offeredCode] = true;

                if (isset($availableSubjects[$offeredCode])) {
                    $matchedOfferableCodes[$offeredCode] = true;
                }
            }

            if ($teacherClassLimit > 0 && $teacherClassEnrolledCount >= $teacherClassLimit) {
                $hasFullTeacherClass = true;
            }
        }

        foreach ($availableSubjects as $code => $fixedRow) {
            if (!isset($offeredCodes[$code])) {
                $missingFixedCodes[] = $code;
            }
        }

        $isClassFull = ($classSectionLimit > 0 && $classEnrolledCount >= $classSectionLimit);

        /*
        |--------------------------------------------------------------------------
        | Regular students still require a full section match
        |--------------------------------------------------------------------------
        */
        if ($student_academic_status === 'Regular') {
            if (empty($missingFixedCodes) && !$hasFullTeacherClass && !$isClassFull) {
                $sections[] = [
                    'class_id' => $classId,
                    'class_name' => $className,
                    'fixed_subject_count' => count($availableSubjects),
                    'matched_subject_count' => count($matchedOfferableCodes),
                    'class_enrolled_count' => $classEnrolledCount,
                    'class_section_limit' => $classSectionLimit,
                    'remaining_slots' => $classSectionLimit > 0 ? max(0, $classSectionLimit - $classEnrolledCount) : null,
                ];
            }
            continue;
        }

        /*
        |--------------------------------------------------------------------------
        | Irregular students only need at least one eligible active-sem subject
        |--------------------------------------------------------------------------
        */
        if (count($matchedOfferableCodes) > 0 && !$hasFullTeacherClass && !$isClassFull) {
            $sections[] = [
                'class_id' => $classId,
                'class_name' => $className,
                'fixed_subject_count' => count($availableSubjects),
                'matched_subject_count' => count($matchedOfferableCodes),
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

    if ($student_academic_status === 'Irregular') {
        foreach ($offeringsByClass[$selected_class_id] ?? [] as $row) {
            $subject_code = trim($row['subject_code'] ?? '');

            if ($subject_code === '' || !isset($availableSubjects[$subject_code])) {
                continue;
            }

            $curriculumRow = $availableSubjects[$subject_code];
            $compatibleOfferings = $offeringsByCode[$subject_code] ?? [];
            $sectionText = implode(', ', array_unique(array_column($compatibleOfferings, 'class_name')));

            $fixedResponse[] = [
                'teacher_class_id' => intVal($row['teacher_class_id'] ?? 0),
                'class_id' => intVal($row['class_id'] ?? 0),
                'class_name' => $row['class_name'] ?? '',
                'subject_code' => $subject_code,
                'subject_title' => $row['subject_title'] ?? ($curriculumRow['subject_title'] ?? ''),
                'unit' => intVal($row['unit'] ?? ($curriculumRow['unit'] ?? 0)),
                'pre_req' => $curriculumRow['pre_req'] ?? '',
                'schedule' => $row['schedule'] ?? '',
                'section_text' => $sectionText,
                'subject_id' => intVal($row['subject_id'] ?? 0),
                'curriculum_year_level' => intVal($curriculumRow['year_level'] ?? 0),
                'curriculum_semester' => $curriculumRow['semester'] ?? '',
            ];
        }
    } else {
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
