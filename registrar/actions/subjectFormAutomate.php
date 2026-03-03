<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
try {
if(!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')){
    http_response_code(500);
    echo json_encode([
        'status' => false, 
        'code' => 400,
        'message' => "Unavailable"
    ]);
    exit();
}

if($g_user_role !== "REGISTRAR"){
    http_response_code(500);
    echo json_encode([
        'status' => false, 
        'code' => 401,
        'message' => "Unavailable"
    ]);
    exit();
}

$data = [];
$query = "SELECT subject_code, lec_lab, unit, flag_manual_enroll FROM subject WHERE flag_manual_enroll = 1 GROUP BY subject_code";
$result = call_mysql_query($query);
while ($row = call_mysql_fetch_array($result)) {
    $lec_lab = json_decode(html_entity_decode($row['lec_lab']));
    $data[] = [
        'subject_code' => $row['subject_code'],
        'lec' => $lec_lab[0],
        'lab' => $lec_lab[1],
        'unit' => $row['unit'],
        'manualEnroll' => $row['flag_manual_enroll']
    ];
}

echo json_encode(['status' => true, 'code' => 200, 'data' => $data]);
exit();
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode([
        'code' => 500,
        'error' => "server error",
        'status' => false,
        'message' => $th->getMessage()
    ]);
    exit();
}
?>