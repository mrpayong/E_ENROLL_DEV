<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
	include HTTP_404;
	exit();
}

// $csrf = new CSRF($session_class);
// $result = $csrf->validateCsrfToken();
// if(!$result){
// 	$output =  json_encode(["last_page"=>1, "data"=>"","total_record"=>0,"error"=>"Invalid Token"]);
// 	echo $output;
// 	exit();
// }

$session_class->session_close();

if (!($g_user_role == "REGISTRAR")) {
	$output =  json_encode(["last_page" => 1, "data" => "", "total_record" => 0, "error" => "Invalid User"]);
	echo $output;
	exit();
}

$flag_reload = isset($_GET['load_all']) ? $_GET['load_all'] : 0;
$query_limit = 100;

$table_name = "final_grade";
$field_query = '*';
$pages = 0;
$start = 0;
$size = 0;

$sorters = array();
$orderby = "final_id DESC";
$sql_where = "";
$sql_conds = "";
$sql_where_array = array();
$to_encode = array();
$output = "";

$dborig = array('final_id', 'teacher_class_id', 'student_id', 'student_name', 'student_id_text', 'section_name', 'subject_code', 'course_desc', 'program_id', 'program_code', 'major', 'units', 'prelimterm_grade', 'midterm_grade', 'finalterm_grade', 'final_grade', 'final_grade_text', 'converted_grade', 'orig_final_grade', 'completion', 'school_year_id', 'school_year', 'sem', 'remarks', 'yr_level', 'flag_fixed', 'status', 'date_added', 'date_updated', 'school_name', 'credit_code'); // tabulator  checking
$dbfield = array('final_id', 'teacher_class_id', 'student_id', 'student_name', 'student_id_text', 'section_name', 'subject_code', 'course_desc', 'program_id', 'program_code', 'major', 'units', 'prelimterm_grade', 'midterm_grade', 'finalterm_grade', 'final_grade', 'final_grade_text', 'converted_grade', 'completion', 'school_year_id', 'school_year', 'sem', 'remarks', 'yr_level', 'flag_fixed', 'status', 'date_added', 'date_updated', 'school_name', 'credit_code');


if (isset($_GET['filters'])) {
	$filters = array();
	$sort_filters = array();
	$filters = $_GET['filters'];
	foreach ($filters as $filter) {
		if (isset($filter['field'])) {

			if (is_array($filter['value'])) {
				$filter['value'] = $filter['value'][0];
			}

			$id = $filter['field'];
			$sort_filters[$id] = $filter['value'];
		}
	}


	foreach ($dborig as $id) {
		if (isset($sort_filters[$id])) {
			$value = escape($db_connect, $sort_filters[$id]);

			if ($id == "school_year_id") {
				array_push($sql_where_array, "final_grade.school_year_id = '" . $value . "')");
				continue;
			}

			if ($id == "final_grade") {
				array_push($sql_where_array, "(COALESCE(NULLIF(`final_grade`, 0), NULLIF(`final_grade_text`, '')) = '" . $value . "')");
				continue;
			}
			array_push($sql_where_array, $id . ' LIKE \'%' . $value . '%\'');
		}
	}
}

if (!empty($sql_where_array)) {
	$temp_arr = implode(' AND ', $sql_where_array);
	$sql_where = (empty($temp_arr)) ? '' : $temp_arr;
}

if (isset($_GET['sorters'])) {
	$sorters = $_GET['sorters'];
	$tag = array('asc', 'desc');
	if (in_array($sorters[0]['field'], $dborig) and in_array($sorters[0]['dir'], $tag)) {
		$orderby = $sorters[0]['field'] . ' ' . $sorters[0]['dir'];
	}
}

if (isset($_GET['size']) and is_digit($_GET['size'])) {
	$query_limit = ($_GET['size'] > $query_limit) ? $_GET['size'] : $query_limit;
}

//total query counter 
$field_query = 'COUNT(DISTINCT final_id) as count'; // baguhin based sa need
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . "  " . $sql_conds;
$total_query = 0;
if ($query = call_mysql_query($default_query)) {
	if ($num = call_mysql_num_rows($query)) {
		while ($data = call_mysql_fetch_array($query)) {
			$total_query = $data['count'];
		}
	}
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
$pages = ($flag_reload == 0) ? $pages : 1;
if (isset($_GET['page']) and is_digit($_GET['page'])) {
	$page_no = $_GET['page'] - 1;
	$start = $page_no * $query_limit;
}

$start_no = ($start >= $total_query) ? $total_query : $start;

$field_query = implode(',', $dbfield);
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where; // ichange based sa need
$default_query = "SELECT final_id, teacher_class_id, final_grade.student_id,student_id_no, firstname, lastname, middle_name, suffix_name, gender,CASE WHEN middle_name IS NOT NULL AND middle_name != '' THEN CONCAT(LEFT(middle_name,1),'.') ELSE '' END as middle_initial, student_name, student_id_text, section_name, subject_code, course_desc, final_grade.program_id, final_grade.program_code, final_grade.major, units, midterm_grade, finalterm_grade, final_grade as orig_final_grade, final_grade_text, converted_grade, completion, final_grade.school_year_id, school_year, sem, remarks, date_added, yr_level, flag_fixed, date_updated, final_grade.status, school_name, credit_code,COALESCE(NULLIF(`final_grade`, 0), NULLIF(`final_grade_text`, '')) AS `final_grade` FROM " . $table_name . " LEFT JOIN student ON " . $table_name . ".student_id = student.student_id " . $sql_conds . "   ORDER BY " . $orderby;
$limit = ($flag_reload == 0) ? " LIMIT " . $start_no . "," . $query_limit : '';
$sql_limit = $default_query . ' ' . $limit;
if ($query = call_mysql_query($sql_limit)) {
	if ($num = call_mysql_num_rows($query)) {
		while ($data = call_mysql_fetch_array($query)) {
			//$data = array_html($data);
			$data['id'] = $data['final_id'];
			$data['hash_id'] = encrypted_string($data['final_id'], GRADE_KEY);
			$to_encode[] = $data;
		}
	}
	$output = json_encode(["last_page" => $pages, "data" => $to_encode, "total_record" => $total_query]);
} else {
	$output =  json_encode(["last_page" => 0, "data" => "", "total_record" => 0]);
}

echo $output;
exit();
