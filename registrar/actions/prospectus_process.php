<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
header('Content-Type: application/json');
try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitProspectus']) && $_POST['submitProspectus'] === "createProspectus"){
        $prospectus = isset($_POST['prospectus_json']) ? json_decode($_POST['prospectus_json'], true) : '';
        $curr_id   = isset($_POST['curriculum_id']) ? intVal(trim($_POST['curriculum_id'])) : '';
        $units  = isset($_POST['required_units']) ? intVal(trim($_POST['required_units'])) : '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        $curr_title = '';
        $curr_code = '';
        $program_id = '';
        $lec_units = '';
        $lab_units = '';
        $year_level = '';
        $subject_id = '';

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
        
        if(empty($prospectus) || dataEmptyCheck($curr_id) || dataEmptyCheck($units)){
            $output['msg_response'] = 'All fields are required.';
            $output['code'] = 501;
            echo json_encode($output);
            exit();
        }

        foreach ($prospectus as $block) {
            $year_level = isset($block['year_level']) ? intVal($block['year_level']) : 0;
            $semester   = isset($block['semester']) ? intVal($block['semester']) : 0;
            $subjects   = !empty($block['subjects']) ? $block['subjects'] : [];

            if (count($subjects) === 0) {
                $output['msg_response'] = "No courses in Year level ".$year_level.", Semester ".$semester.".";
                $output['code'] = 502;
                echo json_encode($output);
                exit();
            }
        }

        $sql_curr = "SELECT curriculum_code, header, program_id FROM curriculum_master WHERE curriculum_id = '$curr_id'";
        if($sql = call_mysql_query($sql_curr)){
            if($data = call_mysql_fetch_array($sql)){
                $curr_title = $data['header'];
                $curr_code = $data['curriculum_code'];
                $program_id = intVal($data['program_id']);
            }
        } else {
            $output['msg_response'] = 'Curriculum not found.';
            echo json_encode($output);
            exit();
        }

        // Flatten rows
        $rows = [];
        foreach ($prospectus as $block) {
            $year_level = intVal($block['year_level'] ?? 0);
            $semester   = intVal($block['semester'] ?? 0);
            $subjects   = $block['subjects'] ?? [];
            // echo "blocks: ";
            // var_dump($block);
            // echo "subjects: ";
            // var_dump($subjects);

            if(is_array($subjects) && count($subjects) !== 0){
                foreach ($subjects as $s) {
                    $subject_id = intVal($s['subject_id'] ?? 0);
                    if ($subject_id <= 0) continue;

                    $lec = intVal($s['lec'] ?? 0);
                    $lab = intVal($s['lab'] ?? 0);
                    $lec_lab = json_encode([$lec, $lab]);

                    $rows[] = [
                        "curriculum_id" => $curr_id,
                        "curriculum_code" => $curr_code,
                        "program_id" => $program_id,
                        "curriculum_title" => $curr_title,
                        "year_level" => $year_level,
                        "semester" => $semester === 1 ? '1st Semester' : '2nd Semester',
                        "subject_id" => $subject_id,
                        "subject_code" => $s['subject_code'] ?? '',
                        "subject_title" => $s['subject_title'] ?? '',
                        "units" => intVal($s['units'] ?? 0),
                        "lec_lab" => $lec_lab,
                        "pre-req" => $s['prereq_code'] ?? '',
                        "pre_req_id" => $s['prereq_subject_id'] ?? '',
                    ];
                }
            }
        }


        $values = [];
        foreach ($rows as $r) {
            $values[] = "(
                '".escape($db_connect,$r["curriculum_id"])."',
                '".escape($db_connect,$r["curriculum_code"])."',
                '".escape($db_connect,$r["program_id"])."',
                '".escape($db_connect,$r["curriculum_title"])."',
                '".escape($db_connect,$r["year_level"])."',
                '".escape($db_connect,$r["semester"])."',
                '".escape($db_connect,$r["subject_id"])."',
                '".escape($db_connect,$r["subject_code"])."',
                '".escape($db_connect,$r["subject_title"])."',
                '".escape($db_connect,$r["units"])."',
                '".escape($db_connect,$r["lec_lab"])."',
                '".escape($db_connect,$r["pre-req"])."',
                '".escape($db_connect,$r["pre_req_id"])."'
            )";
        }

        $db_connect->begin_transaction();
        $sql_insert = "INSERT INTO curriculum (curriculum_id, curriculum_code, 
        program_id, curriculum_title, year_level, semester, subject_id, 
        subject_code, subject_title, unit, lec_lab, pre_req, pre_req_id)
        VALUES ".(implode(',', $values)).""
        ;
        if($sql = call_mysql_query($sql_insert)){
            $add_unit = "UPDATE curriculum_master SET units = '".escape($db_connect, $units)."s' WHERE curriculum_id = '".escape($db_connect, $curr_id)."'";
            if(call_mysql_query($add_unit)){
                $db_connect->commit();

                $output["code"] = 200;
                $output['msg_response'] = "Prospectus created successfully.";
                $output['msg_span'] = "";
                $output['msg_status'] = true;
                echo json_encode($output);
                exit();
            }
        }
    }
} catch (Throwable $th) {
    $db_connect->rollback();
    $output["code"] = 500;
    $output['msg_response'] = $th->getMessage();
    echo json_encode($output);
    exit();
}
?>