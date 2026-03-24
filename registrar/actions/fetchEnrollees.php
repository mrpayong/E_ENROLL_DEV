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
    $table_name = "student AS s";

    $dbfield = [
        // student fields
        's.student_id', 's.student_id_no', 's.year_level',
        's.major', 's.program_id', 's.curriculum_id', 's.class_id',

        // user fields (for name/info)
        'u.f_name', 'u.m_name', 'u.l_name', 'u.suffix',
        'u.status', 'u.locked',

        // lookup fields
        'p.program', 'p.short_name', 

        // curriculum
        'c.curriculum_code',

        // section
        'sc.class_name'
    ];

    $dborig = [
        'student_id', 'year_level', 'major', 'program_id', 'curriculum_id',
        'status', 'locked', 'f_name', 'm_name', 'l_name', 
        'program'
    ];

    $left_join = "LEFT JOIN users AS u ON u.general_id = s.student_id_no " .
                 "LEFT JOIN programs AS p ON s.program_id = p.program_id ".
                 "LEFT JOIN curriculum_master AS c ON s.curriculum_id = c.curriculum_id ".
                 "LEFT JOIN class_section AS sc ON s.class_id = sc.class_id ";

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

                if ($id === 'program') {
                    $sql_where_array[] = "p.program LIKE '%$value%'";
                } elseif ($id === 'department') {
                    $sql_where_array[] = "d.department LIKE '%$value%'";
                } elseif (in_array($id, ['user_id', 'status', 'locked'])) {
                    $sql_where_array[] = "u.$id = '$value'";
                } elseif (in_array($id, ['student_id', 'program_id', 'curriculum_id', 'department_id', 'year_level'])) {
                    $sql_where_array[] = "s.$id = '$value'";
                } elseif (in_array($id, ['f_name', 'm_name', 'l_name', 'username', 'email_address'])) {
                    $sql_where_array[] = "u.$id LIKE '%$value%'";
                } else {
                    $sql_where_array[] = "s.$id LIKE '%$value%'";
                }
            }
        }
    }
    $sql_where = '';
    if (!empty($sql_where_array)) {
        $sql_where = implode(' AND ', $sql_where_array);
    }

    // Sorting
    $orderby = "s.student_id DESC";
    if (isset($_GET['sorters'])) {
        $sorters = $_GET['sorters'];
        $tag = ['asc', 'desc'];
        $sort_field = $sorters[0]['field'];
        $sort_dir = $sorters[0]['dir'];

        if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
            if ($sort_field === 'program') {
                $orderby = "p.program $sort_dir";
            } elseif ($sort_field === 'department') {
                $orderby = "d.department $sort_dir";
            } elseif (in_array($sort_field, ['user_id', 'status', 'locked', 'f_name', 'm_name', 'l_name', 'username', 'email_address'])) {
                $orderby = "u.$sort_field $sort_dir";
            } else {
                $orderby = "s.$sort_field $sort_dir";
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
    $sql_conds = empty($sql_where) ? '' : "WHERE $sql_where";
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
    $data_query = "SELECT $field_query FROM $table_name $left_join $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

    $to_encode = [];
    if ($query = call_mysql_query($data_query)) {
        if ($num = call_mysql_num_rows($query)) {
            while ($data = call_mysql_fetch_array($query)) {
                $data = array_html($data);

                $data['program_id'] = isset($data['program_id']) ? intVal($data['program_id']) : '';
                $data['student_id'] = isset($data['student_id']) ? intVal($data['student_id']) : '';
                $data['curriculum_id'] = isset($data['curriculum_id']) ? intVal($data['curriculum_id']) : '';
                $data['year_level'] = isset($data['year_level']) ? intVal($data['year_level']) : '';
                $data['status'] = isset($data['status']) ? intVal($data['status']) : '';
                $data['locked'] = isset($data['locked']) ? intVal($data['locked']) : '';
                $data['program'] = isset($data['program']) ? $data['short_name'].' ~ '.$data['major'] : "No program";
                $data['section'] = isset($data['class_name']) ? $data['class_name'] : "No section";

                $data['name'] = get_full_name($data['f_name'], $data['m_name'], $data['l_name'], $data['suffix']);

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
        "message" => $th->getMessage(),
        "total_record" => 0
    ]);
    exit();
}
?>
