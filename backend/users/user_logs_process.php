<?php
// IMPORTANT: Ensure this file is saved WITHOUT UTF-8 BOM to avoid stray invisible bytes before JSON output.
// Begin unified output buffering BEFORE any include to capture BOM, warnings, notices, echoes.
// Activity and Login/Logout logs endpoint for e_dev_enrollment.

ob_start();

@ini_set('display_errors', 0);
@ini_set('html_errors', 0);
@ini_set('log_errors', 1);

// Bootstrap application using the dev app's DOMAIN_PATH pattern
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

if (!defined('QUERY_LIMIT')) {
    define('QUERY_LIMIT', 20);
}

// Ensure session is started (defensive in case included files did not start it)
if (function_exists('session_status') && session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Defensive population of role & user id if globals not set by included scripts
if (!isset($g_user_role) && isset($_SESSION['user_role'])) {
    $g_user_role = $_SESSION['user_role'];
}
if (!isset($s_user_id) && isset($_SESSION['user_id'])) {
    $s_user_id = $_SESSION['user_id'];
}

// Debug logging for role resolution (check php_error_log in XAMPP logs folder)
error_log('SESSION ROLE DEBUG (user_logs_process - dev): role=' . var_export($g_user_role ?? '(unset)', true) . ' user_id=' . var_export($s_user_id ?? '(unset)', true));

register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (headers_sent() === false) {
            header('Content-Type: application/json');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode([
            'data' => [],
            'page' => 1,
            'size' => 0,
            'last_page' => 1,
            'total_records' => 0,
            'error' => true,
            'message' => 'Server error: ' . preg_replace('/\s+/', ' ', $err['message'])
        ]);
    }
});

// Authorize based on active session (and role restriction where applicable)
if (empty($s_user_id)) {
    $resp = [
        'data' => [],
        'page' => 1,
        'size' => 0,
        'last_page' => 1,
        'total_records' => 0,
        'error' => true,
        'message' => 'Unauthorized: no active session'
    ];
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode($resp);
    exit();
}

// Check if accessing activity_log endpoint - allow ADMIN and REGISTRAR
if (isset($_GET['table']) && $_GET['table'] === 'activity_log') {
    // Allow ADMIN and REGISTRAR to access activity logs
    $user_role = strtoupper(trim($g_user_role ?? ''));
    if ($user_role !== 'ADMIN' && $user_role !== 'REGISTRAR') {
        $resp = ['error' => true, 'message' => 'Access denied'];
        header('Content-Type: application/json');
        ob_end_clean();
        echo json_encode($resp);
        exit();
    }
} else {
    // For other endpoints, restrict to ADMIN only
    if (isset($g_user_role) && strtoupper(trim($g_user_role)) !== 'ADMIN') {
        $resp = ['error' => true, 'message' => 'Access denied'];
        header('Content-Type: application/json');
        ob_end_clean();
        echo json_encode($resp);
        exit();
    }
}

if (!in_array(trim($_SERVER['REQUEST_METHOD']), ['POST', 'GET'])) {
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode([
        'data' => [],
        'page' => 1,
        'size' => 0,
        'last_page' => 1,
        'total_records' => 0,
        'error' => true,
        'message' => 'Invalid method'
    ]);
    exit();
}

