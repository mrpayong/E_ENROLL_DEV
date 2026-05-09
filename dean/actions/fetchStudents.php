<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;
require DOMAIN_PATH . '/dean/process/dean_backlog_helper.php';

header('Content-Type: application/json');

$session_class->session_close();

function dean_students_json_exit($payload) {
    echo json_encode($payload);
    exit();
}

function dean_students_department_context($db_connect, $general_id) {
    $sql = "
        SELECT d.department_id, d.department
        FROM departments d
        INNER JOIN users u ON d.user_id = u.user_id
        WHERE u.general_id = '" . escape($db_connect, $general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql)) {
        if ($data = call_mysql_fetch_array($query)) {
            return [
                'department_id' => intVal($data['department_id'] ?? 0),
                'department_name' => trim((string)($data['department'] ?? '')),
            ];
        }
    }

    return [
        'department_id' => 0,
        'department_name' => '',
    ];
}

function dean_students_format_name($lastname, $firstname, $middle_name) {
    $middle_initial = '';
    $middle_name = trim((string)$middle_name);
    if ($middle_name !== '') {
        $middle_initial = ' ' . strtoupper(substr($middle_name, 0, 1)) . '.';
    }

    return strtoupper(trim((string)$lastname) . ', ' . trim((string)$firstname) . $middle_initial);
}

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if (!in_array($g_user_role, ['DEAN', 'ADMIN'], true)) {
        dean_students_json_exit([
            'last_page' => 1,
            'data' => [],
            'total_record' => 0,
            'msg_status' => false,
            'msg_response' => 'Unauthorized request.',
        ]);
    }

    $query_limit = QUERY_LIMIT;
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

    $current_school_year = get_school_year();
    $current_sem = trim((string)($current_school_year['sem'] ?? ''));

    $table_name = 'student AS s';
    $dbfield = [
        's.student_id',
        's.student_id_no',
        's.firstname',
        's.middle_name',
        's.lastname',
        's.year_level',
        's.status AS student_status',
        's.program_id',
        'p.program',
        'p.short_name',
        'd.department',
    ];

    $fieldMap = [
        'student_id' => 's.student_id_no',
        'student_name' => "CONCAT(s.lastname, ', ', s.firstname, ' ', s.middle_name)",
        'program' => "COALESCE(NULLIF(p.short_name, ''), p.program)",
        'year_level' => 's.year_level',
        'department' => 'd.department',
    ];

    $left = "
        LEFT JOIN programs p ON s.program_id = p.program_id
        LEFT JOIN departments d ON p.department_id = d.department_id
    ";

    $sql_where_array = [];
    if ($g_user_role === 'DEAN') {
        $dean_department = dean_students_department_context($db_connect, trim((string)($g_general_id ?? '')));
        $dean_department_id = intVal($dean_department['department_id'] ?? 0);

        if ($dean_department_id <= 0) {
            dean_students_json_exit([
                'last_page' => 1,
                'data' => [],
                'total_record' => 0,
                'msg_status' => false,
                'msg_response' => 'Dean department assignment was not found.',
            ]);
        }

        $sql_where_array[] = "p.department_id = '" . escape($db_connect, $dean_department_id) . "'";
    }

    $status_filter = '';
    if (isset($_GET['filters']) && is_array($_GET['filters'])) {
        $sort_filters = [];
        foreach ($_GET['filters'] as $filter) {
            if (isset($filter['field'])) {
                $sort_filters[$filter['field']] = $filter['value'] ?? '';
            }
        }

        foreach ($sort_filters as $field => $raw_value) {
            $value = trim((string)$raw_value);
            if ($value === '') {
                continue;
            }

            if ($field === 'status') {
                $status_filter = strtoupper($value);
                continue;
            }

            if (!isset($fieldMap[$field])) {
                continue;
            }

            $escaped_value = escape($db_connect, $value);
            $sql_where_array[] = $fieldMap[$field] . " LIKE '%$escaped_value%'";
        }
    }

    $sql_where = empty($sql_where_array) ? '' : 'WHERE ' . implode(' AND ', $sql_where_array);

    $orderby = 's.lastname ASC, s.firstname ASC';
    $sort_by_status = false;
    $sort_status_dir = 'asc';
    if (isset($_GET['sorters']) && is_array($_GET['sorters']) && !empty($_GET['sorters'])) {
        $sort_field = $_GET['sorters'][0]['field'] ?? '';
        $sort_dir = strtolower(trim((string)($_GET['sorters'][0]['dir'] ?? '')));
        $allowed_dir = ['asc', 'desc'];

        if ($sort_field === 'status' && in_array($sort_dir, $allowed_dir, true)) {
            $sort_by_status = true;
            $sort_status_dir = $sort_dir;
        } elseif (isset($fieldMap[$sort_field]) && in_array($sort_dir, $allowed_dir, true)) {
            $orderby = $fieldMap[$sort_field] . ' ' . $sort_dir;
        }
    }

    $field_query = implode(',', $dbfield);
    $data_query = "SELECT $field_query FROM $table_name $left $sql_where ORDER BY $orderby";

    $rows = [];
    if ($query = call_mysql_query($data_query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $student_id_no = trim((string)($data['student_id_no'] ?? ''));
            $program_id = intVal($data['program_id'] ?? 0);
            $year_level = intVal($data['year_level'] ?? 0);
            $academic_status = 'Irregular';

            if ($student_id_no !== '' && $program_id > 0 && $year_level > 0) {
                $academic_status = dean_determine_academic_status_from_curriculum(
                    $db_connect,
                    $student_id_no,
                    $program_id,
                    $year_level,
                    $current_sem
                );
            }

            if ($status_filter !== '' && strpos(strtoupper($academic_status), $status_filter) === false) {
                continue;
            }

            $safe = array_html($data);
            $rows[] = [
                'student_id' => $student_id_no,
                'student_name' => dean_students_format_name($safe['lastname'] ?? '', $safe['firstname'] ?? '', $safe['middle_name'] ?? ''),
                'program' => trim((string)($safe['short_name'] ?? '')) !== '' ? trim((string)$safe['short_name']) : trim((string)($safe['program'] ?? '')),
                'year_level' => $year_level,
                'status' => $academic_status,
                'academic_status' => $academic_status,
                'department' => trim((string)($safe['department'] ?? '')),
                'program_id' => $program_id,
                'student_status' => trim((string)($safe['student_status'] ?? '')),
            ];
        }
    }

    if ($sort_by_status) {
        usort($rows, function($a, $b) use ($sort_status_dir) {
            $compare = strcmp(strtoupper($a['status'] ?? ''), strtoupper($b['status'] ?? ''));
            return $sort_status_dir === 'desc' ? -$compare : $compare;
        });
    }

    $total_query = count($rows);
    $pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
    $to_encode = array_slice($rows, $start_no, $query_limit);

    dean_students_json_exit([
        'last_page' => $pages,
        'data' => $to_encode,
        'total_record' => $total_query,
        'msg_status' => true,
        'msg_response' => 'Students loaded successfully.',
    ]);
} catch (Throwable $th) {
    dean_students_json_exit([
        'last_page' => 1,
        'data' => [],
        'total_record' => 0,
        'msg_status' => false,
        'msg_response' => 'Unable to load students.',
        'debug_message' => $th->getMessage(),
    ]);
}
?>
