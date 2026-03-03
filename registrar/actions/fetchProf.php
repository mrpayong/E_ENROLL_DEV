<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

header('Content-Type: application/json');

try {


if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}

if ($g_user_role !== "REGISTRAR") {
    include HTTP_401;
    echo json_encode(["error" => "Unavailable Data."]);
    exit();
}

$deans = [];
// user_role is a JSON array, so use JSON_CONTAINS for string "3"
$query = "SELECT user_id, f_name, m_name, l_name FROM users WHERE JSON_CONTAINS(user_role, '\"4\"')";

$result = call_mysql_query($query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $deans[] = [
            'user_id' => $row['user_id'],
            'name' => trim($row['f_name'] . ' ' . $row['m_name'] . ' ' . $row['l_name'])
        ];
    }
    mysqli_free_result($result);
}

    echo json_encode(['status' => true, 'code' => 200, 'data' => $deans]);
    exit();

} catch (\Throwable $th) {
    mysqli_free_result($result);
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