<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCourse']) && $_POST['submitCourse'] === "createCourse"){
        $subject_title = isset($_POST['courseName']) ? $_POST['courseName'] : '';
        $subject_code = isset($_POST['courseCode']) ? $_POST['courseCode'] : '';
        $unit = isset($_POST['unit']) ? intVal(trim($_POST['unit'])) : '';
        $lec_units = isset($_POST['lec_units']) ? intVal(trim($_POST['lec_units'])) : '';
        $lab_units = isset($_POST['lab_units']) ? intVal(trim($_POST['lab_units'])) : '';
        $lec_lab = array();
        $manual = isset($_POST['manual']) 
            ? 1
            : (isset($_POST['newManual'])
                ? intVal(trim($_POST['newManual']))
                : 0);

        
        $output = array(
            'code' => 0,
            'status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        
        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }

        if(is_array($subject_title)){
            foreach($subject_title as $courseName){
                if(empty($courseName)){
                    $output['code'] = 501;
                    $output['msg_response'] = "All fields are required.";
                    echo json_encode($output);
                    exit();
                }
            }
        }
       
        if(empty($subject_code) || dataEmptyCheck($unit) || dataEmptyCheck($lec_units) || dataEmptyCheck($lab_units)){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }
        if($manual === 0){
            if(is_array($subject_title)){
                foreach($subject_title as $courseName){
                    $courseExist = "SELECT subject_title, subject_code FROM subject
                    WHERE subject_title = '".escape($db_connect, $courseName)."' OR subject_code = '".escape($db_connect, $subject_code)."'
                    ";
                    $query_table = call_mysql_query($courseExist);
                    $data = call_mysql_fetch_array($query_table);


                    if(call_mysql_num_rows($query_table) > 0){
                        $output['code'] = 502;
                        $output['msg_response'] = "Course name or code already exist for ".$courseName.".";
                        echo json_encode($output);
                        exit();
                    }
                }
            }
        }
        if($manual === 1){
            if(is_array($subject_title)){
                foreach($subject_title as $courseName){
                    $courseExist = "SELECT subject_title FROM subject
                    WHERE subject_title = '".escape($db_connect, $courseName)."'
                    ";
                    $query_table = call_mysql_query($courseExist);


                    if(call_mysql_num_rows($query_table) > 0){
                        $output['code'] = 502;
                        $output['msg_response'] = "".$courseName." already exists.";
                        echo json_encode($output);
                        exit();
                    }
                }
            }
        }

        
        array_push($lec_lab, $lec_units);
        array_push($lec_lab, $lab_units);
        $capped_code = strtoupper($subject_code);
        
        $encoded_lec_lab = json_encode($lec_lab);
        $db_connect->begin_transaction();
        if(is_array($subject_title)){
            foreach($subject_title as $courseName){
                $capped_title = strtoupper($courseName);
                $sql_course = "INSERT INTO subject (subject_code, subject_title, lec_lab, unit, flag_manual_enroll) VALUES (
                    '".escape($db_connect, $capped_code)."',
                    '".escape($db_connect, $capped_title)."',
                    '".escape($db_connect, $encoded_lec_lab)."',
                    '".escape($db_connect, $unit)."',
                    '".escape($db_connect, $manual)."'
                )";
                call_mysql_query($sql_course);
            }
        }
        $db_connect->commit();


        $output['code'] = 200;
        $output['status'] = true;
        $output['msg_response'] = 'Course created successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    // if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitCourse']) && $_POST['submitCourse'] === "editCourse"){
    //     $subject_title = isset($_POST['newCourseName']) ? trim($_POST['newCourseName']) : "";
    //     $unit = isset($_POST['newUnit']) ? intVal(trim($_POST['newUnit'])) : '';
    //     $subject_code = isset($_POST['newCourseCode']) ? trim($_POST['newCourseCode']) : '';
    //     $lec_units = isset($_POST['newLec_units']) ? intVal(trim($_POST['newLec_units'])) : '';
    //     $lab_units = isset($_POST['newLab_units']) ? intVal(trim($_POST['newLab_units'])) : '';
    //     $lec_lab = array();
    //     $subject_id = isset($_POST['editId']) ? intVal(trim($_POST['editId'])) : "";
    //     $to_edit = '';
    //     $courseID_list = array();
    //     $exist_subjTitle =  '';
    //     $exist_subjCode =  '';
    //     $manual = isset($_POST['newManual']) 
    //         ? trim($_POST['newManual']) === "true" 
    //             ? 1 
    //             : 0
    //         : 0;

        

    //     $output = array(
    //         'code' => 0,
    //         'msg_status' => false,
    //         'msg_response' => 'Request error, please try again.',
    //         'msg_span' => '_system',
    //     );
        
    //     function dataEmptyCheck($val){
    //         return ($val === null || $val === '');
    //     }

    //     if(empty($subject_title) || empty($subject_code) || dataEmptyCheck($unit) || dataEmptyCheck($lec_units) || dataEmptyCheck($lab_units)){
    //         $output['code'] = 501;
    //         $output['msg_response'] = "All fields are required.";
    //         echo json_encode($output);
    //         exit();
    //     }

    //     array_push($lec_lab, $lec_units);
    //     array_push($lec_lab, $lab_units);
    //     $encoded_lec_lab = json_encode($lec_lab);

    //     $old_data = "";
    //     $new_data = sha1($subject_title . $subject_code . $unit . $encoded_lec_lab);
    //     $program_exist = "SELECT subject_id, subject_title, subject_code, unit, lec_lab FROM subject WHERE 
    //     subject_id = '".    escape($db_connect, $subject_id).   "' ";

    //     if ($query = call_mysql_query($program_exist)){
    //         if($data = call_mysql_fetch_array($query)){
    //             $to_edit = intVal($data['subject_id']);
    //             $exist_subjTitle = $data['subject_title'];
    //             $exist_subjCode = $data['subject_code'];
    //             $old_data = sha1($data['subject_title'] . $data['subject_code'] . $data['unit'] . $data['lec_lab']);
    //         } else {
    //             $output['code'] = 503;
    //             $output['msg_response'] = "Connection failed";
    //             echo json_encode($output);
    //             exit();
    //         }
    //     } else {
    //         $output['code'] = 502;
    //         $output['msg_reponse'] = "It seems the information you are trying to update does not exist or you have unstable network.";
    //         echo json_encode($output);
    //         exit();
    //     }

    //     if($new_data === $old_data){
    //         $output['code'] = 504;
    //         $output['msg_response'] = "You did not make any changes.";
    //         echo json_encode($output);
    //        exit();
    //     }

    //     // allow same subject code
    //     if($manual === 1){
    //         $capped_title = strtoupper($subject_title);
    //         $capped_code = strtoupper($subject_code);

    //         $fetch_IDs = "SELECT subject_id FROM subject 
    //         WHERE subject_code = '".     escape($db_connect, $exist_subjCode)   ."'
    //         ";
    //         $query_IDs = call_mysql_query($fetch_IDs);
    //         while($data_IDs = call_mysql_fetch_array($query_IDs)){
    //             // the only IDs that can only have the same subject code
    //             array_push($courseID_list, $data_IDs['subject_id']);
    //         }

    //         if(!empty($courseID_list)){
    //             $allowed_ids = implode(",", array_map('intval', $courseID_list));
    //             $check_code = "SELECT subject_id FROM subject 
    //             WHERE subject_code = '".     escape($db_connect, $capped_code)   ."'
    //             AND subject_id NOT IN ($allowed_ids);
    //             ";

    //             $query = call_mysql_query($check_code);
    //             if(call_mysql_num_rows($query) > 0){
    //                 $output['code'] = 505;
    //                 $output['msg_response'] = "Course code already taken.";
    //                 echo json_encode($output);
    //                 exit();
    //             }
    //         }

    //         $check_title = "SELECT subject_title, subject_code FROM subject 
    //         WHERE subject_title = '".     escape($db_connect, $capped_title)   ."'
    //         AND subject_id != '".   escape($db_connect, $to_edit)    ."' 
    //         ";
    //         $query = call_mysql_query($check_title);
    //         if(call_mysql_num_rows($query) > 0){
    //             $output['code'] = 505;
    //             $output['msg_response'] = "Course name already taken.";
    //             echo json_encode($output);
    //             exit();
    //         }

    //         $db_connect->begin_transaction();

    //         if($capped_title !== $exist_subjTitle){
    //             $sql1 = "UPDATE subject SET 
    //                 subject_title =   '".     escape($db_connect, $capped_title).      "'
    //                 WHERE subject_id = '".      escape($db_connect, $to_edit)        ."'
    //             ";
    //             $result1 = call_mysql_query($sql1);
    //         }

    //         if(is_array($courseID_list)){
    //             foreach($courseID_list as $s_id){
    //                 $sql2 = "UPDATE subject SET 
    //                     unit =   '".     escape($db_connect, $unit).      "',
    //                     lec_lab =   '".     escape($db_connect, $encoded_lec_lab).      "',
    //                     subject_code =   '".     escape($db_connect, $capped_code).      "',
    //                     date_modified = NOW()
    //                     WHERE subject_id = '".      escape($db_connect, $s_id)        ."' 
    //                 ";
    //                 $result2 = call_mysql_query($sql2);
    //             }
    //         }
    //         $db_connect->commit();
    //     }

    //     // disallow same subject code and subject title
    //     if($manual === 0){
    //         // manual_enroll_flag = 0 means: 
    //         // subject code and subject title must be unique
    //         $capped_title = strtoupper($subject_title);
    //         $capped_code = strtoupper($subject_code);

    //         $check_title = "SELECT subject_title, subject_code FROM subject 
    //         WHERE (subject_title = '".     escape($db_connect, $capped_title).      "' OR
    //         subject_code = '".     escape($db_connect, $capped_code).      "') AND
    //         subject_id != '".   escape($db_connect, $to_edit)    ."'
    //         ";
    //         $query = call_mysql_query($check_title);
    //         if(call_mysql_num_rows($query) > 0){
    //             if(($data['subject_title'] === $capped_title) || ($data['subject_code'] === $capped_code)){
    //                 $output['code'] = 505;
    //                 $output['msg_response'] = "Course name or code already taken.";
    //                 echo json_encode($output);
    //                 exit();
    //             }
    //         }
            
    //         $db_connect->begin_transaction();

    //         $sql1 = "UPDATE subject SET 
    //             subject_title =   '".     escape($db_connect, $capped_title).      "',
    //             subject_code =   '".     escape($db_connect, $capped_code).      "',
    //             unit =   '".     escape($db_connect, $unit).      "',
    //             lec_lab =   '".     escape($db_connect, $encoded_lec_lab).      "',
    //             date_modified = NOW()
    //             WHERE subject_id = '".      escape($db_connect, $to_edit)        ."'
    //         ";
    //         $result1 = call_mysql_query($sql1);
    //         $db_connect->commit();
    //     }

    //     $output['code'] = 200;
    //     $output['msg_status'] = true;
    //     $output['msg_response'] = 'Course updated successfully.';
    //     $output['msg_span'] = '';
    //     echo json_encode($output);
    //     exit();
    // }
} catch (Throwable $th) {
    $db_connect->rollback();
    $output['code'] = 500;
    $output['msg_response'] = $th->getMessage();
    echo json_encode($output);
    exit();
}
?>