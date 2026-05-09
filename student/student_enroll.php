<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Student Enrollment";
$get_user_value = strtoupper($_GET['none'] ?? '');
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [
    ['label' => $page_header_title, 'url' => '']
];

if (!($g_user_role == "STUDENT")) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

$active_fiscalYear = array();
$student_user = array();
$active_school_year = '';
$active_semester = '';
$student_year_level = 0;
$student_curriculum_id = 0;
$student_program_id = '';
$student_program = '';
$active_school_year_id = 0;
$student_class_id = 0;

$sql_fy = "SELECT school_year_id, school_year, sem, date_from, date_to, enrollment_start_date, enrollment_end_date, flag_used 
FROM school_year
WHERE flag_used != 0
ORDER BY createdAt DESC
LIMIT 1
";

if($sql = call_mysql_query($sql_fy)){
    if($data = call_mysql_fetch_array($sql)){
        array_push($active_fiscalYear, $data);
        $active_school_year = trim($data['school_year']);
        $active_semester = trim($data['sem']);
        $active_school_year_id = intVal($data['school_year_id']);
    }
}

$sql_student = "SELECT student_id, student_id_no, firstname, middle_name, lastname, year_level, curriculum_id, program_id, class_id
FROM student 
WHERE student_id_no = '".  escape($db_connect, $g_general_id)  ."'
LIMIT 1
";
if($sql = call_mysql_query($sql_student)){
    if($data = call_mysql_fetch_array($sql)){
        array_push($student_user, $data);
        $student_year_level = intVal($data['year_level']);
        $student_curriculum_id = intVal($data['curriculum_id']);
        $student_program_id = intVal($data['program_id']);
        $student_class_id = intVal($data['class_id'] ?? 0);
        $student_id_data = $data['student_id_no'];
    }
}

$sql_program = "SELECT short_name
FROM programs
WHERE program_id = '".  escape($db_connect, $student_program_id)    ."'
";

if($sql = call_mysql_query($sql_program)){
    if($data = call_mysql_fetch_array($sql)){
        $student_program = $data['short_name'];
    }
}

$student_academic_status = 'Regular';
$previous_term_school_year = '';
$previous_term_sem = '';
$comparison_year_level = 0;
$display_required_units = 0;
$tracked_year_level_for_display = $student_year_level;
$progressed_year_level = $student_year_level;
$progressed_semester = $active_semester;

$passed_earned_units = 0;
$required_curriculum_units = 0;
$previous_term_average_final_grade = 0;
$passed_subject_count = 0;
$required_subject_codes = array();
$passed_subject_codes = array();
$missing_subject_codes = array();

if (!empty($active_school_year) && !empty($active_semester) && $student_year_level != 0) {
    $normalized_current_sem = strtolower(trim($active_semester));

    if (strpos($normalized_current_sem, '2nd') !== false) {
        $previous_term_school_year = $active_school_year;
        $previous_term_sem = '1ST SEMESTER';
        $comparison_year_level = $student_year_level;
    } elseif (strpos($normalized_current_sem, '1st') !== false) {
        $parts = explode('-', $active_school_year);

        if (count($parts) === 2) {
            $start_year = (int)$parts[0];
            $end_year = (int)$parts[1];

            $previous_term_school_year = ($start_year - 1) . '-' . ($end_year - 1);
            $previous_term_sem = '2ND SEMESTER';
            $comparison_year_level = max(1, $student_year_level - 1);
        }
    }
}

/*
|--------------------------------------------------------------------------
| 4. Compute earned units and average final grade from final_grade
|--------------------------------------------------------------------------
| Only include rows where remarks = PASSED
|--------------------------------------------------------------------------
*/
$check_stdn = false;
$check_stdn_data = '';
$sql_student_check = "SELECT student_id_text FROM final_grade 
    WHERE student_id_text = '".    escape($db_connect, $student_id_data)    ."' ";

    if ($query = call_mysql_query($sql_student_check)) {
        if ($data = call_mysql_fetch_array($query)) {
            $check_stdn = true;
        }
    }


if (!empty($previous_term_school_year) && !empty($previous_term_sem) && !empty($g_general_id)){
    $sql_passed_grades = "
        SELECT subject_code, units, final_grade
        FROM final_grade
        WHERE student_id_text = '". escape($db_connect, $g_general_id)  ."'
          AND school_year = '". escape($db_connect, $previous_term_school_year) ."'
          AND UPPER(sem) = UPPER('".    escape($db_connect, $previous_term_sem) ."')
          AND UPPER(remarks) = 'PASSED'
    ";

    $final_grade_total = 0;

    if ($query = call_mysql_query($sql_passed_grades)) {
        while ($data = call_mysql_fetch_array($query)){
            $subject_code = trim($data['subject_code']);
            $units = intVal($data['units']);
            $final_grade = (float)($data['final_grade']);

            if ($subject_code !== '') {
                $passed_subject_codes[$subject_code] = true;
                $passed_earned_units += $units;
                $final_grade_total += $final_grade;
                $passed_subject_count++;
            }
        }
    }

    if ($passed_subject_count > 0) {
        $previous_term_average_final_grade = round($final_grade_total / $passed_subject_count, 2);
    }
}

if ($student_curriculum_id > 0 && $comparison_year_level > 0 && !empty($previous_term_sem)) {
    $curriculum_id_safe = intVal($student_curriculum_id);
    $comparison_year_level_safe = intVal($comparison_year_level);

    $curriculum_sem = '';
    if (stripos($previous_term_sem, '1ST') !== false) {
        $curriculum_sem = '1st Semester';
    } elseif (stripos($previous_term_sem, '2ND') !== false) {
        $curriculum_sem = '2nd Semester';
    }

    if (!empty($curriculum_sem)) {
        $curriculum_sem_safe = escape($db_connect, $curriculum_sem);

        $sql_curriculum_units = "
            SELECT subject_code, unit
            FROM curriculum
            WHERE curriculum_id = $curriculum_id_safe
              AND year_level = $comparison_year_level_safe
              AND semester = '".    escape($db_connect, $curriculum_sem)    ."'
        ";

        if ($query = call_mysql_query($sql_curriculum_units)) {
            while ($data = call_mysql_fetch_array($query)) {
                $subject_code = trim($data['subject_code']);
                $unit = intVal($data['unit']);

                if ($subject_code !== '') {
                    $required_subject_codes[$subject_code] = true;
                    $required_curriculum_units += $unit;
                }
            }
        }
    }
}

