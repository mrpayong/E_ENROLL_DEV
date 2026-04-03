<?php
ignore_user_abort(true);
set_time_limit(0);
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$session_class->session_close();

## verify user access
if (!($g_user_role == "DEAN")) {
    header("Location: " . BASE_URL . "index.php"); //balik sa login then sa login aalamain kung anung role at saang page landing dapat
    exit();
}


$id = isset($_GET['attach']) ? trim($_GET['attach']) : '';
if ($id != "") {
    if ($id == "IMP_BLK_SCT" && ($g_user_role == "DEAN")) {
        $fullPath = join(DIRECTORY_SEPARATOR, array(DOMAIN_PATH, 'upload', 'guide', 'guide_section_import.csv'));
    } else {
        include HTTP_404;
        exit();
    }

    if (file_exists($fullPath)) {
        if ($fd = fopen($fullPath, "r")) {
            $fsize = filesize($fullPath);
            $path_parts = pathinfo($fullPath);
            $ext = strtolower($path_parts["extension"]);
            switch ($ext) {
                case "pdf":
                    header("Content-type: application/pdf");
                    header("Content-Disposition: attachment; filename=\"" . $path_parts["basename"] . "\""); // use 'attachment' to force a file download
                    break;
                // add more headers for other content types here
                default;
                    header("Content-type: application/octet-stream");
                    header("Content-Disposition: filename=\"" . $path_parts["basename"] . "\"");
                    break;
            }
            header("Content-length: " . $fsize);
            header("Cache-control: private"); //use this to open files directly
            //session_write_close();
            while (!feof($fd)) {
                $buffer = fread($fd, 2048);
                echo $buffer;
            }
        }
        fclose($fd);
        exit();
    }
    include HTTP_404;
    exit();
}



include HTTP_404;
exit();
