<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitAddUnits']) && $_POST['submitAddUnits'] === "createUnits"){
        $student_id_no = isset($_POST['student_id_no']) ? trim($_POST['student_id_no']) : '';
        $year_level = isset($_POST['yr_lvl']) ? intVal(trim($_POST['yr_lvl'])) : '';
        $load_flag = isset($_POST['load_flag']) ? trim($_POST['load_flag']) : '';
        $mod_unit = isset($_POST['additional_units']) ? intVal(trim($_POST['additional_units'])) : 0;
        $school_year_id = isset($_POST['school_year_id']) ? trim($_POST['school_year_id']) : '';
        $curriculum_id = isset($_POST['curriculum_id']) ? trim($_POST['curriculum_id']) : '';
        $sem = '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => "Request failed",
            'msg_span' => "_system"
        );

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }

        if(dataEmptyCheck($mod_unit)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $fetch_sem = "SELECT sem FROM school_year WHERE school_year_id = ".escape($db_connect, $school_year_id)." LIMIT 1
        ";
        if($sql = call_mysql_query($fetch_sem)){
            if(call_mysql_num_rows($sql) !== 0){
                if($data = call_mysql_fetch_array($sql)){
                    $sem = strtoupper($data['sem']);
                }
            } else {
                $output['code'] = 502;
                $output['msg_response'] = "Fiscal year not found.";
                echo json_encode($output);
                exit();
            }
        }

        $sql_check = "SELECT modified_unit FROM modify_units_students 
            WHERE student_id_no = '".escape($db_connect, $student_id_no)."'
            AND semester = '".escape($db_connect, $sem)."'
            AND year_level = '".escape($db_connect, $year_level)."'
        ";
        if($sql_mus = call_mysql_query($sql_check)){
            if(call_mysql_num_rows($sql_mus) !== 0){
                $output['code'] = 503;
                $output['msg_response'] = "Already added units to this student in this semester.";
                echo json_encode($output);
                exit();
            }
        }
        $db_connect->begin_transaction();
        $sql_mod = "INSERT INTO modify_units_students (student_id_no, year_level, ol_ul_flag, modified_unit, school_year_id, semester, curriculum_id)
        VALUES(
        '"      .escape($db_connect, $student_id_no).      "',
        '"      .escape($db_connect, $year_level).      "',
        '"      .escape($db_connect, $load_flag).      "',
        '"      .escape($db_connect, $mod_unit).      "',
        '"      .escape($db_connect, $school_year_id).      "',
        '"      .escape($db_connect, $sem).      "',
        '"      .escape($db_connect, $curriculum_id).      "'
        )
        ";
        call_mysql_query($sql_mod);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Added units to student.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }
} catch (Throwable $th) {
    $db_connect->rollback();
    $output['code'] = 500;
    $output['msg_response'] = $th->getMessage();
    echo json_encode($output);
    exit();
}

?>



