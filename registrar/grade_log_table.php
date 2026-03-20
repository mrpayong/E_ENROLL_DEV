<?php
set_time_limit(0);
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

$session_class->session_close();
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_404;
    exit();
}

if (!($g_user_role == "REGISTRAR")) {
    $output =  json_encode(["last_page" => 1, "data" => "", "total_record" => 0]);
    echo $output;
    exit();
}

$query_limit = QUERY_LIMIT;

$table_name = "grade_tracker";
$field_query = '*';
$pages = 0;
$start = 0;
$size = 0;

$sorters = array();
$orderby = "tracker_id DESC";
$sql_where = "";
$sql_conds = "";
$sql_where_array = array();
$to_encode = array();
$output = "";

$dbfield = array('gt.data_changed', 'gt.upload_file', 'gt.date_added', 'gt.added_by', 'gt.reason', 'u.full_name');
$dborig = array('data_changed', 'upload_file', 'date_added', 'added_by','full_name');
$left_join = "AS gt LEFT JOIN (SELECT user_id, CONCAT(f_name, ' ', l_name) AS full_name FROM users) AS u ON gt.added_by = u.user_id";

if (isset($_GET['filters'])) {
    $filters = array();
    $sort_filters = array();
    $filters = $_GET['filters'];


    foreach ($filters as $filter) {
        if (isset($filter['field'])) {
            $id = $filter['field'];
            $sort_filters[$id] = $filter['value'];
        }
    }

    foreach ($dborig as $id) {
        if (isset($sort_filters[$id])) {
            $value = escape($db_connect, $sort_filters[$id]);
            if ($id == 'full_name') {
                array_push($sql_where_array, ' full_name LIKE \'%' . $value . '%\'');
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
$field_query = 'COUNT(DISTINCT tracker_id) as count'; // baguhin based sa need
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . "  " . $left_join . "  " . $sql_conds;
$total_query = 0;
if ($query = call_mysql_query($default_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $total_query = $data['count'];
        }
    }
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
if (isset($_GET['page']) and is_digit($_GET['page'])) {
    $page_no = $_GET['page'] - 1;
    $start = $page_no * $query_limit;
}

$start_no = ($start >= $total_query) ? $total_query : $start;
$field_query = implode(',', $dbfield);
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where; // ichange based sa need
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $left_join . " " . $sql_conds . "   ORDER BY " . $orderby;
$limit = " LIMIT " . $start_no . "," . $query_limit;
$sql_limit = $default_query . ' ' . $limit;
if ($query = call_mysql_query($sql_limit)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {

            $data_text = "CHANGED GRADE: [" . strtoupper(trim($data['reason'])) . "] \r\n" . $data['data_changed'];
            $data['data_changed'] = $data_text;
            $to_encode[] = $data;
        }
    }
    $output = json_encode(["last_page" => $pages, "data" => $to_encode, "total_record" => $total_query]);
} else {
    $output = json_encode(["last_page" => 0, "data" => "", "total_record" => 0]);
}

echo $output;
exit();
