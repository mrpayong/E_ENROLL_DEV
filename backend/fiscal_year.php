<?php

// Unified fiscal year endpoint used by the global fiscal-year modal.
// Returns the list of school years (action=list) and allows setting
// the active fiscal year in the session (action=set).

defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;

// Initialize session (without using islogin.php to avoid redirects/HTML).
// cl_session.php already creates $session_class and starts the session, but we
// defensively ensure it exists here for direct calls.
if (!isset($session_class) || !($session_class instanceof Session)) {
    $session_class = new Session(SESSION_CONFIG);
    $session_class->start();
}

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';

if (!in_array($action, array('list', 'set'), true)) {
    echo json_encode(array(
        'success' => false,
        'message' => 'Invalid action.'
    ));
    exit;
}

try {
    if ($action === 'list') {
        global $db_connect;

        $schoolYears = array();

        $sql = "SELECT school_year_id, school_year, sem, flag_used, date_from FROM school_year ORDER BY date_from DESC, school_year_id DESC";
        if ($res = mysqli_query($db_connect, $sql)) {
            $current = get_school_year();
            $currentId = isset($current['school_year_id']) ? (int) $current['school_year_id'] : 0;

            while ($row = mysqli_fetch_assoc($res)) {
                $label = $row['school_year'] . ' - ' . $row['sem'];
                $schoolYears[] = array(
                    'school_year_id' => (int) $row['school_year_id'],
                    'school_year'    => $row['school_year'],
                    'sem'            => $row['sem'],
                    'flag_used'      => (int) $row['flag_used'],
                    'label'          => $label,
                    'is_current'     => ((int) $row['school_year_id'] === $currentId)
                );
            }
        }

        echo json_encode(array(
            'success' => true,
            'items'   => $schoolYears
        ));
        exit;
    }

    if ($action === 'set') {
        if ($method !== 'POST') {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid request method.'
            ));
            exit;
        }

        $id = isset($_POST['school_year_id']) ? trim($_POST['school_year_id']) : '';
        if ($id === '' || !ctype_digit($id)) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Invalid fiscal year.'
            ));
            exit;
        }

        $id = (int) $id;
        $fy = get_school_year($id);
        if (!$fy || !isset($fy['school_year_id'])) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Fiscal year not found.'
            ));
            exit;
        }

        // Persist in session so that subsequent calls to get_school_year()
        // for this user will use this as the active fiscal year.
        $session_class->setValue('global_fy', $fy);

        echo json_encode(array(
            'success'        => true,
            'school_year_id' => (int) $fy['school_year_id'],
            'school_year'    => $fy['school_year'],
            'sem'            => $fy['sem'],
            'label'          => $fy['school_year'] . ' - ' . $fy['sem']
        ));
        exit;
    }
} catch (Exception $e) {
    echo json_encode(array(
        'success' => false,
        'message' => 'An unexpected error occurred.'
    ));
    exit;
}

