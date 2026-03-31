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

if ($g_user_role !== "DEAN") {
    include HTTP_401;
    echo "Unavailable Data.";
    exit();
}

// --- Table Setup ---
$query_limit = QUERY_LIMIT;
$table_name = "class_section AS cs";
$dbfield = [
    'cs.class_id',
    'cs.class_name',
    'cs.date_modified',
    'cs.sem_limit',
    'p.program_id',
    'p.short_name',
    'year_level'
];
$dborig = [
    'class_id',
    'class_name',
    'sem_limit',
    'short_name',
    'date_modified',
    'year_level'
];

$left = "
    LEFT JOIN programs p ON cs.program_id = p.program_id
";

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
            $sql_where_array[] = "$id LIKE '%$value%'";
        }
    }
}
$sql_where = '';
if (!empty($sql_where_array)) {
    $sql_where = implode(' AND ', $sql_where_array);
}

// --- Sorting ---
$orderby = "cs.date_modified DESC";
if (isset($_GET['sorters'])) {
    $sorters = $_GET['sorters'];
    $tag = ['asc', 'desc'];
    $sort_field = $sorters[0]['field'];
    $sort_dir = $sorters[0]['dir'];
    if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
        $orderby = "$sort_field $sort_dir";
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

$school_year_id = isset($_GET['school_year_id']) ? strVal($_GET['school_year_id']) : null;
$assoc_sy_id = '';
$to_encode = [];
$logic = "";
if ($query = call_mysql_query($data_query)) {
    if ($num = call_mysql_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $is_default = false;
            $data = array_html($data);
            $data['class_id'] = intVal($data['class_id']);
            $data['short_name'] = isset($data['short_name']) ? $data['short_name'] : null;
            $data['program_id'] = isset($data['program_id']) ? intVal($data['program_id']) : null;
            $data['date_modified'] = isset($data['date_modified']) ? formatterDateLong($data['date_modified']) : null;

            $sem_limits = json_decode(html_entity_decode($data['sem_limit']),true);

            // checks if $school_year_id exists as key in the sem_limits array
            // before assigngin value to variable
            if (array_key_exists($school_year_id, $sem_limits)) {
                $limit_value = $sem_limits[$school_year_id];
                $assoc_sy_id = strVal(array_keys($sem_limits, $limit_value)[0]);

                if($assoc_sy_id === "0"){
                    $is_default = true;
                }
            } 

            // 2. If not found, try to get the default value (key "0")
            elseif (!(array_key_exists($school_year_id, $sem_limits))) {
                if(array_key_exists("0", $sem_limits)){
                    $is_default = true;
                    $limit_value = $sem_limits["0"];
                    $assoc_sy_id = strVal(array_keys($sem_limits, $limit_value)[0]);                    
                }
                // if there is no "0" in the array, this is for sections with no default value with key "0"
                // these are section made only for specific school year/sem
                elseif(!(array_key_exists("0", $sem_limits))){
                    $limit_value = null;
                    $assoc_sy_id = null;  
                } 
            }


            if ($school_year_id !== null && $limit_value !== null) {
                $to_encode[] = [
                    'class_id' => $data['class_id'],
                    'class_name' => $data['class_name'],
                    'short_name' => $data['short_name'],
                    'program_id' => $data['program_id'],
                    'school_year_id' => $assoc_sy_id,
                    'sem_limit' => $limit_value,
                    'is_default' => $is_default,
                    'date_modified' => $data['date_modified'],
                    'year_level' => intVal($data['year_level'])
                ];
            }
            
        }
    }
}

echo json_encode([
    "last_page" => $pages,
    "data" => $to_encode,
    "total_record" => $total_query
]);
exit();