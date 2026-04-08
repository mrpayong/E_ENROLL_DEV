<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;
header('Content-Type: application/json');

try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitProspectus']) && $_POST['submitProspectus'] === "createProspectus"){
        $prospectus = isset($_POST['table_Data']) ? json_decode(trim($_POST['table_Data']), true) : '';
        $curr_id = isset($_POST['curriculum_id']) ? intVal(trim($_POST['curriculum_id'])) : '';
        $program_id = isset($_POST['program_id']) ? intVal(trim($_POST['program_id'])) : '';
        $curr_title = isset($_POST['curr_title']) ? trim($_POST['curr_title']) : '';
        $units  = isset($_POST['required_units']) ? intVal(trim($_POST['required_units'])) : '';
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        $values = [];
        $sem = '';
        $lec_units = '';
        $lab_units = '';
        $year_level = '';
        $subject_id = '';

        $subjects_seen = [];
        $rows = [];

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
        
        if(empty($prospectus) || dataEmptyCheck($curr_id) || dataEmptyCheck($program_id)){
            $output['msg_response'] = 'All fields are required.';
            $output['code'] = 501;
            echo json_encode($output);
            exit();
        }


        // var_dump($prospectus);
        // exit();
        foreach ($prospectus as $semBlock) {
            $subjects = json_decode($semBlock['table'], true);
            $sem = intVal($semBlock['sem']);
            $year_level = intVal($semBlock['year_level']);
            if (!is_array($subjects)) {
                $output['msg_response'] = 'No entered courses found.';
                $output['code'] = 401;
                echo json_encode($output);
                exit();
            };
            // echo "sem block: ";var_dump($semBlock);

            foreach ($subjects as $subj) {
                $code  = trim($subj['code'] ?? '');
                $title = trim($subj['title'] ?? '');
                $lec   = isset($subj['lec']) ? (int)$subj['lec'] : 0;
                $lab   = isset($subj['lab']) ? (int)$subj['lab'] : 0;
                $unit  = isset($subj['unit']) ? (int)$subj['unit'] : 0;

                // Skip blank rows
                if ($code === '' && $title === '' && $unit === 0) {
                    continue;
                }

                // Optional de-dup in-memory
                $key = strtoupper($code) . '|' . strtoupper($title);
                if (isset($subjects_seen[$key])) continue;
                $subjects_seen[$key] = true;

                // var_dump($subjects_seen);
                $rows[] = [
                    'code' => $code,
                    'title' => $title,
                    'lec_lab' => json_encode([$lec, $lab]),
                    'unit' => $unit,
                    'status' => 0,
                ];
            }
        }

        $result = 0;
        
        // create subjects
        if (!empty($rows)) {
            $values = [];
            foreach ($rows as $r) {
                $values[] = "(
                    '".escape($db_connect, $r['code'])."',
                    '".escape($db_connect, $r['title'])."',
                    '".escape($db_connect, $r['lec_lab'])."',
                    '".escape($db_connect, $r['unit'])."',
                    '".escape($db_connect, $program_id)."',
                    '".escape($db_connect, $curr_id)."',
                    '".escape($db_connect, $r['status'])."',
                    NOW()
                )";
            }
            
            $db_connect->begin_transaction();
            $sql_insert = "INSERT INTO subject
                (subject_code, subject_title, lec_lab, unit, program_id, curriculum_id, status, date_modified)
                VALUES ".implode(',', $values);

            if(call_mysql_query($sql_insert)){
                $result = 1;
            }
            $db_connect->commit();
        } else {
            $output['msg_response'] = 'No valid subjects to create.';
            $output['code'] = 403;
            echo json_encode($output);
            exit();
        }

        
        $subj_data = [];
        if($result === 1){
            var_dump($semData);
            $sql_subjID = "SELECT subject_id, subject_title, subject_code, lec_lab, unit FROM subject WHERE curriculum_id = '".    escape($db_connect, $curr_id).  "'  AND program_id = '".escape($db_connect, $program_id)."'";
            if($fetch_id = call_mysql_query($sql_subjID)){
                while($data = call_mysql_fetch_array($fetch_id)){
                    $subj_data[] = "(
                        curriculum_id = '".escape($db_connect, $curr_id)."',
                        program_id = '".escape($db_connect, $program_id)."',
                        curriculum_title = '".escape($db_connect, $curr_title)."',
                        year_level = '".escape($db_connect, $semData['year_level'])."',
                        semester = '".escape($db_connect, $semData['sem'])."',
                        subject_id = '".escape($db_connect, $data['subject_id'])."',
                        subject_code = '".escape($db_connect, $data['subject_code'])."',
                        subject_title = '".escape($db_connect, $data['subject_title'])."',
                        unit = '".escape($db_connect, $data['unit'])."',
                        lec_lab = '".escape($db_connect, $data['lec_lab'])."'
                    )";
                }
            }

            // $create_curr = "INSERT INTO curriculum (curriculum_id, program_id, curriculum_title, year_level, semester, subject_id, subject_code, subject_title, unit, lec_lab, pre_req) 
            // VALUES".implode(',', $subj_data);
        }
        // Nothing to insert
       echo "rows: "; var_dump($values);
        exit();

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
