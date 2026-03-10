<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitProgram']) && $_POST['submitProgram'] === "createProgram"){
        $program = isset($_POST['programName']) ? trim($_POST['programName']) : '';
        $department_id = isset($_POST['department_id']) ? trim($_POST['department_id']) : '';
        $major = isset($_POST['major']) ? trim($_POST['major']) : '';
        $program_code = isset($_POST['programCode']) ? strtoupper(trim($_POST['programCode'])) : '';
        $output = array(
            'code' => 0,
            'status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        $majorArr = array();

       
        if(empty($program) || empty($department_id) || empty($program_code)){
            $output['code'] = 501;
            $output['msg_response'] = "You did not fill a required field.";
            echo json_encode($output);
            exit();
        }

        

        $fetch_program = "SELECT program, short_name FROM programs 
        WHERE program = '".escape($db_connect, $program)."' OR
        short_name = '".escape($db_connect, $program_code)."'
        ";
        if($query_table = call_mysql_query($fetch_program)){
            while($existing_program = call_mysql_fetch_array($query_table)){
                if($existing_program !== null){
                    $output['code'] = 502;
                    $output['msg_response'] = "Program name or code already exist.";
                    echo json_encode($output);
                    exit();
                }
            }

        }
        
        if(!empty($major)){
            array_push($majorArr, strtoupper($major));
        }

        $major_encoded = json_encode($majorArr);
        $db_connect->begin_transaction();
        $new_program = "INSERT INTO programs (program, department_id, major, short_name) VALUES (
            '".escape($db_connect, $program)."',
            '".escape($db_connect, $department_id)."',
            '".escape($db_connect, $major_encoded)."',
            '".escape($db_connect, $program_code)."'
        )";

        call_mysql_query($new_program);
        $db_connect->commit();

        $output['code'] = 200;
        $output['status'] = true;
        $output['msg_response'] = 'Program created successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitProgram']) && $_POST['submitProgram'] === "editProgram"){
        $program = isset($_POST['newProgram']) ? trim($_POST['newProgram']) : "";
        $department_id = isset($_POST['newDepartment']) ? trim($_POST['newDepartment']) : '';
        $major = isset($_POST['newMajor']) ? strtoupper(trim($_POST['newMajor'])) : '';
        $oldMajor = isset($_POST['oldMajor']) ? (trim($_POST['oldMajor'])) : '';
        $program_code = isset($_POST['newCode']) ? trim($_POST['newCode']) : "";
        $program_id = isset($_POST['programId']) ? trim($_POST['programId']) : "";
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system',
        );
        $majorArr = array();
        $oldMajorArr = array();
        // echo "program: $program, department_id: $department_id, major: $major, program_code: $program_code, program_id: $program_id";

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
        if(empty($program) || empty($department_id) || empty($program_code) || empty($major) || empty($program_code) || dataEmptyCheck($department_id)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = sha1($program_id . $program . $program_code . $department_id);
        $program_exist = "SELECT program_id, program, short_name, department_id, major FROM programs WHERE 
        program_id = '".    escape($db_connect, $program_id).   "' ";

        if ($query = call_mysql_query($program_exist)){
            if($data = call_mysql_fetch_array($query)){
                $oldMajorArr = is_array(json_decode(html_entity_decode($data['major']), true))
                    ? json_decode(html_entity_decode($data['major']), true)
                    : array();
                $old_data = sha1($data['program_id'] . $data['program'] . $data['short_name'] . $data['department_id']);

            } else {
                $output['code'] = 503;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        if($new_data === $old_data && in_array($major, $oldMajorArr)){
            $output['code'] = 504;
            $output['msg_response'] = "You did not make any changes.";
            echo json_encode($output);
            exit();
        }

        if($oldMajor !== $major){
            if(in_array($oldMajor, $oldMajorArr)){
                $index = array_search($oldMajor, $oldMajorArr);
                if(isset($index)){
                    $oldMajorArr[$index] = $major;
                }
            }
        }



        $db_connect->begin_transaction();
        $encodedMajor = json_encode($oldMajorArr);

        $sql = "UPDATE programs SET 
        program =   '".     escape($db_connect, $program).      "',
        department_id =   '".     escape($db_connect, $department_id).      "',
        major =   '".     escape($db_connect, $encodedMajor).      "',
        short_name =   '".     escape($db_connect, $program_code).      "'
        WHERE program_id = '".      escape($db_connect, $program_id)        ."'
        ";
        $result = call_mysql_query($sql);
        $db_connect->commit();


        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Major updated successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitProgram']) && $_POST['submitProgram'] === "addMajor"){
        $program_id = isset($_POST['programId']) ? trim($_POST['programId']) : '';
        $major = isset($_POST['major']) ? trim($_POST['major']) : '';
        $major_array = array();
        $to_update = '';
        $exist_major_array = array();
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system',
            'data' => ''
        );


        if(empty($major)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $sql = "SELECT major, program_id FROM programs WHERE program_id = '".   escape($db_connect, $program_id)    ."' ";
        if($query = call_mysql_query($sql)){
            if($data = call_mysql_fetch_array($query)){
                $to_update = $data['program_id'];
                if(empty($data['major'])){
                    array_push($major_array, strtoupper($major));
                }
                if(!empty($data['major'])){
                    $exist_major_array = is_array(json_decode(html_entity_decode($data['major']), true))
                        ? json_decode(html_entity_decode($data['major']), true)
                        : array();
                    if(in_array(strtoupper($major), $exist_major_array)){
                        $output['code'] = 505;
                        $output['msg_response'] = "This major already exists for this program.";
                        echo json_encode($output);
                        exit();
                    }
                    array_push($exist_major_array, strtoupper($major));
                }
            } else {
                $output['code'] = 503;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 502;
            $output['msg_reponse'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }
        




        $stringified = "";
        $stringified = $major_array !== [] ? json_encode($major_array) : json_encode($exist_major_array);
        $db_connect->begin_transaction();
        $update_sql = "UPDATE programs SET
        major = '".     escape($db_connect, $stringified)     ."'
        WHERE program_id = '".      escape($db_connect, $to_update)    ."'
        ";
        $result = call_mysql_query($update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = "Major has been added successfully.";
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