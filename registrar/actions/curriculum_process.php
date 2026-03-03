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
        $department_id = isset($_POST['department']) ? trim($_POST['department']) : '';
        $school_year_id = isset($_POST['sem']) ? trim($_POST['sem']) : '';
        $curriculum_title = isset($_POST['curriculumName']) ? trim($_POST['curriculumName']) : '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
       
        if(empty($program_id) || empty($department_id) || empty($curriculum_title) || empty($school_year_id)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        
        $fetch_curriculum = "SELECT curriculum_title FROM curriculum 
        WHERE curriculum_title = '".escape($db_connect, $curriculum_title)."' 
        ";
        if($query_table = call_mysql_query($fetch_curriculum)){
            if($data = call_mysql_fetch_array($query_table)){
                if($data['curriculum_title'] === $curriculum_title){
                    $output['code'] = 504;
                    $output['msg_response'] = "Curriculum title already exist.";
                    echo json_encode($existing_curriculum);
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
        $new_curriculum = "INSERT INTO curriculum (school_year_id, department_id, program_id, curriculum_title) VALUES (
            '".escape($db_connect, $school_year_id)."',
            '".escape($db_connect, $department_id)."',
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
        $program_id = isset($_POST['newProgram']) ? trim($_POST['newProgram']) : '';
        $department_id = isset($_POST['newDepartment']) ? trim($_POST['newDepartment']) : '';
        $school_year_id = isset($_POST['newSem']) ? trim($_POST['newSem']) : '';
        $curriculum_title = isset($_POST['newCurriculumName']) ? trim($_POST['newCurriculumName']) : '';
        $curriculum_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';

        $to_edit = '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );

        if(empty($program_id) || empty($department_id) || empty($school_year_id) || empty($curriculum_title) || empty($curriculum_id)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = sha1($program_id . $department_id . $school_year_id . $curriculum_title);
        $curr_exist = "SELECT program_id, department_id, school_year_id, curriculum_title, curriculum_id FROM curriculum WHERE 
        curriculum_id = '".    escape($db_connect, $curriculum_id).   "' ";

        if ($query = call_mysql_query($curr_exist)){
            if($data = call_mysql_fetch_array($query)){
                $old_data = sha1($data['program_id'] . $data['department_id'] . $data['school_year_id'] . $data['curriculum_title']);
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


        $db_connect->begin_transaction();
        $sql = "UPDATE curriculum SET 
        program_id =   '".     escape($db_connect, $program_id).      "',
        department_id =   '".     escape($db_connect, $department_id).      "',
        school_year_id =   '".     escape($db_connect, $school_year_id).      "',
        curriculum_title =   '".     escape($db_connect, $curriculum_title).      "'
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

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCurriculum']) && $_POST['submitCurriculum'] === "archiveCurriculum"){
        $status = isset($_POST['newStatus']) ? trim($_POST['newStatus']) : '';
        $curriculum_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';

        $to_edit = '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );


        if(empty($curriculum_id) || $status === '' || $status === null){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $newStatus = intVal($status);
        $sql = "SELECT status, curriculum_id FROM curriculum WHERE curriculum_id = '".   escape($db_connect, $curriculum_id)    ."' ";
        if($query = call_mysql_query($sql)){
            if($data = call_mysql_fetch_array($query)){
                if(intVal($data['status']) === $status){
                    $output['code'] = 504;
                    $output['msg_response'] = "This program is already archived.";
                    echo json_encode($output);
                    exit();
                }
                $to_edit = $data['curriculum_id'];
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }
        




        $db_connect->begin_transaction();
        $update_sql = "UPDATE curriculum SET
        status = '".     escape($db_connect, $status)     ."'
        WHERE curriculum_id = '".      escape($db_connect, $to_edit)    ."'
        ";
        $result = call_mysql_query($update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Curriculum archived successfully.';
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