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
WHERE isDefault = '".escape($db_connect, 1)."'
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
];

$enroll_status = false;
$sql_enroll = "SELECT student_id_no FROM enrollments
WHERE student_id_no = '".   escape($db_connect, $g_general_id)."'
AND school_year_id = '".   escape($db_connect, $active_school_year_id)."'
AND sem = '".   escape($db_connect, strtoupper($active_semester))."'
LIMIT 1
";

if($sql = call_mysql_query($sql_enroll)){
    if($data = call_mysql_fetch_array($sql)){
        if(empty($data['student_id_no'])){
            $enroll_status = true;
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


                    <div class="row mb-4">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <label class="info-label">ID Number</label>
                                <span class="fs-6 fw-bold" id="id_num"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Student Name</div>
                                <span class="fs-6 fw-bold" id="full_name"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Academic Status</div>
                                <span class="fs-6 fw-bold" id="student_class"></span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Year Level</div>
                                <span class="fs-6 fw-bold" id="year_level"></span>
                            </div>
                        </div>
                    </div>

                    <div class="row subjects-container-scroll">
                        <div class="<?php echo strcasecmp($student_academic_status, 'Regular') === 0 ? 'col-md-12' : 'col-md-8'; ?>">
                            <!-- Container 1: Available Subjects -->
                            <div class="card card-round">
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3" id="subjects_tabs" role="tablist">
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
                                    </ul>

                                    <div id="available_subjects_panel" class="subjects-tab-panel">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 id="available_subjects_title" class="fw-bold mb-0">Available Subjects for Your Program</h4>
                                        </div>

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
                                                <button class="btn btn-success" id="submitCourse">Enroll courses at this section</button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <div id="offered_subjects_table"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        
                        <!-- <div class="col-md-4">
                            <div class="card subject-cart-card">
                                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-book-open me-2"></i> My Subject Cart</h5>
                                    <span class="badge bg-primary rounded-pill" id="cart_count">0</span>
                                </div>
                                
                                <div class="cart-body-container">
                                    <div class="empty-cart-state" id="cart_empty_state">
                                        <i class="fas fa-book fa-3x mb-2"></i>
                                        <p class="mb-0">Subject is empty.</p>
                                    </div>
                                    
                                    <div id="cart_list" style="display:none;">
                                        <div id="cart_items"></div>
                                    </div>
                                </div>
                                
                                <div class="btn-proceed-container">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Required Units:</span>
                                        <span class="fw-bold" id="required_units_label">N/A</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Total Units:</span>
                                        <span class="fw-bold text-primary" id="cart_total_units">0.0</span>
                                    </div>
                                    <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3" id="cart_section_row">
                                        <span class="fw-bold">Your Section Classification:</span>
                                        <span id="cart_section_label" class="ms-2">Not yet determined</span>
                                    </div>
                                    <?php endif; ?>
                                    <button class="btn btn-proceed">ENROLL IN THIS CLASS SECTION</button>
                                </div>
                            </div>
                        </div> -->

                        <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                            <div class="col-md-4">
                                <div class="card subject-cart-card">
                                    <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                        <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-book-open me-2"></i> My Subject Cart</h5>
                                        <span class="badge bg-primary rounded-pill" id="cart_count">0</span>
                                    </div>
                                    
                                    <div class="cart-body-container">
                                        <div class="empty-cart-state" id="cart_empty_state">
                                            <i class="fas fa-book fa-3x mb-2"></i>
                                            <p class="mb-0">Subject is empty.</p>
                                        </div>
                                        
                                        <div id="cart_list" style="display:none;">
                                            <div id="cart_items"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="btn-proceed-container">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="fw-bold">Required Units:</span>
                                            <span class="fw-bold" id="required_units_label">N/A</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span class="fw-bold">Total Units:</span>
                                            <span class="fw-bold text-primary" id="cart_total_units">0.0</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-3" id="cart_section_row">
                                            <span class="fw-bold">Your Section Classification:</span>
                                            <span id="cart_section_label" class="ms-2">Not yet determined</span>
                                        </div>
                                        <button class="btn btn-proceed">ENROLL IN THIS CLASS SECTION</button>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                            <!-- Container 2: Backlog / Higher-Year Subjects (shown only when applicable) -->
                            <div class="card card-round" id="backlog_container_card">
                                <div class="card-body">
                                    <div class="subjects-tab-panel" id="backlog_subjects_card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="fw-bold mb-0">Backlog Subjects Offered This Term</h4>
                                        </div>
                                        <p id="backlog_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-warning"></i>
                                            These are backlog subject(s) from previous term(s) that are offered this term. You may add them to your cart as needed.
                                        </p>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="backlog_subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="backlog_schedule_header">Schedule</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    <tr id="backlog_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="backlog_subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No backlog subjects available for this term.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Container 3: Higher-Year Subjects (shown only for irregular students when applicable) -->
                            <div class="card card-round mt-4" id="higher_year_container_card">
                                <div class="card-body">
                                    <div class="subjects-tab-panel" id="higher_year_subjects_card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="fw-bold mb-0">Higher-Year Subjects Offered This Term</h4>
                                        </div>
                                        <p id="higher_year_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-info"></i>
                                            These are higher-year subject(s) without pre-requisites that are offered this term.
                                        </p>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="higher_year_subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="higher_year_schedule_header">Schedule</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    <tr id="higher_year_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="higher_year_subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No higher-year subjects available for this term.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
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
        const dateObj = new Date(dateValue + "T00:00:00");

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
                document.getElementById('required_units_label').textContent = 'N/A';

                if (document.getElementById('cart_section_label')) {
                    document.getElementById('cart_section_label').textContent = 'Not yet determined';
                }

                return [];
            }

            document.getElementById('required_units_label').textContent = response.required_units ?? 'N/A';

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

    function loadOfferedSubjects() {
        const selectedClassId = getSelectedSectionId();


        if (!selectedClassId) {
            offeredTable.clearData();
            document.getElementById('required_units_label').textContent = 0;

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
            }
        });
    }

        bootstrapSections();
        const student_prog = <?php echo json_encode($student_program). "\n "; ?>
        const fy_data = <?php echo json_encode($active_fiscalYear). "\n "; ?>
        const student_data = <?php echo json_encode($student_user); ?>
        
        console.log("fiscal yaer:", fy_data);

        const today = new Date();
        const dateFrom = new Date(fy_data[0].date_from + "T00:00:00");
        const dateTo = new Date(fy_data[0].date_to + "T23:59:59");

        const formattedDateFrom = formatReadableDate(fy_data[0].date_from);
        const formattedDateTo = formatReadableDate(fy_data[0].date_to);

        const isEnrollmentPeriod = today >= dateFrom && today <= dateTo;

        const alertBox = document.getElementById('enroll_period');

        if (isEnrollmentPeriod) {
            alertBox.className = "alert alert-success text-black";
            alertBox.innerHTML = `
                <span class="fs-4 fw-bold">Enrollment Period Ongoing</span><br>
                <label class="fw-bold text-black">Term: ${fy_data[0].school_year} ${fy_data[0].sem}</label><br>
                Enrollment is open from ${formattedDateFrom} to ${formattedDateTo}.
            `;
        } else {
            alertBox.className = "alert alert-danger text-black";
            alertBox.innerHTML = `  
                <span class="fs-4 fw-bold">Enrollment Period Closed</span><br>
                Enrollment period is from ${formattedDateFrom} to ${formattedDateTo}.
            `;
        }

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
                                button: false,
                                timer: 2000
                            })
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

        const enroll_status = <?php echo json_encode($enroll_status); ?>;
        if(enroll_status === false){
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