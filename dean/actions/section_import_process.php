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

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_404;
    exit();
}

if ($g_user_role !== "DEAN") {
    echo json_encode(["error" => "Invalid User"]);
    exit();
}

$uploader = new UploaderHandler();
$uploader->allowedExtensions = ['csv'];
$uploader->sizeLimit = CSV_SIZE;
$uploader->uploadDirectory = CSV_PATH;
$uploader->inputFileName = "import_section_file";

$result = $uploader->handleFileUpload();
$result["uploadName"] = $uploader->getUploadName();

if (!empty($result["error"])) {
    $result['total'] = 0;
    $result['success_insert'] = 0;
    $result['success_update'] = 0;
    $result['error_id'] = [];
    unset($result['success']);
    echo json_encode($result);
    exit();
}

if (!isset($result["success"]) && $result["uploadName"] == "") {
    echo json_encode(["error" => "Upload failed"]);
    exit();
}

$file = $uploader->getTargetFilePath();
if (($handle = fopen($file, "r")) === false) {
    echo json_encode(["error" => "Unable to read file"]);
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
            echo json_encode([
                "error" => "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]",
                "total" => $total_count,
                "success_insert" => 0,
                "success_update" => 0,
                "error_id" => []
            ]);
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

    // Determine if section already exists
    $exists = 0;
    $check = "SELECT class_id FROM class_section
              WHERE class_name = '" . escape($db_connect, $section_name) . "'
                AND program_id = '" . escape($db_connect, $program_id) . "'
                AND year_level = '" . escape($db_connect, $year_level) . "'
              LIMIT 1";
    if ($cq = call_mysql_query($check)) {
        $exists = call_mysql_num_rows($cq);
    }

    if ($exists > 0) {
        // UPDATE (only name/program/year_level)
        $update = "UPDATE class_section SET
            date_modified = NOW()
            WHERE class_name = '" . escape($db_connect, $section_name) . "'
              AND program_id = '" . escape($db_connect, $program_id) . "'
              AND year_level = '" . escape($db_connect, $year_level) . "'";
        if (call_mysql_query($update)) $success_update++;
    } else {
        // INSERT
        $insert = "INSERT INTO class_section
            (class_name, program_id, year_level, date_modified)
            VALUES (
                '" . escape($db_connect, $section_name) . "',
                '" . escape($db_connect, $program_id) . "',
                '" . escape($db_connect, $year_level) . "',
                NOW()
            )";
        if (call_mysql_query($insert)) $success_insert++;
    }

    $total_count++;
}

fclose($handle);

echo json_encode([
    "total" => $total_count - 1,
    "skipped" => $skipped_count,
    "success_insert" => $success_insert,
    "success_update" => $success_update,
    "error_id" => $return_error
]);
exit();
