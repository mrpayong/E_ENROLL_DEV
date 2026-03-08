<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

$session_class->session_close();

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}
if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}



try {
    $output = array(
        "code" => 0,
        "msg_status" => false,
        "msg_span" => "_system",
        "msg_response" => "Request Error, please try again.",
    );
    $curriculum = array();

    $sql = "SELECT curriculum_id, curriculum_code, header FROM curriculum_master WHERE status_allowable = 0 ORDER BY date_created DESC
    ";

    if($query = call_mysql_query($sql)){
        while($data = call_mysql_fetch_array($query)){
            $curriculum[] = array(
                "curriculum_id" => intVal($data['curriculum_id']),
                "curriculum_code" => $data['curriculum_code'],
                "header" => $data['header']
            );
        }
    }

    $output['code'] = 200;
    $output['msg_response'] = "success fetched";
    $output['msg_status'] = true;
    $output['msg_span'] = "";
    $output['data'] = $curriculum;
    echo json_encode($output);
} catch (Throwable $th) {
    $output['code'] = 500;
    echo json_encode($output);
}
?>