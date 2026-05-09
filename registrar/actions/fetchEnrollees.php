<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require API_PATH;

header('Content-Type: application/json');
$session_class->session_close();

function registrar_json_exit($payload) {
    echo json_encode($payload);
    exit();
}

function registrar_semester_label($sem) {
    $sem = strtolower(trim((string)$sem));
    if (strpos($sem, '1st') !== false || strpos($sem, 'first') !== false) {
        return '1st Semester';
    }
    if (strpos($sem, '2nd') !== false || strpos($sem, 'second') !== false) {
        return '2nd Semester';
    }
    return trim((string)$sem);
}

function registrar_previous_term($school_year, $sem, $incoming_year_level) {
    $sem_label = registrar_semester_label($sem);
    $previous_school_year = trim((string)$school_year);
    $previous_semester = '1st Semester';
    $comparison_year_level = intVal($incoming_year_level);

    if (strcasecmp($sem_label, '1st Semester') === 0) {
        $parts = explode('-', trim((string)$school_year));
        if (count($parts) === 2) {
            $previous_school_year = (intVal($parts[0]) - 1) . '-' . (intVal($parts[1]) - 1);
        }
        $previous_semester = '2nd Semester';
        $comparison_year_level = max(1, intVal($incoming_year_level) - 1);
    }

    return [
        'school_year' => $previous_school_year,
        'semester' => $previous_semester,
        'year_level' => $comparison_year_level,
    ];
}

function registrar_incoming_year_level($stored_year_level, $selected_semester, $max_year_level) {
    $incoming_year_level = intVal($stored_year_level);
    if (strcasecmp(registrar_semester_label($selected_semester), '1st Semester') === 0 && $max_year_level > 0) {
        $incoming_year_level = min($incoming_year_level + 1, $max_year_level);
    }
    if ($max_year_level > 0) {
        $incoming_year_level = min(max($incoming_year_level, 1), $max_year_level);
    }
    return $incoming_year_level;
}

function registrar_curriculum_meta($db_connect, $curriculum_id) {
    $meta = [
        'max_year_level' => 0,
        'units_by_year_sem' => [],
        'subjects_by_year_sem' => [],
    ];

    $sql = "
        SELECT subject_code, unit, year_level, semester
        FROM curriculum
        WHERE curriculum_id = '" . escape($db_connect, $curriculum_id) . "'
          AND subject_code <> ''
    ";

    if ($query = call_mysql_query($sql)) {
        while ($row = call_mysql_fetch_array($query)) {
            $year_level = intVal($row['year_level'] ?? 0);
            $semester = registrar_semester_label($row['semester'] ?? '');
            $subject_code = trim((string)($row['subject_code'] ?? ''));
            $unit = intVal($row['unit'] ?? 0);

            if ($year_level <= 0 || $semester === '' || $subject_code === '') {
                continue;
            }

            if (!isset($meta['units_by_year_sem'][$year_level])) {
                $meta['units_by_year_sem'][$year_level] = [];
            }
            if (!isset($meta['subjects_by_year_sem'][$year_level])) {
                $meta['subjects_by_year_sem'][$year_level] = [];
            }
            if (!isset($meta['units_by_year_sem'][$year_level][$semester])) {
                $meta['units_by_year_sem'][$year_level][$semester] = 0;
            }
            if (!isset($meta['subjects_by_year_sem'][$year_level][$semester])) {
                $meta['subjects_by_year_sem'][$year_level][$semester] = [];
            }

            $meta['units_by_year_sem'][$year_level][$semester] += $unit;
            $meta['subjects_by_year_sem'][$year_level][$semester][$subject_code] = $unit;
            $meta['max_year_level'] = max($meta['max_year_level'], $year_level);
        }
    }

    return $meta;
}

function registrar_earned_units_for_term($db_connect, $student_id_no, $school_year, $semester, $required_subjects) {
    if (empty($required_subjects)) {
        return 0;
    }

    $subject_codes = array_keys($required_subjects);
    $subject_sql = "'" . implode("','", array_map(function($code) use ($db_connect) {
        return escape($db_connect, $code);
    }, $subject_codes)) . "'";

    $passed_codes = [];
    $seen_codes = [];
    $sql = "
        SELECT subject_code, remarks, converted_grade
        FROM final_grade
        WHERE student_id_text = '" . escape($db_connect, $student_id_no) . "'
          AND school_year = '" . escape($db_connect, $school_year) . "'
          AND UPPER(sem) = UPPER('" . escape($db_connect, $semester) . "')
          AND subject_code IN ($subject_sql)
          AND status = '1'
        ORDER BY date_updated DESC, final_id DESC
    ";

    if ($query = call_mysql_query($sql)) {
        while ($row = call_mysql_fetch_array($query)) {
            $code = trim((string)($row['subject_code'] ?? ''));
            if ($code === '' || isset($seen_codes[$code])) {
                continue;
            }

            // echo "\n row: " . json_encode($row) . "\n";
            $seen_codes[$code] = true;

            $remarks = strtolower(trim((string)($row['remarks'] ?? '')));
            $converted_grade = trim((string)($row['converted_grade'] ?? ''));
            $is_passed = false;

            if ($remarks !== '' && (strpos($remarks, 'fail') !== false || $remarks === 'f')) {
                $is_passed = false;
            } elseif (is_numeric($converted_grade)) {
                $grade_value = floatVal($converted_grade);
                $is_passed = ($grade_value >= 1.00 && $grade_value <= 3.00);
            } elseif ($remarks !== '') {
                $is_passed = (strpos($remarks, 'pass') !== false || $remarks === 'p');
            }

            if ($is_passed) {
                $passed_codes[$code] = true;
            }
        }
    }

    $earned_units = 0;
    foreach ($required_subjects as $code => $unit) {
        if (isset($passed_codes[$code])) {
            $earned_units += intVal($unit);
        }
    }

    return $earned_units;
}

