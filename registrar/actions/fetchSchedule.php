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

// --- Table Setup ---
$query_limit = QUERY_LIMIT;
$table_name = "teacher_class tc";
$dbfield = [
    'tc.teacher_class_id',
    'tc.teacher_id',
    'tc.class_id',
    'tc.subject_id',
    'tc.subject_text',
    'tc.schedule',
    'tc.sem',
    'tc.date_added',
    'tc.program_id',
    'tc.year_level',
    'tc.unit',
    'tc.lec_lab',
    'tc.section_limit',
    'tc.status',
    'tc.schoolyear_id',
    'tc.total_hours',
    // Joined fields:
    "CONCAT(u.f_name, ' ', LEFT(u.m_name, 1), IF(u.m_name != '', '.', ''), ' ', u.l_name) AS instructor_name", // users table
    "cs.class_name", // class_section table
    "s.subject_code", // subjects table
    "p.short_name", // programs table
    "sy.school_year"
];

// --- LEFT JOINs ---
$left = "
    LEFT JOIN users u ON tc.teacher_id = u.user_id
    LEFT JOIN school_year sy ON tc.schoolyear_id = sy.school_year_id
    LEFT JOIN class_section cs ON tc.class_id = cs.class_id
    LEFT JOIN subject s ON tc.subject_id = s.subject_id
    LEFT JOIN programs p ON tc.program_id = p.program_id
";

$alias_map = [
    'instructor_name' => "CONCAT(u.f_name, ' ', LEFT(u.m_name, 1), IF(u.m_name != '', '.', ''), ' ', u.l_name)",
    'class_name'      => 'cs.class_name',
    'subject_code'    => 's.subject_code',
    'subject_text'   => 'tc.subject_text',
    'program'         => 'p.short_name',
    'school_year'     => 'sy.school_year'
];

// --- Filtering ---
$sql_where_array = [];
if (isset($_GET['filters'])) {
    $filters = $_GET['filters'];
    if (is_string($filters)) $filters = json_decode($filters, true); // handle JSON string
    foreach ($filters as $filter) {
        if (isset($filter['field']) && isset($filter['value']) && $filter['value'] !== '') {
            $id = $filter['field'];
            $value = escape($db_connect, $filter['value']);
            if ($id == "sy_id") {
                $sql_where_array[] = "tc.schoolyear_id = '$value'";
            }
            if (isset($alias_map[$id])) {
                $sql_where_array[] = $alias_map[$id] . " LIKE '%$value%'";
            } else {
                // fallback: try direct field
                $sql_where_array[] = "$id LIKE '%$value%'";
            }
        }
    }
}
$sql_where_array[] = "tc.status = 0";
$sql_where = implode(' AND ', $sql_where_array);

// --- Sorting ---
$orderby = "tc.date_added DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    if (is_string($sorters)) $sorters = json_decode($sorters, true); // handle JSON string
    $tag = ['asc', 'desc'];
    if (!empty($sorters)) {
        $sort_field = $sorters[0]['field'];
        $sort_dir = strtolower($sorters[0]['dir']);
        if (isset($alias_map[$sort_field]) && in_array($sort_dir, $tag)) {
            $orderby = $alias_map[$sort_field] . " " . strtoupper($sort_dir);
        } else if (in_array($sort_field, $dbfield) && in_array($sort_dir, $tag)) {
            $orderby = "$sort_field " . strtoupper($sort_dir);
        }
    }
}

// --- Pagination ---
if (isset($_GET['size']) && is_numeric($_GET['size'])) {
    $query_limit = ($_GET['size'] > $query_limit) ? $query_limit : $_GET['size'];
}
$page_no = 0;
if (isset($_GET['page']) && is_numeric($_GET['page'])) {
    $page_no = max(0, $_GET['page'] - 1);
}
$start_no = $page_no * $query_limit;

// --- Count total records ---
$field_query = 'COUNT(*) as count';
$sql_conds = empty($sql_where) ? "" : "WHERE $sql_where";
$count_query = "SELECT $field_query FROM $table_name $left $sql_conds";
$total_query = 0;
if ($query = call_mysql_query($count_query)) {
    if ($num = call_mysql_num_rows($query)) {
        $data = call_mysql_fetch_array($query);
        $total_query = $data['count'];
    }
}
$pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);

// --- Fetch Data ---
$field_query = implode(',', $dbfield);
$sql_conds = empty($sql_where) ? "" : "WHERE $sql_where";
$data_query = "SELECT $field_query FROM $table_name $left $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

function commanize($item) {
    return str_replace('::', ',', $item);
}
$to_encode = [];
$long_sched = '';
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);

            // Map fields to match Tabulator columns (adjust as needed)
            $lec_lab = json_decode(html_entity_decode($data['lec_lab']));
            $lec = $lec_lab["0"];
            $lab = $lec_lab["1"];
            $data['schedule'] = json_decode(html_entity_decode($data['schedule']));
            if(is_array($data['schedule'])){  
                $schedule_commanize = array_map('commanize', $data['schedule']);
                $long_sched = implode('| <br>', $schedule_commanize);
            } else {
                $long_sched = str_replace('::', ',', $data['schedule']);
            }
            $data['status'] = intVal($data['status']);
            $arr_sched = is_array($data['schedule']) ? $data['schedule'] : (array)$data['schedule']; // raw schedule array

            $row = [
                "teacher_class_id" => $data['teacher_class_id'],
                "teacher_id"       => $data['teacher_id'],
                "instructor_name"  => $data['instructor_name'], // from users table
                "class_id"         => $data['class_id'],
                "class_name"       => $data['class_name'], // from class_section table
                "subject_id"       => $data['subject_id'],
                "subject_code"     => $data['subject_code'], // from subjects table
                "subject_text"     => $data['subject_text'],
                "program_id"       => $data['program_id'],
                "program"          => $data['short_name'], // from programs table
                "schedule"         => $long_sched,
                "sem"              => $data['sem'],
                "lec"              => $lec,
                "lab"              => $lab,
                "section_limit"    => $data['section_limit'],
                "school_year"      => $data['school_year'],
                "date_added"       => $data['date_added'],
                "year_level"       => $data['year_level'],
                "unit"             => $data['unit'],
                "status"           => $data['status'],
                "schedule_array"   => $arr_sched, // raw schedule array
                'sy_id'            => $data['schoolyear_id'],
                'hours'            => $data['total_hours'],
            ];

            $row['actions'] = ''; // Placeholder for action buttons

            $to_encode[] = $row;
        }
    } 
}

echo json_encode([
    "last_page" => $pages,
    "data" => $to_encode,
    "total_record" => $total_query
]);
exit();