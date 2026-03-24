<?php
set_time_limit(0);
ini_set('max_execution_time', '0');
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require UPLOAD_HANDLER;

$session_class->session_close();
header("Content-type: application/json; charset=utf-8");

## access validation
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_404;
    exit();
}

if (!($g_user_role == "REGISTRAR")) {
    $error = true;
    $response_msg['msg'] = '';
    $response_msg['errors'] = 'Invalid User';
    $response_msg['result'] = 'error';
    echo output($response_msg);
    exit();
}
##

$error = false;
$response_msg = array();
$response_msg['msg'] = "error";
$response_msg['errors'] = "Request Error";
$response_msg['result'] = "";

$to_encode = array();
$action = isset($_POST['action']) ? $_POST['action'] : '';
$data_cell =  isset($_POST['data']) ? $_POST['data'] : '';

$data_cell_array = json_decode($data_cell, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    $response_msg['msg'] = '';
    $response_msg['errors'] = 'Invalid Input!';
    $response_msg['result'] = 'error';
    echo output($response_msg);
    exit();
}

if (customIsEmpty($data_cell_array)) {
    $response_msg['msg'] = '';
    $response_msg['errors'] = 'Invalid Input!';
    $response_msg['result'] = 'error';
    echo output($response_msg);
    exit();
}

