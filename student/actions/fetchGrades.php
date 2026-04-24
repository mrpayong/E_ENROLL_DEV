<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if ($g_user_role !== "STUDENT") {
        include HTTP_401;
        echo "Unavailable Data.";
        exit();
    }

    $output = array(
        'code' => 0,
        'msg_status' => false,
        'msg_response' => "Request failed",
        'msg_span' => "_system"
    );

    $query_limit = QUERY_LIMIT;
    $table_name = "final_grade AS fg";

    $dbfield = [
        'fg.final_id',
        'fg.student_name',
        'fg.student_id_text',
        'fg.subject_code',
        'fg.course_desc',
        'fg.units',
        'fg.final_grade',
        'fg.converted_grade',
        'fg.completion',
        'fg.remarks',
        'fg.school_year_id',
        'fg.school_year',
        'fg.sem',
        'fg.yr_level',
        'fg.date_added',
        'fg.date_updated'
    ];

    $dborig = [
        'subject_code',
        'course_desc',
        'units',
        'converted_grade',
        'completion',
        'remarks',
        'school_year',
        'sem',
        'yr_level'
    ];

    $student_id_for_query = escape($db_connect, $g_general_id);

    /* Filtering */
    $sql_where_array = [];
    $sql_where_array[] = "fg.student_id_text = '$student_id_for_query'";

    if (isset($_GET['filters'])) {
        $filters = $_GET['filters'];
        $sort_filters = [];

        foreach ($filters as $filter) {
            if (isset($filter['field'])) {
                $sort_filters[$filter['field']] = $filter['value'];
            }
        }

        foreach ($dborig as $id) {
            if (isset($sort_filters[$id])) {
                $value = escape($db_connect, $sort_filters[$id]);

                if (in_array($id, ['units', 'school_year_id'])) {
                    $sql_where_array[] = "fg.$id = '$value'";
                } else {
                    $sql_where_array[] = "fg.$id LIKE '%$value%'";
                }
            }
        }
    }

    $sql_where = '';
    if (!empty($sql_where_array)) {
        $sql_where = implode(' AND ', $sql_where_array);
    }

    /* Sorting */
    $orderby = "fg.date_updated DESC";

    if (isset($_GET['sorters'])) {
        $sorters = $_GET['sorters'];
        $tag = ['asc', 'desc'];
        $sort_field = $sorters[0]['field'];
        $sort_dir = $sorters[0]['dir'];

        if (in_array($sort_field, $dborig) && in_array($sort_dir, $tag)) {
            $orderby = "fg.$sort_field $sort_dir";
        }
    }

    /* Pagination */
    if (isset($_GET['size']) && is_numeric($_GET['size'])) {
        $query_limit = ($_GET['size'] > $query_limit) ? $query_limit : $_GET['size'];
    }

    $page_no = 0;
    if (isset($_GET['page']) && is_numeric($_GET['page'])) {
        $page_no = max(0, $_GET['page'] - 1);
    }

    $start_no = $page_no * $query_limit;

    /* Count query */
    $field_query = "COUNT(*) as count";
    $sql_conds = !empty($sql_where) ? "WHERE $sql_where" : "";

    $count_query = "SELECT $field_query FROM $table_name $sql_conds";

    $total_query = 0;
    if ($query = call_mysql_query($count_query)) {
        if ($num = call_mysql_num_rows($query)) {
            $data = call_mysql_fetch_array($query);
            $total_query = (int)$data['count'];
        }
    }

    $pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);

    /* Data query */
    $field_query = implode(',', $dbfield);
    $data_query = "SELECT $field_query FROM $table_name $sql_conds ORDER BY $orderby LIMIT $start_no, $query_limit";

    $to_encode = [];
    if ($query = call_mysql_query($data_query)) {
        if ($num = call_mysql_num_rows($query)) {
            while ($data = call_mysql_fetch_array($query)) {
                $data = array_html($data);

                $data['final_id'] = isset($data['final_id']) ? (int)$data['final_id'] : 0;
                $data['units'] = isset($data['units']) ? (int)$data['units'] : 0;
                $data['school_year_id'] = isset($data['school_year_id']) ? (int)$data['school_year_id'] : 0;
                $data['final_grade'] = isset($data['final_grade']) ? (double)$data['final_grade'] : 0;

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
    $output['code'] = 500;
    echo json_encode($output);
}
?>