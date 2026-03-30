<?php
require 'config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$page_id = "";
$error_encounter = false;

## change based on roles [change also in other role files or folder]
if (isset($g_user_role) || !empty($g_user_role)) {
	if ($g_user_role == "ADMIN") {
		header("Location: " . BASE_URL . "admin/main_admin.php");
		exit();
	} else if ($g_user_role == "REGISTRAR") {
		header("Location: " . BASE_URL . "registrar/index.php");
		exit();
	} else if ($g_user_role == "DEAN") {
		header("Location: " . BASE_URL . "dean/index.php");
		exit();
	} else if ($g_user_role == "STUDENT") {
		header("Location: " . BASE_URL . "student/index.php");
		exit();
	} else {
		header("Location: " . API_URL);
		exit();
	}
} else {
	header("Location: " . API_URL);
	exit();
}
