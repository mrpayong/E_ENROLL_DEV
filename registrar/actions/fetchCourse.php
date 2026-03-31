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
$table_name = "subject as S";

$dbfield = [
    's.subject_id', 's.subject_code', 's.subject_title',
    's.unit', 's.status', 's.date_modified', 's.lec_lab', 's.flag_manual_enroll', 's.limit'
];
$dborig = [
    'subject_id', 'subject_code', 'subject_title',
    'unit', 'status', 'date_modified', 'limit'
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
            if ($id == 'program') {
                $sql_where_array[] = "p.program LIKE '%$value%'";
            } elseif ($id == 'sem') {
                $sql_where_array[] = "sy.sem LIKE '%$value%'";
            } elseif (in_array($id, ['program_id', 'school_year_id', 'unit', 'status', 'excepted'])) {
                $sql_where_array[] = "s.$id = '$value'";
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
$orderby = "s.date_modified DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        if ($sort_field == 'program') {
            $orderby = "p.program $sort_dir";
        } elseif ($sort_field == 'sem') {
            $orderby = "sy.sem $sort_dir";
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
if (empty($sql_where)) {
    $sql_conds = "WHERE s.status = 0";
} else {
    $sql_conds = "WHERE s.status = 0 AND $sql_where";
}
$count_query = "SELECT $field_query FROM $table_name $sql_conds";
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
    $sql_conds = "WHERE s.status = 0";
} else {
    $sql_conds = "WHERE s.status = 0 AND $sql_where";
}
$data_query = "SELECT $field_query FROM $table_name $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

$to_encode = [];
$lec = 0;
$lab = 1;
$finalSubjects = '';    
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $data['subject_id'] = isset($data['subject_id']) ? intVal($data['subject_id']) : 0;
            $data['limit'] = isset($data['limit']) ? intVal($data['limit']) : 0;

            $lec_labArr = json_decode(html_entity_decode($data['lec_lab']));
            $data['lec'] = $lec_labArr[$lec];
            $data['lab'] = $lec_labArr[$lab]; 

            $data['unit'] = (int)$data['unit'];
            $data['flag_manual_enroll'] = intVal($data['flag_manual_enroll']) === 1 ? true : false; 

            $data['status'] = (int)$data['status'];
            $data['date_modified'] = isset($data['date_modified']) ? formatterDateLong($data['date_modified']) : "";
            $to_encode[] = $data;
        }

        $manualEnrollMap = [];
        $regularSubjects = [];

        foreach ($to_encode as $subject) {
            if ($subject['flag_manual_enroll']) {
                // Group by subject_code
                $code = $subject['subject_code'];
                if (!isset($manualEnrollMap[$code])) {
                    $manualEnrollMap[$code] = [];
                }
                $manualEnrollMap[$code][] = $subject;
            } else {
                $regularSubjects[] = $subject;
            }
        }

        // Flatten manual enroll groups for consecutive rows
        $manualEnrollSubjects = [];
        foreach ($manualEnrollMap as $code => $subjects) {
            foreach ($subjects as $subject) {
                $manualEnrollSubjects[] = $subject;
            }
        }

        // Merge manual enroll subjects first (for consecutive rows), then regular subjects
        $finalSubjects = array_merge($manualEnrollSubjects, $regularSubjects);
    }
}

echo json_encode([
    "last_page" => $pages,
    "data" => $finalSubjects,
    "total_record" => $total_query,
]);
exit();
?>