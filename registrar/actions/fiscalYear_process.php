<?php
include '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');


// create fiscal year
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actionSubmitFiscalYear']) && $_POST['actionSubmitFiscalYear'] === 'submitFiscalYear'){
        $school_year = isset($_POST['schoolYear']) ? trim($_POST['schoolYear']) : "";
        $sem = isset($_POST['semester']) ? trim($_POST['semester']) : "";
        $date_from = isset($_POST['startDate']) ? trim($_POST['startDate']) : "";
        $date_to = isset($_POST['endDate']) ? trim($_POST['endDate']) : "";
        $syFrom_class_array = array();
        $to_pushID = '';
        $output = array(
            "code" => 0,
            "msg_status" => false,
            "msg_span" => "_system",
            "msg_response" => "Request Error, please try again.",
        );

        // Validation: All fields required
        if (empty($school_year) || empty($sem) || empty($date_from) || empty($date_to)) {
            $output['code'] = 400;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        // Validation: Check for duplicate fiscal year and semester
        $dup_query = "SELECT school_year_id FROM school_year WHERE school_year = '" . escape($db_connect, $school_year) . "' AND sem = '" . escape($db_connect, $sem) . "' LIMIT 1";
        $dup_result = call_mysql_query($dup_query, $db_connect);
        if ($dup_result && call_mysql_num_rows($dup_result) > 0) {
            $output['code'] = 400;
            $output['msg_response'] = "Failed to Create. Duplication detected.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();

        $sql1 = "INSERT INTO school_year (school_year, sem, date_from, date_to) 
        VALUES (
            '" . escape($db_connect, $school_year) . "',
            '" . escape($db_connect, $sem) . "',
            '" . escape($db_connect, $date_from) . "',
            '" . escape($db_connect, $date_to) . "')
        ";

        $result1 = call_mysql_query($sql1);
        $db_connect->commit();


        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_span'] = "";
        $output['msg_response'] = "Fiscal year created successfully.";
        echo json_encode($output);
        exit();
    } 

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateFiscalYear']) && $_POST['updateFiscalYear'] === "updateNewData") {
        $id = isset($_POST['school_year_id']) ? intVal(trim($_POST['school_year_id'])) : '';
        $school_year = isset($_POST['schoolYear']) ? trim($_POST['schoolYear']) : '';
        $sem = isset($_POST['semester']) ? trim($_POST['semester']) : '';
        $date_from = isset($_POST['startDate']) ? trim($_POST['startDate']) : '';
        $date_to = isset($_POST['endDate']) ? trim($_POST['endDate']) : '';
        $isDefault = isset($_POST['isDefault']) ? intVal(trim($_POST['isDefault'])) : '';
        $old_vals = "";
        $new_vals = "";
        $output = array(
            "code" => 0,
            "msg_status" => false, 
            "msg_span" => "_system", 
            "msg_response" => "Request Error, please try again."
        );
        function dataEmptyCheck($val){
            return ($val === null || $val === '');
         }
        // Basic validation
        if (empty($id) || empty($school_year) || empty($sem) || empty($date_from) || empty($date_to) || dataEmptyCheck($isDefault)) {
            $output['msg_response'] = "All fields are required.";
            $output['code'] = 501;
            echo json_encode($output);
            exit();
        }

        $new_vals = sha1($school_year . $sem . $date_from . $date_to . $isDefault);

        $default_query = "SELECT school_year, sem, date_from, date_to, isDefault FROM school_year WHERE school_year_id = '" . escape($db_connect, $id) . "'";
        if($query = call_mysql_query($default_query)){
            if($num = call_mysql_num_rows($query)){
                if($data = call_mysql_fetch_array($query)){
                    $old_vals = sha1($data['school_year'] . $data['sem'] . $data['date_from'] . $data['date_to'] . $data['isDefault']);
                }
            } else {
                $output['code'] = 502;
                $output['msg_response'] = "Fiscal year record not found.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 503;
            $output['msg_response'] = "Database query failed.";
            echo json_encode($output);
            exit();
        }

        if($old_vals === $new_vals){
            $output['code'] = 504;
            $output['msg_response'] = "You haven't changed any of the information.";
            echo json_encode($output);
            exit();
        }

        ## update
        ## start the transaction $newLimit
        $db_connect->begin_transaction();
        $sql1 = "UPDATE school_year SET school_year = '" . escape($db_connect, $school_year) . "',
                sem = '" . escape($db_connect, $sem) . "',
                date_from = '" . escape($db_connect, $date_from) . "',
                date_to = '" . escape($db_connect, $date_to) . "',
                isDefault = '" . escape($db_connect, $isDefault) . "',
                updatedAt = NOW()
                WHERE school_year_id = '" .  escape($db_connect, $id) . "'";
    
        $result1 = call_mysql_query($sql1);

        $db_connect->commit(); 

        $output['msg_status'] = true;
        $output['msg_span'] = "";
        $output['code'] = 200;
        $output['msg_response'] = "Fiscal year updated successfully.";
        echo json_encode($output);
        exit();
    }

    // lock/unlock fiscal year
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateFiscalYear']) && $_POST['updateFiscalYear'] === "LockUnlockFiscalYear"){
        $id = isset($_POST['school_year_id']) ? trim($_POST['school_year_id']) : '';
        $newFlag = isset($_POST['flag_status']) ? trim($_POST['flag_status']) : '';
        $isDefault = isset($_POST['isDefault']) ? trim($_POST['isDefault']) : '';
        $school_year_id = '';
        $output = array(
            "code" => 0,
            "msg_status" => false,
            "msg_span" => "_system",
            "msg_response" => "Request Error, please try again."
        );

        // Fetch current flag_used value
        $select_query = "SELECT flag_used, school_year_id FROM school_year WHERE school_year_id = '" . escape($db_connect, $id) . "'";
        $result = call_mysql_query($select_query);


        
        if ($result && call_mysql_num_rows($result) > 0) {
            $row = call_mysql_fetch_array($result);
            $current_flag = intVal($row['flag_used']);
            $school_year_id = intVal($row['school_year_id']);
            $new_flag = $current_flag === 1 ? 0 : 1;
        } else {
            $output['code'] = 404;
            $output['msg_response'] = "Fiscal year record not found.";
            echo json_encode($output);
            exit();
        }

        // Update flag_used and updatedAt
        $db_connect->begin_transaction();
        try {
            $update_query = "UPDATE school_year 
            SET flag_used = '".     escape($db_connect, $new_flag)     ."', 
            isDefault = '".     escape($db_connect, $isDefault)     ."',
            updatedAt = NOW() 
            WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'";
            $update_result = call_mysql_query($update_query);

            $db_connect->commit();

            $output['code'] = 200;
            $output['msg_status'] = true;
            $output['msg_span'] = "";
            $output['msg_response'] = $new_flag === 1 ? "Fiscal year unlocked successfully." : "Fiscal year locked successfully.";
            echo json_encode($output);
            exit();
        } catch (Exception $e) {
            $db_connect->rollback();
            $output['code'] = 500;
            $output['msg_status'] = false;
            $output['msg_span'] = "";
            $output['msg_response'] = "Error updating fiscal year: " . $e->getMessage();
            echo json_encode($output);
            exit();
        }
    }

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateFiscalYear']) && $_POST['updateFiscalYear'] === "defaultFiscalYear"){
        $school_year_id = isset($_POST['school_year_id']) ? intVal(trim($_POST['school_year_id'])) : '';
        $isDefault = isset($_POST['isDefault']) ? intVal(trim($_POST['isDefault'])) : 0;
        $output = array(
            "code" => 0,
            "msg_status" => false,
            "msg_span" => "_system",
            "msg_response" => "Request Error, please try again."
        );
        $edit_id = "";

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
        if(empty($school_year_id) || dataEmptyCheck($isDefault)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $fetch_query = "SELECT school_year_id, isDefault FROM school_year WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'";
        if($fetch = call_mysql_query($fetch_query)){
            if($data = call_mysql_fetch_array($fetch)){
                // default sy is with value of 1.
                if($isDefault === 1){
                    if(intVal($data['isDefault']) === 1){
                        $output['code'] = 502;
                        $output['msg_response'] = "This Fiscal Year is already the default.";
                        echo json_encode($output);
                        exit();
                    }
                }
                if($isDefault === 0){
                    if(intVal($data['isDefault']) === 0){
                        $output['code'] = 502;
                        $output['msg_response'] = "This Fiscal Year is not the default.";
                        echo json_encode($output);
                        exit();
                    }
                }   
                $edit_id = isset($data['school_year_id']) ? intVal($data['school_year_id']) : '';
                if(empty($edit_id)){
                    $output['code'] = 503;
                    $output['msg_response'] = "Fiscal year record not found.";
                    echo json_encode($output);
                    exit();
                }
            } else {
                $output['code'] = 404;
                $output['msg_response'] = "Connetion failed.";
                echo json_encode($output);
                exit();
            }
        }

        $fetch_default_query = "SELECT school_year_id FROM school_year WHERE isDefault = 1";
        if($fetch_default = call_mysql_query($fetch_default_query)){
            if($num = call_mysql_num_rows($fetch_default) !== 0){
                while($data = call_mysql_fetch_array($fetch_default)){
                    $current_default_id = intVal($data['school_year_id']);
                    $db_connect->begin_transaction();
                    $change_default = 0;
                    $update_sql = "UPDATE school_year 
                    SET isDefault = '" . escape($db_connect, $change_default) . "' 
                    WHERE school_year_id = '" . escape($db_connect, $current_default_id) . "'";

                    call_mysql_query($update_sql);
                    $db_connect->commit();
                }
            }
        }


        $db_connect->begin_transaction();
        $default_update_sql = "UPDATE school_year 
        SET isDefault = '" . escape($db_connect, $isDefault) . "' 
        WHERE school_year_id = '" . escape($db_connect, $edit_id) . "'";

        call_mysql_query($default_update_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_response'] = "New default successfully set.";
        $output['msg_status'] = true;
        $output['msg_span'] = "";
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