// Remote pagination for user_log table (login/logout history)
if (isset($_GET['table']) && $_GET['table'] === 'user_log') {
    $orderby = 'ul.user_log_id DESC';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $size = isset($_GET['size']) ? max(1, intval($_GET['size'])) : QUERY_LIMIT;
    $offset = ($page - 1) * $size;

    $count_sql = 'SELECT COUNT(*) as total FROM user_log';
    $total_records = 0;
    if ($cq = call_mysql_query($count_sql)) {
        if ($r = call_mysql_fetch_array($cq)) {
            $total_records = (int) $r['total'];
        }
    }
    $last_page = ($size > 0) ? ceil($total_records / $size) : 1;
    if ($last_page < 1) $last_page = 1;
    if ($page > $last_page) $page = $last_page;
    $offset = ($page - 1) * $size;

    // Join users table to get full_name for display
    $sql = "SELECT 
                ul.user_log_id, ul.login_date, ul.logout_date, ul.action, 
                ul.user_id, ul.session_id, ul.ip_address, ul.device, ul.login_flag,
                CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) AS full_name
            FROM user_log ul
            LEFT JOIN users u ON u.user_id = ul.user_id
            ORDER BY $orderby 
            LIMIT $offset, $size";

    $rows = [];
    if ($dq = call_mysql_query($sql)) {
        if ($num = call_mysql_num_rows($dq)) {
            while ($d = call_mysql_fetch_array($dq)) {
                $d = array_html($d);
                $d['active'] = ($d['login_flag'] == 1 && $d['logout_date'] == '0000-00-00 00:00:00');
                if (!isset($d['full_name']) || trim($d['full_name']) === '') {
                    $d['full_name'] = 'Unknown';
                }
                $rows[] = $d;
            }
        }
    }

    $response = [
        'data' => $rows,
        'page' => $page,
        'size' => $size,
        'last_page' => $last_page,
        'total_records' => $total_records,
        'error' => false,
    ];
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// Activity_log endpoint (used by admin/dean/registrar/student_logs pages)
if (isset($_GET['table']) && $_GET['table'] === 'activity_log') {
    $orderby = 'activity_log_id DESC';
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $size = isset($_GET['size']) ? max(1, intval($_GET['size'])) : QUERY_LIMIT;
    $offset = ($page - 1) * $size;

    // Role filter from request
    $role_filter = isset($_GET['role']) ? trim($_GET['role']) : 'all';
    $search_filter = isset($_GET['search']) ? trim($_GET['search']) : '';
    $role_condition = '';
    $search_condition = '';

    // Map role filters to JSON role IDs (aligned with your views)
    //   Admin     -> role id 1
    //   Registrar -> role id 2
    //   Dean      -> role id 4
    //   Student   -> role id 6
    if ($role_filter === 'admin') {
        $role_condition = 'WHERE JSON_CONTAINS(u.user_role, "\"1\"")';
    } elseif ($role_filter === 'registrar') {
        $role_condition = 'WHERE JSON_CONTAINS(u.user_role, "\"2\"")';
    } elseif ($role_filter === 'dean') {
        $role_condition = 'WHERE JSON_CONTAINS(u.user_role, "\"4\"")';
    } elseif ($role_filter === 'student') {
        $role_condition = 'WHERE JSON_CONTAINS(u.user_role, "\"6\"")';
    } else {
        $role_condition = '';
    }

    // Optional search term across action, full name, and date
    if (!empty($search_filter)) {
        $search_term = escape($db_connect, $search_filter);
        $search_condition = " AND (a.action LIKE '%$search_term%' OR 
                              CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) LIKE '%$search_term%' OR 
                              a.date_log LIKE '%$search_term%')";

        if (empty($role_condition)) {
            $search_condition = "WHERE (a.action LIKE '%$search_term%' OR 
                                 CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) LIKE '%$search_term%' OR 
                                 a.date_log LIKE '%$search_term%')";
        }
    }

    $count_sql = "SELECT COUNT(*) AS total FROM activity_log a
                  LEFT JOIN users u ON u.user_id = a.user_id
                  $role_condition $search_condition";
    $total_records = 0;
    if ($count_q = call_mysql_query($count_sql)) {
        if ($row = call_mysql_fetch_array($count_q)) {
            $total_records = (int) $row['total'];
        }
    }
    $last_page = ($size > 0) ? ceil($total_records / $size) : 1;
    if ($last_page < 1) $last_page = 1;
    if ($page > $last_page) $page = $last_page;
    $offset = ($page - 1) * $size;

    $data_rows = [];
    $data_sql = "SELECT a.activity_log_id, a.user_id, a.date_log, a.action, 
                        CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) AS full_name
                 FROM activity_log a
                 LEFT JOIN users u ON u.user_id = a.user_id
                 $role_condition $search_condition
                 ORDER BY $orderby LIMIT $offset, $size";
    if ($data_q = call_mysql_query($data_sql)) {
        if ($num = call_mysql_num_rows($data_q)) {
            while ($data = call_mysql_fetch_array($data_q)) {
                $data = array_html($data);
                if (trim($data['full_name']) === '') {
                    $data['full_name'] = 'Unknown';
                }
                $data_rows[] = $data;
            }
        }
    }

    $response = [
        'data' => $data_rows,
        'page' => $page,
        'size' => $size,
        'last_page' => $last_page,
        'total_records' => $total_records,
        'error' => false,
    ];
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode($response);
    exit();
}

// Fallback for unknown table
header('Content-Type: application/json');
ob_end_clean();
echo json_encode([
    'data' => [],
    'page' => 1,
    'size' => 0,
    'last_page' => 1,
    'total_records' => 0,
    'error' => true,
    'message' => 'Unknown table',
]);
exit();
