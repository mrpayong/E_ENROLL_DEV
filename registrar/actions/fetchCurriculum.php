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
$table_name = "curriculum AS c";
$left_join = "
    LEFT JOIN programs AS p ON c.program_id = p.program_id
    LEFT JOIN school_year AS sy ON c.school_year_id = sy.school_year_id
    LEFT JOIN departments AS d ON c.department_id = d.department_id
";

$dbfield = [
    'c.curriculum_id',
    'c.curriculum_title',
    'c.status',
    'c.createdAt',
    'c.updatedAt',
    'p.program AS program_name',
    'p.program_id',
    'sy.sem AS sem_name',
    'sy.school_year_id',
    'd.department AS department_name',
    'd.department_id'
];

$dborig = [
    'curriculum_id',
    'curriculum_title',
    'status',
    'createdAt',
    'updatedAt',
    'program_name',
    'sem_name',
    'department_name'
];

// Filtering
$sql_where_array[] = "c.status = 0";
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
            } elseif ($id == 'sem_name') {
                $sql_where_array[] = "sy.sem LIKE '%$value%'";
            } elseif ($id == 'department_name') {
                $sql_where_array[] = "d.department LIKE '%$value%'";
            } elseif (in_array($id, ['curriculum_id', 'status'])) {
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
$orderby = "c.createdAt DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        if ($sort_field == 'program_name') {
            $orderby = "p.program $sort_dir";
        } elseif ($sort_field == 'sem_name') {
            $orderby = "sy.sem $sort_dir";
        } elseif ($sort_field == 'department_name') {
            $orderby = "d.department $sort_dir";
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
if (empty($sql_where)) {
    $sql_conds = "";
} else {
    $sql_conds = "WHERE $sql_where";
}
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
if (empty($sql_where)) {
    $sql_conds = "";
} else {
    $sql_conds = "WHERE $sql_where";
}
$data_query = "SELECT $field_query FROM $table_name $left_join $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['curriculum_id'] = (int)$data['curriculum_id'];
            $data['status'] = (int)$data['status'];
            $data['createdAt'] = isset($data['createdAt']) ? formatterDateLong($data['createdAt']) : "";
            $data['updatedAt'] = isset($data['updatedAt']) ? formatterDateLong($data['updatedAt']) : "";
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