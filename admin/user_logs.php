<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Page header title / active page
$general_page_title = 'User Log';
$_get_value        = strtoupper($_GET['user'] ?? '');
$page_header_title = ACCESS_NAME[$_get_value] ?? $general_page_title;
$header_breadcrumbs = [];
$active_page        = 'user_logs';

// Verify the access role (ADMIN only)
if ($g_user_role !== 'ADMIN') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Map requested user type to user_level using existing SYSTEM_ACCESS mapping
$user_access_role = SYSTEM_ACCESS[GLOBAL_SYSTEM_ACCESS]['role'] ?? [];
$user_type        = $_get_value;

// Fetch user logs with user full name
$logs = [];

if (in_array($user_type, $user_access_role, true)) {
    $role = array_search($user_type, $user_access_role, true);
    $role = (int) $role;

    $whereClause = "WHERE ul.user_level = {$role}";
} else {
    // If user type is not recognized, do not filter by user_level
    $whereClause = '';
}

$sql = "SELECT ul.user_log_id, ul.login_date, ul.logout_date, ul.ip_address, ul.device,
               CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) AS name
        FROM user_log ul
        LEFT JOIN users u ON u.user_id = ul.user_id
        {$whereClause}
        ORDER BY ul.user_log_id DESC";

if ($result = $db_connect->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $row['name'] = trim($row['name'] ?? '');
        if ($row['name'] === '') {
            $row['name'] = 'Unknown';
        }

        // Decode device JSON and keep only description when possible
        if (!empty($row['device'])) {
            $decoded = json_decode($row['device'], true);
            if (is_array($decoded) && isset($decoded['description'])) {
                $row['device'] = $decoded['description'];
            }
        }

        $logs[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/css/activity_logs.css?v=<?php echo FILE_VERSION; ?>">
</head>

<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>

            <div class="container">
                <div class="page-inner">
                    <div class="row">
                        <div class="col-12">
                            <div class="card log-card">
                                <div class="card-header text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="background-color: #2563EB; font-size: large;">
                                    <div>
                                        <i class="bi bi-person-workspace"></i>&ensp;User Log
                                    </div>
                                </div>
                                <div class="card-body mt-3 bg-white">
                                    <div id="user-log-table"></div>
                                    <div class="mt-3">
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-csv">Download CSV</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-json">Download JSON</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="download-xlsx">Download XLSX</button>
                                        <button class="btn btn-sm btn-label-custom btn-round" id="print-table">Print</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
        </div>
    </div>

    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
    <script>
        window.userLogsConfig = {
            tableData: <?php echo json_encode($logs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
        };
    </script>
    <script src="<?php echo BASE_URL; ?>admin/js/user_logs.js?v=<?php echo FILE_VERSION; ?>"></script>
</body>

</html>