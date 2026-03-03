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
$table_name = "programs p LEFT JOIN departments d ON p.department_id = d.department_id";
$dbfield = [
    'program_id', 'p.department_id', 'program', 'short_name', 'major', 'p.status', 'archiveStatus', 'duration', 'd.department'
];
$dborig = [
    'program_id', 'department_id', 'program', 'short_name', 'major', 'status', 'archiveStatus', 'duration', 'department'
];

// Filtering
$sql_where_array = [];
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
            if ($id == 'department') {
                $sql_where_array[] = "d.department LIKE '%$value%'";
            } elseif ($id == 'status' || $id == 'archiveStatus' || $id == 'duration' || $id == 'department_id') {
                $sql_where_array[] = "p.$id = '$value'";
            } else {
                $sql_where_array[] = "p.$id LIKE '%$value%'";
            }
        }
    }
}
$sql_where = '';
if (!empty($sql_where_array)) {
    $sql_where = implode(' AND ', $sql_where_array);
}

// Sorting
$orderby = "program_id DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        if ($sort_field == 'department') {
            $orderby = "d.department $sort_dir";
        } else {
            $orderby = "p.$sort_field $sort_dir";
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
if (empty($sql_where)) {
    $sql_conds = "WHERE p.archiveStatus = 1";
} else {
    $sql_conds = "WHERE p.archiveStatus = 1 AND $sql_where";
}
$count_query = "SELECT $field_query FROM programs p LEFT JOIN departments d ON p.department_id = d.department_id $sql_conds";
$total_query = 0;
if ($query = call_mysql_query($count_query)) {
    if ($num = call_mysql_num_rows($query)) {
        $data = call_mysql_fetch_array($query);
        $total_query = $data['count'];
    }
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);

// Fetch data
$field_query = [];
foreach ($dbfield as $field) {
    $field_query[] = $field;
}
$field_query = implode(',', $field_query);
if (empty($sql_where)) {
    $sql_conds = "WHERE p.archiveStatus = 1";
} else {
    $sql_conds = "WHERE p.archiveStatus = 1 AND $sql_where";
}
$data_query = "SELECT $field_query FROM programs p LEFT JOIN departments d ON p.department_id = d.department_id $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['program_id'] = (int)$data['program_id'];
            $data['department_id'] = (int)$data['department_id'];
            $data['status'] = (int)$data['status'];
            $data['archiveStatus'] = (int)$data['archiveStatus'];
            $data['duration'] = (int)$data['duration'];
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
?>