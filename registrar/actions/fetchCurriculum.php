<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

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
$table_name = "curriculum_master AS c";
$left_join = "
    LEFT JOIN programs AS p ON c.program_id = p.program_id
";

$dbfield = [
    'c.curriculum_id',
    'c.curriculum_code',
    'c.program_id',
    'c.header',
    'c.units',
    'c.status_allowable',
    'c.date_created',
    'p.program AS program_name'
];

$dborig = [
    'curriculum_id',
    'curriculum_code',
    'program_id',
    'header',
    'units',
    'status_allowable',
    'date_created',
    'program_name'
];

// Filtering
$sql_where_array = [];
$sql_where_array[] = "1=1"; // Always true, so you can safely append ANDs
if (isset($_GET['filters'])) {
    $filters = $_GET['filters'];
    $sort_filters = [];
    foreach ($filters as $filter) {
        if (isset($filter['field'])) {
            $id = $filter['field'];
            $sort_filters[$id] = $filter['value'];
        }
    }
    foreach ($dborig as $id) {
        if (isset($sort_filters[$id])) {
            $value = escape($db_connect, $sort_filters[$id]);
            if ($id == 'program_name') {
                $sql_where_array[] = "p.program LIKE '%$value%'";
            } elseif (in_array($id, ['curriculum_id', 'program_id', 'units', 'status_allowable'])) {
                $sql_where_array[] = "c.$id = '$value'";
            } else {
                $sql_where_array[] = "c.$id LIKE '%$value%'";
            }
        }
    }
}
$sql_where = '';
if (!empty($sql_where_array)) {
    $sql_where = implode(' AND ', $sql_where_array);
}

// Sorting
$orderby = "c.date_created DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        if ($sort_field == 'program_name') {
            $orderby = "p.program $sort_dir";
        } else {
            $orderby = "c.$sort_field $sort_dir";
        }
    }
}

// Pagination
if (isset($_GET['size']) && is_numeric($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $query_limit : $_GET['size'];
}
$page_no = 0;
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page_no = max(0, $_GET['page'] - 1);
}
$start_no = $page_no * $query_limit;

// Count total records
$field_query = 'COUNT(*) as count';
$sql_conds = empty($sql_where) ? "" : "WHERE $sql_where";
$count_query = "SELECT $field_query FROM $table_name $left_join $sql_conds";
$total_query = 0;
if ($query = call_mysql_query($count_query)) {
    if ($num = call_mysql_num_rows($query)) {
        $data = call_mysql_fetch_array($query);
        $total_query = $data['count'];
    }
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);

// Fetch data
$field_query = implode(',', $dbfield);
$sql_conds = empty($sql_where) ? "" : "WHERE $sql_where";
$data_query = "SELECT $field_query FROM $table_name $left_join $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['curriculum_id'] = intVal($data['curriculum_id']);
            $data['program_id'] = intVal($data['program_id']);
            $data['units'] = intVal($data['units']);
            $data['status_allowable'] = intVal($data['status_allowable']);
            $data['date_created'] = isset($data['date_created']) ? formatterDateLong($data['date_created']) : "";
            $to_encode[] = $data;
        }
    }
}

echo json_encode([
    "last_page" => $pages,
    "data" => $to_encode,
    "total_record" => $total_query
]);
exit();