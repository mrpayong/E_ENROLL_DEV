<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCurriculum']) && $_POST['submitCurriculum'] === "createCurriculum"){
        $program_id = isset($_POST['program']) ? trim($_POST['program']) : '';
        $curriculum_title = isset($_POST['currTitle']) ? strtoupper(trim($_POST['currTitle'])) : '';
        $curriculum_code = isset($_POST['currCode']) ? strtoupper(trim($_POST['currCode'])) : '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
       
        if(empty($program_id) || empty($curriculum_title) || empty($curriculum_code)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        
        $fetch_curriculum = "SELECT header, curriculum_code FROM curriculum_master 
        WHERE header = '".escape($db_connect, $curriculum_title)."' OR curriculum_code = '".escape($db_connect, $curriculum_code)."'
        ";
        if($query_table = call_mysql_query($fetch_curriculum)){
            while($data = call_mysql_fetch_array($query_table)){
                if($data['header'] === $curriculum_title){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum title already exist.";
                    echo json_encode($output);
                    exit();
                }
                if($data['curriculum_code'] === $curriculum_code){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum code already exist.";
                    echo json_encode($output);
                    exit();
                }
            }
        } else {
            $output['code'] = 502;
            $output['msg_response'] = "Connection failed";
            echo json_encode($output);
            exit();
        }


        $db_connect->begin_transaction();
        $new_curriculum = "INSERT INTO curriculum_master (program_id, header, curriculum_code) VALUES (
            '".escape($db_connect, $program_id)."',
            '".escape($db_connect, $curriculum_title)."',
            '".escape($db_connect, $curriculum_code)."'
        )";

        call_mysql_query($new_curriculum);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Curriculum created successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCurriculum']) && $_POST['submitCurriculum'] === "updateCurriculum"){
        $program_id = isset($_POST['newProgram']) ? trim($_POST['newProgram']) : '';
        $curriculum_title = isset($_POST['newCurrTitle']) ? strtoupper(trim($_POST['newCurrTitle'])) : '';
        $curriculum_code = isset($_POST['newCurrCode']) ? strtoupper(trim($_POST['newCurrCode'])) : '';
        $curriculum_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';
        $to_edit = '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );

        if(empty($program_id) || empty($curriculum_title) || empty($curriculum_code)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = sha1($program_id . $curriculum_title .  $curriculum_code);
        $curr_exist = "SELECT program_id, header, curriculum_id, curriculum_code FROM curriculum_master WHERE 
        curriculum_id = '".    escape($db_connect, $curriculum_id).   "' ";

        if ($query = call_mysql_query($curr_exist)){
            if($data = call_mysql_fetch_array($query)){
                $old_data = sha1($data['program_id'] . $data['header'] . $data['curriculum_code']);
                $to_edit = $data['curriculum_id'];
            } else {
                $output['code'] = 502;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 503;
            $output['msg_reponse'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        if($new_data === $old_data){
            $output['code'] = 504;
            $output['msg_response'] = "You did not make any changes.";
            echo json_encode($output);
            exit();
        }

        $fetch_curriculum = "SELECT header, curriculum_code FROM curriculum_master 
        WHERE (header = '".escape($db_connect, $curriculum_title)."' 
        OR curriculum_code = '".escape($db_connect, $curriculum_code)."')
        AND NOT (curriculum_id = '".escape($db_connect, $to_edit)."')
        ";
        if($query_table = call_mysql_query($fetch_curriculum)){
            while($data = call_mysql_fetch_array($query_table)){
                if($data['header'] === $curriculum_title){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum title already exist.";
                    echo json_encode($output);
                    exit();
                }
                if($data['curriculum_code'] === $curriculum_code){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum code already exist.";
                    echo json_encode($output);
                    exit();
                }
            }
        } else {
            $output['code'] = 502;
            $output['msg_response'] = "Connection failed";
            echo json_encode($output);
            exit();
        }


        $db_connect->begin_transaction();
        $sql = "UPDATE curriculum_master SET 
        program_id =   '".     escape($db_connect, $program_id).      "',
        header =   '".     escape($db_connect, $curriculum_title).      "',
        curriculum_code =   '".     escape($db_connect, $curriculum_code).      "'
        WHERE curriculum_id = '".      escape($db_connect, $to_edit)        ."'
        ";
        $result = call_mysql_query($sql);
        $db_connect->commit();


        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'curriculum updated successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCurriculum']) && $_POST['submitCurriculum'] === "updateStatus"){
        $curriculum_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';
        $new_status = isset($_POST['newStatus']) ? intVal(trim($_POST['newStatus'])) : '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }

        if(empty($curriculum_id) || dataEmptyCheck($new_status)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $sql_exist = "SELECT curriculum_id, status_allowable FROM curriculum_master WHERE curriculum_id = '".escape($db_connect, $curriculum_id)."'";
        if ($query = call_mysql_query($sql_exist)){
            if($data = call_mysql_fetch_array($query)){
                if(intVal($data['status_allowable']) === $new_status && $new_status === 0){
                    $output['code'] = 504;
                    $output['msg_response'] = "This curriculum is already allowed to be assigned to students.";
                    echo json_encode($output);
                    exit();
                }
                if(intVal($data['status_allowable']) === $new_status && $new_status === 1){
                    $output['code'] = 504;
                    $output['msg_response'] = "This curriculum is already not allowed to be assigned to students.";
                    echo json_encode($output);
                    exit();
                }
            }
        } else {
            $output['code'] = 503;
            $output['msg_reponse'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();
        $sql = "UPDATE curriculum_master SET 
        status_allowable =   '".     escape($db_connect, $new_status).      "'
        WHERE curriculum_id = '".      escape($db_connect, $curriculum_id)        ."'
        ";
        call_mysql_query($sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Curriculum status updated successfully.';
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