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
        $curriculum_title = isset($_POST['curriculum']) ? strtoupper(trim($_POST['curriculum'])) : '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
       
        if(empty($program_id) || empty($curriculum_title)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        
        $fetch_curriculum = "SELECT header FROM curriculum_master 
        WHERE header = '".escape($db_connect, $curriculum_title)."'
        ";
        if($query_table = call_mysql_query($fetch_curriculum)){
            while($data = call_mysql_fetch_array($query_table)){
                if($data['header'] === $curriculum_title){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum title already exist.";
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
        $new_curriculum = "INSERT INTO curriculum_master (program_id, header) VALUES (
            '".escape($db_connect, $program_id)."',
            '".escape($db_connect, $curriculum_title)."'
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
        $program_id = isset($_POST['newProgram']) 
            ? intVal(trim($_POST['newProgram']))
            : (isset($_POST['program'])
                ? intVal(trim($_POST['program']))
                : ''
            );
        $curriculum_title = isset($_POST['newCurrTitle']) ? strtoupper(trim($_POST['newCurrTitle'])) : '';
        $curriculum_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';
        $approve_date = isset($_POST['chedDate']) ? trim($_POST['chedDate']) : '';
        $to_edit = '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );

        if(empty($program_id) || empty($curriculum_title)){
            echo $program_id ." ___ ".$curriculum_title;
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = sha1($program_id . $curriculum_title . $approve_date);
        $curr_exist = "SELECT program_id, header, curriculum_id, ched_aprrv_date FROM curriculum_master WHERE 
        curriculum_id = '".    escape($db_connect, $curriculum_id).   "' ";

        if ($query = call_mysql_query($curr_exist)){
            if($data = call_mysql_fetch_array($query)){
                $old_data = sha1($data['program_id'] . $data['header'] . $data['ched_aprrv_date']);
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

        $fetch_curriculum = "SELECT header FROM curriculum_master 
        WHERE header = '".escape($db_connect, $curriculum_title)."'
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
            }
        } else {
            $output['code'] = 502;
            $output['msg_response'] = "Connection failed";
            echo json_encode($output);
            exit();
        }


        $db_connect->begin_transaction();
        if(!empty($approve_date)){
            $sql = "UPDATE curriculum_master SET 
            program_id =   '".     escape($db_connect, $program_id).      "',
            header =   '".     escape($db_connect, $curriculum_title).      "',
            ched_aprrv_date =   '".     escape($db_connect, $approve_date).      "'
            WHERE curriculum_id = '".      escape($db_connect, $to_edit)        ."'
            ";
            $result = call_mysql_query($sql);
            $db_connect->commit();
        }
        if(empty($approve_date)){
            $sql = "UPDATE curriculum_master SET 
            program_id =   '".     escape($db_connect, $program_id).      "',
            header =   '".     escape($db_connect, $curriculum_title).      "'
            WHERE curriculum_id = '".      escape($db_connect, $to_edit)        ."'
            ";
            $result = call_mysql_query($sql);
            $db_connect->commit();
        }


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