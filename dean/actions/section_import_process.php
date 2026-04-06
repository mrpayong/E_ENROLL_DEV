<?php
set_time_limit(0);
ini_set('max_execution_time', '0');
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require UPLOAD_HANDLER;

$session_class->session_close();
header("Content-type: application/json; charset=utf-8");

try {
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_404;
    exit();
}

$response = array(
    'code' => 0,
    'msg_status' => false,
    'msg_response' => 'Request error, please try again.',
    'msg_span' => '_system',
);

if ($g_user_role !== "DEAN") {
    $response['code'] = 501;
    $response['msg_response'] = "Invalid User";
    echo json_encode($response);
    exit();
}

$uploader = new UploaderHandler();
$uploader->allowedExtensions = ['csv'];
$uploader->sizeLimit = CSV_SIZE;
$uploader->uploadDirectory = CSV_PATH;
$uploader->inputFileName = "import_section_file";

$result = $uploader->handleFileUpload();
$result["uploadName"] = $uploader->getUploadName();
$school_year_id = isset($_POST['school_year_id']) ? intVal($_POST['school_year_id']) : 0;

if (!empty($result["error"])) {
    $response['code'] = 502;
    $response['msg_response']  = $result["error"];
    echo json_encode($response);
    exit();
}

if (!isset($result["success"]) && $result["uploadName"] == "") {
    $response['code'] = 503;
    $response['msg_response']  = "Upload failed";
    echo json_encode($response);
    exit();
}

$file = $uploader->getTargetFilePath();
if (($handle = fopen($file, "r")) === false) {
    $response['code'] = 504;
    $response['msg_response']  = "Unable to read file";
    echo json_encode($response);
    exit();
}

$total_count = 1;
$success_insert = 0;
$success_update = 0;
$skipped_count = 0;
$return_error = [];

$required_header = [
    'SECTION NAME',
    'PROGRAM CODE',
    'YEAR LEVEL'
];

$fixed_header = $required_header;
$user_header = [];
$error_header = false;
$found_header_error = [];

while (($column = fgetcsv($handle, 0, ",")) !== false) {
    foreach ($column as $index => $value) {
        $column[$index] = trim($value);
    }

    // Header row
    if ($total_count == 1) {
        $column = array_map('strtoupper', $column);
        foreach ($fixed_header as $header) {
            $key = array_search($header, $column, true);
            if ($key !== false) {
                $user_header[$header] = $key;
            } else {
                $error_header = true;
                $found_header_error[] = $header;
            }
        }

        if ($error_header) {
            fclose($handle);
            $response['code'] = 505;
            $response['msg_response']  = "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]";
            echo json_encode($response);
            exit();
        }

        $skipped_count++;
        $total_count++;
        continue;
    }

    // Skip row 2 (helper row like "All Columns required")
    if ($total_count == 2) {
        $skipped_count++;
        $total_count++;
        continue;
    }

    $row = [];
    foreach ($user_header as $header => $key) {
        $row[$header] = trim($column[$key] ?? '');
    }

    // Required checks
    $missing = [];
    foreach ($required_header as $h) {
        if ($row[$h] === '') $missing[] = $h;
    }
    if (!empty($missing)) {
        $return_error[] = [
            "id" => "row_" . $total_count,
            "msg" => "Missing required: " . implode(", ", $missing)
        ];
        $total_count++;
        continue;
    }

    $section_name = strtoupper($row['SECTION NAME']);
    $program_code = strtoupper($row['PROGRAM CODE']);
    $year_level = (int)$row['YEAR LEVEL'];

    if ($year_level < 1 || $year_level > 5) {
        $return_error[] = [
            "id" => "row_" . $total_count,
            "msg" => "Invalid YEAR LEVEL"
        ];
        $total_count++;
        continue;
    }

    // Find program_id by short_name
    $program_id = 0;
    $pquery = "SELECT program_id FROM programs WHERE UPPER(short_name) = '" . escape($db_connect, $program_code) . "' LIMIT 1";
    if ($pq = call_mysql_query($pquery)) {
        if ($p = call_mysql_fetch_array($pq)) {
            $program_id = (int)$p['program_id'];
        }
    }
    if ($program_id === 0) {
        $return_error[] = [
            "id" => "row_" . $total_count,
            "msg" => "PROGRAM CODE not found"
        ];
        $total_count++;
        continue;
    }

    $db_connect->begin_transaction();
    $insert = "INSERT INTO class_section
        (class_name, program_id, year_level, school_year_id, date_modified)
        VALUES (
            '" . escape($db_connect, $section_name) . "',
            '" . escape($db_connect, $program_id) . "',
            '" . escape($db_connect, $year_level) . "',
            '" . escape($db_connect, $school_year_id) . "',
            NOW()
        )";
    if (call_mysql_query($insert)) $success_insert++;
    $db_connect->commit();
    

    $total_count++;
}
fclose($handle);
$response['msg_status'] = true;
$response['code'] = 200;
$response['msg_response']  = "Import completed.";
$response['total'] = $total_count - 1;
$response['skipped'] = $skipped_count;
$response['success_insert'] = $success_insert;
$response['success_update'] = $success_update;
$response['error_id'] = $return_error;
echo json_encode($response);
exit();

} catch (Throwable $th) {
    if (isset($handle) && is_resource($handle)) fclose($handle);
    $db_connect->rollback();
    $response['code'] = 500;
    $response['msg_response']  = "Import failed, unknown error. Please flag for IT Support.";
    echo json_encode($response);
    exit();
}

