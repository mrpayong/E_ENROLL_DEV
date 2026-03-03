<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
// var_dump($_POST);

//create department
if($_SERVER["REQUEST_METHOD"] = "POST" && isset($_POST['departmentSubmit']) && $_POST['departmentSubmit'] === "createDepartment"){
    $department = isset($_POST["departmentName"]) ? trim($_POST["departmentName"]) : '';
    $code_name = isset($_POST["codeName"]) ? trim($_POST["codeName"]) : '';
    $dean_id = isset($_POST["deptHead"]) ? trim($_POST["deptHead"]) : '';
    $status = 1;
    $output = array(
        "code" => 0,
        "msg_status" => false, 
        "msg_span" => "_system", 
        "msg_response" => "Request Error, please try again."
    );

    if(empty($department) || empty($code_name)){
        $output['code'] = 501;
        $output['msg_response'] = "All fields are required.";
        exit();
    }

    $department_exist = "SELECT department, code_name FROM departments WHERE department = '".escape($db_connect, $department)."' OR code_name = '" . escape($db_connect, $code_name) . "' LIMIT 1";
    $fetched_depts = call_mysql_query($department_exist, $db_connect);
    if(call_mysql_num_rows($fetched_depts) > 0){
        $output['code'] = 502;
        $output['msg_response'] = "Department name or code already exist.";
        echo json_encode($output);
        exit();
    }

    $dean_exist = "SELECT user_id FROM departments WHERE user_id = '".escape($db_connect, $dean_id)."' LIMIT 1";
    $fetched_depts = call_mysql_query($dean_exist, $db_connect);
    if(call_mysql_num_rows($fetched_depts) > 0){
        $output['code'] = 504;
        $output['msg_response'] = "Dean already assigned to a department.";
        echo json_encode($output);
        exit();
    }

    $result1 = 0;
    $db_connect->begin_transaction();

    try {
        $sql1 = "INSERT INTO departments (department, code_name, status, user_id)
        VALUES (
            '".escape($db_connect, $department)."',
            '".escape($db_connect, $code_name)."',
            '".escape($db_connect, $status)."',
            '".escape($db_connect, $dean_id)."'
        )
        ";

        $result1 = call_mysql_query($sql1);
        $db_connect->commit();
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['code'] = 404;
        $output['msg_response'] = $e->getMessage();
        echo json_encode($output);
        exit();
    }


    if($result1 = 0){
        $output['code'] = 503;
        $output['msg_response'] = "Failed qeury";
        echo json_encode($output);
    }

    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_span'] = "";
    $output['msg_response'] = "Department created successfully.";
    echo json_encode($output);
    exit();
} 

// update department
if($_SERVER['REQUEST_METHOD'] = "POST" && isset($_POST['departmentSubmit']) && $_POST['departmentSubmit'] === "updateDepartment"){
    $department_id = isset($_POST['department_id']) ? trim($_POST['department_id']) : '';
    $department = isset($_POST["departmentNewName"]) ? trim($_POST['departmentNewName']) : '';
    $code_name = isset($_POST["newCodeName"]) ? trim($_POST['newCodeName']) : "";
    $dean_id = isset($_POST["editDeptHead"]) ? trim($_POST['editDeptHead']) : "";
    $output = array(
        "code" => 0,
        "msg_status" =>  false,
        "msg_span" => "_system",
        'msg_response' => "Request Error, please try again."
    );


    if(empty($department) || empty($code_name) || empty($dean_id)){
        $output['code'] = 501;
        $output['msg_response'] = "All fields are required.";
        echo json_encode($output);
        exit();
    }

    $new_vals = sha1($department . $code_name . $dean_id);
    $default_query = "SELECT department, code_name, user_id FROM departments WHERE department_id = '     ".escape($db_connect, $department_id)."'    ";
    if($query = call_mysql_query($default_query)){
        if($num = call_mysql_num_rows($query)){
            if($data = call_mysql_fetch_array($query)){
                if($data['user_id'] !== $dean_id){
                    $dean_exist = "SELECT user_id FROM departments WHERE user_id = '".escape($db_connect, $dean_id)."' ";
                    $fetch_depts = call_mysql_query($dean_exist);
                    $data = call_mysql_fetch_array($fetch_depts);
                    if(intval($data['user_id']) === intval($dean_id)){
                        $output['code'] = 504;
                        $output['msg_response'] = "Dean already assigned to a department.";
                        echo json_encode($output);
                        exit();
                    }
                }
                $old_vals = sha1($data['department'] . $data['code_name'] . $data['user_id']);
            }
        } else {
            $output['code'] = 500;
            $output['msg_response'] = "Department not found.";
            echo json_encode($output);
            exit();
        }
    } else {
        $output['code'] = 503;
        $output['msg_response'] = "Database connection unsuccessful.";
        echo json_encode($output);
        exit();
    }


    if($new_vals === $old_vals){
        $output['code'] = 502;
        $output['msg_response'] = "You haven't change any of the information.";
        echo json_encode($output);
        exit();
    }

    $db_connect->begin_transaction();
    try {
        $sql1 = "UPDATE departments SET
                department = '".    escape($db_connect, $department)     ."',
                code_name = '".     escape($db_connect, $code_name)     ."',
                updatedAt = NOW()
                WHERE department_id = '".   escape($db_connect, $department_id).    "'
                ";
        
        $result1 = call_mysql_query($sql1);

        $db_connect->commit();
    } catch (Exception $e) {
        $db_connect->rollback();
        $output['msg_response'] = $e->getMessage();
        $output['code'] = 404;
        echo json_encode($output);
        exit();
    }

    $output['msg_status'] = true;
    $output['msg_span'] = '';
    $output['code'] = 200;
    $output['msg_response'] = "Department has been updated.";
    echo json_encode($output);
    exit();


}



$output = array(
    "code" => 500,
    "msg_status" =>  false,
    "msg_span" => "_system",
    'msg_response' => "Invalid request method."
);
echo json_encode($output);
exit();
?>

