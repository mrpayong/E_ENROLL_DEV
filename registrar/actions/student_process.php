<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitStudent']) && $_POST['submitStudent'] === 'updateStudent'){
        $student_id = isset($_POST['idNumber']) ? trim($_POST['idNumber']) : '';
        $barangay = isset($_POST['barangay']) ? trim($_POST['barangay']) : '';
        $address = isset($_POST['addressLong']) ? trim($_POST['addressLong']) : '';
        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : '';
        $year_level = isset($_POST['year_level']) ? intVal(trim($_POST['year_level'])) : '';
        $program_id = isset($_POST['program']) ? intVal(trim($_POST['program'])) : '';
        $major = isset($_POST['major']) ? trim($_POST['major']) : '';
        $curriculum_id = isset($_POST['curriculum']) ? intVal(trim($_POST['curriculum'])) : '';
        $emergency = isset($_POST['emergency']) ? trim($_POST['emergency']) : '';
        $additional = isset($_POST['additional_data']) ? trim($_POST['additional_data']) : '';
        $old_data = "";
        $to_edit = '';
        $firstname    = isset($_POST['f_name']) ? trim($_POST['f_name']) : '';
        $lastname     = isset($_POST['l_name']) ? trim($_POST['l_name']) : '';
        $middle_name  = isset($_POST['m_name']) ? trim($_POST['m_name']) : '';
        $suffix_name  = isset($_POST['suffix']) ? trim($_POST['suffix']) : '';
        $gender       = isset($_POST['gender']) ? trim($_POST['gender']) : '';
        $dob          = isset($_POST['birth_date']) ? trim($_POST['birth_date']) : '';
        $username     = isset($_POST['username']) ? trim($_POST['username']) : '';
        $ccc_email    = isset($_POST['ccc_email']) ? trim($_POST['ccc_email']) : '';
        $department_id = isset($_POST['department']) ? intVal(trim($_POST['department'])) : '';

        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => "Response error, please try again.",
            'msg_span' => '_system',
        );

        if(
            empty($student_id) || 
            empty($barangay) || 
            empty($address) || 
            empty($contact) || 
            empty($year_level) || 
            empty($program_id) || 
            empty($curriculum_id) || 
            empty($emergency) || 
            empty($additional)
        ){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $new_data = sha1($student_id . $barangay . $address . $contact . $year_level . $program_id . $major . $curriculum_id . $emergency . $additional . $department_id);

        $student_exist = "SELECT student_id, barangay, address, contact, year_level, program_id, major, curriculum_id, emergency_data, additional_data, department_id FROM student WHERE student_id = '".     escape($db_connect, $student_id)        ."'";
        if($query = call_mysql_query($student_exist)){
            if($data = call_mysql_fetch_array($query)){
                $to_edit = $data['student_id'];

                $old_data = sha1($data['student_id'] . $data['barangay'] . $data['address'] . $data['contact'] . $data['year_level'] . $data['program_id'] . $data['major'] . $data['curriculum_id'] . $data['emergency_data'] . $data['additional_data']) . $data['department_id'];
            }

            if(empty($to_edit)){
                $db_connect->begin_transaction();

                $sql_create = "INSERT INTO student (
                    student_id,
                    firstname,
                    lastname,
                    ccc_email,
                    middle_name,
                    suffix_name,
                    contact,
                    barangay,
                    address,
                    gender,
                    dob,
                    year_level,
                    major,
                    username,
                    curriculum_id,
                    emergency_data,
                    additional_data,
                    flag_update,
                    program_id,
                    department_id
                ) VALUES (
                    '".escape($db_connect, $student_id)."',
                    '".escape($db_connect, $firstname)."',
                    '".escape($db_connect, $lastname)."',
                    '".escape($db_connect, $ccc_email)."',
                    '".escape($db_connect, $middle_name)."',
                    '".escape($db_connect, $suffix_name)."',
                    '".escape($db_connect, $contact)."',
                    '".escape($db_connect, $barangay)."',
                    '".escape($db_connect, $address)."',
                    '".escape($db_connect, $gender)."',
                    '".escape($db_connect, $dob)."',
                    '".escape($db_connect, $year_level)."',
                    '".escape($db_connect, $major)."',
                    '".escape($db_connect, $username)."',
                    '".escape($db_connect, $curriculum_id)."',
                    '".escape($db_connect, $emergency)."',
                    '".escape($db_connect, $additional)."',
                    NOW(),
                    '".escape($db_connect, $program_id)."',
                    '".escape($db_connect, $department_id)."'
                )";

                call_mysql_query($sql_create);
                $db_connect->commit();

                $output['code'] = 200;
                $output['msg_status'] = true;
                $output['msg_response'] = "Student's account updated successfully.";
                $output['msg_span'] = '';
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 503;
            $output['msg_response'] = "It seems the account you are trying to update does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        if($new_data === $old_data){
            $output['code'] = 504;
            $output['msg_response'] = "You did not change any information.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();

        $update_sql = "
            UPDATE student SET
                barangay = '".escape($db_connect, $barangay)."',
                address = '".escape($db_connect, $address)."',
                contact = '".escape($db_connect, $contact)."',
                year_level = '".escape($db_connect, $year_level)."',
                program_id = '".escape($db_connect, $program_id)."',
                major = '".escape($db_connect, $major)."',
                curriculum_id = '".escape($db_connect, $curriculum_id)."',
                emergency_data = '".escape($db_connect, $emergency)."',
                additional_data = '".escape($db_connect, $additional)."',
                department_id = '".escape($db_connect, $department_id)."',
                flag_update = NOW()
            WHERE student_id = '".escape($db_connect, $to_edit)."'
        ";

        $result = call_mysql_query($update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = "Studen's account updated successfully.";
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitStudent']) && $_POST['submitStudent'] === 'lockStudent'){
        $student_id = isset($_POST['editId']) ? trim($_POST['editId']) : '';
        $lock_status = isset($_POST['newStatus']) ? intVal(trim($_POST['newStatus'])) :'';
        $to_edit = '';
        $output = array(
            'code' => 0,
            'msg_title' => '',
            'msg_status' => false,
            'msg_response' => "Response error, please try again.",
            'msg_span' => '_system'
        );

        if(empty($student_id) || $lock_status === '' || $lock_status === null){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $student_exist = "SELECT general_id FROM users WHERE general_id = '".     escape($db_connect, $student_id)        ."'";
        if($query = call_mysql_query($student_exist)){
            if($data = call_mysql_fetch_array($query)){
                $to_edit = $data['general_id'];
            } else {
                $output['code'] = 502;
                $output['msg_response'] = "Connection failed.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 503;
            $output['msg_response'] = "It seems the account you are trying to lock does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();
        $update_sql = "
            UPDATE users 
            SET status = '".    escape($db_connect, $lock_status).      "'
            WHERE general_id = '".      escape($db_connect, $student_id)   ."'
        ";

        $result = call_mysql_query($update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_title'] = $lock_status !== 0 ? "Successfully locked!" : "Successfully unlocked!";
        $output['msg_response'] = $lock_status !== 0 ? "Student's account has been locked." : "Student's account has been unlocked.";
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