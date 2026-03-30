<?php
// Handles Dean approval/rejection for irregular enrollment requests (shared endpoint)

defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');

// Only DEAN or ADMIN can act
if (!in_array($g_user_role, ['DEAN', 'ADMIN'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
$status     = strtoupper(trim($_POST['status'] ?? ''));
$remarks    = trim($_POST['remarks'] ?? '');
$dean_id    = !empty($g_general_id) ? $g_general_id : trim($_POST['dean_id'] ?? '');
// Optional JSON-encoded array of recommended subject codes from prospectus
$recommended_raw = $_POST['recommended_subjects'] ?? '';

// Normalize recommended subjects to a compact JSON array of unique, non-empty codes
$recommended_json = '';
if ($recommended_raw !== '') {
    $decoded = json_decode($recommended_raw, true);
    if (is_array($decoded)) {
        $clean = [];
        foreach ($decoded as $code) {
            $code = trim((string)$code);
            if ($code === '') {
                continue;
            }
            $upper = strtoupper($code);
            $clean[$upper] = true;
        }
        if (!empty($clean)) {
            $recommended_json = json_encode(array_keys($clean));
        }
    }
}

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Missing request identifier.']);
    exit;
}

if (!in_array($status, ['APPROVED', 'REJECTED'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value.']);
    exit;
}

// Ensure the request exists and is still pending in the unified enrollment table
// (classification = 'IRREGULAR' and current status = 'PENDING').
$check_sql = "SELECT enrollment_id FROM enrollment
                            WHERE enrollment_id = $request_id
                                AND classification = 'IRREGULAR'
                                AND status = 'PENDING'
                            LIMIT 1";
$check_rows = mysqliquery_return($check_sql);
if (empty($check_rows)) {
    echo json_encode(['success' => false, 'message' => 'Request not found or already processed.']);
    exit;
}

$remarks_sql = $remarks !== '' ? "'" . escape($db_connect, $remarks) . "'" : 'NULL';
$dean_sql    = $dean_id !== '' ? "'" . escape($db_connect, $dean_id) . "'" : 'NULL';
$recommended_sql = $recommended_json !== '' ? "'" . escape($db_connect, $recommended_json) . "'" : 'NULL';

$update_sql = "UPDATE enrollment
               SET status = '" . escape($db_connect, $status) . "',
                   dean_id = $dean_sql,
                   evaluated_at = NOW(),
                   remarks = $remarks_sql,
                   recommended_subjects = $recommended_sql,
                   updated_at = NOW()
               WHERE enrollment_id = $request_id
                 AND classification = 'IRREGULAR'";

if (mysqli_query($db_connect, $update_sql)) {
    echo json_encode(['success' => true, 'message' => 'Request ' . strtolower($status) . ' successfully.']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Database update failed.']);
