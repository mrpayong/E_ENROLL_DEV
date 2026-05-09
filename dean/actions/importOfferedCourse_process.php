<?php
set_time_limit(0);
ini_set('max_execution_time', '0');
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));

require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
require UPLOAD_HANDLER;

$session_class->session_close();
header("Content-type: application/json; charset=utf-8");

function offered_import_json_exit($payload) {
    echo json_encode($payload);
    exit();
}

function offered_import_norm($value) {
    return strtoupper(trim(preg_replace('/\s+/', ' ', (string)$value)));
}

function offered_import_dean_department($db_connect, $general_id) {
    $sql = "
        SELECT d.department_id
        FROM departments d
        INNER JOIN users u ON d.user_id = u.user_id
        WHERE u.general_id = '" . escape($db_connect, $general_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql)) {
        if ($data = call_mysql_fetch_array($query)) {
            return intVal($data['department_id'] ?? 0);
        }
    }

    return 0;
}

try {
    if (!(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')) {
        include HTTP_404;
        exit();
    }

    if ($g_user_role !== "DEAN") {
        offered_import_json_exit([
            'code' => 401,
            'msg_status' => false,
            'msg_response' => 'Unauthorized request.',
            'data' => [],
        ]);
    }

    $school_year_id = isset($_POST['school_year_id']) ? intVal($_POST['school_year_id']) : 0;
    if ($school_year_id <= 0) {
        offered_import_json_exit([
            'code' => 501,
            'msg_status' => false,
            'msg_response' => 'Please select a valid school year / semester before uploading.',
            'data' => [],
        ]);
    }

    $dean_department_id = offered_import_dean_department($db_connect, trim((string)($g_general_id ?? '')));
    if ($dean_department_id <= 0) {
        offered_import_json_exit([
            'code' => 502,
            'msg_status' => false,
            'msg_response' => 'Dean department assignment was not found.',
            'data' => [],
        ]);
    }

    $school_year = null;
    $sql_school_year = "
        SELECT school_year_id, school_year, sem
        FROM school_year
        WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
        LIMIT 1
    ";

    if ($query = call_mysql_query($sql_school_year)) {
        if ($data = call_mysql_fetch_array($query)) {
            $school_year = $data;
        }
    }

    if (empty($school_year)) {
        offered_import_json_exit([
            'code' => 503,
            'msg_status' => false,
            'msg_response' => 'Selected school year / semester was not found.',
            'data' => [],
        ]);
    }

    $selected_school_year = trim((string)($school_year['school_year'] ?? ''));
    $selected_semester = trim((string)($school_year['sem'] ?? ''));

    $uploader = new UploaderHandler();
    $uploader->allowedExtensions = ['csv'];
    $uploader->sizeLimit = CSV_SIZE;
    $uploader->uploadDirectory = CSV_PATH;
    $uploader->inputFileName = "import_offered_course_file";

    $result = $uploader->handleFileUpload();
    $result["uploadName"] = $uploader->getUploadName();

    if (!empty($result["error"])) {
        offered_import_json_exit([
            'code' => 504,
            'msg_status' => false,
            'msg_response' => $result["error"],
            'data' => [],
        ]);
    }

    if (!isset($result["success"]) && $result["uploadName"] == "") {
        offered_import_json_exit([
            'code' => 505,
            'msg_status' => false,
            'msg_response' => 'Upload failed.',
            'data' => [],
        ]);
    }

    $scheduled_courses = [];
    $scheduled_by_exact = [];
    $scheduled_by_program_code = [];
    $sql_scheduled = "
        SELECT
            tc.teacher_class_id,
            tc.class_id,
            tc.subject_id,
            tc.schedule,
            tc.program_id,
            tc.year_level,
            tc.section_limit,
            cs.class_name,
            cs.sec_limit,
            s.subject_code,
            s.subject_title,
            s.unit AS subject_unit,
            p.short_name AS program_short_name
        FROM teacher_class tc
        INNER JOIN subject s ON s.subject_id = tc.subject_id
        INNER JOIN class_section cs ON cs.class_id = tc.class_id
        LEFT JOIN programs p ON p.program_id = tc.program_id
        WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
          AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $selected_semester) . "')
          AND p.department_id = '" . escape($db_connect, $dean_department_id) . "'
          AND tc.status = 0
          AND cs.status = 0
          AND tc.class_id > 0
          AND tc.subject_id > 0
          AND TRIM(tc.schedule) <> ''
          AND tc.schedule <> '[]'
        ORDER BY p.short_name ASC, cs.class_name ASC, s.subject_code ASC
    ";

    if ($query = call_mysql_query($sql_scheduled)) {
        while ($data = call_mysql_fetch_array($query)) {
            $subject_code = trim((string)($data['subject_code'] ?? ''));
            $subject_title = trim((string)($data['subject_title'] ?? ''));
            $teacher_class_id = intVal($data['teacher_class_id'] ?? 0);
            $unit = intVal($data['subject_unit'] ?? 0);

            if ($teacher_class_id <= 0 || $subject_code === '' || $subject_title === '') {
                continue;
            }

            $row = [
                'teacher_class_id' => $teacher_class_id,
                'subject_code' => $subject_code,
                'subject_title' => $subject_title,
                'class_name' => trim((string)($data['class_name'] ?? '')),
                'program_short_name' => trim((string)($data['program_short_name'] ?? '')),
                'display_text' => trim($subject_code . ' | ' . $subject_title . ' | ' . $unit . ' Unit' . ($unit === 1 ? '' : 's')),
            ];

            $scheduled_courses[] = $row;
            $program_key = offered_import_norm($row['program_short_name']);
            $exact_key = $program_key . '|' . offered_import_norm($subject_code) . '|' . offered_import_norm($subject_title);
            $program_code_key = $program_key . '|' . offered_import_norm($subject_code);

            if (!isset($scheduled_by_exact[$exact_key])) {
                $scheduled_by_exact[$exact_key] = $row;
            }
            if (!isset($scheduled_by_program_code[$program_code_key])) {
                $scheduled_by_program_code[$program_code_key] = $row;
            }
        }
    }

    if (empty($scheduled_courses)) {
        offered_import_json_exit([
            'code' => 506,
            'msg_status' => false,
            'msg_response' => 'No scheduled courses are available for the selected school year / semester.',
            'data' => [],
        ]);
    }

    $file = $uploader->getTargetFilePath();
    if (($handle = fopen($file, "r")) === false) {
        offered_import_json_exit([
            'code' => 507,
            'msg_status' => false,
            'msg_response' => 'Unable to read uploaded file.',
            'data' => [],
        ]);
    }

    $required_header = [
        'COURSE CODE',
        'COURSE TITLE',
        'PROGRAM CODE',
        'YEAR LEVEL',
        'SEMESTER',
        'SCHOOL YEAR',
    ];
    $user_header = [];
    $errors = [];
    $parsed_rows = [];
    $seen_rows = [];
    $line_no = 0;

    while (($column = fgetcsv($handle, 0, ",")) !== false) {
        $line_no++;
        foreach ($column as $index => $value) {
            $column[$index] = trim((string)$value);
        }

        if ($line_no === 1) {
            if (isset($column[0])) {
                $column[0] = preg_replace('/^\xEF\xBB\xBF/', '', $column[0]);
            }
            $header = array_map('strtoupper', $column);
            $missing_headers = [];
            foreach ($required_header as $name) {
                $key = array_search($name, $header, true);
                if ($key === false) {
                    $missing_headers[] = $name;
                    continue;
                }
                $user_header[$name] = $key;
            }

            if (!empty($missing_headers)) {
                fclose($handle);
                offered_import_json_exit([
                    'code' => 508,
                    'msg_status' => false,
                    'msg_response' => 'CSV header invalid. Missing: ' . implode(', ', $missing_headers),
                    'data' => [],
                ]);
            }
            continue;
        }

        if ($line_no === 2) {
            continue;
        }

        $is_empty_row = true;
        foreach ($column as $value) {
            if (trim((string)$value) !== '') {
                $is_empty_row = false;
                break;
            }
        }
        if ($is_empty_row) {
            continue;
        }

        $row = [];
        foreach ($user_header as $header_name => $key) {
            $row[$header_name] = trim((string)($column[$key] ?? ''));
        }

        $missing = [];
        foreach ($required_header as $name) {
            if (($row[$name] ?? '') === '') {
                $missing[] = $name;
            }
        }
        if (!empty($missing)) {
            $errors[] = 'Row ' . $line_no . ': missing ' . implode(', ', $missing) . '.';
            continue;
        }

        $year_level = intVal($row['YEAR LEVEL']);
        if ($year_level < 1 || $year_level > 5) {
            $errors[] = 'Row ' . $line_no . ': invalid year level.';
            continue;
        }

        if (offered_import_norm($row['SCHOOL YEAR']) !== offered_import_norm($selected_school_year)) {
            $errors[] = 'Row ' . $line_no . ': school year does not match selected term.';
            continue;
        }

        if (offered_import_norm($row['SEMESTER']) !== offered_import_norm($selected_semester)) {
            $errors[] = 'Row ' . $line_no . ': semester does not match selected term.';
            continue;
        }

        $program_code = offered_import_norm($row['PROGRAM CODE']);
        $exact_key = $program_code . '|' . offered_import_norm($row['COURSE CODE']) . '|' . offered_import_norm($row['COURSE TITLE']);
        $program_code_key = $program_code . '|' . offered_import_norm($row['COURSE CODE']);
        $scheduled = $scheduled_by_exact[$exact_key] ?? ($scheduled_by_program_code[$program_code_key] ?? null);

        if (empty($scheduled)) {
            $errors[] = 'Row ' . $line_no . ': scheduled course was not found for ' . $row['PROGRAM CODE'] . ' ' . $row['COURSE CODE'] . '.';
            continue;
        }

        $duplicate_key = intVal($scheduled['teacher_class_id']) . '|' . $year_level;
        if (isset($seen_rows[$duplicate_key])) {
            $errors[] = 'Row ' . $line_no . ': duplicate course/year level row.';
            continue;
        }
        $seen_rows[$duplicate_key] = true;

        $parsed_rows[] = [
            'teacher_class_id' => intVal($scheduled['teacher_class_id']),
            'year_level' => $year_level,
            'course_code' => $scheduled['subject_code'],
            'course_title' => $scheduled['subject_title'],
            'program_short_name' => $scheduled['program_short_name'],
            'course_label' => $scheduled['display_text'],
        ];
    }

    fclose($handle);

    if (!empty($errors)) {
        offered_import_json_exit([
            'code' => 509,
            'msg_status' => false,
            'msg_response' => implode("\n", array_slice($errors, 0, 10)),
            'data' => $parsed_rows,
            'error_count' => count($errors),
        ]);
    }

    if (empty($parsed_rows)) {
        offered_import_json_exit([
            'code' => 510,
            'msg_status' => false,
            'msg_response' => 'No valid offered-course rows were found in the CSV file.',
            'data' => [],
        ]);
    }

    offered_import_json_exit([
        'code' => 200,
        'msg_status' => true,
        'msg_response' => 'CSV parsed successfully.',
        'data' => $parsed_rows,
        'total' => count($parsed_rows),
    ]);
} catch (Throwable $th) {
    if (isset($handle) && is_resource($handle)) {
        fclose($handle);
    }
    offered_import_json_exit([
        'code' => 500,
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
        'data' => [],
    ]);
}
?>
