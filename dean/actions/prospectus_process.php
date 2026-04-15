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
        $subjectRows = [];   // unique rows for subject table
        $currRows = [];      // all rows for curriculum table
        $subjectSeen = [];

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
        
        if(empty($prospectus) || dataEmptyCheck($curr_id) || dataEmptyCheck($program_id)){
            $output['msg_response'] = 'All fields are required.';
            $output['code'] = 501;
            echo json_encode($output);
            exit();
        }


        foreach ($prospectus as $semBlock) {
            $subjects = json_decode($semBlock['table'], true);
            $sem = trim($semBlock['sem']);
            $year_level = intVal($semBlock['year_level']);
            if (!is_array($subjects)) {
                $output['msg_response'] = 'No entered courses found.';
                $output['code'] = 401;
                echo json_encode($output);
                exit();
            };

            foreach ($subjects as $subj) {
                $code  = trim($subj['code'] ?? '');
                $title = trim($subj['title'] ?? '');
                $lec   = isset($subj['lec']) ? (int)$subj['lec'] : 0;
                $lab   = isset($subj['lab']) ? (int)$subj['lab'] : 0;
                $unit  = isset($subj['unit']) ? (int)$subj['unit'] : 0;
                $pre_req = isset($subj['prereq']) ? trim($subj['prereq']) : '';

                // Skip blank rows
                if ($code === '' && $title === '') {
                    continue;
                }

                // Optional de-dup in-memory
                $key = strtoupper($code) . '|' . strtoupper($title);
                if (isset($subjects_seen[$key])) continue;
                $subjects_seen[$key] = true;

                $currRows[] = [
                    'year_level' => $year_level,
                    'sem' => $sem,
                    'code' => $code,
                    'title' => $title,
                    'lec_lab' => json_encode([$lec, $lab]),
                    'unit' => $unit,
                    'pre_req' => $pre_req
                ];

                // var_dump($subjects_seen);
                $key = strtoupper($code).'|'.strtoupper($title);
                if (!isset($subjectSeen[$key])) {
                    $subjectSeen[$key] = true;
                    $subjectRows[] = [
                        'code' => $code,
                        'title' => $title,
                        'lec_lab' => json_encode([$lec, $lab]),
                        'unit' => $unit,
                        'status' => 0
                    ];
                }
            }
        }


        $result = 0;
        
        // create subjects
        if (!empty($subjectRows)) {
            $values = [];
            foreach ($subjectRows as $r) {
                $sql_exists = "SELECT subject_code, subject_id
                FROM subject
                WHERE program_id = '".escape($db_connect,$program_id)."'
                    AND curriculum_id = '".escape($db_connect,$curr_id)."'
                    AND subject_title = '".escape($db_connect, $r['title'])."'
                    AND subject_code = '".escape($db_connect, $r['code'])."'
                ";

                if ($res = call_mysql_query($sql_exists)) {
                    if(call_mysql_num_rows($res) !== 0){
                        continue;
                    }
                }
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
            
            if(!empty($values)){
                $db_connect->begin_transaction();
                $sql_insert = "INSERT INTO subject
                    (subject_code, subject_title, lec_lab, unit, program_id, curriculum_id, status, date_modified)
                    VALUES ".implode(',', $values);

                if(call_mysql_query($sql_insert)){
                    $result = 1;
                }
                $db_connect->commit();
            }
        } else {
            $output['msg_response'] = 'No valid subjects to create.';
            $output['code'] = 403;
            echo json_encode($output);
            exit();
        }
        
        $pairs = [];
        foreach ($subjectRows as $r) {
            $pairs[] = "('".escape($db_connect, $r['code'])."','".escape($db_connect, $r['title'])."')";
        }
        // echo "pair: ";var_dump($pairs);

        if($result === 1){
            $sql_map = "SELECT subject_code, subject_title, subject_id AS subject_id
                        FROM subject
                        WHERE program_id = '".escape($db_connect, $program_id)."'
                        AND curriculum_id = '".escape($db_connect, $curr_id)."'
                        AND (subject_code, subject_title) IN (".implode(',', $pairs).")";

            $subjectMap = [];
            $skip = array();
            if ($res = call_mysql_query($sql_map)) {
                while ($row = call_mysql_fetch_array($res)) {
                    // echo "rows from subjects: \n";var_dump($row);
                    $sql_curr = "SELECT prospectus_id FROM curriculum 
                    WHERE subject_id = '".escape($db_connect, intVal($row['subject_id']))."'
                    AND program_id = '".escape($db_connect, $program_id)."'
                    AND curriculum_id = '".escape($db_connect, $curr_id)."'
                    ";
                    if($fetch_sql = call_mysql_query($sql_curr)){
                        if(call_mysql_num_rows($fetch_sql) === 0){
                            $key = strtoupper($row['subject_code']).'|'.strtoupper($row['subject_title']);
                            $subjectMap[$key] = $row['subject_id'];
                        }
                    }
                }
            }


            $currValues = [];

            // echo "subject map: ";var_dump($subjectMap);
            // echo "\n curr rows: ";var_dump($currRows);
            foreach ($currRows as $r) {
                $key = strtoupper($r['code']).'|'.strtoupper($r['title']);
                if (!isset($subjectMap[$key])) {
                    continue;
                }

                $currValues[] = "(
                    '".escape($db_connect, $curr_id)."',
                    '".escape($db_connect, $program_id)."',
                    '".escape($db_connect, $curr_title)."',
                    '".escape($db_connect, $r['year_level'])."',
                    '".escape($db_connect, $r['sem'])."',
                    '".escape($db_connect, $subjectMap[$key])."',
                    '".escape($db_connect, $r['code'])."',
                    '".escape($db_connect, $r['title'])."',
                    '".escape($db_connect, $r['unit'])."',
                    '".escape($db_connect, $r['lec_lab'])."',
                    '".escape($db_connect, $r['pre_req'])."'
                )";
            }

            // var_dump($currValues);
            // exit();

            if(!empty($currValues)){
                $db_connect->begin_transaction();
                $create_sql = "INSERT INTO curriculum
                (curriculum_id, program_id, curriculum_title, year_level, semester, subject_id,
                subject_code, subject_title, unit, lec_lab, pre_req)
                VALUES".implode(',', $currValues);

                call_mysql_query($create_sql);
                $db_connect-> commit();
            }

        }

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Prospectus created successfully.';
        echo json_encode($output);
        exit();
    }
} catch (Throwable $th) {
    $db_connect->rollback();
    $output["code"] = 500;
    $output['msg_response'] = $th->getMessage();
    echo json_encode($output);
    exit();
}
?>
