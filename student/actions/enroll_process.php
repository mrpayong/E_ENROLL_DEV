<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitEnrollment']) && $_POST['submitEnrollment'] === "createEnrollment") {
        $selected_class_id = isset($_POST['selected_class_id']) ? intVal(trim($_POST['selected_class_id'])) : 0;
        $school_year_id = isset($_POST['school_year_id']) ? intVal(trim($_POST['school_year_id'])) : 0;
        $student_id_no = isset($_POST['idNumber']) ? trim($_POST['idNumber']) : 0;
        $enrollData = isset($_POST['enrollCourses']) ? json_decode(trim($_POST['enrollCourses'])) : '';
        $curriculum_id = isset($_POST['curriculum_id']) ? intVal(trim($_POST['curriculum_id'])) : 0;
        $sem = isset($_POST['semester']) ? strtoupper(trim($_POST['semester'])) : '';
        $program_id = isset($_POST['program_id']) ? intVal(trim($_POST['program_id'])) : 0;
        $duplicate_teacher_class_ids = array();

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

        if (dataEmptyCheck($selected_class_id) || empty($school_year_id) || empty($student_id_no) || empty($enrollData) || empty($curriculum_id) || empty($sem) || empty($program_id)) {
            $output['code'] = 501;
            $output['msg_response'] = "Please select a valid section.";
            echo json_encode($output);
            exit();
        }

        if (!is_array($enrollData) || count($enrollData) === 0) {
            $output['code'] = 502;
            $output['msg_response'] = "No courses found in enrollment payload.";
            echo json_encode($output);
            exit();
        }

        $semester_value = '';
        foreach ($enrollData as $course) {
            $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
            $semester_value = isset($course->curriculum_semester) && trim((string)$course->curriculum_semester) !== ''
                ? strtoupper(trim($course->curriculum_semester))
                : $sem;
            
            if ($teacher_class_id <= 0 && empty($semester_value)) {
                continue;
            }

            $duplicate_sql = "
                SELECT enrollment_id
                FROM enrollments
                WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
                AND teacher_class_id = '" . escape($db_connect, $teacher_class_id) . "'
                AND school_year_id = '" . escape($db_connect, $school_year_id) . "'
                AND UPPER(sem) = UPPER('" . escape($db_connect, $semester_value) . "')
                LIMIT 1
            ";

            if ($query = call_mysql_query($duplicate_sql)) {
                if ($data = call_mysql_fetch_array($query)) {
                    $duplicate_teacher_class_ids[] = $teacher_class_id;
                }
            }
        }

        if (count($duplicate_teacher_class_ids) > 0) {
            $output['code'] = 504;
            $output['msg_response'] = "Some courses are already enrolled for this term.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();

        foreach ($enrollData as $course) {
            $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
            $class_id = isset($course->class_id) ? intVal($course->class_id) : 0;
            $class_name = isset($course->class_name) ? trim($course->class_name) : '';
            $subject_id = isset($course->subject_id) ? intVal($course->subject_id) : 0;
            $schedule = isset($course->schedule) ? trim($course->schedule) : '';
            $course_semester_value = isset($course->curriculum_semester) && trim((string)$course->curriculum_semester) !== ''
                ? strtoupper(trim($course->curriculum_semester))
                : $sem;

            if ($teacher_class_id <= 0 || $class_id <= 0 || $subject_id <= 0 || $class_name === '' || $schedule === '') {
                throw new Exception("Invalid course data found in enrollment payload.");
            }

            $insert_sql = "
                INSERT INTO enrollments (
                    student_id_no,
                    teacher_class_id,
                    subject_id,
                    class_id,
                    program_id,
                    curriculum_id,
                    section_name,
                    schedule,
                    school_year_id,
                    sem,
                    status
                ) VALUES (
                    '" . escape($db_connect, $student_id_no) . "',
                    '" . escape($db_connect, $teacher_class_id) . "',
                    '" . escape($db_connect, $subject_id) . "',
                    '" . escape($db_connect, $class_id) . "',
                    '" . escape($db_connect, $program_id) . "',
                    '" . escape($db_connect, $curriculum_id) . "',
                    '" . escape($db_connect, $class_name) . "',
                    '" . escape($db_connect, $schedule) . "',
                    '" . escape($db_connect, $school_year_id) . "',
                    '" . escape($db_connect, $course_semester_value) . "',
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
        echo json_encode($output);
        exit();
    }
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
