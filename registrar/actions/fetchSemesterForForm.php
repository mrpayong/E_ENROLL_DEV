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

$schoolYear_sem = [];
$query = "SELECT school_year_id, sem, isDefault, limit_perSem, school_year
    FROM school_year 
    WHERE flag_used = 1
    ORDER BY date_from DESC";
$result = call_mysql_query($query);

if($result){
    while($data = call_mysql_fetch_array($result)){
        $data['isDefault'] = intVal($data['isDefault']);
        $schoolYear_sem[] = $data;
    }
    mysqli_free_result($result);
}

echo json_encode(['status' => true, 'code' => 200, 'data' => $schoolYear_sem]);
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