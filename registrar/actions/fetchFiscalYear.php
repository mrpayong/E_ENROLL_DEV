<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH; // API data

$session_class->session_close();
if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
    include HTTP_401;
    exit();
}

if($g_user_role !== "REGISTRAR"){
    include HTTP_401;
    echo "Unavailable Data.";
    exit();
}

// --- Fiscal Year Table Setup ---
$query_limit = QUERY_LIMIT;

$table_name = "school_year";
$field_query = '*';
$pages = 0;
$start = 0;
$size = 0;

$sorters = array();
$orderby = "school_year_id DESC";
$sql_where = "";
$sql_conds = "";
$sql_where_array = array();
$to_encode = array();
$output = "";
$total_query = 0;

// Define fields for filtering/sorting
$dbfield = array('school_year_id', 'school_year', 'sem', 'date_from', 'date_to', 'flag_used', 'isDefault', 'createdAt', 'updatedAt');
$dborig = array('school_year_id', 'school_year', 'sem', 'date_from', 'date_to', 'flag_used', 'isDefault', 'createdAt', 'updatedAt');

// --- Filtering ---
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
            array_push($sql_where_array, $id . " LIKE '%" . $value . "%'");
        }
    }
}

if (!empty($sql_where_array)) {
    $temp_arr = implode(' AND ', $sql_where_array);
    $sql_where = (empty($temp_arr)) ? '' : $temp_arr;
}

// --- Sorting ---
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = array('asc', 'desc');
    if (in_array($sorters[0]['field'], $dborig) and in_array($sorters[0]['dir'], $tag)) {
        $orderby = $sorters[0]['field'] . ' ' . $sorters[0]['dir'];
    }
}

// --- Pagination ---
if (isset($_GET['size']) and is_digit($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $_GET['size'] : $query_limit;
}

// --- Count total records for pagination ---
$field_query = 'COUNT(*) as count';
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $sql_conds;
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

// --- Fetch Data ---
$field_query = implode(',', $dbfield);
$sql_conds = (empty($sql_where)) ? '' : 'WHERE ' . $sql_where;
$default_query = "SELECT " . $field_query . " FROM " . $table_name . " " . $sql_conds . " ORDER BY " . $orderby;
$limit = " LIMIT " . $start_no . "," . $query_limit;
$sql_limit = $default_query . ' ' . $limit;




if ($query = call_mysql_query($sql_limit)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            // Format/secure data as needed
            $data = array_html($data);
            $data['date_from'] = formatterDateLong($data['date_from']);
            $data['date_to'] = formatterDateLong($data['date_to']);
            $data['school_year_id'] = (int)$data['school_year_id'];
            $data['isDefault'] = (int)$data['isDefault'];
            $data['flag_used'] = (int)$data['flag_used'] === 1 ? "Active" : "Locked";
            $data['flag_status'] = intVal($data['flag_used']);
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
echo $output; // output