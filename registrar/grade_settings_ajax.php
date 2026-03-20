<?php

set_time_limit(0);
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$session_class->session_close();

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_404;
    exit();
}

header("Content-type: application/json; charset=utf-8");

$response_msg = array();
$response_msg['errors'] = "";
$response_msg['result'] = "";
$response_msg['msg'] = "";

if (!($g_user_role == "REGISTRAR")) {
    $response_msg['msg'] = '';
    $response_msg['errors'] = 'Invalid User Role';
    $response_msg['result'] = 'error';
    echo output($response_msg);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$data =  isset($_POST['data']) ? $_POST['data'] : '';
$school_year =  isset($_POST['school_year']) ? $_POST['school_year'] : '';
$type =  isset($_POST['type']) ? trim($_POST['type']) : '';

$error = false;
$to_encode = array();

if (!is_digit($school_year) or $school_year < 0) {
    $response_msg['errors'] = "Invalid Fiscal Year";
    $response_msg['msg'] = $school_year;
    $response_msg['result'] = "error";
    echo output($response_msg);
    exit();
}

if (empty($type)) {
    $response_msg['errors'] = "Invalid Type";
    $response_msg['msg'] = '';
    $response_msg['result'] = "error";
    echo output($response_msg);
    exit();
}

$allowed_type = ['subject_exceptions', 'grade_system', 'additional'];

if ($action == "SETTING") {
    $data_json = json_decode($data);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $error = true;
        $response_msg['errors'] = "Invalid Data";
        $response_msg['msg'] = '';
        $response_msg['status'] = "error";
        echo output($response_msg);
        exit();
    }

    if ($type == "subject_exceptions") {
        $data_json = array_unique($data_json);
    }

    $data_json = json_encode($data_json, JSON_PRESERVE_ZERO_FRACTION | JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);

    if (!in_array($type, $allowed_type)) {
        $error = true;
        $response_msg['errors'] = "Invalid Module";
        $response_msg['msg'] = '';
        $response_msg['status'] = "error";
        echo output($response_msg);
        exit();
    }

    $setting_query = "INSERT INTO  settings ( module, settings, school_year_id, date_added ) VALUES ('" . escape($db_connect, $type) . "','" . escape($db_connect, $data_json) . "','" . escape($db_connect, $school_year) . "','" . DATE_NOW . ' ' . TIME_NOW . "') ON DUPLICATE KEY UPDATE settings = VALUES(settings) , date_added = VALUES(date_added) ";
    $result = call_mysql_query($setting_query);
    $extend_text = "UPDATED";
    if ($result) {
        if (mysqli_affected_rows($db_connect)) {
            $extend_text = "ADDED";
        }

        activity_log_new($extend_text . " GRADE SETTING :: " . $type . " [FY-ID: " . $school_year . "] :: DETAILS : \r\n(" . $data . ")");

        $response_msg['errors'] = "";
        $response_msg['msg'] = '';
        $response_msg['result'] = "success";
        echo output($response_msg);
        exit();
    }

    $response_msg['errors'] = "Unable to save";
    $response_msg['msg'] = '';
    $response_msg['result'] = "error";
    echo output($response_msg);
    exit();
}


$response_msg['errors'] = "Request Error";
$response_msg['msg'] = '';
$response_msg['result'] = "error";
echo output($response_msg);
exit();
