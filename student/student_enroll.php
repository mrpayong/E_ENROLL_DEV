<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Enrollment";
$get_user_value = strtoupper($_GET['none'] ?? '');
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [];

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

$sql_fy = "SELECT school_year_id, school_year, sem, date_from, date_to, flag_used 
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

$sql_student = "SELECT student_id, student_id_no, firstname, middle_name, lastname, year_level, curriculum_id, program_id
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

    $display_required_units = intVal($curriculumByYearSem[$target_year_level_for_display][$active_semester]['required_units'] ?? 0);
}

$enrollment_context = [
    'student_id_no' => $g_general_id,
    'program_id' => $student_program_id,
    'curriculum_id' => $student_curriculum_id,
    'student_year_level' => $student_year_level,
    'effective_year_level' => $effective_year_level,
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
AND sem = '".   escape($db_connect, stripos($active_semester, '1st') !== false ? 1 : 2)."'
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

$sql_back_subject_request = "SELECT backSubject_enroll_id FROM backSubject_enroll
WHERE student_id_no = '".   escape($db_connect, $g_general_id)."'
AND school_year_id = '".   escape($db_connect, $active_school_year_id)."'
AND sem = '".   escape($db_connect, strtoupper($active_semester))."'
AND status IN ('Pending', 'Approved')
LIMIT 1
";

if($sql = call_mysql_query($sql_back_subject_request)){
    if($data = call_mysql_fetch_array($sql)){
        if(!empty($data['backSubject_enroll_id'])){
            $back_subject_request_status = true;
        }
    }
}

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
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 id="available_subjects_title" class="fw-bolder mb-0 text-white">Available Subjects for Your Program</h4>
                                        </div></div>
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
                                                <label for="required_units_label" class="form-label">Required Units: </label>
                                                <span name="required_units_label" class="fw-bold" id="required_units_label"></span>
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
                                        <h4 class="fw-bolder mb-0 text-dark">Suggested Subjects</h4>
                                        <span>For This Term</span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row mb-3 d-flex justify-content-evenly align-items center">
                                        <div class="col-md-4">
                                            <label class="form-label">Missing Units: </label>
                                            <span class="fw-bold" id="back_required_units_label">0</span>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Required Units: </label>
                                            <span class="fw-bold" id="back_target_units_label">0</span>
                                        </div>

                                        <!-- <div class="col-md-4 d-flex justify-content-end">
                                            <button class="btn btn-secondary" id="submitBackSubjects" disabled>Request back subjects for Dean approval</button>
                                        </div> -->
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

    function populateSectionDropdown(selector, sections, selectedId = null) {
        const $dropdown = $(selector);
        if (!$dropdown.length) return;

        const currentValue = $dropdown.val();

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
                loadOfferedSubjects();
                loadBackSubjects();
            }
        });

        const selectize = $dropdown[0].selectize;
        const finalValue = selectedId || currentValue || '';

        if (finalValue) {
            selectize.setValue(String(finalValue), true);
        }
    }

    // const offeredTable = new Tabulator('#offered_subjects_table', {
    //     ajaxURL: "<?php echo BASE_URL; ?>student/actions/fetchEligibleSections.php",
    //     ajaxConfig: "GET",
    //     pagination: "remote",
    //     paginationSize: 10,
    //     movableColumns: true,
    //     ajaxFiltering: true,
    //     ajaxSorting: true,
    //     headerFilterPlaceholder: "Search",
    //     placeholder: "No Data Found",
    //     layout: "fitDataStretch",
    //     minHeight: 150,
    //     ajaxResponse: function (url, params, response) {
    //         if (!response || response.msg_status !== true) {
    //             return [];
    //         }

    //         document.getElementById('required_units_label').textContent = response.required_units ?? 'N/A';

    //         if (document.getElementById('cart_section_label')) {
    //             document.getElementById('cart_section_label').textContent = response.base_section_label || 'Not yet determined';
    //         }

    //         return Array.isArray(response.data) ? response.data : [];
    //     },
    //     columns: [
    //         {
    //             title: "Course Code",
    //             field: "subject_code",
    //             headerFilter: "input"
    //         },
    //         {
    //             title: "Course Title",
    //             field: "subject_title",
    //             headerFilter: "input"
    //         },
    //         {
    //             title: "Schedule",
    //             field: "schedule",
    //             headerFilter: "input"
    //         },
    //         {
    //             title: "Units",
    //             field: "unit",
    //             hozAlign: "center",
    //             headerFilter: "input"
    //         },
    //         {
    //             title: "Pre-req",
    //             field: "pre_req",
    //             headerFilter: "input"
    //         },
    //         {
    //             title: "Action",
    //             field: "section_text",
    //             formatter: function () {
    //                 return '<span class="badge bg-success">Fixed</span>';
    //             },
    //             hozAlign: "center"
    //         }
    //     ]

    // })

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

            document.getElementById('required_units_label').textContent = enrollmentContext.display_required_units || response.required_units || 'N/A';

            if (document.getElementById('cart_section_label')) {
                document.getElementById('cart_section_label').textContent = response.base_section_label || 'Not yet determined';
            }

            return Array.isArray(response.data) ? response.data : [];
        },
        columns: [
            {
                title: "Action",
                field: "section_text",
                formatter: function () {
                    return '<span class="badge bg-success">Fixed</span>';
                },
                hozAlign: "center"
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

    const backSubjectSubmitBtn = document.getElementById('submitBackSubjects');
    let isEnrollmentPeriodOpen = false;
    let hasBackSubjectRequest = <?php echo json_encode($back_subject_request_status); ?>;

    function updateBackSubjectButtonState() {
        if (!backSubjectSubmitBtn || !backSubjectsTable) return;

        const hasRows = backSubjectsTable.getDataCount() > 0;
        backSubjectSubmitBtn.disabled = hasBackSubjectRequest || !(isEnrollmentPeriodOpen && hasRows);
        backSubjectSubmitBtn.textContent = hasBackSubjectRequest
            ? 'Subject offer request submitted'
            : (hasRows ? 'Request for Subject Offer to Dean' : 'No back subjects to request');
        backSubjectSubmitBtn.classList.remove('btn-success', 'btn-secondary');
        backSubjectSubmitBtn.classList.add(backSubjectSubmitBtn.disabled ? 'btn-secondary' : 'btn-success');
    }

    const backSubjectsTableEl = document.getElementById('offered_back_subjects_table');
    const backSubjectsTable = backSubjectsTableEl ? new Tabulator('#offered_back_subjects_table', {
        pagination: "local",
        paginationSize: 10,
        movableColumns: true,
        headerFilterPlaceholder: "Search",
        placeholder: "No Data Found",
        layout: "fitDataStretch",
        minHeight: 120,
        ajaxResponse: function (url, params, response) {
            if (!response || response.msg_status !== true) {
                const backRequiredEl = document.getElementById('back_required_units_label');
                const backTargetEl = document.getElementById('back_target_units_label');

                if (backRequiredEl) backRequiredEl.textContent = '0';
                if (backTargetEl) backTargetEl.textContent = '0';

                return [];
            }

            const backRequiredEl = document.getElementById('back_required_units_label');
            const backTargetEl = document.getElementById('back_target_units_label');

            if (backRequiredEl) backRequiredEl.textContent = response.missing_units ?? 0;
            if (backTargetEl) backTargetEl.textContent = response.required_units ?? 0;

            setTimeout(updateBackSubjectButtonState, 0);

            return Array.isArray(response.data) ? response.data : [];
        },
        columns: [
            {
                title: "Action",
                field: "section_text",
                formatter: function () {
                    return '<span class="badge bg-warning text-dark">Offered Back</span>';
                },
                hozAlign: "center"
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
                title: "Offered By",
                field: "offered_program",
                headerFilter: "input"
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
        ]
    }) : null;

    function loadOfferedSubjects() {
        const selectedClassId = getSelectedSectionId();


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

    function loadBackSubjects() {
        if (!backSubjectsTable) return;

        const selectedClassId = getSelectedSectionId();

        backSubjectsTable.setData(
            "<?php echo BASE_URL; ?>student/actions/backSubjects.php",
            {
                selected_class_id: hasBackSubjectRequest ? '' : selectedClassId
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

                populateSectionDropdown('#section', response.sections || [], response.selected_class_id || '');

                if (document.getElementById('cart_section_label')) {
                    document.getElementById('cart_section_label').textContent = response.base_section_label || 'Not yet determined';
                }

                loadOfferedSubjects();
                loadBackSubjects();
            }
        });
    }

        bootstrapSections();
        const student_prog = <?php echo json_encode($student_program). "\n "; ?>
        const fy_data = <?php echo json_encode($active_fiscalYear). "\n "; ?>
        const student_data = <?php echo json_encode($student_user); ?>
        
        console.log("fiscal yaer:", fy_data);

        // 2026-01-30T00:00:00
        const today = new Date('2026-01-30T00:00:00');
        const dateFrom = new Date(fy_data[0].date_from + "T00:00:00");
        dateFrom.setHours(0, 0, 0, 0);

        const enrollmentStart = new Date(dateFrom);
        enrollmentStart.setDate(enrollmentStart.getDate() - 14);

        const startEnrollPeriod = formatReadableDate(enrollmentStart);
        const endEnrollPeriod = formatReadableDate(dateFrom);


        const isEnrollmentPeriod = today >= enrollmentStart && today < dateFrom;

        const alertBox = document.getElementById('enroll_period');

        if (isEnrollmentPeriod) {
            alertBox.className = "alert alert-success text-black";
            alertBox.innerHTML = `
                <span class="fs-4 fw-bold">Enrollment Period Ongoing</span><br>
                <label class="fw-bold text-black">Term: ${fy_data[0].school_year} ${fy_data[0].sem}</label><br>
                Enrollment is open from ${startEnrollPeriod} to ${endEnrollPeriod}.
            `;

            document.getElementById('submitCourse').disabled = false;
            document.getElementById('submitCourse').textContent = 'Enroll Courses and Create Request';
            document.getElementById('submitCourse').classList.remove('btn-secondary');
            document.getElementById('submitCourse').classList.add('btn-success');
            isEnrollmentPeriodOpen = true;
        } else {
            alertBox.className = "alert alert-danger text-black";
            alertBox.innerHTML = `  
                <span class="fs-4 fw-bold">Enrollment Period Closed</span><br>
                Enrollment period is from ${startEnrollPeriod} to ${endEnrollPeriod}.
            `;
            document.getElementById('submitCourse').disabled = true;
            document.getElementById('submitCourse').textContent = 'Enrollment Period Closed';
            document.getElementById('submitCourse').classList.remove('btn-success');
            document.getElementById('submitCourse').classList.add('btn-secondary');
            isEnrollmentPeriodOpen = false;
        }

        updateBackSubjectButtonState();

        const student_classification = <?php echo json_encode($student_academic_status). " \n "; ?>;
        console.log('student: ', student_data)
        document.getElementById('id_num').textContent = student_data[0].student_id_no;
        document.getElementById('full_name').textContent = `${student_data[0].lastname}, ${student_data[0].firstname} ${student_data[0].middle_name}`;
        document.getElementById('student_class').textContent = `${student_classification}`;
        document.getElementById('year_level').textContent = formatYearLevel(student_data[0].year_level);
        document.getElementById('program_name').value = student_prog;
        document.getElementById('enrollPeriod').value = `F.Y. ${fy_data[0].school_year}  ${fy_data[0].sem}`
        
        $('#submitCourse').on('click', function(){
            const tableData = offeredTable.getData();
            console.log(JSON.stringify(tableData))
            console.log("context: ", enrollmentContext)
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
                            })

                            document.getElementById('available_subjects_title').textContent = 'Enrolled Courses';
                            document.getElementById('submitCourse').disabled = true;
                            document.getElementById('submitCourse').textContent = 'Already Enrolled';
                            document.getElementById('submitCourse').classList.remove('btn-success');
                            document.getElementById('submitCourse').classList.add('btn-secondary');

                            const sectionEl = document.getElementById('section');
                            if (sectionEl && sectionEl.selectize) {
                                sectionEl.selectize.disable();
                            } else if (sectionEl) {
                                sectionEl.disabled = true;
                            }
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

        $('#submitCourse').on('click', function(){
            if (!backSubjectsTable) return;

            const tableData = backSubjectsTable.getData();

            if (!Array.isArray(tableData) || tableData.length === 0) {
                swal({
                    title: "No Back Subjects",
                    icon: 'info',
                    text: "No offered back subjects are available to request.",
                    button: true
                });
                return;
            }

            const postData = [
                {
                    name: "submitBackSubjectEnrollment",
                    value: "createBackSubjectRequest"
                },
                {
                    name: "school_year_id",
                    value: enrollmentContext.school_year_id
                },
                {
                    name: "idNumber",
                    value: enrollmentContext.student_id_no
                },
                {
                    name: "backSubjectCourses",
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
                url: "<?php echo BASE_URL; ?>student/actions/enroll_backSubject_process.php",
                method: "POST",
                data: postData,
                dataType: "json",
                success: function(data){
                    if (data && data.code === 200 && data.msg_status === true) {
                        swal({
                            title: "Done!",
                            icon: 'success',
                            text: "Request has been sent and fixed courses has been enrolled.",
                            button: true
                        });

                        if (backSubjectSubmitBtn) {
                            hasBackSubjectRequest = true;
                            backSubjectSubmitBtn.disabled = true;
                            backSubjectSubmitBtn.textContent = 'Subject offer request submitted';
                            backSubjectSubmitBtn.classList.remove('btn-success');
                            backSubjectSubmitBtn.classList.add('btn-secondary');
                        }
                    } else {
                        swal({
                            title: "Request Failed",
                            icon: 'error',
                            text: data?.msg_response || 'Unable to submit back subject request.',
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
        });

        const enroll_status = <?php echo json_encode($enroll_status); ?>;

        if(enroll_status === true) {
            document.getElementById('available_subjects_title').textContent = 'Enrolled Subjects';
            document.getElementById('submitCourse').disabled = true;
            document.getElementById('submitCourse').textContent = 'Already Enrolled';
            document.getElementById('submitCourse').classList.remove('btn-success');
            document.getElementById('submitCourse').classList.add('btn-secondary');
            const sectionEl = document.getElementById('section');

            if (sectionEl && sectionEl.selectize) {
                sectionEl.selectize.disable();
            } else if (sectionEl) {
                sectionEl.disabled = true;
            }
        }


})
</script>
</body>
</html>
