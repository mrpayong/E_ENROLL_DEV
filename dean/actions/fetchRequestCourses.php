<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

header('Content-Type: application/json');

$session_class->session_close();

function dean_json_exit($payload) {
    echo json_encode($payload);
    exit();
}

function get_dean_department_context($db_connect, $general_id) {
    $department = null;
    $sql_dept = "
        SELECT d.department_id, d.department
        FROM departments d
        INNER JOIN users u ON d.user_id = u.user_id
        WHERE u.general_id = '" . escape($db_connect, $general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_dept)) {
        if ($data = call_mysql_fetch_array($query)) {
            $department = [
                'department_id' => intVal($data['department_id'] ?? 0),
                'department_name' => trim((string)($data['department'] ?? '')),
            ];
        }
    }

    return $department;
}

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if ($g_user_role !== "DEAN") {
        dean_json_exit([
            'last_page' => 1,
            'data' => [],
            'total_record' => 0,
            'msg_status' => false,
            'msg_response' => 'Unauthorized request.',
        ]);
    }

    $dean_department = get_dean_department_context($db_connect, trim((string)($g_general_id ?? '')));
    $dean_department_id = intVal($dean_department['department_id'] ?? 0);

    if ($dean_department_id <= 0) {
        dean_json_exit([
            'last_page' => 1,
            'data' => [],
            'total_record' => 0,
            'msg_status' => false,
            'msg_response' => 'Dean department assignment was not found.',
        ]);
    }

    $query_limit = QUERY_LIMIT;
    $school_year_id = isset($_GET['school_year_id']) ? intVal($_GET['school_year_id']) : 0;
    $table_name = 'backsubject_enroll AS bse';
    $dbfield = [
        'bse.offered_subject_id',
        'bse.school_year_id',
        'bse.sem',
        'bse.program_id',
        'bse.year_level',
        'bse.class_id',
        'bse.class_name',
        'bse.teacher_class_id',
        'bse.subject_id',
        'bse.course_code',
        'bse.course_title',
        'bse.schedule',
        'bse.section_limit',
        'bse.course_limit',
        'bse.dean_id',
        'bse.dean_name',
        'bse.status',
        'bse.createdAt',
        'bse.updatedAt',
        'p.short_name',
        'sy.school_year',
    ];

    $dborig = [
        'offered_subject_id',
        'course_code',
        'course_title',
        'class_name',
        'short_name',
        'year_level',
        'sem',
        'status',
        'dean_name',
        'school_year',
    ];

    $fieldMap = [
        'offered_subject_id' => 'bse.offered_subject_id',
        'course_code' => 'bse.course_code',
        'course_title' => 'bse.course_title',
        'class_name' => 'bse.class_name',
        'short_name' => 'p.short_name',
        'year_level' => 'bse.year_level',
        'sem' => 'bse.sem',
        'status' => 'bse.status',
        'dean_name' => 'bse.dean_name',
        'school_year' => 'sy.school_year',
    ];

    $left = "
        LEFT JOIN programs p ON bse.program_id = p.program_id
        LEFT JOIN school_year sy ON bse.school_year_id = sy.school_year_id
    ";

    $sql_where_array = [];
    $sql_where_array[] = "p.department_id = '" . escape($db_connect, $dean_department_id) . "'";
    if ($school_year_id > 0) {
        $sql_where_array[] = "bse.school_year_id = '" . escape($db_connect, $school_year_id) . "'";
    }

    if (isset($_GET['filters']) && is_array($_GET['filters'])) {
        $filters = $_GET['filters'];
        $sort_filters = [];

        foreach ($filters as $filter) {
            if (isset($filter['field'])) {
                $sort_filters[$filter['field']] = $filter['value'] ?? '';
            }
        }

        foreach ($dborig as $id) {
            if (!isset($sort_filters[$id]) || trim((string)$sort_filters[$id]) === '') {
                continue;
            }

            if (!isset($fieldMap[$id])) {
                continue;
            }

            $value = escape($db_connect, trim((string)$sort_filters[$id]));
            $sql_where_array[] = $fieldMap[$id] . " LIKE '%$value%'";
        }
    }

    $sql_where = '';
    if (!empty($sql_where_array)) {
        $sql_where = implode(' AND ', $sql_where_array);
    }

    $orderby = 'bse.createdAt DESC';
    if (isset($_GET['sorters']) && is_array($_GET['sorters']) && !empty($_GET['sorters'])) {
        $sorters = $_GET['sorters'];
        $tag = ['asc', 'desc'];
        $sort_field = $sorters[0]['field'] ?? '';
        $sort_dir = strtolower(trim((string)($sorters[0]['dir'] ?? '')));

        if (isset($fieldMap[$sort_field]) && in_array($sort_dir, $tag, true)) {
            $orderby = $fieldMap[$sort_field] . ' ' . $sort_dir;
        }
    }

    if (isset($_GET['size']) && is_numeric($_GET['size'])) {
        $query_limit = ($_GET['size'] > $query_limit) ? $query_limit : intVal($_GET['size']);
    }
    if ($query_limit <= 0) {
        $query_limit = 10;
    }

    $page_no = 0;
    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
        $page_no = max(0, intVal($_GET['page']) - 1);
    }
    $start_no = $page_no * $query_limit;

    $sql_conds = empty($sql_where) ? '' : "WHERE $sql_where";

    $count_query = "SELECT COUNT(*) AS count FROM $table_name $left $sql_conds";
    $total_query = 0;
    if ($query = call_mysql_query($count_query)) {
        if ($data = call_mysql_fetch_array($query)) {
            $total_query = intVal($data['count'] ?? 0);
        }
    }

    $pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);

    $field_query = implode(',', $dbfield);
    $data_query = "SELECT $field_query FROM $table_name $left $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

    $to_encode = [];
    if ($query = call_mysql_query($data_query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);

            $to_encode[] = [
                'offered_subject_id' => intVal($data['offered_subject_id'] ?? 0),
                'school_year_id' => intVal($data['school_year_id'] ?? 0),
                'school_year' => trim((string)($data['school_year'] ?? '')),
                'sem' => trim((string)($data['sem'] ?? '')),
                'program_id' => intVal($data['program_id'] ?? 0),
                'program_short_name' => trim((string)($data['short_name'] ?? '')),
                'year_level' => intVal($data['year_level'] ?? 0),
                'class_id' => intVal($data['class_id'] ?? 0),
                'class_name' => trim((string)($data['class_name'] ?? '')),
                'teacher_class_id' => intVal($data['teacher_class_id'] ?? 0),
                'subject_id' => intVal($data['subject_id'] ?? 0),
                'course_code' => trim((string)($data['course_code'] ?? '')),
                'course_title' => trim((string)($data['course_title'] ?? '')),
                'schedule' => $data['schedule'] ?? '',
                'section_limit' => intVal($data['section_limit'] ?? 0),
                'course_limit' => intVal($data['course_limit'] ?? 0),
                'dean_id' => trim((string)($data['dean_id'] ?? '')),
                'dean_name' => trim((string)($data['dean_name'] ?? '')),
                'status' => trim((string)($data['status'] ?? '')),
                'createdAt' => !empty($data['createdAt']) ? formatterDateLong($data['createdAt']) : '',
                'updatedAt' => !empty($data['updatedAt']) ? formatterDateLong($data['updatedAt']) : '',
            ];
        }
    }

    dean_json_exit([
        'last_page' => $pages,
        'data' => $to_encode,
        'total_record' => $total_query,
        'msg_status' => true,
        'msg_response' => 'Offered subject list loaded successfully.',
    ]);
} catch (Throwable $th) {
    dean_json_exit([
        'last_page' => 1,
        'data' => [],
        'total_record' => 0,
        'msg_status' => false,
        'msg_response' => 'Unable to load offered subject list.',
        'debug_message' => $th->getMessage(),
    ]);
}
?>