function registrar_enrolled_units_for_term($db_connect, $student_id_no, $school_year_id, $semester, $curriculum_id) {
    $total_units = 0;
    $sql = "
        SELECT enrollment_unit
        FROM (
            SELECT
                e.enrollment_id,
                GREATEST(
                    COALESCE(MAX(NULLIF(c.unit, 0)), 0),
                    COALESCE(MAX(NULLIF(sub.unit, 0)), 0),
                    COALESCE(MAX(NULLIF(tc.unit, 0)), 0),
                    0
                ) AS enrollment_unit
            FROM enrollments e
            LEFT JOIN subject sub ON sub.subject_id = e.subject_id
            LEFT JOIN teacher_class tc ON tc.teacher_class_id = e.teacher_class_id
            LEFT JOIN curriculum c
                   ON c.curriculum_id = '" . escape($db_connect, $curriculum_id) . "'
                  AND UPPER(TRIM(c.subject_code)) = UPPER(TRIM(sub.subject_code))
            WHERE e.student_id_no = '" . escape($db_connect, $student_id_no) . "'
              AND e.school_year_id = '" . escape($db_connect, $school_year_id) . "'
              AND UPPER(e.sem) = UPPER('" . escape($db_connect, $semester) . "')
              AND e.status = 'Enrolled'
            GROUP BY e.enrollment_id
        ) enrolled_units
    ";

    if ($query = call_mysql_query($sql)) {
        while ($row = call_mysql_fetch_array($query)) {
            $total_units += intVal($row['enrollment_unit'] ?? 0);
        }
    }

    return $total_units;
}

function registrar_filter_match($row, $filters) {
    foreach ($filters as $field => $value) {
        $value = trim((string)$value);
        if ($value === '') {
            continue;
        }
        $row_value = isset($row[$field]) ? (string)$row[$field] : '';
        if (stripos($row_value, $value) === false) {
            return false;
        }
    }
    return true;
}

