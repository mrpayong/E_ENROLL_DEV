<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;
require DOMAIN_PATH . '/dean/process/dean_backlog_helper.php';

$general_page_title = "Enrollment Approval";
$page_header_title = "Dean's Review Portal";
$header_breadcrumbs = [];
$active_page = 'student_approvals';

// 1. STRICT GUARD: Only allow DEAN 
if ($g_user_role !== "DEAN") {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// 2. Determine Dean's department
$dean_department_id = null;
$sql_dept = "SELECT d.department_id
             FROM departments d
             INNER JOIN users u ON d.user_id = u.user_id
             WHERE u.general_id = '" . escape($db_connect, $g_general_id) . "'
             LIMIT 1";

if ($res_dept = mysqli_query($db_connect, $sql_dept)) {
    if ($row_dept = mysqli_fetch_assoc($res_dept)) {
        $dean_department_id = (int)$row_dept['department_id'];
    }
}

// 3. Fetch Data based on Department from unified enrollment table
// Only IRREGULAR rows are shown here.
$where_extra = '';
// if ($dean_department_id !== null) {
//     $where_extra = " AND p.department_id = " . (int)$dean_department_id;
// }

$sy = get_school_year();
$current_sem = $sy['sem'] ?? '';
$current_sy_id = (int)($sy['school_year_id'] ?? 0);
$current_sem_trunc = escape($db_connect, substr((string)$current_sem, 0, 10));

$sql = "SELECT 
                        e.enrollment_id AS request_id,
                        e.student_id,
                        e.program_id,
                        e.created_at,
                        e.status,
                        e.evaluated_at,
                        e.remarks,
                        e.recommended_subjects,
                        s.firstname,
                        s.lastname,
                        s.year_level,
                        p.program
                FROM enrollment e
                LEFT JOIN student s ON e.student_id COLLATE utf8mb4_general_ci = s.student_id_no
                LEFT JOIN programs p ON e.program_id = p.program_id
                WHERE e.classification = 'IRREGULAR'
                    AND e.schoolyear_id = " . $current_sy_id . "
                    AND e.sem = '" . $current_sem_trunc . "'
                    AND e.status IN ('PENDING','APPROVED','REJECTED')" . $where_extra . "
                ORDER BY e.created_at ASC";

$result = mysqli_query($db_connect, $sql);
$requests_data = [];

// Simple caches
$program_curriculum_map = [];
$curriculum_pre_req_map = [];
$curriculum_year_level_map = [];

while ($row = mysqli_fetch_assoc($result)) {
    $row['fullname'] = strtoupper(($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? ''));
    $raw_status = isset($row['status']) ? (string)$row['status'] : '';
    $row['status_normalized'] = strtoupper(trim($raw_status));
    $student_id = $row['student_id'] ?? '';
    $program_id = (int)($row['program_id'] ?? 0);
    $year_level = (int)($row['year_level'] ?? 0);

    // Backlog Logic
    $backlog_codes = [];
    if ($student_id !== '' && $program_id > 0 && $year_level > 0) {
        $backlog_codes = dean_list_backlog_subject_codes($db_connect, $student_id, $program_id, $year_level, $current_sem);
    }
    $backlog_map = array_flip(array_map('trim', $backlog_codes));

    // Curriculum resolution
    $curriculum_id = 0;
    if ($program_id > 0) {
        if (!isset($program_curriculum_map[$program_id])) {
            $program_curriculum_map[$program_id] = (int)dean_get_default_curriculum($db_connect, $program_id);
        }
        $curriculum_id = $program_curriculum_map[$program_id];
    }

    // Load Subjects for this request from unified enrollment_subjects
    $req_id = (int)($row['request_id'] ?? 0);
    $subjects = [];
    if ($req_id > 0) {
        $sub_sql = "SELECT s.subject_code, s.subject_title, s.unit
               FROM enrollment_subjects AS es
               JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id
               JOIN subject s ON tc.subject_id = s.subject_id
               WHERE es.enrollment_id = $req_id";
        $sub_res = mysqli_query($db_connect, $sub_sql);
        while ($srow = mysqli_fetch_assoc($sub_res)) {
            $code = trim((string)($srow['subject_code'] ?? ''));
            $subjects[] = [
                'subject_code'  => $code,
                'subject_title' => $srow['subject_title'] ?? '',
                'unit'          => $srow['unit'] ?? 0,
                'is_backlog'    => isset($backlog_map[$code]) ? 1 : 0
            ];
        }
    }
    $row['requested_subjects'] = $subjects;
    $row['subject_count'] = count($subjects); // Added for the table display
    $requests_data[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <!-- <link rel="stylesheet" href="<?php echo BASE_URL; ?>dean/css/student_approvals.css?v=<?php echo time(); ?>"> -->
    <style>
        .card-header { background-color: #2563EB !important; border-bottom: none; }
        .badge-pending { background-color: #ff9800; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .subject-pill { background-color: #eef2ff; color: #4338ca; padding: 2px 10px; border-radius: 20px; border: 1px solid #c7d2fe; font-size: 11px; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>
        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <div class="card card-round">
                        <div class="card-header text-white p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4 class="m-0"><i class="fas fa-clipboard-check"></i> Irregular Enrollment Requests</h4>
                                <small class="opacity-75">Review student subject enlistments</small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-3">
                                <div class="btn-group">
                                    <button id="filter_status_pending" class="btn btn-primary btn-sm px-3">Pending</button>
                                    <button id="filter_status_done" class="btn btn-outline-secondary btn-sm px-3">Done</button>
                                </div>
                            </div>
                            <div id="approval-table"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
        </div>
    </div>

    <?php include_once DOMAIN_PATH . '/dean/modals/student_approvals_modals.php'; ?>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

    <?php
        // Safely encode the request data for JS; if encoding fails because of
        // bad UTF-8, fall back to an empty array instead of the literal
        // "false" value (which would coerce to an empty list in JS).
        $json_requests = json_encode(
            $requests_data,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE
        );
        if ($json_requests === false) {
            $json_requests = '[]';
        }
    ?>

    <script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>
    <script>
        // Expose data/config for the dedicated JS controller
        window.deanApprovalsConfig = {
            tableData: <?php echo $json_requests; ?>,
            deanId: '<?php echo $g_general_id; ?>',
            baseUrl: '<?php echo BASE_URL; ?>'
        };

        // Lightweight debug: log how many rows PHP actually sent
        if (Array.isArray(window.deanApprovalsConfig.tableData)) {
            console.log('Dean approvals data rows:', window.deanApprovalsConfig.tableData.length);
        } else {
            console.warn('Dean approvals tableData is not an array:', window.deanApprovalsConfig.tableData);
        }
    </script>
    <script src="<?php echo BASE_URL; ?>dean/js/student_approvals.js?v=<?php echo time(); ?>"></script>
</body>
</html>