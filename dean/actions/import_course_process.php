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

    $response = [
        'code' => 0,
        'msg_status' => false,
        'msg_response' => 'Request error, please try again.',
        'msg_span' => '_system',
    ];

    if ($g_user_role !== "DEAN") {
        $response['code'] = 501;
        $response['msg_response'] = "Invalid User";
        echo json_encode($response);
        exit();
    }

    // Required context coming from frontend
    $curriculum_id = isset($_POST['curriculum_id']) ? intVal($_POST['curriculum_id']) : '';
    $program_id = isset($_POST['program_id']) ? intVal($_POST['program_id']) : '';

    function dataEmptyCheck($val){
        return ($val === null || $val === '' || $val === 0);
    }

    if (dataEmptyCheck($curriculum_id) || dataEmptyCheck($program_id)) {
        $response['code'] = 502;
        $response['msg_response'] = "Missing curriculum_id or program_id.";
        echo json_encode($response);
        exit();
    }

    // Optional: fetch curriculum title
    $curriculum_title = "";
    $q = "SELECT header FROM curriculum_master WHERE curriculum_id = '".escape($db_connect,$curriculum_id)."' LIMIT 1";
    if ($rs = call_mysql_query($q)) {
        if ($r = call_mysql_fetch_array($rs)) {
            $curriculum_title = $r['header'];
        }
    }

    $uploader = new UploaderHandler();
    $uploader->allowedExtensions = ['csv'];
    $uploader->sizeLimit = CSV_SIZE;
    $uploader->uploadDirectory = CSV_PATH;
    $uploader->inputFileName = "import_course_file"; // must match your input name

    $result = $uploader->handleFileUpload();
    $result["uploadName"] = $uploader->getUploadName();

    if (!empty($result["error"])) {
        $response['code'] = 503;
        $response['msg_response'] = $result["error"];
        echo json_encode($response);
        exit();
    }

    if (!isset($result["success"]) && $result["uploadName"] == "") {
        $response['code'] = 504;
        $response['msg_response'] = "Upload failed";
        echo json_encode($response);
        exit();
    }


    $file = $uploader->getTargetFilePath();
    if (($handle = fopen($file, "r")) === false) {
        $response['code'] = 505;
        $response['msg_response'] = "Unable to read file";
        echo json_encode($response);
        exit();
    }


    $total_count = 1;
    $success_insert = 0;
    $skipped_count = 0;
    $return_error = [];
    $parsed_courses = [];

    $required_header = [
        'COURSE TITLE',
        'COURSE CODE',
        'UNIT',
        'LEC',
        'LAB',
        'YEAR LEVEL',
        'PRE-REQ',
        'SEMESTER'
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
                $response['code'] = 506;
                $response['msg_response'] = "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]";
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
            $row[$header] = trim($column[$key]);
        }

        // Required checks
        $missing = [];
        foreach ($required_header as $h) {
            if ($h === 'PRE-REQ') continue;
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

        $course_title = $row['COURSE TITLE'];
        $course_code  = $row['COURSE CODE'];
        $unit = (int)$row['UNIT'];
        $lec  = (int)$row['LEC'];
        $lab  = (int)$row['LAB'];
        $pre_req = $row['PRE-REQ'];
        $year_level = (int)$row['YEAR LEVEL'];
        $semester = $row['SEMESTER'];

        if ($year_level < 1 || $year_level > 5) {
            $return_error[] = [
                "id" => "row_" . $total_count,
                "msg" => "Invalid YEAR LEVEL"
            ];
            $total_count++;
            continue;
        }

        $lec_lab = json_encode([$lec, $lab]);

        // $db_connect->begin_transaction();
        // $insert = "INSERT INTO curriculum
        //     (curriculum_id, program_id, curriculum_title, year_level, semester,
        //      subject_id, subject_code, subject_title, description, unit, lec_lab, pre_req, status, createdAt)
        //     VALUES (
        //         '".escape($db_connect, $curriculum_id)."',
        //         '".escape($db_connect, $program_id)."',
        //         '".escape($db_connect, $curriculum_title)."',
        //         '".escape($db_connect, $year_level)."',
        //         '".escape($db_connect, $semester)."',
        //         NULL,
        //         '".escape($db_connect, $course_code)."',
        //         '".escape($db_connect, $course_title)."',
        //         '',
        //         '".escape($db_connect, $unit)."',
        //         '".escape($db_connect, $lec_lab)."',
        //         '".escape($db_connect, $pre_req)."',
        //         0,
        //         NOW()
        //     )";
        // if (call_mysql_query($insert)) $success_insert++;
        // $db_connect->commit();
        $parsed_courses[] = [
            "subject_id" => "", // empty because not saved yet
            "subject_code" => $course_code,
            "subject_title" => $course_title,
            "unit" => $unit,
            "lec" => $lec,
            "lab" => $lab,
            "pre_req" => $pre_req,
            "year_level" => $year_level,
            "semester" => $semester
        ];
        $success_insert++;

        $total_count++;
    }

    fclose($handle);

    $response['msg_status'] = true;
    $response['code'] = 200;
    $response['courses_upload'] = $parsed_courses;
    $response['msg_response'] = "Import completed.";
    $response['total'] = $total_count - 1;
    $response['skipped'] = $skipped_count;
    $response['success_insert'] = $success_insert;
    $response['error_id'] = $return_error;
    echo json_encode($response);
    exit();

} catch (Throwable $th) {
    if (isset($handle) && is_resource($handle)) fclose($handle);
    $db_connect->rollback();
    $response['code'] = 500;
    $response['msg_response'] = "Import failed, unknown error. Please flag for IT Support.";
    echo json_encode($response);
    exit();
}
