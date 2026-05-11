<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
$session_class->session_close();

function dean_program_department_id($db_connect, $general_id) {
    $sql = "
        SELECT d.department_id
        FROM departments d
        INNER JOIN users u ON d.user_id = u.user_id
        WHERE u.general_id = '" . escape($db_connect, $general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql)) {
        if ($row = call_mysql_fetch_array($query)) {
            return intVal($row['department_id'] ?? 0);
        }
    }

    return 0;
}

try {
    if(!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')){
        http_response_code(400);
        echo json_encode([
            'status' => false, 
            'code' => 400,
            'message' => "Unavailable"
        ]);
        exit();
    }

    if($g_user_role !== "DEAN"){
        http_response_code(401);
        echo json_encode([
            'status' => false, 
            'code' => 401,
            'message' => "Unavailable"
        ]);
        exit();
    }

    $dean_department_id = dean_program_department_id($db_connect, trim((string)($g_general_id ?? '')));
    if ($dean_department_id <= 0) {
        echo json_encode([
            'status' => true,
            'code' => 200,
            'data' => [],
            'message' => 'No handled department was found for this Dean account.'
        ]);
        exit();
    }

    $programs = [];
    $query = "
        SELECT program_id, program
        FROM programs
        WHERE status = '0'
          AND department_id = '" . escape($db_connect, $dean_department_id) . "'
        ORDER BY program ASC
    ";
    $result = call_mysql_query($query);

    if($result){
        while($row = mysqli_fetch_assoc($result)){
            $programs[] = $row;
        }
        mysqli_free_result($result);
    }

    echo json_encode(['status' => true, 'code' => 200, 'data' => $programs]);
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
