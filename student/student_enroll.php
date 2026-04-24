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
$sql_fy = "SELECT school_year_id, school_year, sem, date_from, date_to, flag_used 
FROM school_year
WHERE isDefault = '".escape($db_connect, 1)."'
";

if($sql = call_mysql_query($sql_fy)){
    if($data = call_mysql_fetch_array($sql)){
        array_push($active_fiscalYear, $data);
    }
}

$sql_student = "SELECT student_id, student_id_no, firstname, middle_name, lastname, year_level 
FROM student WHERE student_id_no = '".  escape($db_connect, $g_general_id)  ."'";
if($sql = call_mysql_query($sql_student)){
    if($data = call_mysql_fetch_array($sql)){
        array_push($student_user, $data);
    }
}


$student_academic_status = 'Irregular';
$previous_term_school_year = '';
$previous_term_sem = '';
$comparison_year_level = 0;

$passed_earned_units = 0;
$required_curriculum_units = 0;
$previous_term_average_final_grade = 0;
$passed_subject_count = 0;

/*
|--------------------------------------------------------------------------
| 1. Get student info needed for status computation
|--------------------------------------------------------------------------
| Add curriculum_id if your student table has it. This is strongly recommended.
|--------------------------------------------------------------------------
*/
$student_info = null;
$student_year_level = 0;
$student_curriculum_id = 0;

$sql_student_status = "
    SELECT student_id_no, year_level, curriculum_id
    FROM student
    WHERE student_id_no = '" . escape($db_connect, $g_general_id) . "'
    LIMIT 1
";

if ($query = call_mysql_query($sql_student_status)) {
    if ($data = call_mysql_fetch_array($query)) {
        $student_info = $data;
        $student_year_level = (int)($data['year_level'] ?? 0);
        $student_curriculum_id = (int)($data['curriculum_id'] ?? 0);
    }
}

/*
|--------------------------------------------------------------------------
| 2. Get current active school year based on today's date
|--------------------------------------------------------------------------
*/
$current_school_year = '';
$current_sem = '';

$sql_current_term = "
    SELECT school_year_id, school_year, sem, date_from, date_to
    FROM school_year
    WHERE CURDATE() BETWEEN date_from AND date_to
    LIMIT 1
";

if ($query = call_mysql_query($sql_current_term)) {
    if ($data = call_mysql_fetch_array($query)) {
        $current_school_year = trim($data['school_year'] ?? '');
        $current_sem = trim($data['sem'] ?? '');
    }
}

