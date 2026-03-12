<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;


header('Content-Type: application/json');

try {
    $output = array(
        'code' => 0,
        'msg_status' => false,
        'msg_response' => 'Request failed.',
        'data' => ''
    );
    if ($g_user_role !== "REGISTRAR") {
        $output['msg_response'] = "Unauthorized access.";
        $output['code'] = 404;
        exit();
    }

    $prospectus = array();
    $curr_id = isset($_GET['curriculum_id']) ? intVal($_GET['curriculum_id']) : 0;
    $sql_pros = "SELECT year_level, semester, subject_code, subject_title, unit, lec_lab, curriculum_code, curriculum_title, pre_req, pre_req_id
        FROM curriculum
        WHERE curriculum_id = '".escape($db_connect, $curr_id)."'
        ORDER BY year_level ASC, semester ASC";
    if($sql = call_mysql_query($sql_pros)){
        while($data = call_mysql_fetch_array($sql)){
            $data['lab'] = isset($data['lec_lab']) ? json_decode($data['lec_lab'])[0] : '';
            $data['lec'] = isset($data['lec_lab']) ? json_decode($data['lec_lab'])[1] : '';
            array_push($prospectus, $data);
        }
    }

    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_response'] = "Success";
    $output['data'] = $prospectus;
    echo json_encode($output);
    exit();
} catch (Throwable $th) {
    $output['code'] = 500;
    $output['msg_status'] = false;
    $output['msg_reponse'] = "Failed to fetch";
    echo json_encode($output);
    exit();
}
?>