<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
try {
    //edit fiscal year
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateFiscalYear']) && $_POST['updateFiscalYear'] === "updateNewData") {
        $id = isset($_POST['school_year_id']) ? trim($_POST['school_year_id']) : '';
        $school_year = isset($_POST['schoolYear']) ? trim($_POST['schoolYear']) : '';
        $sem = isset($_POST['semester']) ? trim($_POST['semester']) : '';
        $date_from = isset($_POST['startDate']) ? trim($_POST['startDate']) : '';
        $date_to = isset($_POST['endDate']) ? trim($_POST['endDate']) : '';
        $old_vals = "";
        $new_vals = "";
        $output = array("code" => 0,"msg_status" => false, "msg_span" => "_system", "msg_response" => "Request Error, please try again.");

        // Basic validation
        if (!$id || !$school_year || !$sem || !$date_from || !$date_to) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required.',  'code' => 501]);
            exit;
        }

        $new_vals = sha1($school_year . $sem . $date_from . $date_to);

        $default_query = "SELECT school_year, sem, date_from, date_to FROM school_year WHERE school_year_id = '" . escape($db_connect, $id) . "'";
        if($query = call_mysql_query($default_query)){
            if($num = call_mysql_num_rows($query)){
                if($data = call_mysql_fetch_array($query)){
                    $old_vals = sha1($data['school_year'] . $data['sem'] . $data['date_from'] . $data['date_to']);
                }
            } else {
                $output['msg_status'] = false;
                $output['msg_span'] = "_msg";
                $output['code'] = 500;
                $output['msg_response'] = "Fiscal year record not found.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['msg_status'] = false;
            $output['msg_span'] = "_msg";
            $output['code'] = 500;
            $output['msg_response'] = "Database query failed.";
            echo json_encode($output);
            exit();
        }

        if($old_vals === $new_vals){
            $output['msg_status'] = false;
            $output['msg_span'] = "_msg";
            $output['code'] = 500;
            $output['msg_response'] = "You haven't change any of the information.";
            echo json_encode($output);
            exit();
        }

        ## update
        ## start the transaction
        $db_connect->begin_transaction();
        try {
            $sql1 = "UPDATE school_year SET school_year = '" . escape($db_connect, $school_year) . "',
                    sem = '" . escape($db_connect, $sem) . "',
                    date_from = '" . escape($db_connect, $date_from) . "',
                    date_to = '" . escape($db_connect, $date_to) . "',
                    updatedAt = NOW()
                    WHERE school_year_id = '" .  escape($db_connect, $id) . "'";
        
            $result1 = call_mysql_query($sql1);

            $db_connect->commit(); 
        } catch (Exception $e) {
            $db_connect->rollback();
            $output['msg_status'] = false;
            $output['msg_span'] = "_msg";
            $output['code'] = 500;
            $output['msg_response'] = $e->getMessage();
            echo json_encode($output);
            exit();
        }

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
        $newFlag = isset($_POST['new_flag']) ? trim($_POST['new_flag']) : '';
        $unDefault = 0;
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
            isDefault = '".     escape($db_connect, $unDefault)     ."',
            updatedAt = NOW() 
            WHERE school_year_id = '" . escape($db_connect, $id) . "'";
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


} catch (Throwable $th) {
    $db_connect->rollback();
    $output['code'] = 500;
    $output['msg_response'] = "An unexpected error occurred: " . $th->getMessage();
    echo json_encode($output);
    exit();
}