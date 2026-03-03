<?php
include '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');


// create fiscal year
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actionSubmitFiscalYear'])) {
    $school_year = trim($_POST['schoolYear'] ?? '');
    $sem = trim($_POST['semester'] ?? '');
    $date_from = trim($_POST['startDate'] ?? '');
    $date_to = trim($_POST['endDate'] ?? '');
    $default_limit = isset($_POST['semLimit']) ? intVal(trim($_POST['semLimit'])) : "";
    $output = array(
        "code" => 0,
        "msg_status" => false,
        "msg_span" => "_system",
        "msg_response" => "Request Error, please try again.",
    );

    function dataEmptyCheck($val){
        return ($val === null || $val === '' || $val === 0);
    }

    // Validation: All fields required
    if (empty($school_year) || empty($sem) || empty($date_from) || empty($date_to) || dataEmptyCheck($default_limit)) {
        $output['code'] = 400;
        $output['msg_response'] = "All fields are required.";
        echo json_encode($output);
        exit();
    }

    // Validation: Check for duplicate fiscal year and semester
    $dup_query = "SELECT school_year_id FROM school_year WHERE school_year = '" . escape($db_connect, $school_year) . "' AND sem = '" . escape($db_connect, $sem) . "' LIMIT 1";
    $dup_result = call_mysql_query($dup_query, $db_connect);
    if ($dup_result && call_mysql_num_rows($dup_result) > 0) {
        $output['code'] = 400;
        $output['msg_response'] = "Failed to Create. Duplication detected.";
        echo json_encode($output);
        exit();
    }

    $result1 = 0;
    // Main logic in try-catch
    $db_connect->begin_transaction();
    try {
        $sql1 = "INSERT INTO school_year (school_year, sem, date_from, date_to, limit_perSem) 
        VALUES (
            '" . escape($db_connect, $school_year) . "',
            '" . escape($db_connect, $sem) . "',
            '" . escape($db_connect, $date_from) . "',
            '" . escape($db_connect, $date_to) . "',
            '" . escape($db_connect, $default_limit) . "')
        ";

        $result1 = call_mysql_query($sql1);
        $db_connect->commit();
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['code'] = 405;
        $output['msg_response'] = $e->getMessage();
        echo json_encode($output);
        exit();
    }

    if ($result1 === 0) {
        $output['code'] = 500;
        $output['msg_response'] = "Failed query.";
        echo json_encode($output);
        exit();
    }
    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_span'] = "";
    $output['msg_response'] = "Fiscal year created successfully.";
    echo json_encode($output);
    exit();

} else {
    $output['code'] = 500;
    $output['msg_response'] = "Invalid request method.";
    echo json_encode($output);
    exit();
}
?>