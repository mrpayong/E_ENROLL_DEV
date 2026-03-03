<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

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
$dbfields = [
    'd.department_id',
    'd.department',
    'd.code_name',
    'd.status',
    'd.user_id',
    'u.f_name',
    'u.m_name',
    'u.suffix',
    'u.l_name',
    'p.program_id',
    'p.program',
    'p.major',
    'p.short_name'
];

// Filtering
$sql_where_array = [];
$dborig = [
    'department_id', 'department', 'code_name', 'status', 'user_id',
    'f_name', 'm_name', 'suffix', 'l_name', 'program_id', 'program'
];
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
            $sql_where_array[] = "$id LIKE '%$value%'";
        }
    }
}
$sql_where = !empty($sql_where_array) ? implode(' AND ', $sql_where_array) : "";

// Sorting
$orderby = "d.department_id DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    if (in_array($sorters[0]['field'], $dborig) && in_array($sorters[0]['dir'], $tag)) {
        $field = $sorters[0]['field'];
        if (in_array($field, ['program_id', 'program'])) {
            $orderby = "p.$field " . $sorters[0]['dir'];
        } elseif (in_array($field, ['f_name', 'm_name', 'suffix', 'l_name'])) {
            $orderby = "u.$field " . $sorters[0]['dir'];
        } else {
            $orderby = "d.$field " . $sorters[0]['dir'];
        }
    }
}

// Pagination
if (isset($_GET['size']) && is_digit($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $_GET['size'] : $query_limit;
}

// Count total records (count distinct departments)
$count_query = "SELECT COUNT(DISTINCT d.department_id) as count
    FROM departments d
    LEFT JOIN users u ON d.user_id = u.user_id
    LEFT JOIN programs p ON d.department_id = p.department_id
    WHERE d.status = 1" . ($sql_where ? " AND $sql_where" : "");
$total_query = 0;
if ($query = call_mysql_query($count_query)) {
    if ($num = call_mysql_num_rows($query)) {
        $data = call_mysql_fetch_array($query);
        $total_query = $data['count'];
    }
    mysqli_free_result($query);
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
$start = 0;
if (isset($_GET['page']) && is_digit($_GET['page'])) {
    $page_no = $_GET['page'] - 1;
    $start = $page_no * $query_limit;
}
$start_no = ($start >= $total_query) ? $total_query : $start;

// Fetch flat data: one row per program, department info repeated
$field_query = implode(',', $dbfields);
$table_join = "departments d
    LEFT JOIN users u ON d.user_id = u.user_id
    LEFT JOIN programs p ON d.department_id = p.department_id";
$sql_conds = "WHERE d.status = 1" . ($sql_where ? " AND $sql_where" : "");
$data_query = "SELECT $field_query FROM $table_join $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        // $F_data = call_mysql_fetch_array($query);
        // echo "data: ";
        // var_dump(json_decode($F_data['major']));      

        while ($data = call_mysql_fetch_array($query)) {


            $data = array_html($data);
            $data['department_id'] = (int)$data['department_id'];
            $data['status'] = (int)$data['status'];
            $data['program_id'] = isset($data['program_id']) ? (int)$data['program_id'] : null;

            $data['major'] = isset($data['major']) && $data['major'] !== ''
                ? json_decode(html_entity_decode($data['major']))
                : [];
            
            
            $dean_name = trim(
                ($data['f_name']) .
                (isset($data['m_name']) && $data['m_name'] !== ''
                    ? ' ' . $data['m_name'] . " "
                    : ""
                ) .
                ($data['l_name']) .
                (isset($data['suffix']) && $data['suffix'] !== ''
                    ? " " . $data['suffix'] . " "
                    : ""
                )
            );
            $data['dean'] = $dean_name;

            if(is_array($data['major']) && count($data['major']) > 0){
                foreach($data['major'] as $specialize){
                    $row = $data;
                    $row['major'] = $specialize;
                    $to_encode[] = $row;
                }
            } else {
                $data['major'] = '';
                $to_encode[] = $data;
            }
        }
    }
    mysqli_free_result($query);
}

echo json_encode([
    "last_page" => $pages,
    "data" => $to_encode,
    "total_record" => $total_query
]);
exit();
?>