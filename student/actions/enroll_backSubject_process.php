<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');

try {
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitBackSubjectEnrollment']) && $_POST['submitBackSubjectEnrollment'] === "createBackSubjectRequest") {
        $student_id_no = isset($_POST['idNumber']) ? trim($_POST['idNumber']) : '';
        $school_year_id = isset($_POST['school_year_id']) ? intVal(trim($_POST['school_year_id'])) : 0;
        $curriculum_id = isset($_POST['curriculum_id']) ? intVal(trim($_POST['curriculum_id'])) : 0;
        $program_id = isset($_POST['program_id']) ? intVal(trim($_POST['program_id'])) : 0;
        $sem = isset($_POST['semester']) ? strtoupper(trim($_POST['semester'])) : '';
        $requestData = isset($_POST['backSubjectCourses']) ? json_decode(trim($_POST['backSubjectCourses'])) : '';

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

        if (dataEmptyCheck($student_id_no) || empty($school_year_id) || empty($curriculum_id) || empty($program_id) || dataEmptyCheck($sem) || empty($requestData)) {
            $output['code'] = 501;
            $output['msg_response'] = "Please select a valid back subject request.";
            echo json_encode($output);
            exit();
        }

        if (!is_array($requestData) || count($requestData) === 0) {
            $output['code'] = 502;
            $output['msg_response'] = "No back subjects found in request payload.";
            echo json_encode($output);
            exit();
        }

        $student = array();
        $sql_student = "
            SELECT student_id_no, firstname, middle_name, lastname, program_id, curriculum_id
            FROM student
            WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
            LIMIT 1
        ";

        if ($query = call_mysql_query($sql_student)) {
            if ($data = call_mysql_fetch_array($query)) {
                $student = $data;
            }
        }

        if (empty($student)) {
            $output['code'] = 503;
            $output['msg_response'] = "Student record not found.";
            echo json_encode($output);
            exit();
        }

        $student_full_name = trim(($student['lastname'] ?? '') . ', ' . ($student['firstname'] ?? '') . ' ' . ($student['middle_name'] ?? ''));
        $duplicate_teacher_class_ids = array();

        foreach ($requestData as $course) {
            $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
            $semester_value = isset($course->curriculum_semester) ? strtoupper(trim($course->curriculum_semester)) : $sem;

            if ($teacher_class_id <= 0) {
                continue;
            }

            $duplicate_sql = "
                SELECT backSubject_enroll_id
                FROM backSubject_enroll
                WHERE student_id_no = '" . escape($db_connect, $student_id_no) . "'
                AND teacher_class_id = '" . escape($db_connect, $teacher_class_id) . "'
                AND school_year_id = '" . escape($db_connect, $school_year_id) . "'
                AND sem = '" . escape($db_connect, $semester_value) . "'
                AND status IN ('Pending', 'Approved')
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
            $output['msg_response'] = "Some back subjects were already requested for this term.";
            echo json_encode($output);
            exit();
        }

        $db_connect->begin_transaction();

        foreach ($requestData as $course) {
            $teacher_class_id = isset($course->teacher_class_id) ? intVal($course->teacher_class_id) : 0;
            $class_id = isset($course->class_id) ? intVal($course->class_id) : 0;
            $class_name = isset($course->class_name) ? trim($course->class_name) : '';
            $subject_id = isset($course->subject_id) ? intVal($course->subject_id) : 0;
            $subject_code = isset($course->subject_code) ? trim($course->subject_code) : '';
            $subject_title = isset($course->subject_title) ? trim($course->subject_title) : '';
            $schedule = isset($course->schedule) ? trim($course->schedule) : '';
            $unit = isset($course->unit) ? intVal($course->unit) : 0;
            $offered_program = isset($course->offered_program) ? trim($course->offered_program) : '';
            $semester_value = isset($course->curriculum_semester) ? strtoupper(trim($course->curriculum_semester)) : $sem;

            if ($teacher_class_id <= 0 || $class_id <= 0 || $subject_id <= 0 || $subject_code === '' || $subject_title === '' || $class_name === '' || $schedule === '' || $unit <= 0) {
                throw new Exception("Invalid back subject data found in request payload.");
            }

            $insert_sql = "
                INSERT INTO backsubject_enroll (
                    student_id_no,
                    student_name,
                    curriculum_id,
                    home_program_id,
                    offered_program,
                    teacher_class_id,
                    subject_id,
                    subject_code,
                    subject_title,
                    class_id,
                    class_name,
                    schedule,
                    unit,
                    school_year_id,
                    sem,
                    status,
                    createdAt
                ) VALUES (
                    '" . escape($db_connect, $student_id_no) . "',
                    '" . escape($db_connect, $student_full_name) . "',
                    '" . escape($db_connect, $curriculum_id) . "',
                    '" . escape($db_connect, $program_id) . "',
                    '" . escape($db_connect, $offered_program) . "',
                    '" . escape($db_connect, $teacher_class_id) . "',
                    '" . escape($db_connect, $subject_id) . "',
                    '" . escape($db_connect, $subject_code) . "',
                    '" . escape($db_connect, $subject_title) . "',
                    '" . escape($db_connect, $class_id) . "',
                    '" . escape($db_connect, $class_name) . "',
                    '" . escape($db_connect, $schedule) . "',
                    '" . escape($db_connect, $unit) . "',
                    '" . escape($db_connect, $school_year_id) . "',
                    '" . escape($db_connect, $semester_value) . "',
                    'Pending',
                    NOW()
                )
            ";

            call_mysql_query($insert_sql);
        }

        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = "Back subject request submitted successfully.";
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
