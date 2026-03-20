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

function upload_log($file_path, $log)
{
    file_put_contents($file_path, $log, FILE_APPEND);
}

## check the text logs folder
if (!is_dir(TEXT_LOGS_PATH)) {
    mkdir(TEXT_LOGS_PATH, 0755);
}

$uploader = new UploaderHandler();
$uploader->allowedExtensions = array('csv'); // all files types allowed by default
$uploader->sizeLimit = CSV_SIZE; ## Specify max file size in bytes.
$uploader->uploadDirectory = CSV_PATH; ## Specify directory upload file.
$uploader->inputFileName = "import_file_grade"; // matches Fine Uploader's default inputName value by default
$method = get_request_method();

$session_class->session_close();


function get_request_method()
{
    global $HTTP_RAW_POST_DATA;

    if (isset($HTTP_RAW_POST_DATA)) {
        parse_str($HTTP_RAW_POST_DATA, $_POST);
    }

    if (isset($_POST["_method"]) && $_POST["_method"] != null) {
        return $_POST["_method"];
    }

    return $_SERVER["REQUEST_METHOD"];
}

function data_encoding($column = array())
{
    $result = [];
    if (is_array($column)) {
        foreach ($column as $index => $value) { //get data column
            $result[$index] = customTrim($value);
            if (mb_detect_encoding($value)) {
                if (!mb_check_encoding($column, 'UTF-8')) {
                    $result[$index] =  mb_convert_encoding($value, "UTF-8", mb_detect_encoding($value));
                }
            }
        }
    }
    unset($column);

    return $result;
}