/*
|--------------------------------------------------------------------------
| 3. Resolve previous term and comparison year level
|--------------------------------------------------------------------------
| Rules:
| - If current term is 2nd Semester:
|     previous term = 1st Semester of same school year
|     comparison year level = student's current year level
|
| - If current term is 1st Semester:
|     previous term = 2nd Semester of previous school year
|     comparison year level = student's current year level - 1
|--------------------------------------------------------------------------
*/
if (!empty($current_school_year) && !empty($current_sem) && $student_year_level > 0) {
    $normalized_current_sem = strtolower(trim($current_sem));

    if (strpos($normalized_current_sem, '2nd') !== false) {
        $previous_term_school_year = $current_school_year;
        $previous_term_sem = '1ST SEMESTER';
        $comparison_year_level = $student_year_level;
    } elseif (strpos($normalized_current_sem, '1st') !== false) {
        $parts = explode('-', $current_school_year);

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
if (!empty($previous_term_school_year) && !empty($previous_term_sem) && !empty($g_general_id)) {
    $student_id_text = escape($db_connect, $g_general_id);
    $prev_sy = escape($db_connect, $previous_term_school_year);
    $prev_sem = escape($db_connect, $previous_term_sem);

    $sql_passed_grades = "
        SELECT
            COUNT(*) AS passed_subject_count,
            COALESCE(SUM(units), 0) AS earned_units,
            COALESCE(AVG(final_grade), 0) AS average_final_grade
        FROM final_grade
        WHERE student_id_text = '$student_id_text'
          AND school_year = '$prev_sy'
          AND UPPER(sem) = UPPER('$prev_sem')
          AND UPPER(remarks) = 'PASSED'
    ";

    if ($query = call_mysql_query($sql_passed_grades)) {
        if ($data = call_mysql_fetch_array($query)) {
            $passed_subject_count = (int)($data['passed_subject_count'] ?? 0);
            $passed_earned_units = (int)($data['earned_units'] ?? 0);
            $previous_term_average_final_grade = round((float)($data['average_final_grade'] ?? 0), 2);
        }
    }
}

/*
|--------------------------------------------------------------------------
| 5. Compute required units from curriculum
|--------------------------------------------------------------------------
| Match:
| - curriculum_id
| - year_level
| - semester
|--------------------------------------------------------------------------
*/
if ($student_curriculum_id > 0 && $comparison_year_level > 0 && !empty($previous_term_sem)) {
    $curriculum_id_safe = (int)$student_curriculum_id;
    $comparison_year_level_safe = (int)$comparison_year_level;

    /*
    | curriculum.semester values are like:
    | 1st Semester / 2nd Semester
    | so we normalize the previous sem string to the same style
    */
    $curriculum_sem = '';
    if (stripos($previous_term_sem, '1ST') !== false) {
        $curriculum_sem = '1st Semester';
    } elseif (stripos($previous_term_sem, '2ND') !== false) {
        $curriculum_sem = '2nd Semester';
    }

    if (!empty($curriculum_sem)) {
        $curriculum_sem_safe = escape($db_connect, $curriculum_sem);

        $sql_curriculum_units = "
            SELECT COALESCE(SUM(unit), 0) AS required_units
            FROM curriculum
            WHERE curriculum_id = $curriculum_id_safe
              AND year_level = $comparison_year_level_safe
              AND semester = '$curriculum_sem_safe'
        ";

        if ($query = call_mysql_query($sql_curriculum_units)) {
            if ($data = call_mysql_fetch_array($query)) {
                $required_curriculum_units = (int)($data['required_units'] ?? 0);
            }
        }
    }
}

/*
|--------------------------------------------------------------------------
| 6. Determine academic status
|--------------------------------------------------------------------------
*/
if ($required_curriculum_units > 0 && $passed_earned_units >= $required_curriculum_units) {
    $student_academic_status = "Regular";
} else {
    $student_academic_status = "Irregular";
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
                    
                    <?php if ($has_active_enrollment): ?>
                    <div class="alert alert-success bg-success text-white d-flex align-items-center p-4 mb-4 border-0" role="alert">
                        <i class="fas fa-check-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">You Are Successfully Enrolled</h5>
                            <p class="mb-0">
                                Your regular enrollment<?php echo $active_enrollment_date ? ' on <strong>' . date('F d, Y', strtotime($active_enrollment_date)) . '</strong>' : ''; ?> has been recorded.
                                You are now officially enrolled for the current term.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_pending_request): ?>
                    <div class="alert alert-warning d-flex align-items-center p-4 mb-3" role="alert">
                        <i class="fas fa-clock fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Pending</h5>
                            <p class="mb-0">Your enrollment request has been submitted<?php echo $pending_request_date ? ' on <strong>' . date('F d, Y', strtotime($pending_request_date)) . '</strong>' : ''; ?>. Please wait for Dean approval. You will be notified once your request has been reviewed.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && $has_approved_request): ?>
                    <div class="alert alert-success bg-success text-white d-flex align-items-center p-4 mb-3 border-0" role="alert">
                        <i class="fas fa-check-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Approved</h5>
                            <p class="mb-0">
                                Your irregular enrollment request<?php echo $approved_request_date ? ' on <strong>' . date('F d, Y', strtotime($approved_request_date)) . '</strong>' : ''; ?> has been <strong>approved</strong> by the Dean.
                                Please monitor your official enrollment record for any further updates.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && !$has_approved_request && $has_rejected_request): ?>
                    <div class="alert alert-danger bg-danger text-white d-flex align-items-center p-4 mb-3 border-0" role="alert">
                        <i class="fas fa-times-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Rejected</h5>
                            <p class="mb-1">
                                Your previous enrollment request<?php echo $rejected_request_date ? ' on <strong>' . date('F d, Y', strtotime($rejected_request_date)) . '</strong>' : ''; ?> has been <strong>rejected</strong> by the Dean.
                                You may review the subjects and submit a new request.
                            </p>
                            <?php if (!empty($rejected_remarks)): ?>
                            <p class="mb-0 small"><strong>Dean's Remarks:</strong> <?php echo htmlspecialchars($rejected_remarks); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($rejected_recommended)): ?>
                            <p class="mb-0 small mt-1">
                                <strong>Dean's Recommended Subjects:</strong>
                                <?php echo htmlspecialchars(implode(', ', $rejected_recommended)); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

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
                                <div class="info-value">
                                    <?php
                                    if (!is_null($student_year_level_display) && $student_year_level_display > 0) {
                                        $y_level = $student_year_level_display;
                                        $suffix = ['th', 'st', 'nd', 'rd'];
                                        $val = $y_level % 100;
                                        echo $y_level . ($suffix[($val - 20) % 10] ?? $suffix[$val] ?? $suffix[0]) . " Year";
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($has_active_enrollment && empty($has_pending_request) && empty($has_approved_request) && !empty($regular_time_slots)): ?>
                    <div class="card card-round mb-4" id="enrolled_schedule_print_area">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold mb-0">Enrolled Subjects Schedule</h4>
                                <div class="btn-group no-print" role="group" aria-label="Enrolled schedule actions">
                                    <a href="<?php echo BASE_URL; ?>student/process/print_enrolled_schedule_pdf.php" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-print me-1"></i> Print / Save as PDF
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>student/process/export_enrolled_schedule_excel.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i> Save as Excel
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-success"></i>
                                This is your current enrolled class schedule for the term.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($regular_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($regular_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($regular_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($regular_day_order as $day_label):
                                                $subjects_cell = $regular_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects_cell) ? implode('<br>', array_map('htmlspecialchars', $subjects_cell)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_pending_request && !empty($pending_time_slots)): ?>
                    <div class="card card-round mb-4">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Requested Subjects Schedule</h4>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-primary"></i>
                                Time shows in AM/PM; each cell displays subject code with its name for that day/time.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($pending_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($pending_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($pending_day_order as $day_label):
                                                $subjects = $pending_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects) ? implode('<br>', array_map('htmlspecialchars', $subjects)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && $has_approved_request && !empty($approved_time_slots)): ?>
                    <div class="card card-round mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold mb-0">Approved Subjects Schedule</h4>
                                <div class="btn-group" role="group" aria-label="Approved schedule actions">
                                    <a href="<?php echo BASE_URL; ?>student/process/print_irregular_schedule_pdf.php" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-print me-1"></i> Print / Save as PDF
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>student/process/export_irregular_schedule_excel.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i> Save as Excel
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-success"></i>
                                This is the schedule of your <strong>approved</strong> irregular enrollment request.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($approved_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($approved_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($approved_day_order as $day_label):
                                                $subjects = $approved_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects) ? implode('<br>', array_map('htmlspecialchars', $subjects)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && !$has_approved_request && !$has_active_enrollment): ?>
                    <div class="row subjects-container-scroll">
                        <div class="col-md-8">
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
                                        <p id="available_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-primary"></i> Choose your current-term subjects. The system will assign the best section automatically based on your chosen subjects.
                                        </p>

                                        <div class="row mb-4">
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <?php
                                                // For students with an assigned program, show a simple
                                                // read-only text field instead of a disabled dropdown
                                                // to make it clear that the program cannot be changed.
                                                if ($student_program_id > 0 && !empty($programs)) {
                                                    $program_label = '';
                                                    foreach ($programs as $program) {
                                                        if ((int)$program['program_id'] === (int)$student_program_id) {
                                                            $program_label = !empty($program['short_name']) ? $program['short_name'] : $program['program'];
                                                            break;
                                                        }
                                                    }
                                                    if ($program_label === '' && !empty($programs[0])) {
                                                        $program_label = !empty($programs[0]['short_name']) ? $programs[0]['short_name'] : $programs[0]['program'];
                                                    }
                                                ?>
                                                    <label for="program_name_display" class="form-labe fw-bold">Program</label>
                                                    <input type="text" class="form-control" title="Program" id="program_name_display" value="<?php echo htmlspecialchars($program_label); ?>" readonly>
                                                    <input type="hidden" name="program_id" id="program_id" value="<?php echo (int)$student_program_id; ?>">
                                                <?php } else { ?>
                                                    <label for="program_id" class="form-labe fw-bold">Program</label>
                                                    <select class="form-select" name="program_id" id="program_id">
                                                        <?php if ($student_program_id <= 0): ?>
                                                            <option value="">All Programs</option>
                                                        <?php endif; ?>
                                                        <?php
                                                        if (!empty($programs)) {
                                                            $seen_programs = [];
                                                            foreach ($programs as $program) {
                                                                $pid = (int)$program['program_id'];
                                                                $label = !empty($program['short_name']) ? $program['short_name'] : $program['program'];

                                                                // Avoid duplicate entries with the same label
                                                                if (isset($seen_programs[$label])) {
                                                                    continue;
                                                                }
                                                                $seen_programs[$label] = true;

                                                                $selected = ($pid === $student_program_id) ? 'selected' : '';
                                                        ?>
                                                                <option value="<?php echo $pid; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label); ?></option>
                                                        <?php
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                <?php } ?>
                                            </div>

                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <label for="fiscal_year_display" class="form-labe fw-bold">School Year</label>
                                                <?php
                                                // Normalize current school year and semester into separate labels
                                                $sy_text  = $current_sy['school_year'] ?? '';
                                                $sem_text = $current_sy['sem'] ?? '';
                                                ?>
                                                <input type="text" class="form-control" title="School Year" id="fiscal_year_display" value="<?php echo htmlspecialchars($sy_text); ?>" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="semester_display" class="form-labe fw-bold">Semester</label>
                                                <input type="text" class="form-control" title="Semester" id="semester_display" value="<?php echo htmlspecialchars($sem_text); ?>" readonly>
                                            </div>
                                        </div>

                                        <?php if (strcasecmp($student_academic_status, 'Regular') === 0): ?>
                                        <div class="row mb-3">
                                            <div class="col-md-4" id="section_select_group">
                                                <label for="class_id" class="form-labe fw-bold">Section</label>
                                                <select class="form-select" name="class_id" id="class_id">
                                                    <?php
                                                    if (!empty($sections)) {
                                                        $seen_sections = [];
                                                        foreach ($sections as $section) {
                                                            $cid = (int)$section['class_id'];

                                                            if (isset($seen_sections[$cid])) {
                                                                continue;
                                                            }
                                                            $seen_sections[$cid] = true;
                                                            $is_full = !empty($section_capacity_info[$cid]['is_full']);
                                                            $disabled_attr = $is_full ? ' disabled' : '';
                                                            $label = $section['class_name'] . ($is_full ? ' (Full)' : '');
                                                    ?>
                                                            <option value="<?php echo $cid; ?>"<?php echo $disabled_attr; ?>><?php echo htmlspecialchars($label); ?></option>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (strcasecmp($student_academic_status, 'Regular') === 0): ?>
                                        <div id="autoFillRow" class="d-flex justify-content-start align-items-center mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="autoFill" checked>
                                                <label class="form-check-label" for="autoFill">Auto-fill <b>"My Subject Cart"</b> with all subjects.</label>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="subjects_prereq_header">Pre-Req</th>
                                                        <th id="subjects_action_header">Action</th>
                                                    </tr>
                                                    <tr id="subjects_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_subject_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_subject_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">Loading subjects...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

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
                                    <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3" id="cart_section_row">
                                        <span class="fw-bold">Your Section Classification:</span>
                                        <span id="cart_section_label" class="ms-2">Not yet determined</span>
                                    </div>
                                    <?php endif; ?>
                                    <button class="btn btn-proceed">ENROLL IN THIS CLASS SECTION</button>
                                </div>
                            </div>
                        </div>
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

                            <div class="card card-round mt-4" id="schedule_preview_card">
                                <div class="card-body">
                                    <h4 class="fw-bold mb-4">Schedule Preview</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered schedule-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 10%;"></th>
                                                    <?php
                                                    // Days shown in the schedule preview (no Sunday classes).
                                                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                                    foreach ($days as $day): ?>
                                                        <th style="width: 15%;" data-day="<?php echo $day; ?>"><?php echo strtoupper($day); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody id="schedule_table_body">
                                                <?php 
                                                $timeSlots = [
                                                    '07:00' => '7am',
                                                    '08:00' => '8am',
                                                    '09:00' => '9am',
                                                    '10:00' => '10am',
                                                    '11:00' => '11am',
                                                    '12:00' => '12pm',
                                                    '13:00' => '1pm',
                                                    '14:00' => '2pm',
                                                    '15:00' => '3pm',
                                                    '16:00' => '4pm',
                                                    '17:00' => '5pm',
                                                    '18:00' => '6pm',
                                                    '19:00' => '7pm',
                                                    '20:00' => '8pm',
                                                    '21:00' => '9pm',
                                                    '22:00' => '10pm',
                                                ];

                                                foreach ($timeSlots as $time24 => $label): ?>
                                                <tr data-time="<?php echo $time24; ?>">
                                                    <td class="text-center small"><?php echo $label; ?></td>
                                                    <?php foreach ($days as $day): ?>
                                                        <td data-day="<?php echo $day; ?>"></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

    <?php if (!$has_pending_request && !$has_approved_request): ?>
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
                    Enrollment is open from ${formattedDateFrom} to ${formattedDateTo}.
                `;
            } else {
                alertBox.className = "alert alert-danger text-black";
                alertBox.innerHTML = `  
                    <span class="fs-4 fw-bold">Enrollment Period Closed</span><br>
                    Enrollment period is from ${formattedDateFrom} to ${formattedDateTo}.
                `;
            }

            let student_classification = "<?php echo $student_academic_status; ?>"
            document.getElementById('id_num').textContent = student_data[0].student_id_no;
            document.getElementById('full_name').textContent = `${student_data[0].lastname}, ${student_data[0].firstname} ${student_data[0].middle_name}`;
            document.getElementById('student_class').textContent = `${student_classification}`;
        })

        
    </script>
    <!-- <script src="<?php echo BASE_URL; ?>student/js/enrollment_status.js?v=<?php echo time(); ?>"></script> -->
    <?php endif; ?>
</body>
</html>