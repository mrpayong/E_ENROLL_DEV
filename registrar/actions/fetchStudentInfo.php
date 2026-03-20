<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

$session_class->session_close();
try {
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
$table_name = "users AS u ";
$dbfield = [
    //from users table
    'u.user_id', 'u.general_id', 'u.img', 'u.f_name', 'u.m_name', 'u.l_name', 'u.suffix', 'u.sex', 'u.birth_date',
    'u.user_role', 'u.username', 'u.email_address', 'u.position', 'u.status', 'u.locked', 'u.last_signin',

    //from student table
    's.student_id', 's.student_id_no', 's.contact', 's.barangay', 's.address', 's.year_level', 's.major', 's.ccc_email',
    's.program_id', 's.curriculum_id', 's.emergency_data', 's.additional_data','s.department_id',

    'd.department'
];
$dborig = [
    'user_id', 'general_id', 'img', 'f_name', 'm_name', 'l_name', 'suffix', 'sex', 'birth_date',
    'user_role', 'username', 'email_address', 'position', 'program', 'status', 'locked', 'last_signin'
];

$left_join = "LEFT JOIN student AS s ON u.general_id = s.student_id_no LEFT JOIN departments AS d ON s.department_id = d.department_id";

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
            if ($id == 'user_role') {
                $sql_where_array[] = "JSON_CONTAINS(user_role, '\"2\"')";
            } elseif (in_array($id, ['user_id', 'status', 'locked'])) {
                $sql_where_array[] = "$id = '$value'";
            } else {
                $sql_where_array[] = "$id LIKE '%$value%'";
            }
        }
    }
}
$sql_where = '';
if (!empty($sql_where_array)) {
    $sql_where = implode(' AND ', $sql_where_array);
}

// Sorting
$orderby = "user_id DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        $orderby = "$sort_field $sort_dir";
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
    $sql_conds = "WHERE JSON_CONTAINS(user_role, '\"5\"')";
} else {
    $sql_conds = "WHERE JSON_CONTAINS(user_role, '\"5\"') AND $sql_where";
}
$count_query = "SELECT $field_query FROM $table_name  $left_join  $sql_conds";
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
    $sql_conds = "WHERE JSON_CONTAINS(user_role, '\"5\"')";
} else {
    $sql_conds = "WHERE JSON_CONTAINS(user_role, '\"5\"') AND $sql_where";
}
$data_query = "SELECT $field_query FROM $table_name  $left_join  $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['user_id'] = (int)$data['user_id'];
            $data['status'] = (int)$data['status'];
            $data['locked'] = (int)$data['locked'];
            $data['student_id'] = (int)$data['student_id'];
            $data['name'] = get_full_name($data['f_name'], $data['m_name'], $data['l_name'], $data['suffix']);
            $data['birth_date'] = isset($data['birth_date']) ? formatterDateLong($data['birth_date']) : "";
            $data['emergency_data'] = isset($data['emergency_data']) ? $data['emergency_data'] : "";

            $user_roles = [];
            $role_str = html_entity_decode($data['user_role']); // decode &quot; to "
            $role_arr = json_decode($role_str, true);
            if (is_array($role_arr)) {
                foreach ($role_arr as $role) {
                    if (isset(SYSTEM_ACCESS['E-ENROLL']['role'][$role])) {
                        $user_roles[] = SYSTEM_ACCESS['E-ENROLL']['role'][$role];
                    }
                }
            }
            $data['user_role'] = !empty($user_roles) ? implode(', ', $user_roles) : $role_str;

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
} catch (Throwable $th) {
echo json_encode([
    "last_page" => 0,
    "data" => [],
    "message" => $th->getMessage(), //uncomment on dev mode
    // "message" => "An error occured, no data available.", //uncomment on prod mode
    "total_record" => 0
]);
}
?>