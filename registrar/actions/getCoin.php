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
$coin = "hil".$g_user_role."ary#"."?#prospectus".date('Ymd')."'7/19";
define('COIN', $coin);
if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

// $coin = "hil".$g_user_role."ary#"."?#prospectus".date('Ymd')."'7/19";

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

try {
    $output = [
        "code" => 0,
        "msg_status" => false,
        "msg_span" => "_system",
        "msg_response" => "Request Error, please try again."
    ];

    $curriculum_id = isset($_POST['curriculum_id']) ? trim($_POST['curriculum_id']) : '';

    if ($curriculum_id === '' || !ctype_digit((string)$curriculum_id)) {
        $output['code'] = 400;
        $output['msg_response'] = "Invalid curriculum.";
        echo json_encode($output);
        exit();
    }

    $check = "SELECT curriculum_id FROM curriculum_master WHERE curriculum_id = '" . escape($db_connect, $curriculum_id) . "' LIMIT 1";
    if($sql = call_mysql_query($check)){
        if(call_mysql_num_rows($sql) === 0){
            $output['code'] = 404;
            $output['msg_response'] = "Curriculum not found.";
            echo json_encode($output);
            exit();
        }
    }

    // Create signed token (expires in 10 minutes)
    $payload = [
        "curriculum_id" => intVal($curriculum_id),
        "exp" => time() + 6000,
        "nonce" => bin2hex(random_bytes(8))
    ];
    
    $payloadJson = json_encode($payload);
    $payloadB64 = base64url_encode($payloadJson);
    $sig = hash_hmac('sha256', $payloadJson, COIN, true);
    $sigB64 = base64url_encode($sig);
    $token = $payloadB64 . "." . $sigB64;

    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_response'] = "Success";
    $output['msg_span'] = "";
    $output['token'] = $token;
    echo json_encode($output);
    exit();
} catch (Throwable $th) {
    $output['code'] = 500;
    $output['msg_response'] = $th->getMessage();
    echo json_encode($output);
    exit();
}

?>