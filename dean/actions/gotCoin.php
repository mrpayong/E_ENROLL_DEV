<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

$session_class->session_close();

header('Content-Type: application/json');

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}
$coin = "hil".$g_user_role."ary#"."?#prospectus".date('Ymd')."'7/19";
define('COIN', $coin);
if (!($g_user_role == "DEAN")) {
    header("Location: " . BASE_URL);
    exit();
}

// $coin = "hil".$g_user_role."ary#"."?#prospectus".date('Ymd')."'7/19";

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

try {
    $output = [
        "code" => 0,
        "msg_status" => false,
        "msg_response" => "Coin not found."
    ];

    $coin_asset = isset($_POST['coin']) ? trim($_POST['coin']) : '';
    if (empty($coin_asset)) {
        $output['code'] = 400;
        $output['msg_response'] = "Coin unsuccessfully delivered.";
        echo json_encode($output);
        exit;
    }

    $parts = explode('.', $coin_asset);
    if (count($parts) !== 2) {
        $output['code'] = 400;
        $output['msg_response'] = "Coin not formatted.";
        echo json_encode($output);
        exit;
    }

    [$payloadB64, $sigB64] = $parts;
    $payloadJson = base64url_decode($payloadB64);
    $payload = json_decode($payloadJson, true);

    // var_dump($payload);
    // exit();

    function dataEmptyCheck($val){
        return ($val === null || $val === '');
    }

    if (dataEmptyCheck($payload['curriculum_id']) || empty($payload['exp']) || empty($payload['nonce'])) {
        $output['code'] = 400;
        $output['msg_response'] = "Coin Unacceptable.";
        echo json_encode($output);
        exit;
    }


    $update_status = 0;
    $subjects = [];
    $sql_curr = "SELECT curriculum_id, ched_aprrv_date FROM curriculum_master WHERE curriculum_id = '" . escape($db_connect, intVal($payload['curriculum_id'])) . "'
    AND program_id = '".escape($db_connect, $payload["program_id"])."' LIMIT 1";
    if($sql = call_mysql_query($sql_curr)){
        if($curr_data = call_mysql_fetch_array($sql)){
            if(!empty($curr_data['ched_aprrv_date'])){
                $update_status = 1;
            }
            $sql_currSubjs = "SELECT subject_id, subject_code, subject_title, unit, lec_lab, pre_req, year_level, semester FROM curriculum WHERE curriculum_id = '" . escape($db_connect, intVal($curr_data['curriculum_id'])) . "'
            AND program_id = '".escape($db_connect, $payload["program_id"])."'
            ";
            if($sqlSubjs = call_mysql_query($sql_currSubjs)){
                while($row = call_mysql_fetch_array($sqlSubjs)){
                    $lec_labData = json_decode($row['lec_lab'], true);
                    $lec = intVal($lec_labData[0]) ?? 0;
                    $lab = intVal($lec_labData[1]) ?? 0;
                    $subjects[] = [
                        "subject_id" => $row['subject_id'],
                        "subject_code" => $row['subject_code'],
                        "subject_title" => $row['subject_title'],
                        "unit" => intVal($row['unit']),
                        "lec" => $lec,
                        "lab" => $lab,
                        "pre_req" => $row['pre_req'],
                        "year_level" => intVal($row['year_level']),
                        "semester" => $row['semester']
                    ];
                }
            }
            
        }
    }

    $calcSig = rtrim(strtr(base64_encode(hash_hmac('sha256', $payloadJson, COIN, true)), '+/', '-_'), '=');
    if (!hash_equals($calcSig, $sigB64)) {
        $output['code'] = 401;
        $output['msg_response'] = "Token signature invalid.";
        echo json_encode($output);
        exit;
    }

    if (time() > $payload['exp']) {
        $output['code'] = 401;
        $output['msg_response'] = "Token expired.";
        echo json_encode($output);
        exit;
    }

    $output['code'] = 200;
    $output['msg_status'] = true;
    $output['msg_response'] = "Success";
    $output['curriculum_id'] = $payload['curriculum_id'] ?? '';
    $output['program_id'] = $payload['program_id'] ?? '';
    $output['update_status'] = $update_status;
    $output['courses'] = $subjects;
    echo json_encode($output);
    exit;

} catch (Throwable $th) {
    $output['code'] = 500;
    $output['msg_response'] = "Server error.";
    echo json_encode($output);
    exit;
}
