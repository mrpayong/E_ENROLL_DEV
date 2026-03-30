<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

// Only allow ADMIN to run this seeding script
if (strtoupper(trim($g_user_role ?? '')) !== 'ADMIN') {
    http_response_code(403);
    echo 'Access denied. ADMIN only.';
    exit();
}

// Simple guard so the script only runs when explicitly requested, e.g. ?run=1
if (!isset($_GET['run']) || $_GET['run'] !== '1') {
    echo 'Seed script ready. Append ?run=1 to actually insert dummy logs.';
    exit();
}

// Known user IDs from e_enrollment.sql with corresponding roles
// user_id 1 -> user_role ["1","2"] (ADMIN + REGISTRAR)
// user_id 2 -> user_role ["2"]       (REGISTRAR)
// user_id 5 -> user_role ["4"]       (INSTRUCTOR, used here as DEAN/INSTRUCTOR view)
// NOTE: The dump does not have a user_role ["6"] student example for this dev DB,
// so student dummy logs will use a synthetic user_level of 6 but still link
// to a real user_id (12, labelled student-1) so joins show a name.

$dummySets = [
    [
        'label'    => 'ADMIN',
        'user_id'  => 1,
        'user_lvl' => '1',
        'actions'  => [
            'ADMIN: Created new user account for testing purposes',
            'ADMIN: Updated system configuration values (dummy change)',
            'ADMIN: Viewed activity dashboard (dummy log)',
            'ADMIN: Deleted sample record from staging table',
            'ADMIN: Exported dummy enrollment report to CSV',
        ],
    ],
    [
        'label'    => 'REGISTRAR',
        'user_id'  => 2,
        'user_lvl' => '2',
        'actions'  => [
            'REGISTRAR: Approved dummy enrollment request',
            'REGISTRAR: Encoded sample grades for testing section',
            'REGISTRAR: Updated dummy student profile information',
            'REGISTRAR: Generated sample enrollment summary report',
            'REGISTRAR: Locked dummy school year record',
        ],
    ],
    [
        'label'    => 'DEAN',
        'user_id'  => 5,
        'user_lvl' => '4',
        'actions'  => [
            'DEAN: Reviewed dummy faculty load distribution',
            'DEAN: Approved sample curriculum revision (test only)',
            'DEAN: Viewed dummy class list for QA testing',
            'DEAN: Commented on sample program assessment report',
            'DEAN: Downloaded test evaluation summary',
        ],
    ],
    [
        'label'    => 'STUDENT',
        'user_id'  => 12,   // student-1 in e_enrollment.sql, treated as demo student
        'user_lvl' => '6',  // role id 6 for student in activity_log
        'actions'  => [
            'STUDENT: Logged in to enrollment portal (dummy)',
            'STUDENT: Viewed dummy enrollment summary page',
            'STUDENT: Updated sample personal information (test data)',
            'STUDENT: Submitted dummy enrollment form for review',
            'STUDENT: Downloaded test copy of enrollment slip',
        ],
    ],
];

$totalInserted = 0;
$now = time();

foreach ($dummySets as $set) {
    $userId   = (int) $set['user_id'];
    $userLvl  = $set['user_lvl'];
    $sessionId = 'dummy-session-' . strtolower($set['label']);

    $offsetMinutes = 0;
    foreach ($set['actions'] as $actionText) {
        // Spread logs over the last few days/minutes for nicer date-range demos
        $logTime = date('Y-m-d H:i:s', $now - ($offsetMinutes * 60));
        $offsetMinutes += 30; // 30 minutes apart

        $actionEsc = escape($db_connect, $actionText);
        $sessionEsc = escape($db_connect, $sessionId);
        $userLvlEsc = escape($db_connect, $userLvl);

        $sql = "INSERT INTO activity_log (user_id, date_log, action, session_id, user_level, system_id)
                VALUES ($userId, '$logTime', '$actionEsc', '$sessionEsc', '$userLvlEsc', 0)";

        if ($db_connect instanceof mysqli) {
            if ($db_connect->query($sql)) {
                $totalInserted++;
            }
        }
    }
}

echo 'Dummy activity logs inserted: ' . $totalInserted . "\n";
exit();