if (!$check_stdn) {
    $student_academic_status = "Regular";
} elseif (!empty($required_subject_codes)) {
    foreach ($required_subject_codes as $subject_code => $flag) {
        if (!isset($passed_subject_codes[$subject_code])) {
            $missing_subject_codes[] = $subject_code;
        }
    }

    if (empty($missing_subject_codes)) {
        $student_academic_status = "Regular";
    } else {
        $student_academic_status = "Irregular";
    }
} else {
    $student_academic_status = "Irregular";
}

$effective_year_level = $student_year_level;

if ($student_academic_status === 'Regular') {
    if (stripos($active_semester, '2nd') !== false) {
        $effective_year_level = $student_year_level;
    } elseif (stripos($active_semester, '1st') !== false) {
        $effective_year_level = max(1, $student_year_level);
    }
}

/*
|--------------------------------------------------------------------------
| Required units indicator for incoming target semester
|--------------------------------------------------------------------------
| Use curriculum progression instead of summing the currently shown rows.
|--------------------------------------------------------------------------
*/
function split_prereq_codes_display($text) {
    $text = trim((string)$text);
    if ($text === '') return [];
    $parts = preg_split('/\s*,\s*|\s*;\s*|\s*\/\s*/', $text);
    return array_values(array_filter(array_map('trim', $parts)));
}

$curriculumByYearSem = [];
$curriculumCourseMap = [];
$curriculumProgressionOrder = [];
$maxCurriculumYearLevel = 0;
$semester_order = ['1st Semester', '2nd Semester'];

