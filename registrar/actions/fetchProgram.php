<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

$session_class->session_close();

if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}

if ($g_user_role !== "REGISTRAR") {
    include HTTP_401;
    echo "Unavailable Data.";
    exit();
}

$query_limit = QUERY_LIMIT;
$table_name = "programs";
$field_query = '*';
$pages = 0;
$start = 0;
$size = 0;
$sorters = array();
$orderby = "program_id DESC";
$sql_where = "";
$sql_conds = "";
$sql_where_array = array();
$to_encode = array();
$output = "";
$total_query = 0;
$dbfield = array('program_id', 'department_id', 'program', 'short_name', 'major', 'status', 'archiveStatus');
$dborig = array('program_id', 'department_id', 'program', 'short_name', 'major', 'status', 'archiveStatus');

// Filtering
if (isset($_GET['filters'])) {
    $filters = $_GET['filters'];
    $sort_filters = array();
    foreach ($filters as $filter) {
        if (isset($filter['field'])) {
            $id = $filter['field'];
            $sort_filters[$id] = $filter['value'];
        }
    }
    foreach ($dborig as $id) {
        if (isset($sort_filters[$id])) {
            $value = escape($db_connect, $sort_filters[$id]);
            array_push($sql_where_array, $id . " LIKE '%" . $value . "%'");
        }
    }
}
if (!empty($sql_where_array)) {
    $temp_arr = implode(' AND ', $sql_where_array);
    $sql_where = (empty($temp_arr)) ? '' : $temp_arr;
}

// Sorting
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = array('asc', 'desc');
    if (in_array($sorters[0]['field'], $dborig) && in_array($sorters[0]['dir'], $tag)) {
        $orderby = $sorters[0]['field'] . ' ' . $sorters[0]['dir'];
    }
}

// Pagination
if (isset($_GET['size']) && is_digit($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $_GET['size'] : $query_limit;
}

// Count total records
$field_query = 'COUNT(*) as count';
$sql_conds = (empty($sql_where)) 
    ? '' 
    : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $sql_conds;
if ($query = call_mysql_query($default_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $total_query = $data['count'];
        }
    }
}

// Calculate pagination offsets
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
if (isset($_GET['page']) && is_digit($_GET['page'])) {
    $page_no = $_GET['page'] - 1;
    $start = $page_no * $query_limit;
}
$start_no = ($start >= $total_query) ? $total_query : $start;

// Fetch data
$field_query = implode(',', $dbfield);
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $sql_conds . " ORDER BY " . $orderby;
$limit = " LIMIT " . $start_no . "," . $query_limit;
$sql_limit = $default_query . ' ' . $limit;

if ($query = call_mysql_query($sql_limit)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['program_id'] = (int)$data['program_id'];
            $data['department_id'] = (int)$data['department_id'];
            $data['status'] = (int)$data['status'];
            $data['archiveStatus'] = (int)$data['archiveStatus'];
            $to_encode[] = $data;
        }
        $output = json_encode(["last_page" => $pages, "data" => $to_encode, "total_record" => $total_query]);
    } else {
        $output =  json_encode(["last_page" => 0, "data" => $to_encode, "total_record" => 0]);
    }
} else {
    $output =  json_encode(["last_page" => 0, "data" => $to_encode, "total_record" => 0]);
}

mysqli_free_result($query);
echo $output;
exit();
?>