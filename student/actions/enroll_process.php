<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitEnrollment']) && $_POST['submitEnrollment'] === "createEnrollment") {
        $selected_class_id = isset($_POST['selected_class_id']) ? intVal($_POST['selected_class_id']) : 0;
        $school_year_id = 


        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system',
        );

        if ($g_user_role !== "STUDENT") {
            $output['code'] = 401;
            $output['msg_response'] = "Unauthorized request.";
            echo json_encode($output);
            exit();
        }

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }

        if (dataEmptyCheck($selected_class_id)) {
            $output['code'] = 501;
            $output['msg_response'] = "Please select a valid section.";
            echo json_encode($output);
            exit();
        }

        $student_id_no = trim($g_general_id);
        $student_curriculum_id = 0;
        $student_program_id = 0;
        $active_school_year_id = 0;
        $active_semester_text = '';
        $active_semester_value = 0;

        $student_sql = "
            SELECT student_id_no, curriculum_id, program_id
            FROM student
            WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
            LIMIT 1
        ";

        if ($query = call_mysql_query($student_sql)) {
            if ($student_data = call_mysql_fetch_array($query)) {
                $student_curriculum_id = intVal($student_data['curriculum_id']);
                $student_program_id = intVal($student_data['program_id']);
            } else {
                $output['code'] = 502;
                $output['msg_response'] = "Student record not found.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 503;
            $output['msg_response'] = "Unable to load student information.";
            echo json_encode($output);
            exit();
        }

        $school_year_sql = "
            SELECT school_year_id, sem
            FROM school_year
            WHERE isDefault = '" . escape($db_connect, 1) . "'
            LIMIT 1
        ";

        if ($query = call_mysql_query($school_year_sql)) {
            if ($school_year_data = call_mysql_fetch_array($query)) {
                $active_school_year_id = intVal($school_year_data['school_year_id']);
                $active_semester_text = trim($school_year_data['sem']);

                if (stripos($active_semester_text, '1st') !== false) {
                    $active_semester_value = 1;
                } elseif (stripos($active_semester_text, '2nd') !== false) {
                    $active_semester_value = 2;
                }
            } else {
                $output['code'] = 504;
                $output['msg_response'] = "No active school year found.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 505;
            $output['msg_response'] = "Unable to load active school year.";
            echo json_encode($output);
            exit();
        }

        $existing_sql = "
            SELECT enrollment_id
            FROM enrollments
            WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
              AND school_year_id = '" . escape($db_connect, $active_school_year_id) . "'
              AND sem = '" . escape($db_connect, $active_semester_value) . "'
              AND status = 'Enrolled'
            LIMIT 1
        ";

        if ($query = call_mysql_query($existing_sql)) {
            if ($existing = call_mysql_fetch_array($query)) {
                $output['code'] = 506;
                $output['msg_response'] = "You are already enrolled for the active term.";
                echo json_encode($output);
                exit();
            }
        }

        $offerings = array();

        $offerings_sql = "
            SELECT
                tc.teacher_class_id,
                tc.subject_id,
                tc.class_id,
                tc.schedule,
                cs.class_name
            FROM teacher_class tc
            INNER JOIN class_section cs ON cs.class_id = tc.class_id
            WHERE tc.class_id = '" . escape($db_connect, $selected_class_id) . "'
              AND tc.program_id = '" . escape($db_connect, $student_program_id) . "'
              AND tc.schoolyear_id = '" . escape($db_connect, $active_school_year_id) . "'
              AND tc.sem = '" . escape($db_connect, $active_semester_text) . "'
              AND tc.status = 0
              AND cs.status = 0
            ORDER BY tc.teacher_class_id ASC
        ";

        if ($query = call_mysql_query($offerings_sql)) {
            while ($row = call_mysql_fetch_array($query)) {
                $offerings[] = $row;
            }
        }

        if (count($offerings) === 0) {
            $output['code'] = 507;
            $output['msg_response'] = "No offered subjects found for the selected section.";
            echo json_encode($output);
            exit();
        }

        $duplicate_teacher_class_ids = array();
        foreach ($offerings as $offering) {
            $check_duplicate_sql = "
                SELECT enrollment_id
                FROM enrollments
                WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
                  AND teacher_class_id = '" . escape($db_connect, intVal($offering['teacher_class_id'])) . "'
                  AND school_year_id = '" . escape($db_connect, $active_school_year_id) . "'
                  AND sem = '" . escape($db_connect, $active_semester_value) . "'
                LIMIT 1
            ";

            if ($query = call_mysql_query($check_duplicate_sql)) {
                if ($duplicate = call_mysql_fetch_array($query)) {
                    $duplicate_teacher_class_ids[] = intVal($offering['teacher_class_id']);
                }
            }
        }

        if (count($duplicate_teacher_class_ids) > 0) {
            $output['code'] = 508;
            $output['msg_response'] = "Some selected subjects are already enrolled.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();

        foreach ($offerings as $offering) {
            $insert_sql = "
                INSERT INTO enrollments (
                    student_id_no,
                    teacher_class_id,
                    subject_id,
                    class_id,
                    curriculum_id,
                    section_name,
                    schedule,
                    school_year_id,
                    sem,
                    status
                ) VALUES (
                    '" . escape($db_connect, $student_id_no) . "',
                    '" . escape($db_connect, intVal($offering['teacher_class_id'])) . "',
                    '" . escape($db_connect, intVal($offering['subject_id'])) . "',
                    '" . escape($db_connect, intVal($offering['class_id'])) . "',
                    '" . escape($db_connect, $student_curriculum_id) . "',
                    '" . escape($db_connect, trim($offering['class_name'])) . "',
                    '" . escape($db_connect, $offering['schedule']) . "',
                    '" . escape($db_connect, $active_school_year_id) . "',
                    '" . escape($db_connect, $active_semester_value) . "',
                    'Enrolled'
                )
            ";

            call_mysql_query($insert_sql);
        }

        $update_student_section_sql = "
            UPDATE student SET
            class_id = '" . escape($db_connect, $selected_class_id) . "'
            WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
        ";
        call_mysql_query($update_student_section_sql);

        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = "Enrollment saved successfully.";
        $output['msg_span'] = '';
        $output['enrolled_count'] = count($offerings);
        echo json_encode($output);
        exit();
    }

    $output = array(
        'code' => 400,
        'msg_status' => false,
        'msg_response' => 'Invalid request.',
        'msg_span' => '_system',
    );
    echo json_encode($output);
    exit();

} catch (Throwable $th) {
    $db_connect->rollback();
    $output = array(
        'code' => 500,
        'msg_status' => false,
        'msg_response' => $th->getMessage(),
        'msg_span' => '_system',
    );
    echo json_encode($output);
    exit();
}
?>