if ($action == "SAVE_GRADE") {
    $response_msg['errors'] = "Saving Request Error";

    if (!is_dir(PROOF_PATH)) {
        mkdir(PROOF_PATH, 0755);
    }


    $reason_text = isset($_POST['reason']) ? trim($_POST['reason']) : '';
    if (empty($reason_text)) {
        $response_msg['msg'] = '';
        $response_msg['errors'] = 'General Reason cannot be empty';
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    $fileUpload =  isset($_FILES['file_upload']['name']) ? trim($_FILES['file_upload']['name']) : '';
    $pathinfo = pathinfo($fileUpload);
    $ext = isset($pathinfo['extension']) ? $pathinfo['extension'] : '';

    $_REQUEST['qqfilename'] = "proof_" . uniqid() . date('Ymdhis') . "." . $ext;
    $_POST['qquuid'] = ""; //folder name


    $uploader = new UploaderHandler();
    $uploader->allowedExtensions = array('jpeg', 'png', 'jpg', 'gif'); // all files types allowed by default
    $uploader->sizeLimit = null; ## Specify max file size in bytes.
    $uploader->inputFileName = "file_upload"; // matches Fine Uploader's default inputName value by default
    $uploader->uploadDirectory = PROOF_PATH; ## Specify directory upload file.

    $result = $uploader->handleFileUpload();

    // To return a name used for uploaded file you can use the following line.
    $result["uploadName"] = $uploader->getUploadName();
    $newname = $uploader->getTargetFilePath(join(DIRECTORY_SEPARATOR, array(PROOF_PATH)));

    if (empty($result["uploadName"]) or isset($result['error'])) {
        $response_msg['msg'] = '';
        $response_msg['errors'] = isset($result['error']) ? $result['error'] : 'Error Uploading';
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    $error_found = false;
    $final_id_list = array();
    $data_new_array = array();

    $fy_id = isset($data_cell_array['fy_id']) && is_digit($data_cell_array['fy_id']) ? $data_cell_array['fy_id'] : '';

    $grade_rating = array();
    $temp_grade_rating = array();
    $temp_grade_rating = get_graderating($fy_id);
    if (!empty($temp_grade_rating)) {
        foreach ($temp_grade_rating as $_data) {
            $grade_rating[] = $_data['grade'];
        }
    }

    $add_temp = array("INC", "DRP", "LOA", "inc", "drp", "loa", "PASSED");
    $grade_rating = array_merge($grade_rating, $add_temp);

    $allowed_fields = [
        'student_name',
        'section_name',
        'subject_code',
        'course_desc',
        'program_code',
        'units',
        'final_grade',
        'final_grade_text',
        'converted_grade',
        'completion',
        'school_year',
        'sem',
        'remarks',
        'school_name',
        'credit_code'
    ];

    $data_new_array = [];
    $final_id_list = [];
    $error_found = '';

    foreach ($data_cell_array['data'] as $changes) {
        $final_id = isset($changes['final_grade_id']) ? decrypted_string($changes['final_grade_id'], GRADE_KEY) : '';

        if (!$final_id || !ctype_digit((string)$final_id)) {
            $error_found = 'Final Grade ID not valid';
            break;
        }
        $final_id_list[] = $final_id;
        foreach ($changes as $field => $value) {
            if (!in_array($field, $allowed_fields) || $field == 'final_grade_id') {
                continue; ## skip disallowed fields
            }

            switch ($field) {
                case 'final_grade':
                    $value = strtoupper($value);
                    if (is_numeric($value)) {
                        if (!($value >= 1 && $value <= 100)) {
                            $error_found = ucfirst(str_replace('_', ' ', $field)) . ' invalid ';
                            break 2;
                        }
                        $data_new_array[$final_id]['final_grade'] = $value;
                        $data_new_array[$final_id]['final_grade_text'] = '';
                    } else {
                        $data_new_array[$final_id]['final_grade'] = '';
                        $data_new_array[$final_id]['final_grade_text'] = $value;
                    }

                    continue 2;

                case 'final_grade_text':
                    $value = strtoupper($value);
                    if (!is_numeric($value)) {
                        $data_new_array[$final_id]['final_grade_text'] = $value;
                        $data_new_array[$final_id]['final_grade'] = '';
                    }
                    continue 2;

                case 'converted_grade':
                case 'completion':
                    $value = strtoupper($value);
                    if (!empty($value) && (!in_array($value, $grade_rating))) {
                        $error_found = ucfirst(str_replace('_', ' ', $field)) . ' invalid ';
                        break 2;
                    }
                    break;

                case 'units':
                    if (!is_numeric($value) && $value !== '') {
                        $error_found = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
                        break 2;
                    }
                    break;

                case 'student_name':
                case 'section_name':
                case 'subject_code':
                case 'course_desc':
                case 'program_code':
                case 'school_year':
                case 'sem':
                    $value = strtoupper(trim($value));
                    if ($value === '') {
                        $error_found = ucfirst(str_replace('_', ' ', $field)) . ' cannot be empty' . $value;
                        break 2;
                    }
                    break;
            }

            ## store valid value
            $data_new_array[$final_id][$field] = $value;
        }

        $data_new_array[$final_id]['hash_id'] = $changes['final_grade_id'];
    }

    if ($error_found !== '') {
        $response_msg['msg'] = '';
        $response_msg['errors'] = 'Invalid Data Inputted! ' . $error_found;
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    // $reason_text = isset($data_cell_array['reason']) ? trim($data_cell_array['reason']) : '';

    unset($data_cell_array);

    if ($error_found != false) {
        $response_msg['msg'] = '';
        $response_msg['errors'] = 'Invalid Data Inputted!' . $error_found;
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    if (empty($data_new_array)) {
        $response_msg['msg'] = '';
        $response_msg['errors'] = 'Empty Data!';
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    $log_saving = [];

    $select_query = "SELECT * FROM final_grade WHERE final_id IN (" . implode(",", $final_id_list) . ")";
    if ($query = call_mysql_query($select_query)) {
        while ($data = call_mysql_fetch_array($query, MYSQLI_ASSOC)) {
            $id = $data['final_id'];
            $has_change = false;
            $changes = [];

            if (isset($data_new_array[$id])) {
                foreach ($allowed_fields as $field) {
                    $old_value = trim($data[$field]);
                    $new_value = isset($data_new_array[$id][$field]) ? trim($data_new_array[$id][$field]) : $old_value;

                    ## if values differ, record change
                    if ($new_value !== $old_value) {
                        $has_change = true;
                        $changes[$field] = [
                            'old' => $old_value,
                            'new' => $new_value
                        ];
                    }
                }
            }

            if ($has_change) {
                $log_saving[$id] = [
                    'final_id' => $data['final_id'] ?? '', ## fallback to empty if not set
                    'student_id' => $data['student_id'] ?? '',
                    'student_id_text' => $data['student_id_text'] ?? '',
                    'student_name' => $data['student_name'] ?? '',
                    'subject_code' => $data['subject_code'] ?? '',
                    'course_desc' => $data['course_desc'] ?? '',
                    'school_year' => $data['school_year'] ?? '',
                    'sem' => $data['sem'] ?? '',
                    'changes' => isset($changes) ? $changes : []  ## ensure $changes exists
                ];
            }
        }
        mysqli_free_result($query);
    }

    $db_connect->begin_transaction();

    try {
        $reason_text = escape($db_connect, $reason_text);
        $newname = escape($db_connect, $newname);
        $s_user_id = escape($db_connect, $s_user_id);

        $updated_array = [];

        foreach ($data_new_array as $final_id => $fields) {
            $set_clauses = [];
            foreach ($fields as $field => $value) {
                if ($field === 'hash_id') continue;
                $escaped_field = $db_connect->real_escape_string($field);
                $escaped_value = $db_connect->real_escape_string($value);

                $set_clauses[] = "`$escaped_field` = '$escaped_value'";
            }

            if (isset($log_saving[$final_id])) {

                $set_clause_sql = implode(', ', $set_clauses);

                $update_query = "UPDATE final_grade SET $set_clause_sql, date_updated = NOW() WHERE final_id = '" . escape($db_connect, $final_id) . "'";
                if (!call_mysql_query($update_query)) {
                    throw new Exception("Update failed for ID: $final_id");
                }

                $text = "SUCCESS";
                $log_saving[$final_id]['result'] = $text;
                $data_json = json_encode($log_saving[$final_id], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

                $insert_query = "INSERT INTO grade_tracker (data_changed, reason, upload_file, date_added, added_by) VALUES ('" . escape($db_connect, $data_json) . "','$reason_text','$newname','" . DATE_NOW . " " . TIME_NOW . "','$s_user_id')";
                if (!call_mysql_query($insert_query)) {
                    throw new Exception("Insert log failed for ID: $final_id");
                }
            } else {
                $text = 'NO CHANGES';
            }

            // safely store result
            if (isset($fields['hash_id'])) {
                $new_final_id = $fields['hash_id'];
                $updated_array[$new_final_id] = $fields;
                $updated_array[$new_final_id]['result'] = $text;
            } else {
                $updated_array[$final_id] = $fields;
                $updated_array[$final_id]['result'] = $text;
            }
        }

        $data_new_array = $updated_array;
        activity_log_new("UPDATED GRADE :: Details: " . json_encode($data_new_array));

        $db_connect->commit();
        $result_text = "success";
    } catch (Exception $e) {
        $data_new_array = [];
        $db_connect->rollback();
        $result_text = "error";
    }

    $response_msg['msg'] = $data_new_array;
    $response_msg['errors'] = '';
    $response_msg['result'] = $result_text;
    echo output($response_msg);
    exit();
}

if ($action == 'DELETE_ID') {
    $response_msg['errors'] = "Deletion Request Error";

    $errors = '';
    $final_id = '';
    $log_saving = [];
    if (isset($data_cell_array['final_id']) && !empty($data_cell_array['final_id'])) {
        $final_id = decrypted_string($data_cell_array['final_id'], GRADE_KEY);
        $select_query = "SELECT * FROM final_grade WHERE final_id = '" . escape($db_connect, $final_id) . "'";
        if ($query = call_mysql_query($select_query)) {
            while ($data = call_mysql_fetch_array($query)) {
                $id = $data['final_id'];
                $log_saving[$id] = $data;
            }
            mysqli_free_result($query);
        }
    } else {
        $response_msg['msg'] = '';
        $response_msg['errors'] = 'Invalid Data';
        $response_msg['result'] = 'error';
        echo output($response_msg);
        exit();
    }

    $db_connect->begin_transaction();
    $text = '';
    try {
        if (isset($log_saving[$final_id])) {
            $delete_query = "DELETE FROM final_grade WHERE status = '1' AND final_id = '" . escape($db_connect, $final_id) . "'";
            $query = call_mysql_query($delete_query);
            if (call_mysql_affected_rows() > 0) {
                $text = "SUCCESS";
            } else {
                $text = "NO MATCHING ROWS";
            }

            $log_saving[$final_id]['result'] = $text;
            $data_json = json_encode($log_saving[$final_id], JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);

            $insert_query = "INSERT INTO grade_tracker (data_changed, reason, upload_file, date_added, added_by) VALUES ('" . escape($db_connect, $data_json) . "', 'DELETED GRADE', '', '" . DATE_NOW . " " . TIME_NOW . "', '" . $s_user_id . "')";
            call_mysql_query($insert_query);

            activity_log_new("DELETED GRADE :: Details: " . json_encode($log_saving));

            $db_connect->commit();
            $result_text = "success";
            $errors = '';
        }
    } catch (Exception $e) {
        $db_connect->rollBack();
        $data_new_array = array();
        $errors = "Failed to delete the record.";
        $result_text = "error";
    }

    $response_msg['msg'] = $text;
    $response_msg['errors'] = $errors;
    $response_msg['result'] = $result_text;
    echo output($response_msg);
    exit();
}

echo output($response_msg);
exit();
