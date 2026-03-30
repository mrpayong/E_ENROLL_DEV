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

    function dataEmptyCheck($val){
        return ($val === null || $val === '');
    }

    if (dataEmptyCheck($payload['curriculum_id']) || empty($payload['exp']) || empty($payload['nonce'])) {
        $output['code'] = 400;
        $output['msg_response'] = "Coin Unacceptable.";
        echo json_encode($output);
        exit;
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
    echo json_encode($output);
    exit;

} catch (Throwable $th) {
    $output['code'] = 500;
    $output['msg_response'] = "Server error.";
    echo json_encode($output);
    exit;
}
