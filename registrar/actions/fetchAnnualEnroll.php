<?php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

try {
    $output = array(
        'code' => 0,
        'msg_status' => false,
        'msg_response' => 'Request error, please try again.',
        'msg_span' => '_system'
    );

    
} catch (Throwable $th) {
    $output = array(
        'code' => 500,
        'msg_status' => false,
        'msg_response' => "Failed to request.",
        'msg_span' => '_system'
    );
    error_log("Error in fetchAnnualEnroll.php: " . $th->getMessage());
}
?>