function registrar_sort_rows(&$rows, $sort_field, $sort_dir) {
    if ($sort_field === '') {
        return;
    }

    usort($rows, function($a, $b) use ($sort_field, $sort_dir) {
        $a_value = $a[$sort_field] ?? '';
        $b_value = $b[$sort_field] ?? '';

        if (is_numeric($a_value) && is_numeric($b_value)) {
            $compare = $a_value <=> $b_value;
        } else {
            $compare = strcmp(strtoupper((string)$a_value), strtoupper((string)$b_value));
        }

        return $sort_dir === 'desc' ? -$compare : $compare;
    });
}

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_401;
        exit();
    }

    if ($g_user_role !== "REGISTRAR") {
        include HTTP_401;
        exit();
    }

    $school_year_id = isset($_GET['school_year_id']) ? intVal($_GET['school_year_id']) : 0;
    if ($school_year_id <= 0) {
        registrar_json_exit([
            'last_page' => 1,
            'data' => [],
            'total_record' => 0,
            'msg_status' => true,
            'msg_response' => 'Please select a fiscal year.',
        ]);
    }

    $school_year = null;
    $sql_sy = "
        SELECT school_year_id, school_year, sem
        FROM school_year
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
        LIMIT 1
    ";
    if ($query = call_mysql_query($sql_sy)) {
        if ($data = call_mysql_fetch_array($query)) {
            $school_year = $data;
        }
    }

    if (empty($school_year)) {
        registrar_json_exit([
            'last_page' => 1,
            'data' => [],
            'total_record' => 0,
            'msg_status' => false,
            'msg_response' => 'Selected fiscal year was not found.',
        ]);
    }

    $selected_school_year = trim((string)$school_year['school_year']);
    $selected_semester = registrar_semester_label($school_year['sem'] ?? '');

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

    $filters = [];
    if (isset($_GET['filters']) && is_array($_GET['filters'])) {
        foreach ($_GET['filters'] as $filter) {
            if (isset($filter['field'])) {
                $filters[$filter['field']] = $filter['value'] ?? '';
            }
        }
    }

    $sort_field = '';
    $sort_dir = 'asc';
    if (isset($_GET['sorters']) && is_array($_GET['sorters']) && !empty($_GET['sorters'])) {
        $sort_field = $_GET['sorters'][0]['field'] ?? '';
        $sort_dir = strtolower(trim((string)($_GET['sorters'][0]['dir'] ?? 'asc')));
        if (!in_array($sort_dir, ['asc', 'desc'], true)) {
            $sort_dir = 'asc';
        }
    }

    $sql_students = "
        SELECT
            s.student_id,
            s.student_id_no,
            s.firstname,
            s.middle_name,
            s.lastname,
            s.suffix_name,
            s.year_level AS stored_year_level,
            s.major,
            s.program_id,
            s.curriculum_id,
            p.short_name,
            p.program,
            cm.header,
            GROUP_CONCAT(DISTINCT COALESCE(NULLIF(e.section_name, ''), cs.class_name) ORDER BY e.enrollment_id SEPARATOR ', ') AS section_name
        FROM enrollments e
        INNER JOIN student s ON s.student_id_no = e.student_id_no
        LEFT JOIN programs p ON p.program_id = s.program_id
        LEFT JOIN curriculum_master cm ON cm.curriculum_id = s.curriculum_id
        LEFT JOIN class_section cs ON cs.class_id = e.class_id
        WHERE e.school_year_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(e.sem) = UPPER('" . escape($db_connect, $selected_semester) . "')
          AND e.status = 'Enrolled'
        GROUP BY s.student_id_no
        ORDER BY s.lastname ASC, s.firstname ASC
    ";

    $curriculum_cache = [];
    $rows = [];
    if ($query = call_mysql_query($sql_students)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data = array_html($data);
            $student_id_no = trim((string)($data['student_id_no'] ?? ''));
            $curriculum_id = intVal($data['curriculum_id'] ?? 0);
            $stored_year_level = intVal($data['stored_year_level'] ?? 0);

            if (!isset($curriculum_cache[$curriculum_id])) {
                $curriculum_cache[$curriculum_id] = registrar_curriculum_meta($db_connect, $curriculum_id);
            }

            $curriculum_meta = $curriculum_cache[$curriculum_id];
            $incoming_year_level = registrar_incoming_year_level(
                $stored_year_level,
                $selected_semester,
                intVal($curriculum_meta['max_year_level'] ?? 0)
            );
            $previous_term = registrar_previous_term($selected_school_year, $selected_semester, $incoming_year_level);

            $previous_required_subjects = $curriculum_meta['subjects_by_year_sem'][$previous_term['year_level']][$previous_term['semester']] ?? [];
            $previous_required_units = intVal($curriculum_meta['units_by_year_sem'][$previous_term['year_level']][$previous_term['semester']] ?? 0);
            $required_units = intVal($curriculum_meta['units_by_year_sem'][$incoming_year_level][$selected_semester] ?? 0);
            $earned_units = registrar_earned_units_for_term(
                $db_connect,
                $student_id_no,
                $previous_term['school_year'],
                $previous_term['semester'],
                $previous_required_subjects
            );
            $enrolled_units = registrar_enrolled_units_for_term(
                $db_connect,
                $student_id_no,
                $school_year_id,
                $selected_semester,
                $curriculum_id
            );
            $student_classification = ($previous_required_units <= 0 || $earned_units >= $previous_required_units) ? 'Regular' : 'Irregular';
            $program_label = trim((string)($data['short_name'] ?? ''));
            if ($program_label !== '') {
                $program_label = trim($program_label . ' ~ ' . trim((string)($data['major'] ?? '')));
            } else {
                $program_label = trim((string)($data['program'] ?? 'No program'));
            }

            $row = [
                'student_id' => intVal($data['student_id'] ?? 0),
                'student_id_no' => $student_id_no,
                'name' => get_full_name($data['firstname'] ?? '', $data['middle_name'] ?? '', $data['lastname'] ?? '', $data['suffix_name'] ?? ''),
                'section' => trim((string)($data['section_name'] ?? '')) !== '' ? trim((string)$data['section_name']) : 'No section',
                'program' => $program_label,
                'curriculum_header' => trim((string)($data['header'] ?? '')),
                'year_level' => $incoming_year_level,
                'earned_units_sem' => $earned_units,
                'required_units_sem' => $required_units,
                'enrolled_units_sem' => $enrolled_units,
                'student_classification' => $student_classification,
                'school_year_id' => $school_year_id,
                'fiscal_year' => $selected_school_year . ' ' . $selected_semester,
                'curriculum_id' => $curriculum_id,
                'program_id' => intVal($data['program_id'] ?? 0),
                'previous_school_year' => $previous_term['school_year'],
                'previous_semester' => $previous_term['semester'],
            ];

            if (registrar_filter_match($row, $filters)) {
                $rows[] = $row;
            }
        }
    }

    registrar_sort_rows($rows, $sort_field, $sort_dir);

    $total_query = count($rows);
    $pages = ($total_query === 0) ? 1 : ceil($total_query / $query_limit);
    $to_encode = array_slice($rows, $start_no, $query_limit);

    registrar_json_exit([
        'last_page' => $pages,
        'data' => $to_encode,
        'total_record' => $total_query,
        'msg_status' => true,
        'msg_response' => 'Enrollees loaded successfully.',
    ]);
} catch (Throwable $th) {
    registrar_json_exit([
        'last_page' => 1,
        'data' => [],
        'total_record' => 0,
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