if ($student_curriculum_id > 0) {
    $sql_curriculum_progress = "
        SELECT subject_code, unit, year_level, semester
        FROM curriculum
        WHERE curriculum_id = '" . escape($db_connect, $student_curriculum_id) . "'
        ORDER BY year_level ASC, semester ASC, subject_code ASC
    ";

    if ($query = call_mysql_query($sql_curriculum_progress)) {
        while ($row = call_mysql_fetch_array($query)) {
            $year_level = intVal($row['year_level'] ?? 0);
            $subject_code = trim($row['subject_code'] ?? '');
            $row_semester = trim($row['semester'] ?? '');
            $unit = intVal($row['unit'] ?? 0);

            if ($year_level === 0 || $subject_code === '' || $row_semester === '') {
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
            $curriculumByYearSem[$year_level][$row_semester]['courses'][$subject_code] = true;
            $curriculumCourseMap[$subject_code] = [
                'year_level' => $year_level,
                'semester' => $row_semester,
                'unit' => $unit,
            ];

            if ($year_level > $maxCurriculumYearLevel) {
                $maxCurriculumYearLevel = $year_level;
            }
        }
    }

    $passedCodesForProgress = [];
    $sql_all_passed = "
        SELECT subject_code, remarks
        FROM final_grade
        WHERE student_id_text = '" . escape($db_connect, $g_general_id) . "'
    ";

    if ($query = call_mysql_query($sql_all_passed)) {
        while ($row = call_mysql_fetch_array($query)) {
            $subject_code = trim($row['subject_code'] ?? '');
            $remarks = strtoupper(trim($row['remarks'] ?? ''));

            if ($subject_code === '' || $remarks !== 'PASSED' || !isset($curriculumCourseMap[$subject_code])) {
                continue;
            }

            $passedCodesForProgress[$subject_code] = true;
            $courseMeta = $curriculumCourseMap[$subject_code];
            $courseYear = intVal($courseMeta['year_level']);
            $courseSemester = trim($courseMeta['semester']);
            $courseUnit = intVal($courseMeta['unit']);

            $curriculumByYearSem[$courseYear][$courseSemester]['earned_units'] += $courseUnit;
        }
    }

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

    $progressed_year_level = $student_year_level;
    $progressed_semester = $active_semester;

    foreach ($curriculumProgressionOrder as $bucket) {
        if (intVal($bucket['earned_units']) < intVal($bucket['required_units'])) {
            $progressed_year_level = intVal($bucket['year_level']);
            $progressed_semester = $bucket['semester'];
            break;
        }
    }

    if (!empty($curriculumProgressionOrder)) {
        $lastBucket = end($curriculumProgressionOrder);
        if (intVal($lastBucket['earned_units']) >= intVal($lastBucket['required_units'])) {
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
    }

    $target_year_level_for_display = $progressed_year_level;
    if (strcasecmp($active_semester, '1st Semester') === 0 && strcasecmp($progressed_semester, '2nd Semester') === 0) {
        $target_year_level_for_display = min($progressed_year_level + 1, $maxCurriculumYearLevel);
    }

    $tracked_year_level_for_display = $target_year_level_for_display;
    $display_required_units = intVal($curriculumByYearSem[$target_year_level_for_display][$active_semester]['required_units'] ?? 0);
}

$enrollment_context = [
    'student_id_no' => $g_general_id,
    'program_id' => $student_program_id,
    'curriculum_id' => $student_curriculum_id,
    'student_year_level' => $student_year_level,
    'effective_year_level' => $effective_year_level,
    'tracked_year_level' => $tracked_year_level_for_display,
    'progressed_year_level' => $progressed_year_level,
    'progressed_semester' => $progressed_semester,
    'academic_status' => $student_academic_status,
    'school_year_id' => $active_school_year_id,
    'school_year' => $active_school_year,
    'semester' => $active_semester,
    'display_required_units' => $display_required_units,
];

$enroll_status = false;
$back_subject_request_status = false;
$id_Data = '';
$sql_enroll = "SELECT student_id_no FROM enrollments
WHERE student_id_no = '".   escape($db_connect, $g_general_id)."'
AND school_year_id = '".   escape($db_connect, $active_school_year_id)."'
AND UPPER(sem) = UPPER('".   escape($db_connect, $active_semester)."')
LIMIT 1
";

if($sql = call_mysql_query($sql_enroll)){
    if($data = call_mysql_fetch_array($sql)){
        $id_Data = $data['student_id_no'];
        if(!empty($data['student_id_no'])){
            $enroll_status = true;
        }
    }
}

$enrolled_courses = array();
$enrolled_fixed_courses = array();
$enrolled_offered_courses = array();

if (!empty($g_general_id) && $active_school_year_id > 0 && !empty($active_semester)) {
    $sql_enrolled_courses = "
        SELECT
            e.enrollment_id,
            e.teacher_class_id,
            e.subject_id,
            e.class_id,
            e.section_name,
            e.schedule,
            e.sem,
            e.status,
            s.subject_code,
            s.subject_title,
            s.unit AS subject_unit,
            c.unit AS curriculum_unit,
            c.pre_req,
            c.year_level AS curriculum_year_level,
            c.semester AS curriculum_semester,
            tc.year_level AS scheduled_year_level,
            cs.class_name,
            p.short_name AS offered_program
        FROM enrollments e
        LEFT JOIN subject s ON s.subject_id = e.subject_id
        LEFT JOIN teacher_class tc ON tc.teacher_class_id = e.teacher_class_id
        LEFT JOIN class_section cs ON cs.class_id = e.class_id
        LEFT JOIN programs p ON p.program_id = tc.program_id
        LEFT JOIN curriculum c
               ON c.curriculum_id = '" . escape($db_connect, $student_curriculum_id) . "'
              AND UPPER(TRIM(c.subject_code)) = UPPER(TRIM(s.subject_code))
        WHERE e.student_id_no = '" . escape($db_connect, $g_general_id) . "'
          AND e.school_year_id = '" . escape($db_connect, $active_school_year_id) . "'
          AND UPPER(e.sem) = UPPER('" . escape($db_connect, $active_semester) . "')
          AND e.status = 'Enrolled'
        ORDER BY e.enrollment_id ASC
    ";

    if ($query = call_mysql_query($sql_enrolled_courses)) {
        while ($data = call_mysql_fetch_array($query)) {
            $class_id = intVal($data['class_id'] ?? 0);
            $curriculum_year_level = intVal($data['curriculum_year_level'] ?? 0);
            $scheduled_year_level = intVal($data['scheduled_year_level'] ?? 0);
            $unit = intVal($data['curriculum_unit'] ?? 0);

            if ($unit <= 0) {
                $unit = intVal($data['subject_unit'] ?? 0);
            }

            $row = array(
                'enrollment_id' => intVal($data['enrollment_id'] ?? 0),
                'teacher_class_id' => intVal($data['teacher_class_id'] ?? 0),
                'subject_id' => intVal($data['subject_id'] ?? 0),
                'class_id' => $class_id,
                'class_name' => !empty($data['section_name']) ? $data['section_name'] : ($data['class_name'] ?? ''),
                'subject_code' => $data['subject_code'] ?? '',
                'subject_title' => $data['subject_title'] ?? '',
                'unit' => $unit,
                'pre_req' => $data['pre_req'] ?? '',
                'schedule' => $data['schedule'] ?? '',
                'section_text' => 'Enrolled',
                'offered_program' => $data['offered_program'] ?? '',
                'year_level' => $scheduled_year_level > 0 ? $scheduled_year_level : $curriculum_year_level,
                'curriculum_year_level' => $curriculum_year_level,
                'curriculum_semester' => $data['curriculum_semester'] ?? '',
                'status' => $data['status'] ?? 'Enrolled'
            );

            $enrolled_courses[] = $row;

            if ($student_class_id > 0 && $class_id > 0 && $class_id !== $student_class_id) {
                $enrolled_offered_courses[] = $row;
            } else {
                $enrolled_fixed_courses[] = $row;
            }
        }
    }
}

// $sql_back_subject_request = "SELECT backSubject_enroll_id FROM backSubject_enroll
// WHERE student_id_no = '".   escape($db_connect, $g_general_id)."'
// AND school_year_id = '".   escape($db_connect, $active_school_year_id)."'
// AND sem = '".   escape($db_connect, strtoupper($active_semester))."'
// AND status IN ('Pending', 'Approved')
// LIMIT 1
// ";

// if($sql = call_mysql_query($sql_back_subject_request)){
//     if($data = call_mysql_fetch_array($sql)){
//         if(!empty($data['backSubject_enroll_id'])){
//             $back_subject_request_status = true;
//         }
//     }
// }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>student/css/enrollment_status.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>

            <div class="container">
                <div class="page-inner">    
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php'; ## page header 
                    ?>

                    <!-- <?php
                    // Global banner indicating whether the currently
                    // selected term is open for enrollment or
                    // read-only. Uses $is_term_open and $current_sy.
                    include DOMAIN_PATH . '/global/term_status_banner.php';
                    ?> -->

                    <div class="alert text-black" id="enroll_period"></div>


                    <div class="row">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2 mb-0">
                                <label>ID Number</label>
                                <span class="fs-6 fw-bold" id="id_num"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2 mb-0">
                                <label>Student Name</label>
                                <span class="fs-6 fw-bold" id="full_name"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2 mb-0">
                                <label>Academic Status</label>
                                <span class="fs-6 fw-bold" id="student_class"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2 mb-0">
                                <label>Year Level</label>
                                <span class="fs-6 fw-bold" id="year_level"></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <div>
                            <!-- Container 1: Available Subjects -->
                            <div class="card card-round">
                                <div class="card-header bg-primary rounded-top-2 pb-1 pt-3">
                                    <div class="d-flex row align-items-center mb-2">
                                        <h4 id="available_subjects_title" class="fw-bolder mb-0 text-white">Available Subjects for Your Program</h4>
                                        <span class="text-body-secodary">Courses you need to enroll base on your curriculum</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <!-- <ul class="nav nav-tabs mb-3" id="subjects_tabs" role="tablist">
                                        <li class="nav-item" role="presentation" id="subjects_tab_available_item">
                                            <button class="nav-link active" id="subjects_tab_available" type="button" role="tab">
                                                Available&nbsp;<span class="badge bg-secondary rounded-pill" id="available_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                                        <li class="nav-item" role="presentation" id="subjects_tab_backlog_item">
                                            <button class="nav-link" id="subjects_tab_backlog" type="button" role="tab">
                                                Backlog&nbsp;<span class="badge bg-secondary rounded-pill" id="backlog_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation" id="subjects_tab_higher_item">
                                            <button class="nav-link" id="subjects_tab_higher" type="button" role="tab">
                                                Higher-Year&nbsp;<span class="badge bg-secondary rounded-pill" id="higher_year_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                    </ul> -->

                                    <div id="available_subjects_panel" class="subjects-tab-panel">
                          

                                        <div class="row mb-4">
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <label for="program_name" class="form-label fw-bold">Program</label>
                                                <input type="text" class="form-control" title="Program" id="program_name" readonly>
                                            </div>

                                            <div class="col-md-8">
                                                <label for="enrollPeriod" class="form-label fw-bold">Enrolling For</label>
                                                <input type="text" class="form-control" id="enrollPeriod" name="enrollPeriod" readonly>
                                            </div>
                                        </div>

                                        <div class="row mb-3">
                                            <div class="col-md-4" id="section_select_group">
                                                <label for="section" class="form-labe fw-bold">Section</label>
                                                <select name="section" id="section">
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="d-flex flex-column">
                                                    <div class="col-md-6">
                                                        <label for="required_units_label" class="form-label">Required Units: </label>
                                                        <span name="required_units_label" class="fw-bold" id="required_units_label">0</span>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label for="units_viewed" class="form-label">Reflected Units: </label>
                                                        <span name="units_viewed" class="fw-bold" id="units_viewed">0</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-md-4 d-flex align-items-end justify-content-end">
                                                <button class="btn btn-secondary" id="submitCourse" disabled></button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <div id="offered_subjects_table"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (strcasecmp($student_academic_status, 'Irregular') === 0): ?>
                            <div class="card card-round mt-4" id="offered_back_subjects_card">
                                <div class="card-header bg-warning rounded-top-2 pb-1 pt-3">
                                    <div class="d-flex row align-items-center mb-2">
                                        <h4 class="fw-bolder mb-0 text-dark">Offered Courses</h4>
                                        <span class="text-body-secondary">Applicable offered course base on your curriculum.</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex row mb-2 align-items-center">
                                        <div class="d-flex col-md-6 flex-column justify-content-evenly align-items-start">
                                            <div class="col-md-6">
                                                <label class="form-label">Missing Units: </label>
                                                <span class="fw-bold" id="back_required_units_label">0</span>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Total Units Selected: </label>
                                                <span class="fw-bold" id="back_target_units_label">0</span>
                                            </div>
                                        </div>

                                        <div class="d-flex col-md-6 justify-content-end">
                                            <button class="btn btn-secondary btn-sm fs-6" id="submitBackSubjects" disabled>Enroll Offered Courses</button>
                                        </div>
                                    </div>
                                    <div class="table-responsive">
                                        <div id="offered_back_subjects_table"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

            </div>
        </div>
    </div>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function(){
    function formatReadableDate(dateValue) {
        const dateObj = new Date(dateValue);

        return dateObj.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }
    
    function formatYearLevel(yearLevel) {
        switch (parseInt(yearLevel, 10)) {
            case 1:
                return "1st Year";
            case 2:
                return "2nd Year";
            case 3:
                return "3rd Year";
            case 4:
                return "4th Year";
            case 5:
                return "5th Year";
            default:
                return "Invalid";
        }
    }

    const enrollmentContext = <?php echo json_encode($enrollment_context); ?>;

    function getSelectedSectionId() {
        const selectEl = document.getElementById('section');
        if (!selectEl) return '';

        if (selectEl.selectize) {
            return selectEl.selectize.getValue() || '';
        }

        return selectEl.value || '';
    }

    function setSectionDropdownEnabled(enabled) {
        const sectionEl = document.getElementById('section');
        if (!sectionEl) return;

        if (sectionEl.selectize) {
            if (enabled) {
                sectionEl.selectize.enable();
            } else {
                sectionEl.selectize.disable();
            }
            return;
        }

        sectionEl.disabled = !enabled;
    }

    function populateSectionDropdown(selector, sections, selectedId = null) {
        const $dropdown = $(selector);
        if (!$dropdown.length) return;

        if ($dropdown[0].selectize) {
            $dropdown[0].selectize.destroy();
        }

        $dropdown.empty();
        $dropdown.append('<option value="" selected disabled>Select Section</option>');

        (sections || []).forEach(function(item) {
            $dropdown.append(
                $('<option>', {
                    value: item.class_id,
                    text: item.class_name
                })
            );
        });

        $dropdown.selectize({
            allowEmptyOption: true,
            create: false,
            sortField: 'text',
            onChange: function(value) {
                if (!value) return;
                loadOfferedSubjects(value);
                loadBackSubjects(value);
            }
        });

        const selectize = $dropdown[0].selectize;
        const finalValue = selectedId || '';

        if (finalValue) {
            selectize.setValue(String(finalValue), true);
        }

        setSectionDropdownEnabled(isEnrollmentPeriodOpen && enroll_status !== true);
    }

    function formatSchedule(scheduleValue) {
        if (!scheduleValue) return '';

        let parsed = scheduleValue;

        if (typeof parsed === 'string') {
            try {
                parsed = JSON.parse(parsed);
            } catch (e) {
                return parsed;
            }
        }

        if (!Array.isArray(parsed)) {
            return String(parsed);
        }

        return parsed.map(function(item) {
            return String(item)
                .replace(',', ' | ')
                .replaceAll('::', ' ')
                .replace(/\s+/g, ' ')
                .trim();
        }).join('<br>');
    }

    function loadingAPIrequest(status){
        if(status === true){
            swal({
                title: "Loading",
                icon: 'info',
                text: "Please wait",
                buttons:false,
                closeOnClickOutside: false,
                closeOnEsc: false
            });
        }
        if(status === false){
            swal.close();
        }

    }

    const enroll_status = <?php echo json_encode($enroll_status); ?>;
    const enrolledCoursesData = <?php echo json_encode($enrolled_courses); ?>;
    const enrolledFixedCoursesData = <?php echo json_encode($enrolled_fixed_courses); ?>;
    const enrolledOfferedCoursesData = <?php echo json_encode($enrolled_offered_courses); ?>;

    const offeredTable = new Tabulator('#offered_subjects_table', {
        pagination: "local",
        paginationSize: 10,
        movableColumns: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        layout: "fitDataStretch",
        minHeight: 250,
        ajaxResponse: function (url, params, response) {
            if (!response || response.msg_status !== true) {
                document.getElementById('required_units_label').textContent = enrollmentContext.display_required_units || 'N/A';

                if (document.getElementById('cart_section_label')) {
                    document.getElementById('cart_section_label').textContent = 'Not yet determined';
                }

                return [];
            }

            document.getElementById('required_units_label').textContent = response.required_units || enrollmentContext.display_required_units || 'N/A';
            document.getElementById('units_viewed').textContent = response.fixed_subject_units || 'N/A';

            if (document.getElementById('cart_section_label')) {
                document.getElementById('cart_section_label').textContent = response.base_section_label || 'Not yet determined';
            }

            return Array.isArray(response.data) ? response.data : [];
        },
        columns: [
            {
                title: "Status",
                field: "section_text",
                formatter: function (cell) {
                    return cell.getValue() === 'Enrolled'
                        ? '<span class="badge bg-primary">Enrolled</span>'
                        : '<span class="badge bg-success">Fixed</span>';
                },
                hozAlign: "center",
                headerHozAlign: "center"
            },
            {
                title: "Course Code",
                field: "subject_code",
                headerFilter: "input"
            },
            {
                title: "Course Title",
                field: "subject_title",
                headerFilter: "input"
            },
            {
                title: "Units",
                field: "unit",
                hozAlign: "center",
                headerFilter: "input"
            },
            {
                title: "Pre-req",
                field: "pre_req",
                headerFilter: "input"
            },
            {
                title: "Schedule",
                field: "schedule",
                headerFilter: "input",
                formatter: function(cell){
                    return formatSchedule(cell.getValue());
                }
            },
        ]
    });

    const primaryEnrollBtn = document.getElementById('submitCourse');
    const backSubjectSubmitBtn = document.getElementById('submitBackSubjects');
    let isEnrollmentPeriodOpen = false;
    const requiresSectionSelection = enrollmentContext.academic_status === 'Irregular';
    const isIrregularEnrollment = enrollmentContext.academic_status === 'Irregular';
    let backSubjectMissingUnits = 0;
    let backSubjectAvailableUnits = 0;
    let backSubjectsTable = null;

    function getSelectedBackSubjectRows() {
        return backSubjectsTable ? backSubjectsTable.getSelectedData() : [];
    }

    function sumBackSubjectUnits(rows) {
        return (rows || []).reduce((total, row) => total + (parseInt(row.unit, 10) || 0), 0);
    }

    function enrolledBadgeFormatter() {
        return '<span class="badge bg-primary">Enrolled</span>';
    }

    function updateBackSubjectSummary() {
        const backRequiredEl = document.getElementById('back_required_units_label');
        const backTargetEl = document.getElementById('back_target_units_label');
        const selectedUnits = sumBackSubjectUnits(getSelectedBackSubjectRows());

        if (backRequiredEl) backRequiredEl.textContent = backSubjectMissingUnits;
        if (backTargetEl) backTargetEl.textContent = selectedUnits;

        return selectedUnits;
    }

    function updateBackSubjectButtonState() {
        if (!backSubjectsTable) return;

        const selectedSectionId = getSelectedSectionId();
        const hasRows = backSubjectsTable.getDataCount() > 0;
        const selectedUnits = updateBackSubjectSummary();
        const hasSelections = getSelectedBackSubjectRows().length > 0;
        const underloadAllowed = backSubjectAvailableUnits > 0 && backSubjectAvailableUnits < backSubjectMissingUnits;
        const unitsMatch = backSubjectMissingUnits > 0 && (
            selectedUnits === backSubjectMissingUnits ||
            (underloadAllowed && selectedUnits === backSubjectAvailableUnits)
        );
        const canSubmit = isEnrollmentPeriodOpen && hasRows && selectedSectionId && hasSelections && unitsMatch;

        let buttonText = 'Enroll Offered Courses';

        if (!selectedSectionId) {
            buttonText = 'Select a section first';
        } else if (!hasRows) {
            buttonText = 'No offered courses available';
        } else if (backSubjectMissingUnits <= 0) {
            buttonText = 'No missing units';
        } else if (!hasSelections) {
            buttonText = 'Select offered courses';
        } else if (selectedUnits < backSubjectMissingUnits && !(underloadAllowed && selectedUnits === backSubjectAvailableUnits)) {
            buttonText = 'Select more offered courses';
        } else if (selectedUnits > backSubjectMissingUnits) {
            buttonText = 'Overloading not allowed';
        } else {
            buttonText = 'Enroll Selected Subjects';
        }

        if (backSubjectSubmitBtn) {
            backSubjectSubmitBtn.disabled = !canSubmit;
            backSubjectSubmitBtn.textContent = buttonText;
            backSubjectSubmitBtn.classList.remove('btn-success', 'btn-secondary');
            backSubjectSubmitBtn.classList.add(backSubjectSubmitBtn.disabled ? 'btn-secondary' : 'btn-success');
        }

        if (isIrregularEnrollment && primaryEnrollBtn && enroll_status !== true) {
            primaryEnrollBtn.disabled = !canSubmit;
            primaryEnrollBtn.textContent = buttonText;
            primaryEnrollBtn.classList.remove('btn-success', 'btn-secondary');
            primaryEnrollBtn.classList.add(primaryEnrollBtn.disabled ? 'btn-secondary' : 'btn-success');
        }
    }

    const backSubjectsTableEl = document.getElementById('offered_back_subjects_table');
    if (isIrregularEnrollment && backSubjectSubmitBtn) {
        backSubjectSubmitBtn.classList.add('d-none');
    }

    function buildBackSubjectColumns(isEnrolledMode = false) {
        const actionColumn = isEnrolledMode
            ? {
                title: "Status",
                field: "section_text",
                formatter: enrolledBadgeFormatter,
                hozAlign: "center",
                headerSort: false,
                width: 100,
                headerHozAlign:"center"
            }
            : {
                title: "Action",
                formatter: "rowSelection",
                titleFormatter: "rowSelection",
                hozAlign: "center",
                headerSort: false,
                width: 60,
                headerAlign: "center",
                headerHozAlign:"center"
            };

        return [
            actionColumn,
            {
                title: "Course Code",
                field: "subject_code",
                headerFilter: "input"
            },
            {
                title: "Course Title",
                field: "subject_title",
                headerFilter: "input"
            },
            {
                title: "Program Offered",
                field: "offered_program",
                headerFilter: "input",
                align: "center"
            },
            {
                title: "Year Level",
                field: "year_level",
                headerFilter: "input",
                align: "center"
            },
            {
                title: "Section",
                field: "class_name",
                headerFilter: "input"
            },
            {
                title: "Units",
                field: "unit",
                hozAlign: "center",
                headerFilter: "input"
            },
            {
                title: "Pre-req",
                field: "pre_req",
                headerFilter: "input"
            },
            {
                title: "Schedule",
                field: "schedule",
                headerFilter: "input",
                formatter: function(cell){
                    return formatSchedule(cell.getValue());
                }
            },
        ];
    }

    backSubjectsTable = backSubjectsTableEl ? new Tabulator('#offered_back_subjects_table', {
        pagination: "local",
        paginationSize: 10,
        movableColumns: true,
        selectableRows: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        layout: "fitDataStretch",
        minHeight: 120,
        ajaxResponse: function (url, params, response) {
            if (!response || response.msg_status !== true) {
                const backRequiredEl = document.getElementById('back_required_units_label');
                const backTargetEl = document.getElementById('back_target_units_label');

                backSubjectMissingUnits = 0;
                backSubjectAvailableUnits = 0;
                if (backRequiredEl) backRequiredEl.textContent = '0';
                if (backTargetEl) backTargetEl.textContent = '0';

                if (response && response.msg_response && getSelectedSectionId()) {
                    swal({
                        title: "Offered Courses Unavailable",
                        icon: 'info',
                        text: response.msg_response,
                        button: true
                    });
                }

                return [];
            }

            const backRequiredEl = document.getElementById('back_required_units_label');
            const backTargetEl = document.getElementById('back_target_units_label');

            backSubjectMissingUnits = parseInt(response.missing_units ?? 0, 10) || 0;
            backSubjectAvailableUnits = parseInt(response.available_offered_units ?? 0, 10) || 0;
            if (backRequiredEl) backRequiredEl.textContent = backSubjectMissingUnits;
            if (backTargetEl) backTargetEl.textContent = '0';

            setTimeout(updateBackSubjectButtonState, 0);

            return Array.isArray(response.data) ? response.data : [];
        },
        columns: buildBackSubjectColumns(false),
        rowSelectionChanged: function () {
            updateBackSubjectButtonState();
        }
    }) : null;

    function disableEnrollmentControls() {
        const submitCourseBtn = document.getElementById('submitCourse');

        if (submitCourseBtn) {
            submitCourseBtn.disabled = true;
            submitCourseBtn.textContent = 'Already Enrolled';
            submitCourseBtn.classList.remove('btn-success');
            submitCourseBtn.classList.add('btn-secondary');
        }

        if (backSubjectSubmitBtn) {
            backSubjectSubmitBtn.disabled = true;
            backSubjectSubmitBtn.textContent = 'Already Enrolled';
            backSubjectSubmitBtn.classList.remove('btn-success');
            backSubjectSubmitBtn.classList.add('btn-secondary');
        }

        const sectionEl = document.getElementById('section');

        if (sectionEl && sectionEl.selectize) {
            sectionEl.selectize.disable();
        } else if (sectionEl) {
            sectionEl.disabled = true;
        }
    }

    function setEnrolledFixedSection(fixedRows) {
        const sectionRow = (fixedRows || []).find(function(row) {
            return parseInt(row.class_id, 10) > 0;
        });

        if (!sectionRow) {
            return;
        }

        const sectionEl = document.getElementById('section');
        if (!sectionEl || !sectionEl.selectize) {
            if (sectionEl) sectionEl.value = sectionRow.class_id;
            return;
        }

        const sectionId = String(sectionRow.class_id);
        const sectionName = sectionRow.class_name || sectionRow.section_name || 'Selected Section';

        if (!sectionEl.selectize.options[sectionId]) {
            sectionEl.selectize.addOption({
                class_id: sectionId,
                class_name: sectionName,
                value: sectionId,
                text: sectionName
            });
            sectionEl.selectize.refreshOptions(false);
        }

        sectionEl.selectize.setValue(sectionId, true);
    }

    function markRowsAsEnrolled(rows) {
        return (rows || []).map(function(row) {
            return Object.assign({}, row, {
                section_text: 'Enrolled',
                status: 'Enrolled'
            });
        });
    }

    function applyEnrolledCourses(fixedRowsInput, offeredRowsInput) {
        const fixedRows = markRowsAsEnrolled(fixedRowsInput);
        const offeredRows = markRowsAsEnrolled(offeredRowsInput);
        const fixedUnits = fixedRows.reduce((total, row) => total + (parseInt(row.unit, 10) || 0), 0);
        const offeredUnits = offeredRows.reduce((total, row) => total + (parseInt(row.unit, 10) || 0), 0);

        document.getElementById('available_subjects_title').textContent = 'Enrolled Subjects';
        document.getElementById('required_units_label').textContent = enrollmentContext.display_required_units || 'N/A';
        document.getElementById('units_viewed').textContent = fixedUnits || 'N/A';

        offeredTable.setData(fixedRows);
        setEnrolledFixedSection(fixedRows);

        if (backSubjectsTable) {
            backSubjectMissingUnits = 0;
            backSubjectAvailableUnits = offeredUnits;
            backSubjectsTable.setColumns(buildBackSubjectColumns(true));
            backSubjectsTable.setData(offeredRows);

            const backRequiredEl = document.getElementById('back_required_units_label');
            const backTargetEl = document.getElementById('back_target_units_label');
            if (backRequiredEl) backRequiredEl.textContent = '0';
            if (backTargetEl) backTargetEl.textContent = offeredUnits;
        }

        disableEnrollmentControls();
    }

    function applyEnrolledCoursesFromDb() {
        const fixedRows = enrolledFixedCoursesData.length > 0 ? enrolledFixedCoursesData : enrolledCoursesData;
        const offeredRows = enrolledOfferedCoursesData;
        applyEnrolledCourses(fixedRows, offeredRows);
    }

    function loadOfferedSubjects(selectedClassIdOverride = null) {
        const selectedClassId = selectedClassIdOverride || getSelectedSectionId();


        if (!selectedClassId) {
            offeredTable.clearData();
            document.getElementById('required_units_label').textContent = enrollmentContext.display_required_units || 0;

            if (document.getElementById('cart_section_label')) {
                document.getElementById('cart_section_label').textContent = 'Not yet determined';
            }

            return;
        }

        offeredTable.setData(
            "<?php echo BASE_URL; ?>student/actions/fetchEligibleSections.php",
            {
                selected_class_id: selectedClassId
            }
        );
    }

    function loadBackSubjects(selectedClassIdOverride = null) {
        if (!backSubjectsTable) return;

        const selectedClassId = selectedClassIdOverride || getSelectedSectionId();

        if (!selectedClassId) {
            backSubjectsTable.clearData();
            const backRequiredEl = document.getElementById('back_required_units_label');
            const backTargetEl = document.getElementById('back_target_units_label');

            backSubjectMissingUnits = 0;
            backSubjectAvailableUnits = 0;
            if (backRequiredEl) backRequiredEl.textContent = '0';
            if (backTargetEl) backTargetEl.textContent = '0';

            updateBackSubjectButtonState();
            return;
        }

        backSubjectsTable.setData(
            "<?php echo BASE_URL; ?>student/actions/backSubjects.php",
            {
                selected_class_id: selectedClassId
            }
        );
    }

    function bootstrapSections() {  
        $.ajax({
            url: "<?php echo BASE_URL; ?>student/actions/fetchEligibleSections.php",
            method: "GET",
            dataType: "json",
            success: function (response) {
                if (!response || response.msg_status !== true) {
                    return;
                }

                const preferredSectionId = response.requires_section_selection ? '' : (response.selected_class_id || '');
                populateSectionDropdown('#section', response.sections || [], preferredSectionId);

                if (document.getElementById('cart_section_label')) {
                    document.getElementById('cart_section_label').textContent = response.base_section_label || 'Not yet determined';
                }

                if (response.incoming_year_level) {
                    document.getElementById('year_level').textContent = formatYearLevel(response.incoming_year_level);
                }

                if (enroll_status === true) {
                    applyEnrolledCoursesFromDb();
                    return;
                }

                if (!response.requires_section_selection || response.selected_class_id) {
                    loadOfferedSubjects(response.selected_class_id || preferredSectionId || '');
                    loadBackSubjects(response.selected_class_id || preferredSectionId || '');
                } else {
                    offeredTable.clearData();
                    loadBackSubjects();
                }
            }
        });
    }

        bootstrapSections();
        const student_prog = <?php echo json_encode($student_program). "\n "; ?>
        const fy_data = <?php echo json_encode($active_fiscalYear). "\n "; ?>
        const student_data = <?php echo json_encode($student_user); ?>
        
        console.log("fiscal yaer:", fy_data);

        function parseDateOnly(dateValue) {
            if (!dateValue) return null;
            const parsedDate = new Date(dateValue + "T00:00:00");
            return isNaN(parsedDate.getTime()) ? null : parsedDate;
        }

        const today = parseDateOnly(<?php echo json_encode(DATE_NOW); ?>);
        const enrollmentStart = parseDateOnly(fy_data[0]?.enrollment_start_date || "");
        const enrollmentEnd = parseDateOnly(fy_data[0]?.enrollment_end_date || "");
        const hasEnrollmentPeriod = enrollmentStart !== null && enrollmentEnd !== null;
        const startEnrollPeriod = hasEnrollmentPeriod ? formatReadableDate(enrollmentStart) : "";
        const endEnrollPeriod = hasEnrollmentPeriod ? formatReadableDate(enrollmentEnd) : "";
        const isEnrollmentPeriod = hasEnrollmentPeriod && today >= enrollmentStart && today <= enrollmentEnd;
        const alertBox = document.getElementById('enroll_period');

        if (isEnrollmentPeriod) {
            alertBox.className = "alert alert-success text-black";
            alertBox.innerHTML = `
                <span class="fs-4 fw-bold">Enrollment Period Ongoing</span><br>
                <label class="fw-bold text-black">Term: ${fy_data[0].school_year} ${fy_data[0].sem}</label><br>
                Enrollment is open from ${startEnrollPeriod} to ${endEnrollPeriod}.
            `;

            document.getElementById('submitCourse').disabled = false;
            document.getElementById('submitCourse').textContent = 'Enroll Available Subjects';
            document.getElementById('submitCourse').classList.remove('btn-secondary');
            document.getElementById('submitCourse').classList.add('btn-success');
            isEnrollmentPeriodOpen = true;
            setSectionDropdownEnabled(enroll_status !== true);
        } else {
            alertBox.className = "alert alert-danger text-black";
            if (hasEnrollmentPeriod) {
                alertBox.innerHTML = `
                    <span class="fs-4 fw-bold">Enrollment Period Closed</span><br>
                    Enrollment period is from ${startEnrollPeriod} to ${endEnrollPeriod}.
                `;
            } else {
                alertBox.innerHTML = `
                    <span class="fs-4 fw-bold">Enrollment Period Closed</span><br>
                    Enrollment period has not been configured for ${fy_data[0].school_year} ${fy_data[0].sem}.
                `;
            }
            document.getElementById('submitCourse').disabled = true;
            document.getElementById('submitCourse').textContent = 'Enrollment Period Closed';
            document.getElementById('submitCourse').classList.remove('btn-success');
            document.getElementById('submitCourse').classList.add('btn-secondary');
            isEnrollmentPeriodOpen = false;
            setSectionDropdownEnabled(false);
        }

        updateBackSubjectButtonState();

        const student_classification = <?php echo json_encode($student_academic_status). " \n "; ?>;
        console.log('student: ', student_data)
        document.getElementById('id_num').textContent = student_data[0].student_id_no;
        document.getElementById('full_name').textContent = `${student_data[0].lastname}, ${student_data[0].firstname} ${student_data[0].middle_name}`;
        document.getElementById('student_class').textContent = `${student_classification}`;
        document.getElementById('year_level').textContent = formatYearLevel(enrollmentContext.tracked_year_level || student_data[0].year_level);
        document.getElementById('program_name').value = student_prog;
        document.getElementById('enrollPeriod').value = `F.Y. ${fy_data[0].school_year}  ${fy_data[0].sem}`

        function validateSelectedOfferedCourses() {
            if (!backSubjectsTable) {
                return {
                    valid: false,
                    title: "Offered Courses Unavailable",
                    text: "No offered courses table is available for this enrollment."
                };
            }

            const selectedSectionId = getSelectedSectionId();
            const selectedOfferedRows = getSelectedBackSubjectRows();
            const selectedUnits = sumBackSubjectUnits(selectedOfferedRows);

            if (!selectedSectionId) {
                return {
                    valid: false,
                    title: "Section Required",
                    text: "Please select a section before enrolling."
                };
            }

            if (!Array.isArray(selectedOfferedRows) || selectedOfferedRows.length === 0) {
                return {
                    valid: false,
                    title: "Selection Required",
                    text: "Please select the offered courses you want to include in this enrollment."
                };
            }

            const underloadAllowed = backSubjectAvailableUnits > 0 && backSubjectAvailableUnits < backSubjectMissingUnits;
            const unitsValid = selectedUnits === backSubjectMissingUnits || (underloadAllowed && selectedUnits === backSubjectAvailableUnits);

            if (!unitsValid) {
                return {
                    valid: false,
                    title: "Unit Validation",
                    text: underloadAllowed
                        ? `Please select all available offered units (${backSubjectAvailableUnits}) to underload this term.`
                        : `Selected offered-course units must exactly match your missing units (${backSubjectMissingUnits}).`
                };
            }

            return {
                valid: true,
                selectedSectionId,
                selectedOfferedRows
            };
        }
        
        $('#submitCourse').on('click', function(){
            const tableData = offeredTable.getData();
            console.log(JSON.stringify(tableData))
            console.log("context: ", enrollmentContext)

            if (isIrregularEnrollment) {
                const validation = validateSelectedOfferedCourses();
                if (!validation.valid) {
                    swal({
                        title: validation.title,
                        icon: 'info',
                        text: validation.text,
                        button: true
                    });
                    return;
                }

                const postData = [
                    {
                        name: "submitBackSubjectEnrollment",
                        value: "createIrregularEnrollment"
                    },
                    {
                        name: "school_year_id",
                        value: enrollmentContext.school_year_id
                    },
                    {
                        name: "selected_class_id",
                        value: validation.selectedSectionId
                    },
                    {
                        name: "idNumber",
                        value: enrollmentContext.student_id_no
                    },
                    {
                        name: "fixedCourses",
                        value: JSON.stringify(tableData)
                    },
                    {
                        name: "backSubjectCourses",
                        value: JSON.stringify(validation.selectedOfferedRows)
                    },
                    {
                        name: "curriculum_id",
                        value: enrollmentContext.curriculum_id
                    },
                    {
                        name: "semester",
                        value: enrollmentContext.semester
                    },
                    {
                        name: "program_id",
                        value: enrollmentContext.program_id
                    }
                ];

                $.ajax({
                    url: "<?php echo BASE_URL; ?>student/actions/enroll_backSubject_process.php",
                    method: "POST",
                    data: postData,
                    dataType: "json",
                    success: function(data){
                        if (data && data.code === 200 && data.msg_status === true) {
                            swal({
                                title: "Enrollment Successful",
                                icon: 'success',
                                text: data.msg_response,
                                button: true
                            }).then(function () {
                                applyEnrolledCourses(tableData, validation.selectedOfferedRows);
                            });
                        } else {
                            swal({
                                title: "Enrollment Failed",
                                icon: 'error',
                                text: data?.msg_response || 'Unable to save enrollment.',
                                button: true
                            });
                        }
                    },
                    error: function(){
                        swal({
                            title: "Error",
                            icon: "error",
                            text: "Network/Server error occured",
                            button:true
                        });
                    }
                });

                return;
            }

            const postData = [
                {
                    name: "submitEnrollment",
                    value: "createEnrollment"
                },
                {
                    name: "school_year_id",
                    value: enrollmentContext.school_year_id
                },
                {
                    name: "selected_class_id",
                    value: getSelectedSectionId()
                },
                {
                    name: "idNumber",
                    value: enrollmentContext.student_id_no
                },
                {
                    name: "enrollCourses",
                    value: JSON.stringify(tableData)
                },
                {
                    name: "curriculum_id",
                    value: enrollmentContext.curriculum_id
                },
                {
                    name: "semester",
                    value: enrollmentContext.semester
                },
                {
                    name: "program_id",
                    value: enrollmentContext.program_id
                }
            ];

            $.ajax({
                url: "<?php echo BASE_URL; ?>student/actions/enroll_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                success: function(data){
                    if(data){
                        if(data.code === 200 && data.msg_status === true){
                            swal({
                                title: "Enrollment Successful",
                                icon: 'success',
                                text: data.msg_response,
                                button: true
                            }).then(function () {
                                applyEnrolledCourses(tableData, []);
                            });
                        } else {
                            swal({
                                title: "Enrollment Failedl",
                                icon: 'error',
                                text: data.msg_response,
                                button: true
                            })
                        }
                    }
                },
                error: function(xhr, status, error){
                    swal.close();
                    swal({
                        title: "Error",
                        icon: "error",
                        text: "Network/Server error occured",
                        button:true
                    })
                }
            })

        });

        $('#submitBackSubjects').on('click', function(){
            $('#submitCourse').trigger('click');
        });

        if(enroll_status === true) {
            applyEnrolledCoursesFromDb();
        }


})
</script>
</body>
</html>