if ($method == "POST") {
    header("Content-Type: text/plain");

    $result = $uploader->handleFileUpload();

    ## To return a name used for uploaded file you can use the following line.
    $result["uploadName"] = $uploader->getUploadName();

    if (!empty($result["error"])) {
        $result["error"] = $result["error"];
        $result['total'] = 0;
        $result['success_insert'] =  0;
        $result['success_update'] =  0;
        $result['error_id'] = [];
        unset($result['success']);

        echo json_encode($result);
        exit();
    }

    ## na finish na iupload both chunked and not
    if ((isset($result["success"])) || ($result["uploadName"] != "")) {
        $file = $uploader->getTargetFilePath();
        if (($handle = fopen($file, "r")) !== FALSE) {
            $file_logs = "";
            $bulk_process = false;
            $time_id = "IMPORT_GRADE_" . time();
            $file_path = TEXT_LOGS_PATH . $time_id . ".txt";
            $new_line = "\r\n";
            $limitDate = date('Y-m-d', strtotime('-15 years'));


            $total_count = 1;
            $row_number = 0;
            $success_count = 0;
            $success_insert = 0;
            $success_update = 0;
            $error_count = 0;
            $skipped_count = 0;

            $return_error = array();
            $collect_ids = array();
            $duplicates = array();
            $counter = array();
            $credentials_log = array();
            $error_found = array();
            $user_header = array();

            $error_header =  false;
            $error_process = false;
            $no_record = true;

            $required_header = array('STUDENT ID', 'PROGRAM', 'MAJOR',  'YEAR LEVEL', 'SECTION', 'COURSE', 'COURSE DESCRIPTION', 'UNITS', 'FINAL GRADE', 'RATING', 'REMARKS', 'FISCAL YEAR', 'SEM');
            $not_required = array('STUDENT NAME', 'CREDIT CODE', 'SCHOOL NAME');
            $fixed_header = array_merge($required_header, $not_required);

            ## 

            $collect_user = [];
            while (($column = fgetcsv($handle, 0, ",")) !== FALSE) {
                $transaction_status = "FAILED";
                $error_found = array("msg" => "", "id" => "");
                $num = 0;
                $blank = false;
                $error = false;
                $duplicate_record = false;
                $data = array();
                $user_data = array(); //header set
                $data_clean = array();
                $user_clean = array();
                $db_update = array();
                $found_header_error = array();

                foreach ($column as $index => $value) {
                    $column[$index] = trim($value);
                    if (!mb_check_encoding($column, 'UTF-8')) {
                        $encoding = mb_detect_encoding($value, ['ISO-8859-1', 'Windows-1252', 'UTF-8'], true);
                        $column[$index] = mb_convert_encoding($value, 'UTF-8', $encoding ?: 'ISO-8859-1');
                    }
                }

                if (($total_count == 2)) { //skipped rows 1-2 start in row 3
                    $file_logs = "File Line No. " . ($total_count) . " : SKIPPED " . $new_line;
                    upload_log($file_path, $file_logs);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }

                $column = data_encoding($column);
                $column = array_map("trim", $column);

                if ($total_count == 1) {
                    $column = array_map('strtoupper', $column);
                    foreach ($fixed_header as $index => $header) {
                        $key =  array_search($header, $column, true); //search column no. 
                        if ($key !== false) { //if found in csv header
                            $user_header[$header] = $key;
                        } else if (in_array($header, $required_header)) { //check for required column
                            $error_header = true;
                            array_push($found_header_error, $header);
                        } else { //assign blank value for unimportant column
                            $user_header[$header] = false;
                        }
                    }


                    if ($error_header) {  //header error found
                        $result['total'] = $total_count;
                        $result['success_insert'] =  0;
                        $result['success_update'] =  0;
                        $result['error_id'] = [];
                        $result['error'] = "FILE CSV HEADER INVALID - NOT FOUND [" . implode(",", $found_header_error) . "]";

                        $file_logs = "File Line No. " . ($total_count) . " : " . $result['error'] . " : HEADER" . $new_line;
                        upload_log($file_path, $file_logs);

                        unset($result['success']);
                        echo json_encode($result);
                        exit();
                    }

                    $file_logs = "File Line No. " . ($total_count) . " : " . json_encode($user_header, JSON_INVALID_UTF8_SUBSTITUTE) . " : HEADER" . $new_line;
                    upload_log($file_path, $file_logs);
                    $skipped_count++;
                    $total_count++;
                    continue;
                }


                foreach ($user_header as $header => $key) {
                    $user_data[$header] = ($key === false) ? '' : $column[$key]; // assign row to correct column header
                }

                $column = array();
                $column = $user_data;

                unset($user_data);

                foreach ($column as $index => $value) {
                    if ($index == 'SECTION') {
                        $column[$index] = strtolower($column[$index]);
                    }

                    if ($index == 'COURSE') {
                        $column[$index] = mb_strtoupper($column[$index]);
                    }

                    if (in_array($index, $not_required)) {
                        continue;
                    } else if (trim($value) == "") {
                        $blank = true;
                        $error_found['msg'] = "File Line No. " . ($total_count) . " : Missing a required data in row [" . $index . "]" . $separator;
                        $error_found['id'] = $error_found['id'] = "row_" . $total_count;
                        $return_error[] = $error_found;
                        break;
                    }
                }

                if ($blank == true) {
                    $file_logs = "File Line No. " . ($total_count) . " : " . $column['STUDENT ID'] . " : " . $error_found['msg'] . ":" . $transaction_status . $new_line;
                    upload_log($file_path, $file_logs);
                    $total_count++;
                    continue;
                }

                $error_found['id'] = "row_" . $total_count;

                $no_record = false;

                ## student information
                $student_id = 0;
                $column['STUDENT NAME'] = trim($column['STUDENT NAME']);
                $student_program_id = 0;
                $student_major = "";
                $sql_query  = "SELECT student_id,lastname,firstname,middle_name,suffix_name,program_id,major,year_level FROM student WHERE student_id_no = '" . escape($db_connect, $column['STUDENT ID']) . "' LIMIT 1";
                $query_student = call_mysql_query($sql_query);
                if ($query_student !== false) {
                    $rdata = mysqli_fetch_array($query_student, MYSQLI_ASSOC);
                    $student_id = $rdata['student_id'];
                    $middle = ($rdata['middle_name'] == "") ? '' : $rdata['middle_name'];
                    $suffix = ($rdata['suffix_name'] == "") ? '' : $rdata['suffix_name'];
                    $column['STUDENT NAME'] = mb_strtoupper($rdata['lastname'] . ", " . $rdata['firstname'] . " " . $middle . $suffix, "UTF-8");
                    $student_program_id = $rdata['program_id'];
                    $student_major = $rdata['major'];
                    mysqli_free_result($query_student);
                }

                if ($student_id == 0) {
                    $error = true;
                    $error_found['msg'] .= "Student ID " . $column['STUDENT ID'] . " doesn\'t exist in DATABASE!" . $separator;
                }

                ## major
                $column['MAJOR'] = (mb_strtoupper($column['MAJOR'], "UTF-8") == "XXX-CCC-XXX") ? "" : $column['MAJOR'];

                ## year level
                $year_list = array(1, 2, 3, 4, 5);
                if (!in_array($column['YEAR LEVEL'], $year_list)) {
                    $error = true;
                    $error_found['msg'] .= "Year Level " . $column['YEAR LEVEL'] . " Invalid!" . $separator;
                }
                $year_level = $column['YEAR LEVEL'];

                $count_class = 0;
                $return_teacher_class = 0;
                $return_student_class = 0;
                $count_student = 0;

                ## final grade
                $final_grade = "";
                $final_grade_insert = "0";
                $final_grade_text = "";
                if (is_digit($column['FINAL GRADE'])) {
                    $final_grade = ",final_grade = '" . escape($db_connect, $column['FINAL GRADE']) . "'";
                    $final_grade_insert = escape($db_connect, $column['FINAL GRADE']);
                } else {
                    $final_grade = ",final_grade_text = '" . escape($db_connect, $column['FINAL GRADE']) . "'";
                    $final_grade_text = escape($db_connect, $column['FINAL GRADE']);
                }

                ## semester
                $column['SEM'] = mb_strtoupper($column['SEM'], "UTF-8");

                /** error encounter */
                if ($error === true) {
                    $file_logs = "File Line No. " . ($total_count) . " : " . $column['STUDENT ID'] . " : " . $error_found['msg'] . " : " . $transaction_status . $new_line;
                    upload_log($file_path, $file_logs);

                    $error_found['msg'] = $file_logs;
                    $return_error[] = $error_found;
                    $total_count++;
                    continue;
                }

                $db_connect->begin_transaction();
                try {
                    $query_student = call_mysql_query("SELECT student_id,subject_code,school_year,sem FROM final_grade WHERE student_id = '" . escape($db_connect, $student_id) . "' AND subject_code = '" . escape($db_connect, $column['COURSE']) . "' AND school_year = '" . escape($db_connect, $column['FISCAL YEAR']) . "' AND sem = '" . escape($db_connect, $column['SEM']) . "' AND status ='1'");
                    if ($query_student) {
                        $count_student = call_mysql_num_rows($query_student);
                        if ($count_student > 0) {
                            $student_class_data = mysqli_fetch_assoc($query_student);
                            $update_into = "UPDATE  final_grade  SET student_id = '" . escape($db_connect, $student_id) . "',student_name = '" . escape($db_connect, $column['STUDENT NAME']) . "',student_id_text = '" . escape($db_connect, $column['STUDENT ID']) . "',section_name = '" . escape($db_connect, $column['SECTION']) . "',subject_code = '" . escape($db_connect, $column['COURSE']) . "',course_desc = '" . escape($db_connect, $column['COURSE DESCRIPTION']) . "',program_code = '" . escape($db_connect, $column['PROGRAM']) . "',major = '" . escape($db_connect, $column['MAJOR']) . "',units = '" . escape($db_connect, $column['UNITS']) . "',midterm_grade = '" . escape($db_connect, $column['MID TERM GRADE']) . "',finalterm_grade = '" . escape($db_connect, $column['FINAL TERM GRADE']) . "'" . $final_grade . ",converted_grade = '" . escape($db_connect, $column['RATING']) . "',school_year_id = '0',school_year = '" . escape($db_connect, $column['FISCAL YEAR']) . "',sem = '" . escape($db_connect, $column['SEM']) . "',remarks = '" . escape($db_connect, $column['REMARKS']) . "',yr_level = '" . escape($db_connect, $column['YEAR LEVEL']) . "',school_name = '" . escape($db_connect, $column['SCHOOL NAME']) . "',credit_code = '" . escape($db_connect, $column['CREDIT CODE']) . "', status='1',date_updated = '" . escape($db_connect, DATE_NOW . " " . TIME_NOW) . "' WHERE student_id = '" . escape($db_connect, $student_id) . "' AND subject_code = '" . escape($db_connect, $column['COURSE']) . "' AND school_year = '" . escape($db_connect, $column['FISCAL YEAR']) . "' AND sem = '" . escape($db_connect, $column['SEM']) . "'";

                            if (call_mysql_query($update_into) === false) {
                                throw new Exception("Unable to save record of student in final grade");
                            } else {
                                $transaction_status = "SUCCESS";
                                $error_found['msg'] .= 'UPDATE';
                                $success_update++;
                                $success_count++;
                            }
                        } else {
                            $insert_into = "INSERT INTO final_grade (student_id,student_name,student_id_text,section_name,subject_code,course_desc,program_code,major,units,midterm_grade,finalterm_grade,final_grade,final_grade_text,converted_grade,school_year_id,school_year,sem,remarks,yr_level,school_name,date_added,status,credit_code) VALUES ('" . escape($db_connect, $student_id) . "','" . escape($db_connect, $column['STUDENT NAME']) . "','" . escape($db_connect, $column['STUDENT ID']) . "','" . escape($db_connect, $column['SECTION']) . "','" . escape($db_connect, $column['COURSE']) . "','" . escape($db_connect, $column['COURSE DESCRIPTION']) . "','" . escape($db_connect, $column['PROGRAM']) . "','" . escape($db_connect, $column['MAJOR']) . "','" . escape($db_connect, $column['UNITS']) . "','" . escape($db_connect, $column['MID TERM GRADE']) . "','" . escape($db_connect, $column['FINAL TERM GRADE']) . "','" . $final_grade_insert . "','" . $final_grade_text . "','" . escape($db_connect, $column['RATING']) . "','0','" . escape($db_connect, $column['FISCAL YEAR']) . "','" . escape($db_connect, $column['SEM']) . "','" . escape($db_connect, $column['REMARKS']) . "','" . escape($db_connect, $column['YEAR LEVEL']) . "','" . escape($db_connect, $column['SCHOOL NAME']) . "','" . escape($db_connect, DATE_NOW . " " . TIME_NOW) . "','1', '" . escape($db_connect, $column['CREDIT CODE']) . "')";
                            if (call_mysql_query($insert_into) === false) {
                                throw new Exception("Unable to save record of student in final grade");
                            } else {
                                $transaction_status = "SUCCESS";
                                $error_found['msg'] .= 'INSERT';
                                $success_insert++;
                                $success_count++;
                            }
                        }
                    }
                    $db_connect->commit();
                } catch (Exception $exception) {
                    $db_connect->rollback();
                    $transaction_status = "FAILED";
                    $error_found['msg'] .= $exception->getMessage();
                }

                $file_logs = "File Line No. " . ($total_count) . " : " . $column['STUDENT ID'] . " : " . $error_found['msg'] . ":" . $transaction_status . $new_line;
                upload_log($file_path, $file_logs);
                $error_found['msg'] = $file_logs;
                if ($transaction_status === 'FAILED') {
                    $return_error[] = $error_found;
                }

                $total_count++;
            }

            fclose($handle);
        }


        $result['total'] =  $total_count - 1;
        $result['skipped'] =  $skipped_count;
        $result['success_insert'] =  $success_insert;
        $result['success_update'] =  $success_update;
        $result['error_id'] = $return_error;

        if ($no_record) {
            $result['error'] = 'File has no Record';
            unset($result['success']);
        } else if ($success_count == 0) {
        } else {
            activity_log_new("IMPORT BULK GRADE :: [" . $time_id . "]");
            $path = DOMAIN_PATH . "/upload/logs/summary_import_grade.log";
            $name = $session_class->getValue('fullname');
            update_summary_logs($path, $time_id, $name);
        }
    }

    echo json_encode($result);
    exit();
} else if ($method == "DELETE") { ## for delete file requests
    //result = $uploader->handleDelete(join(DIRECTORY_SEPARATOR,array(DOMAIN_PATH,'upload','csv')));
    //echo json_encode($result);
} else {
    include HTTP_401;
    exit();
}
