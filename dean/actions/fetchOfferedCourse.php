<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

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
            'msg_status' => false,
            'code' => 401,
            'msg_response' => 'Unauthorized request.',
        ]);
    }

    $dean_department = get_dean_department_context($db_connect, trim((string)($g_general_id ?? '')));
    $dean_department_id = intVal($dean_department['department_id'] ?? 0);

    if ($dean_department_id <= 0) {
        dean_json_exit([
            'msg_status' => false,
            'code' => 402,
            'msg_response' => 'Dean department assignment was not found.',
            'data' => [],
        ]);
    }

    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
        $school_year_id = isset($_GET['school_year_id']) ? intVal($_GET['school_year_id']) : 0;

        if ($school_year_id <= 0) {
            dean_json_exit([
                'msg_status' => false,
                'code' => 501,
                'msg_response' => 'Please select a valid school year / semester.',
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
            dean_json_exit([
                'msg_status' => false,
                'code' => 502,
                'msg_response' => 'Selected school year / semester was not found.',
                'data' => [],
            ]);
        }

        $active_sem = trim((string)($school_year['sem'] ?? ''));

        $data_rows = [];
        $subject_option_map = [];
        $sql_offered = "
            SELECT
                tc.teacher_class_id,
                tc.class_id,
                tc.subject_id,
                tc.subject_text,
                tc.schedule,
                tc.sem,
                tc.schoolyear_id,
                tc.program_id,
                tc.year_level,
                tc.unit,
                tc.section_limit,
                cs.class_name,
                cs.sec_limit,
                s.subject_code,
                s.subject_title,
                s.unit AS subject_unit,
                s.limit AS course_limit,
                p.short_name AS program_short_name
            FROM teacher_class tc
            LEFT JOIN subject s ON s.subject_id = tc.subject_id
            LEFT JOIN class_section cs ON cs.class_id = tc.class_id
            LEFT JOIN programs p ON p.program_id = tc.program_id
            WHERE tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
              AND p.department_id = '" . escape($db_connect, $dean_department_id) . "'
              AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_sem) . "')
              AND tc.status = 0
              AND tc.class_id > 0
              AND tc.subject_id > 0
              AND TRIM(tc.schedule) <> ''
              AND tc.schedule <> '[]'
              AND cs.status = 0
            ORDER BY p.short_name ASC, cs.class_name ASC, s.subject_code ASC, s.subject_title ASC
        ";

        if ($query = call_mysql_query($sql_offered)) {
            while ($data = call_mysql_fetch_array($query)) {
                $data = array_html($data);
                $subject_id = intVal($data['subject_id'] ?? 0);
                $subject_code = trim((string)($data['subject_code'] ?? ''));
                $subject_title = trim((string)($data['subject_title'] ?? ''));
                $teacher_class_id = intVal($data['teacher_class_id'] ?? 0);
                $unit = intVal($data['subject_unit'] ?? 0);

                if ($teacher_class_id <= 0 || $subject_id <= 0 || $subject_code === '' || $subject_title === '') {
                    continue;
                }

                if (!isset($subject_option_map[$subject_id])) {
                    $subject_option_map[$subject_id] = [
                        'teacher_class_id' => $teacher_class_id,
                        'class_id' => intVal($data['class_id'] ?? 0),
                        'class_name' => trim((string)($data['class_name'] ?? '')),
                        'subject_id' => $subject_id,
                        'course_code' => $subject_code,
                        'course_title' => $subject_title,
                        'program_id' => intVal($data['program_id'] ?? 0),
                        'program_short_name' => trim((string)($data['program_short_name'] ?? '')),
                        'schedule' => $data['schedule'] ?? '',
                        'unit' => $unit,
                        'section_limit' => intVal($data['section_limit'] ?? 0) > 0 ? intVal($data['section_limit'] ?? 0) : intVal($data['sec_limit'] ?? 0),
                        'course_limit' => intVal($data['course_limit'] ?? 0),
                        'default_year_level' => intVal($data['year_level'] ?? 0),
                        'display_text' => trim($subject_code . ' | ' . $subject_title . ' | ' . $unit . ' Unit' . ($unit === 1 ? '' : 's')),
                    ];
                }
            }
        }

        if (!empty($subject_option_map)) {
            $data_rows = array_values($subject_option_map);
            usort($data_rows, function ($a, $b) {
                $label_a = strtoupper(trim((string)($a['display_text'] ?? '')));
                $label_b = strtoupper(trim((string)($b['display_text'] ?? '')));
                return strcmp($label_a, $label_b);
            });
        }

        dean_json_exit([
            'msg_status' => true,
            'code' => 200,
            'msg_response' => 'Scheduled offered courses loaded successfully.',
            'data' => $data_rows,
            'school_year_id' => $school_year_id,
            'semester' => $active_sem,
        ]);
    }

    if (isset($_SERVER['REQUEST_METHOD']) && strtoupper($_SERVER['REQUEST_METHOD']) === 'POST') {
        $submit_action = trim((string)($_POST['submitOfferedCourseList'] ?? ''));
        $school_year_id = isset($_POST['school_year_id']) ? intVal($_POST['school_year_id']) : 0;
        $rows_payload = isset($_POST['offered_rows']) ? json_decode(trim((string)$_POST['offered_rows']), true) : [];

        if ($submit_action !== 'createOfferedCourseList') {
            dean_json_exit([
                'msg_status' => false,
                'code' => 503,
                'msg_response' => 'Invalid request action.',
            ]);
        }

        if ($school_year_id <= 0) {
            dean_json_exit([
                'msg_status' => false,
                'code' => 504,
                'msg_response' => 'Please select a valid school year / semester.',
            ]);
        }

        if (!is_array($rows_payload) || count($rows_payload) === 0) {
            dean_json_exit([
                'msg_status' => false,
                'code' => 505,
                'msg_response' => 'Please add at least one course offer row.',
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
            dean_json_exit([
                'msg_status' => false,
                'code' => 506,
                'msg_response' => 'Selected school year / semester was not found.',
            ]);
        }

        $active_sem = trim((string)($school_year['sem'] ?? ''));
        $seen_keys = [];
        $teacher_class_ids = [];
        $year_levels_by_teacher_class = [];

        foreach ($rows_payload as $index => $row) {
            $teacher_class_id = intVal($row['teacher_class_id'] ?? 0);
            $year_level = intVal($row['year_level'] ?? 0);

            if ($teacher_class_id <= 0 || $year_level <= 0) {
                dean_json_exit([
                    'msg_status' => false,
                    'code' => 507,
                    'msg_response' => 'Invalid course row found. Please select a scheduled course and year level.',
                ]);
            }

            $dup_key = $teacher_class_id . '|' . $year_level;
            if (isset($seen_keys[$dup_key])) {
                dean_json_exit([
                    'msg_status' => false,
                    'code' => 508,
                    'msg_response' => 'Duplicate course offer row found in the submission.',
                ]);
            }

            $seen_keys[$dup_key] = true;
            $teacher_class_ids[] = $teacher_class_id;
            $year_levels_by_teacher_class[$dup_key] = $year_level;
        }

        $teacher_class_ids = array_values(array_unique(array_map('intval', $teacher_class_ids)));
        $teacher_class_sql = implode(',', $teacher_class_ids);

        $offering_map = [];
        $sql_offered = "
            SELECT
                tc.teacher_class_id,
                tc.class_id,
                tc.subject_id,
                tc.subject_text,
                tc.schedule,
                tc.sem,
                tc.schoolyear_id,
                tc.program_id,
                tc.year_level,
                tc.unit,
                tc.section_limit,
                cs.class_name,
                cs.sec_limit,
                s.subject_code,
                s.subject_title,
                s.limit AS course_limit
            FROM teacher_class tc
            LEFT JOIN subject s ON s.subject_id = tc.subject_id
            LEFT JOIN class_section cs ON cs.class_id = tc.class_id
            LEFT JOIN programs p ON p.program_id = tc.program_id
            WHERE tc.teacher_class_id IN ($teacher_class_sql)
              AND tc.schoolyear_id = '" . escape($db_connect, $school_year_id) . "'
              AND p.department_id = '" . escape($db_connect, $dean_department_id) . "'
              AND UPPER(tc.sem) = UPPER('" . escape($db_connect, $active_sem) . "')
              AND tc.status = 0
              AND tc.class_id > 0
              AND tc.subject_id > 0
              AND TRIM(tc.schedule) <> ''
              AND tc.schedule <> '[]'
              AND cs.status = 0
        ";

        if ($query = call_mysql_query($sql_offered)) {
            while ($data = call_mysql_fetch_array($query)) {
                $offering_map[intVal($data['teacher_class_id'] ?? 0)] = $data;
            }
        }

        foreach ($teacher_class_ids as $teacher_class_id) {
            if (empty($offering_map[$teacher_class_id])) {
                dean_json_exit([
                    'msg_status' => false,
                    'code' => 509,
                    'msg_response' => 'One or more selected scheduled courses are no longer available.',
                ]);
            }
        }

        foreach ($rows_payload as $row) {
            $teacher_class_id = intVal($row['teacher_class_id'] ?? 0);
            $year_level = intVal($row['year_level'] ?? 0);
            $offering = $offering_map[$teacher_class_id];
            $subject_id = intVal($offering['subject_id'] ?? 0);

            $duplicate_sql = "
                SELECT offered_subject_id
                FROM backsubject_enroll
                WHERE school_year_id = '" . escape($db_connect, $school_year_id) . "'
                  AND UPPER(sem) = UPPER('" . escape($db_connect, $active_sem) . "')
                  AND teacher_class_id = '" . escape($db_connect, $teacher_class_id) . "'
                  AND year_level = '" . escape($db_connect, $year_level) . "'
                LIMIT 1
            ";

            if ($query = call_mysql_query($duplicate_sql)) {
                if ($data = call_mysql_fetch_array($query)) {
                    dean_json_exit([
                        'msg_status' => false,
                        'code' => 510,
                        'msg_response' => 'One or more selected course offers already exist for this term and year level.',
                    ]);
                }
            }

            if ($subject_id <= 0) {
                dean_json_exit([
                    'msg_status' => false,
                    'code' => 511,
                    'msg_response' => 'One or more selected scheduled courses do not have a valid subject reference.',
                ]);
            }
        }

        $dean_id = trim((string)($g_general_id ?? ''));
        $dean_name = trim((string)($g_fullname ?? $g_name ?? ''));

        $db_connect->begin_transaction();

        foreach ($rows_payload as $row) {
            $teacher_class_id = intVal($row['teacher_class_id'] ?? 0);
            $year_level = intVal($row['year_level'] ?? 0);
            $offering = $offering_map[$teacher_class_id];

            $class_id = intVal($offering['class_id'] ?? 0);
            $class_name = trim((string)($offering['class_name'] ?? ''));
            $subject_id = intVal($offering['subject_id'] ?? 0);
            $course_code = trim((string)($offering['subject_code'] ?? ''));
            $course_title = trim((string)($offering['subject_title'] ?? ''));
            $schedule = $offering['schedule'] ?? '';
            $program_id = intVal($offering['program_id'] ?? 0);
            $section_limit = intVal($offering['section_limit'] ?? 0) > 0 ? intVal($offering['section_limit'] ?? 0) : intVal($offering['sec_limit'] ?? 0);
            $course_limit = intVal($offering['course_limit'] ?? 0);

            $insert_sql = "
                INSERT INTO backsubject_enroll (
                    school_year_id,
                    sem,
                    program_id,
                    year_level,
                    class_id,
                    class_name,
                    teacher_class_id,
                    subject_id,
                    course_code,
                    course_title,
                    schedule,
                    section_limit,
                    course_limit,
                    dean_id,
                    dean_name,
                    status,
                    createdAt
                ) VALUES (
                    '" . escape($db_connect, $school_year_id) . "',
                    '" . escape($db_connect, $active_sem) . "',
                    '" . escape($db_connect, $program_id) . "',
                    '" . escape($db_connect, $year_level) . "',
                    '" . escape($db_connect, $class_id) . "',
                    '" . escape($db_connect, $class_name) . "',
                    '" . escape($db_connect, $teacher_class_id) . "',
                    '" . escape($db_connect, $subject_id) . "',
                    '" . escape($db_connect, $course_code) . "',
                    '" . escape($db_connect, $course_title) . "',
                    '" . escape($db_connect, $schedule) . "',
                    '" . escape($db_connect, $section_limit) . "',
                    '" . escape($db_connect, $course_limit) . "',
                    '" . escape($db_connect, $dean_id) . "',
                    '" . escape($db_connect, $dean_name) . "',
                    'Active',
                    NOW()
                )
            ";

            call_mysql_query($insert_sql);
        }

        $db_connect->commit();

        dean_json_exit([
            'msg_status' => true,
            'code' => 200,
            'msg_response' => 'Offered subject list saved successfully.',
        ]);
    }

    dean_json_exit([
        'msg_status' => false,
        'code' => 500,
        'msg_response' => 'Unsupported request method.',
    ]);
} catch (Throwable $th) {
    $db_connect->rollback();
    dean_json_exit([
        'msg_status' => false,
        'code' => 500,
        'msg_response' => $th->getMessage(),
    ]);
}
?>
