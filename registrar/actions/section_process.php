<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitSection']) && $_POST['submitSection'] === "createSection"){
        $program_id = isset($_POST['program']) ? trim($_POST['program']) : '';
        $section = isset($_POST['sectionName']) ? trim($_POST['sectionName']) : '';
        $section_limit = isset($_POST['section_limit']) ? trim($_POST['section_limit']) : '';
        $key_sy = isset($_POST['school_year_id']) ? strVal(trim($_POST['school_year_id'])) : "";
        $output = array(
            'code' => 0,
            'status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );

        function dataEmptyCheck($val){
            return ($val === null || $val === '' || $val === 0);
        }

        if(empty($program_id) || empty($section) || empty($section_limit)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }
        

        $fetch_program = "SELECT class_name FROM class_section 
        WHERE class_name = '".escape($db_connect, $section)."'
        ";
        $query_table = call_mysql_query($fetch_program);
        $existing_program = call_mysql_fetch_array($query_table);

        if($existing_program !== null){
            $output['code'] = 502;
            $output['msg_response'] = "Section name already exist.";
            echo json_encode($output);
            exit();
        }

        $sem_limit = [$key_sy => intVal($section_limit)];
        $encoded_limit = json_encode($sem_limit, JSON_FORCE_OBJECT);

        $db_connect->begin_transaction();
        $new_program = "INSERT INTO class_section (program_id, class_name, sem_limit) VALUES (
            '"      .escape($db_connect, $program_id).      "',
            '"      .escape($db_connect, $section).     "',
            '"      .escape($db_connect, $encoded_limit).      "'
        )";

        call_mysql_query($new_program);
        $db_connect->commit();

        $output['code'] = 200;
        $output['status'] = true;
        $output['msg_response'] = 'Section created successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitSection']) && $_POST['submitSection'] === "editSection"){
        $section = isset($_POST['newSectionName']) ? trim($_POST['newSectionName']) : "";
        $program_id = isset($_POST['newProgram']) ? trim($_POST['newProgram']) : '';
        $sectionLimit = isset($_POST['newLimit']) ? intVal(trim($_POST['newLimit'])) : '';
        $class_id = isset($_POST['editId']) ? trim($_POST['editId']) : "";
        $key_sy = isset($_POST['school_year_id']) ? intVal(trim($_POST['school_year_id'])) : "";
        $fetched_program_id = "";
        $sem_limit = array();
        $school_year_id = '';
        $current_date = date('y-m-d');
        $new_sem_limit = array(
            "key" => "",
            "value" => ""
        );

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system',
        );

        function dataEmptyCheck($val){
            return ($val === null || $val === '' || $val === 0);
        }

        if(empty($section) || dataEmptyCheck($program_id) ||  dataEmptyCheck($sectionLimit) || dataEmptyCheck($class_id)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = sha1($class_id . $section . $program_id . $sectionLimit);
        $pair_limit = "";
        $program_exist = "SELECT class_id, class_name, program_id, sem_limit FROM class_section WHERE 
        class_id = '".    escape($db_connect, $class_id).   "' ";

        if ($query = call_mysql_query($program_exist)){
            if($data = call_mysql_fetch_array($query)){
                $sem_limit = json_decode($data['sem_limit'], true);
                // echo json_encode($sem_limit);
                // exit();
                if(array_key_exists($key_sy, $sem_limit)){
                    $pair_limit = intVal($sem_limit[$key_sy]);
                }
                elseif(!(array_key_exists($key_sy, $sem_limit))){
                    $pair_limit = intVal($sem_limit['0']);
                }
                $old_data = sha1($data['class_id'] . $data['class_name'] . $data['program_id'] . $pair_limit);
            } else {
                $output['code'] = 503;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "It seems the information you are trying to update does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        if($new_data === $old_data){
            $output['code'] = 504;
            $output['msg_response'] = "You did not make any changes.";
            echo json_encode($output);
            exit();
        }

        $sql_program = "SELECT program_id, program FROM programs WHERE program_id = '".     escape($db_connect, $program_id).   "' ";
        if($sqlProgram = call_mysql_query($sql_program)){
            if($programData = call_mysql_fetch_array($sqlProgram)){
                $fetched_program_id = $programData['program_id'];
            } else {
                $output['code'] = 503;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "Can't find selected program or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        $sql_Sy = "SELECT school_year_id FROM school_year 
                WHERE '$current_date' BETWEEN date_from AND date_to";
        if($sy_query = call_mysql_query($sql_Sy)){
            while($data = call_mysql_fetch_array($sy_query)){
                $school_year_id = $data['school_year_id'];
            }
        }

        if(empty($school_year_id) || $school_year_id === ""){
            $output['code'] = 505;
            $output['msg_response'] = "No new fiscal year yet.";
            echo json_encode($output);
            exit();
        }
        // $new_sem_limit = [
        //     "key" => $school_year_id,
        //     "value" => strVal($sectionLimit)
        // ];


        if(array_key_exists(strVal($key_sy), $sem_limit)){
            $sem_limit[strVal($key_sy)] = $sectionLimit;
        }
        if(!(array_key_exists(strVal($key_sy), $sem_limit))){
            $sem_limit[strVal($key_sy)] = $sectionLimit;
        }
        $encoded_sem_limit = json_encode($sem_limit, JSON_FORCE_OBJECT);

        $db_connect->begin_transaction();

        $sql = "UPDATE class_section SET 
        class_name =   '".     escape($db_connect, $section).      "',
        program_id =   '".     escape($db_connect, $fetched_program_id).      "',
        sem_limit =   '".     escape($db_connect, $encoded_sem_limit).      "',
        date_modified = NOW()
        WHERE class_id = '".      escape($db_connect, $class_id)        ."'
        ";
        $result = call_mysql_query($sql);
        $db_connect->commit();


        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Program updated successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitSection']) && $_POST['submitSection'] === "archiveSection"){
        $class_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';
        $archiveStatus = isset($_POST['newStatus']) ? trim($_POST['newStatus']) : '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );


        if(empty($class_id) || $archiveStatus === '' || $archiveStatus === null){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $newStatus = intVal($archiveStatus);
        $sql = "SELECT status FROM class_section WHERE class_id = '".   escape($db_connect, $class_id)    ."' ";
        if($query = call_mysql_query($sql)){
            if($data = call_mysql_fetch_array($query)){
                if(intVal($data['status']) === $newStatus){
                    $output['code'] = 504;
                    $output['msg_response'] = "This section is already archived.";
                    echo json_encode($output);
                    exit();
                }
            } else {
                $output['code'] = 503;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "It seems the section you are trying to archive does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }
        




        $db_connect->begin_transaction();
        $update_sql = "UPDATE class_section SET
        status = '".     escape($db_connect, $newStatus)     ."'
        WHERE class_id = '".      escape($db_connect, $class_id)    ."'
        ";
        $result = call_mysql_query($update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Section archived successfully.';
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