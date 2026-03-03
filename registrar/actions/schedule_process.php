<?php
require '../../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

header('Content-Type: application/json');
try {
    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitSchedule']) && $_POST['submitSchedule'] === "createSched"){
        $subject_id = isset($_POST['courseCode']) ? trim($_POST['courseCode']) : '';
        $subject_title = isset($_POST['courseName']) ? trim($_POST['courseName']) : '';
        $prof = isset($_POST['prof']) ? trim($_POST['prof']) : '';
        $section = isset($_POST['section']) ? trim($_POST['section']) : '';
        $limit = isset($_POST['limit']) ? intVal(trim($_POST['limit'])) : '';
        $year_level = isset($_POST['year_level']) ? intVal(trim($_POST['year_level'])) : '';
        $program_id = isset($_POST['program']) ? trim($_POST['program']) : '';
        $lec_unit = isset($_POST['lec']) ? intVal(trim($_POST['lec'])) : '';
        $lab_unit = isset($_POST['lab']) ? intVal(trim($_POST['lab'])) : '';
        $unit = isset($_POST['unit']) ? intVal(trim($_POST['unit'])) : '';
        $school_year = isset($_POST['schoolYear']) ? trim($_POST['schoolYear']) : '';
        $sched = isset($_POST['schedule_summaries']) ? json_decode($_POST['schedule_summaries']) : '';
        $totalHours = isset($_POST['hours']) ? intVal($_POST['hours']) : '';
        $sched_arr = array();
        $lec_lab = array();
        $sched_time_day = array();
        $day_times = [];
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );
        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }
       
        if(
            empty($subject_id) ||
            empty($subject_title) ||
            dataEmptyCheck($unit) ||
            empty($prof) ||
            empty($section) ||
            dataEmptyCheck($limit) ||
            empty($year_level) ||
            empty($program_id) ||
            dataEmptyCheck($lec_unit) ||
            dataEmptyCheck($lab_unit) ||
            empty($school_year) ||
            empty($sched) ||
            empty($totalHours)
        ){
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        };

        $sem = '';
        $fetchSem = "SELECT sem FROM school_year 
        WHERE 
        school_year_id = '".escape($db_connect, $school_year)."' 
        AND flag_used = 1
        ";
        if($semResult = call_mysql_query($fetchSem)){
            if($num = call_mysql_num_rows($semResult) !== 0) {
                if($semData = call_mysql_fetch_array($semResult)){
                    // fetch for sem name because only sy_id is in the payload
                    $sem = $semData['sem'];
                } else {
                    $output['code'] = 404;
                    $output['msg_response'] = "Connection failed";
                    echo json_encode($output);
                    exit();
                }
            } else {
                $output['code'] = 513;
                $output['msg_response'] = "Selected Fiscal Year is locked";
                echo json_encode($output);
                exit();
            }
        }

        function timeToSeconds($timeStr) {
            list($h, $m) = explode(':', $timeStr);
            return intval($h) * 3600 + intval($m) * 60;
        }

        function format12Hour($seconds) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $time = sprintf('%02d:%02d', $hours, $minutes);
            return date("g:i A", strtotime($time));
        }
        
        $input_duplicates = array();
        $input_overlaps = array();
        $day_time_arr = array();
        $day_times_overlaps = array();
        // deconstruct entered plotted schedules
        foreach ($sched as $entry) {
            // input validation pipeline
            // Split the string by '::'
            $parts = explode('::', $entry);
            $day = isset($parts[0]) ? trim($parts[0]) : '';
            $time = isset($parts[1]) ? trim($parts[1]) : '';
            $room = isset($parts[2]) ? trim($parts[2]) : '';
            $component = isset($parts[3]) ? trim($parts[3]) : '';
            $section_id = isset($section) ? $section : '';

            if(empty($day) || empty($time) || empty($room) || empty($component)){
                $output['code'] = 502;
                $output['msg_response'] = "Recheck your plotted schedules, you are missing: ".
                    (empty($day) ? " Day " : "").
                    (empty($time) ? " Time " : "").
                    (empty($room) ? " Room " : "").
                    (empty($component) ? " Component " : "");
                echo json_encode($output);
                exit();
            }

            $timeParts = explode('-', $time);
            $fromSec = timeToSeconds($timeParts[0]);
            $toSec = timeToSeconds($timeParts[1]);


            // 1st Validation
            $room_day = $room . '_' . $day; // format key
            if (!isset($sched_arr[$room_day])) {
                $sched_arr[$room_day] = [];
            }
            if(is_array($sched_arr[$room_day])) {
                // validation 1.1
                //current inputted list duplicate validation
                $keyAssoc = array_search($sched_arr[$room_day], $sched_arr);
                if(isset($keyAssoc)){
                    foreach($sched_arr[$room_day] as $existingTime){
                        if(isset($keyAssoc) && $existingTime === $time){
                            $fromFormatted = format12Hour(timeToSeconds(trim($timeParts[0])));
                            $toFormatted = format12Hour(timeToSeconds(trim($timeParts[1])));
                            $formattedTime = $fromFormatted . '-' . $toFormatted;
                            $caught_sched = "$day:: $formattedTime:: $room";
                            array_push($input_duplicates, $caught_sched);
                        }
                    }
                }
                // validation 1.2
                if(isset($keyAssoc)){
                    if(!empty($sched_arr[$room_day])){ 
                        // Validation for overlapping time in same day and same room
                        foreach($sched_arr[$room_day] as $day_sched){
                            $existingParts = explode('-', $day_sched);
                            $exFrom = timeToSeconds(trim($existingParts[0]));
                            $exTo = timeToSeconds(trim($existingParts[1]));

                            if (($fromSec < $exTo) && ($toSec > $exFrom)) {
                                $fromFormatted = format12Hour($fromSec);
                                $toFormatted = format12Hour($toSec);
                                $exFromFormatted = format12Hour($exFrom);
                                $exToFormatted = format12Hour($exTo);
                                $input_overlaps[] = "{$day}, {$fromFormatted}-{$toFormatted}=>{$exFromFormatted}-{$exToFormatted} at {$room}";
                            }  
                        } 
                    } 
                    $sched_arr[$room_day][] = $time;
                }
            } else {
                //Reconstuct data. Increment array.
                $sched_arr[$room_day][] = $time;
            }

            // 2nd validation
            $day_time = $day . '_' . $time;
            if(isset($sched_time_day[$day_time])){
                // repeated time and day but not same room validation.
                $keyAssoc = array_search($sched_time_day[$day_time], $sched_time_day);
                if(isset($keyAssoc)){
                    $fromFormatted = format12Hour(timeToSeconds(trim($timeParts[0])));
                    $toFormatted = format12Hour(timeToSeconds(trim($timeParts[1])));
                    $formattedTime = $fromFormatted . '-' . $toFormatted;
                    $day_time_arr[] = "$day, $formattedTime";
                }
                
            } else {
                $sched_time_day[$day_time] = $room;
            }

            // 3rd validation
            $day_id = $section_id . '_' . $day;
            if(isset($day_times[$day_id])){
                // overlapping time in same day but not same room validation
                foreach($day_times[$day_id] as $existingTime){
                    $exFrom = $existingTime["Frm"];
                    $exTo = $existingTime["To"];

                    if (($fromSec < $exTo) && ($toSec > $exFrom)) {
                        $fromFormatted = format12Hour($fromSec);
                        $toFormatted = format12Hour($toSec);
                        $exFromFormatted = format12Hour($exFrom);
                        $exToFormatted = format12Hour($exTo);
                        $day_times_overlaps[] = "{$day}, {$fromFormatted}-{$toFormatted}=>{$exFromFormatted}-{$exToFormatted}";
                    } 
                }
            } 
            $day_times[$day_id][] = ["Frm" => $fromSec, "To" => $toSec];
            
        }

        if(!empty($input_duplicates)){
            // validation 1.1
            $msg = "Duplicate Schedule(s):\n";
            foreach ($input_duplicates as $dup) {
                $schedData = explode("::", $dup);
                $day = isset($schedData[0]) ? trim($schedData[0]) : '';
                $time = isset($schedData[1]) ? trim($schedData[1]) : '';
                $room = isset($schedData[2]) ? trim($schedData[2]) : '';
                $msg .= "{$day}, {$time} at {$room} |\n";
            }
            $output['code'] = 503;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($input_overlaps)){
            // validation 1.2
            $msg = "Overlapping Schedule(s):\n";
            foreach ($input_overlaps as $overlap) {
                $msg .= "{$overlap} |\n";
            }
            $output['code'] = 504;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($day_time_arr)){
            // validation 2
            $msg = "Duplicated following schedule(s):\n";
            foreach ($day_time_arr as $item) {
                $msg .= "{$item} |\n";
            }
            $output['code'] = 505;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($day_times_overlaps)){
            // validation 3
            $msg = "Overlapping Schedules:\n";
            foreach ($day_times_overlaps as $item) {
                $msg .= "{$item} |\n";
            }
            $output['code'] = 506;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }

        $prof_id = intVal($prof);
        $section_id = intVal($section);

        // validates section limit against default limit set in class_section table
        $sqlSec = "SELECT sem_limit, class_name, class_id FROM class_section 
                WHERE class_id = '".escape($db_connect, $section_id)."'
                ";

        if($result = call_mysql_query($sqlSec)){
            if($data = call_mysql_fetch_array($result)){
                $semLimit = (array)json_decode($data['sem_limit']);
                $fetchedLimit = intVal(reset($semLimit));
                if($fetchedLimit > $limit){
                    $output['code'] = 507;
                    $output['msg_response'] = "Entered section limit is lower than the default student limit for ".trim($data['class_name']).".";
                    echo json_encode($output);
                    exit();
                }
            } else {
                $output['code'] = 404;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 401;
            $output['msg_response'] = "Connection Failed. Check you network connection.";
            echo json_encode($output);
            exit();
        }

        function parse_schedule_entry($entry) {
            if (!is_string($entry)) return null;
            $parts = explode('::', $entry);
            if (count($parts) < 3) return null;
            return [
                'day' => trim($parts[0]),
                'time' => trim($parts[1]),
                'room' => trim($parts[2])
            ];
        }

        function time_overlap($time1, $time2) {
            // $time1 and $time2 are strings like "09:00-10:00"
            list($start1, $end1) = explode('-', $time1);
            list($start2, $end2) = explode('-', $time2);
            $start1_sec = timeToSeconds(trim($start1));
            $end1_sec = timeToSeconds(trim($end1));
            $start2_sec = timeToSeconds(trim($start2));
            $end2_sec = timeToSeconds(trim($end2));
            return ($start1_sec < $end2_sec) && ($end1_sec > $start2_sec);
        }

        $fetchSectionSched = "SELECT schedule, subject_id, schoolyear_id FROM teacher_class 
        WHERE class_id = '".escape($db_connect, $section)."'
        AND schoolyear_id = '".escape($db_connect, $school_year)."'
        ORDER BY LENGTH(schedule) ASC
        ";
        // 1. Validate entered schedules against existing schedules in the database with SAME SECTION
        if($result_sched = call_mysql_query($fetchSectionSched)){
            if(call_mysql_num_rows($result_sched) !== 0){
                $all_scheds = array(
                    "schedule" => "",
                    "subject_id" => ""
                );
                $subjects = [];
                while($fetchSched = call_mysql_fetch_array($result_sched)){
                    var_dump($fetchSched);
                    $existingScheds = json_decode($fetchSched['schedule']);
                    $all_scheds['subject_id'] = trim($fetchSched['subject_id']);
                    foreach($existingScheds as $stored){
                        $s = parse_schedule_entry($stored);
                        if (!empty($s)){
                            foreach($sched as $entry){
                                $e = parse_schedule_entry($entry);
                                if($s['day'] === $e['day']){
                                    if (time_overlap($s['time'], $e['time'])) {
                                        $sql_section = "SELECT subject_title FROM subject
                                        WHERE subject_id = '".escape($db_connect, $all_scheds['subject_id'])."'
                                        ";
                                        if($result_section = call_mysql_query($sql_section)){
                                            if($fetchSection = call_mysql_fetch_array($result_section)){
                                                if (!in_array($fetchSection['subject_title'], $subjects)) {
                                                    array_push($subjects, trim($fetchSection['subject_title']));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($subjects)) {
                    $output['code'] = 508;
                    $output['msg_response'] = "Conflict detected for the following ".count($subjects)." subject(s) of this section: " . implode(', ', $subjects) . ".";
                    echo json_encode($output);
                    exit();
                }
            }
        }
        
        // 2. fetch schedules to validate room,day,time conflicts
        $sql_sched = "SELECT tc.schedule, tc.class_id, tc.room, cs.class_name FROM teacher_class AS tc
        LEFT JOIN class_section AS cs ON tc.class_id = cs.class_id
        WHERE tc.schoolyear_id = '".escape($db_connect, $school_year)."'
        ORDER BY LENGTH(room) ASC
        ";
        
        // 3. Validate input against existing schedules in the database
        if($result_sched = call_mysql_query($sql_sched)){
            if(call_mysql_num_rows($result_sched) !== 0){
                $class_names = [];
                while($fetchSched = call_mysql_fetch_array($result_sched)){
                    $room_data = json_decode($fetchSched['room'], true);
                    if(trim(json_encode($sched)) === trim($fetchSched['schedule'])){
                        // for only one sched entry. Prevent to loop if there is only 
                        // one sched entry in the database and it is exactly the same as the input
                        $output['code'] = 509;
                        $output['msg_response'] = "Schedule already exist.";
                        echo json_encode($output);
                        exit();
                    }
                    foreach($sched as $entry){
                        $parts = explode('::', $entry);
                        $day = isset($parts[0]) ? trim($parts[0]) : '';
                        $time = isset($parts[1]) ? trim($parts[1]) : '';
                        $room = isset($parts[2]) ? trim($parts[2]) : '';
                        $room_day = $room . '_' . $day;
                        // $room_data is an array with values that are also array
                        // ["0"=>["0"=>time]]

                        if(isset($room_data[$room_day])){
                            foreach($room_data[$room_day] as $timeData){
                                if(time_overlap($time, $timeData)){
                                    if (!in_array($fetchSched['class_name'], $class_names)) {
                                        array_push($class_names, trim($fetchSched['class_name']));
                                    }
                                }
                            }
                        }
                    }
                }
                
                if (!empty($class_names)) {
                    $output['code'] = 510;
                    $output['msg_response'] = "Conflict detected with following ".count($class_names)." section(s): " . implode(', ', $class_names) . ".";
                    echo json_encode($output);
                    exit();
                }
            }
        }

        $sql_section = "SELECT class_id FROM teacher_class 
        WHERE class_id = '".escape($db_connect, $section)."' AND 
        subject_id = '".escape($db_connect, $subject_id)."' 
        AND schoolyear_id = '".escape($db_connect, $school_year)."'
        ";
        if($result_section = call_mysql_query($sql_section)){
            if(call_mysql_num_rows($result_section) !== 0){
                $output['code'] = 511;
                $output['msg_response'] = "Schedule already exists for this section and subject.";
                echo json_encode($output);
                exit();
            }
        }

        $prof_sched = "SELECT 
            tc.teacher_id, tc.schedule, tc.class_id, 
            u.f_name, u.m_name, u.l_name, u.suffix,
            cs.class_name
        FROM teacher_class AS tc
        LEFT JOIN users AS u ON tc.teacher_id = u.user_id
        LEFT JOIN class_section AS cs ON tc.class_id = cs.class_id
        WHERE tc.teacher_id = '".escape($db_connect, $prof)."' 
        AND tc.schoolyear_id = '".escape($db_connect, $school_year)."'
        AND NOT (tc.class_id = '".escape($db_connect, $section)."') 
        ";

        // 4. prof sched validation. Entered vs. Stored scheds
        if($result_prof_sched = call_mysql_query($prof_sched)){
            if(call_mysql_num_rows($result_prof_sched) !== 0){
                $profName = "";
                $class_names = [];
                while($fetchProfSched = call_mysql_fetch_array($result_prof_sched)){
                    foreach ($sched as $entry){
                        $e = parse_schedule_entry($entry);
                        if (!empty($e)){
                            foreach(json_decode($fetchProfSched['schedule']) as $stored){
                                $s = parse_schedule_entry($stored);
                                if($e['day'] === $s['day']){
                                    if (time_overlap($e['time'], $s['time'])) {
                                        if (!in_array($fetchProfSched['class_name'], $class_names)) {
                                            array_push($class_names, trim($fetchProfSched['class_name']));
                                        }
                                        $profName = trim($fetchProfSched['f_name']) . ' ' .
                                                    (empty($fetchProfSched['m_name']) ? '' : trim($fetchProfSched['m_name']) . ' ') .
                                                    trim($fetchProfSched['l_name']) .
                                                    (empty($fetchProfSched['suffix']) ? '' : ' ' . trim($fetchProfSched['suffix']));
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($class_names) && !empty($profName)) {
                    $output['code'] = 512;
                    $output['msg_response'] = "Conflict detected with existing schedule of Prof. $profName for the following ".count($class_names)." section(s): " . implode(', ', $class_names) . ".";
                    echo json_encode($output);
                    exit();
                }
            }
        }


        $encoded_sched = json_encode($sched);
        $encoded_rooms = json_encode($sched_arr, JSON_FORCE_OBJECT);
        array_push($lec_lab, $lec_unit);
        array_push($lec_lab, $lab_unit);
        $encoded_lec_lab = json_encode($lec_lab);
        $db_connect->begin_transaction();
        $create_sql = "INSERT INTO teacher_class (teacher_id, class_id, subject_id, subject_text, schedule, sem, schoolyear_id, program_id, room, year_level, unit, lec_lab, section_limit, total_hours) 
        VALUES(
        '"      .escape($db_connect, $prof_id).      "',
        '"      .escape($db_connect, $section_id).      "',
        '"      .escape($db_connect, $subject_id).      "',
        '"      .escape($db_connect, $subject_title).      "',
        '"      .escape($db_connect, $encoded_sched).      "',
        '"      .escape($db_connect, $sem).      "',
        '"      .escape($db_connect, $school_year).      "',
        '"      .escape($db_connect, $program_id).      "',
        '"      .escape($db_connect, $encoded_rooms).      "',
        '"      .escape($db_connect, $year_level).      "',
        '"      .escape($db_connect, $unit).      "',
        '"      .escape($db_connect, $encoded_lec_lab).      "',
        '"      .escape($db_connect, $limit).      "',
        '"      .escape($db_connect, $totalHours).      "'
        )
        ";

        call_mysql_query($create_sql);
        $db_connect->commit();

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Schedule created successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

    if(isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === "POST" && isset($_POST['submitSchedule']) && $_POST['submitSchedule'] === "updateSched"){
        $program_id = isset($_POST['newProgram']) ? trim($_POST['newProgram']) : '';
        $school_year_id = isset($_POST['newSchoolYear']) ? trim($_POST['newSchoolYear']) : '';
        $edit_id = isset($_POST['editId']) ? intVal(trim($_POST['editId'])) : '';
        $subject_code = isset($_POST['newCourseCode']) ? trim($_POST['newCourseCode']) : '';
        $subject_title = isset($_POST['newCourseName']) ? trim($_POST['newCourseName']) : '';
        $prof = isset($_POST['newProf']) ? trim($_POST['newProf']) : '';
        $section = isset($_POST['newSection']) ? trim($_POST['newSection']) : '';
        $limit = isset($_POST['newLimit']) ? intVal(trim($_POST['newLimit'])) : '';
        $year_level = isset($_POST['newYear_level']) ? intVal(trim($_POST['newYear_level'])) : '';
        $lec_unit = isset($_POST['newLec']) ? intVal(trim($_POST['newLec'])) : '';
        $lab_unit = isset($_POST['newLab']) ? intVal(trim($_POST['newLab'])) : '';
        $unit = isset($_POST['newUnit']) ? intVal(trim($_POST['newUnit'])) : '';
        $sched = isset($_POST['schedule_summaries']) ? json_decode($_POST['schedule_summaries']) : '';
        $totalHours = isset($_POST['newHours']) ? intVal(trim($_POST['newHours'])) : '';
        $to_edit = '';
        $sched_arr = array();
        $sched_time_day = array();
        $lec_lab = array();
        $output = array(
            'code' => 0,
            'msg_status' => false,
            'msg_response' => 'Request error, please try again.',
            'msg_span' => '_system'
        );

        function dataEmptyCheck($val){
            return ($val === null || $val === '');
        }

        if (
            empty($program_id) ||
            empty($school_year_id) ||
            empty($subject_code) ||
            empty($subject_title) ||
            empty($prof) ||
            empty($section) ||
            dataEmptyCheck($limit) ||
            empty($year_level) ||
            dataEmptyCheck($lec_unit) ||
            dataEmptyCheck($lab_unit) ||
            dataEmptyCheck($unit) ||
            empty($sched) ||
            dataEmptyCheck($totalHours)
        ) {
            $output['code'] = 501;
            $output['msg_response'] = "All fields are required.";
            echo json_encode($output);
            exit();
        }

        $sql_sy = "SELECT school_year_id FROM school_year 
        WHERE school_year_id = '".escape($db_connect, $school_year_id)."'
        AND flag_used = 0
        ";

        if ($result_sy = call_mysql_query($sql_sy)) {
            if (call_mysql_num_rows($result_sy) !== 0) {
                $output['code'] = 514;
                $output['msg_response'] = "Updating this schedule is not allowed. Fiscal Year is locked.";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 401;
            $output['msg_response'] = "Connection Failed. Check your network connection.";
            echo json_encode($output);
            exit();
        }

        function timeToSeconds($timeStr) {
            list($h, $m) = explode(':', $timeStr);
            return intval($h) * 3600 + intval($m) * 60;
        }

        function format12Hour($seconds) {
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $time = sprintf('%02d:%02d', $hours, $minutes);
            return date("g:i A", strtotime($time));
        }

        $input_duplicates = array();
        $input_overlaps = array();
        $day_time_arr = array();
        $day_times_overlaps =array();
        // start of validation pipeline
        $day_times = [];
        foreach ($sched as $entry) {
            // Split the string by '::'
            $parts = explode('::', $entry);
            $day = isset($parts[0]) ? trim($parts[0]) : '';
            $time = isset($parts[1]) ? trim($parts[1]) : '';
            $room = isset($parts[2]) ? trim($parts[2]) : '';
            $component = isset($parts[3]) ? trim($parts[3]) : '';
            $section_id = isset($section) ? $section : '';

            if(empty($day) || empty($time) || empty($room) || empty($component)){
                $output['code'] = 502;
                $output['msg_response'] = "Recheck your plotted schedules, you are missing: ".
                    (empty($day) ? " Day " : "").
                    (empty($time) ? " Time " : "").
                    (empty($room) ? " Room " : "").
                    (empty($component) ? " Component " : "");
                echo json_encode($output);
                exit();
            }

            $timeParts = explode('-', $time); 
            $from = isset($timeParts[0]) ? trim($timeParts[0]) : '';
            $to = isset($timeParts[1]) ? trim($timeParts[1]) : '';

            // echo var_dump($timeParts);

            $fromSec = timeToSeconds($from);
            $toSec = timeToSeconds($to);
            
            
            // 1st Validtion
            $room_day = $room . '_' . $day;// format key
            if (!isset($sched_arr[$room_day])) {
                $sched_arr[$room_day] = [];
            }
            if(is_array($sched_arr[$room_day])) {
                //current inputted list duplicate validation
                // look into current inputted list to see if there is repeating sched
                // also checks for overlapping time in same room and day
                $keyAssoc = array_search($sched_arr[$room_day], $sched_arr);// <-- output is array key
                if(isset($keyAssoc)){
                    foreach($sched_arr[$room_day] as $existingTime){
                        if(isset($keyAssoc) && $existingTime === $time){
                            $fromFormatted = format12Hour(timeToSeconds(trim($timeParts[0])));
                            $toFormatted = format12Hour(timeToSeconds(trim($timeParts[1])));
                            $formattedTime = $fromFormatted . '-' . $toFormatted;
                            $caught_sched = "$day:: $time:: $room";
                            array_push($input_duplicates, $caught_sched);
                        }
                    }
                }

                if(isset($keyAssoc)){
                    if(!empty($sched_arr[$room_day])){ 
                        // Validation for overlapping time in same day and same room
                        foreach($sched_arr[$room_day] as $day_sched){
                            $existingParts = explode('-', $day_sched);
                            $exFrom = timeToSeconds(trim($existingParts[0]));
                            $exTo = timeToSeconds(trim($existingParts[1]));

                            if (($fromSec < $exTo) && ($toSec > $exFrom)) {
                                $fromFormatted = format12Hour($fromSec);
                                $toFormatted = format12Hour($toSec);
                                $exFromFormatted = format12Hour($exFrom);
                                $exToFormatted = format12Hour($exTo);
                                $input_overlaps[] = "{$day}, {$fromFormatted}-{$toFormatted}=>{$exFromFormatted}-{$exToFormatted} at {$room}";
                            }  
                        } 
                    } 
                    $sched_arr[$room_day][] = $time;
                }
            } else {
                $sched_arr[$room_day][] = $time;
            }

            // 2nd Validation
            $day_time = $day . '_' . $time;
            if(isset($sched_time_day[$day_time])) {
                // repeated time and day but not same room validation.
                $keyAssoc = array_search($sched_time_day[$day_time], $sched_time_day);
                if(isset($keyAssoc)){
                    $fromFormatted = format12Hour(timeToSeconds(trim($timeParts[0])));
                    $toFormatted = format12Hour(timeToSeconds(trim($timeParts[1])));
                    $formattedTime = $fromFormatted . '-' . $toFormatted;
                    $day_time_arr[] = "$day, $formattedTime";
                }
            } else {
                //Reconstuct data. Increment array.
                $sched_time_day[$day_time] = $room;
            }

            // 3rd Validation
            $day_id = $section_id . '_' . $day;
            if(isset($day_times[$day_id])){
                // overlapping time in same day but not same room validation
                foreach($day_times[$day_id] as $existingTime){
                    $exFrom = $existingTime["Frm"];
                    $exTo = $existingTime["To"];

                    if (($fromSec < $exTo) && ($toSec > $exFrom)) {
                       $fromFormatted = format12Hour($fromSec);
                        $toFormatted = format12Hour($toSec);
                        $exFromFormatted = format12Hour($exFrom);
                        $exToFormatted = format12Hour($exTo);
                        $day_times_overlaps[] = "{$day}, {$fromFormatted}-{$toFormatted}=>{$exFromFormatted}-{$exToFormatted}";
                    } 
                }
            }
            $day_times[$day_id][] = ["Frm" => $fromSec, "To" => $toSec];
        } // end of validation pipeline

        if(!empty($input_duplicates)){
            // validation 1.1
            $msg = "Duplicate Schedule(s):\n";
            foreach ($input_duplicates as $dup) {
                $schedData = explode("::", $dup);
                $day = isset($schedData[0]) ? trim($schedData[0]) : '';
                $time = isset($schedData[1]) ? trim($schedData[1]) : '';
                $room = isset($schedData[2]) ? trim($schedData[2]) : '';
                $msg .= "{$day}, {$time} at {$room} |\n";
            }
            $output['code'] = 503;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($input_overlaps)){
            // validation 1.2
            $msg = "Overlapping Schedule(s):\n";
            foreach ($input_overlaps as $overlap) {
                $msg .= "{$overlap} |\n";
            }
            $output['code'] = 504;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($day_time_arr)){
            // validation 2
            $msg = "Duplicated following schedule(s):\n";
            foreach ($day_time_arr as $item) {
                $msg .= "{$item} |\n";
            }
            $output['code'] = 505;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }
        if(!empty($day_times_overlaps)){
            // validation 3
            $msg = "Overlapping Schedules:\n";
            foreach ($day_times_overlaps as $item) {
                $msg .= "{$item} |\n";
            }
            $output['code'] = 506;
            $output['msg_response'] = trim($msg);
            echo json_encode($output);
            exit();
        }

        $old_data = "";
        $new_data = "";
        $sched_exist = "SELECT teacher_id, class_id, subject_id, subject_text, schedule, schoolyear_id, program_id, room, year_level, unit, lec_lab, section_limit, subject_id, total_hours
        FROM teacher_class 
        WHERE teacher_class_id = '".escape($db_connect, $edit_id)."' ";
        if ($query = call_mysql_query($sched_exist)){
            if($data = call_mysql_fetch_array($query)){
                $sql_limits = "SELECT sem_limit, class_name FROM class_section
                WHERE class_id = '".escape($db_connect, $data['class_id'])."'
                ";
                if($limit_query = call_mysql_query($sql_limits)){
                    if($dataLimits = call_mysql_fetch_array($limit_query)){
                        $semLimit = (array)json_decode($dataLimits['sem_limit']);
                        $fetchedLimit = intVal(reset($semLimit));
                        if($fetchedLimit > $limit){
                            $output['code'] = 507;
                            $output['msg_response'] = "Entered section limit is lower than the default student limit for ".trim($dataLimits['class_name']).".";
                            echo json_encode($output);
                            exit();
                        }
                    }
                }
                $lec_lab_data = json_decode($data['lec_lab']);
                $lec_unit_data = intVal($lec_lab_data[0]);
                $lab_unit_data = intVal($lec_lab_data[1]);
                $old_data = sha1(
                    $data['teacher_id'].
                    $data['room'].
                    $data['class_id'].
                    $data['subject_id'].
                    $data['subject_text'].
                    $data['schedule'].
                    $data['schoolyear_id'].
                    $data['program_id'].
                    $data['year_level'].
                    $data['unit'].
                    $lec_unit_data.
                    $lab_unit_data.
                    $data['section_limit'].
                    $data['total_hours']
                );

                $new_data = sha1(
                    $prof.
                    json_encode($sched_arr, JSON_FORCE_OBJECT).
                    $section.
                    $subject_code.
                    $subject_title.
                    json_encode($sched).
                    $school_year_id.
                    $program_id.
                    $year_level.
                    $unit.
                    $lec_unit.
                    $lab_unit.
                    $limit.
                    $totalHours
                );
            } else {
                $output['code'] = 404;
                $output['msg_response'] = "Connection failed";
                echo json_encode($output);
                exit();
            }
        } else {
            $output['code'] = 401;
            $output['msg_response'] = "It seems the information you are trying to edit does not exist or you have unstable network.";
            echo json_encode($output);
            exit();
        }

        if($new_data === $old_data){
            $output['code'] = 508;
            $output['msg_response'] = "You did not make any changes.";
            echo json_encode($output);
            exit();
        }

        function parse_schedule_entry($entry) {
            if (!is_string($entry)) return null;
            $parts = explode('::', $entry);
            if (count($parts) < 3) return null;
            return [
                'day' => trim($parts[0]),
                'time' => trim($parts[1]),
                'room' => trim($parts[2])
            ];
        }

        function time_overlap($time1, $time2) {
            // $time1 and $time2 are strings like "09:00-10:00"
            list($start1, $end1) = explode('-', $time1);
            list($start2, $end2) = explode('-', $time2);
            $start1_sec = timeToSeconds(trim($start1));
            $end1_sec = timeToSeconds(trim($end1));
            $start2_sec = timeToSeconds(trim($start2));
            $end2_sec = timeToSeconds(trim($end2));
            return ($start1_sec < $end2_sec) && ($end1_sec > $start2_sec);
        }

        $sql_sched = "SELECT schedule, subject_id, teacher_class_id FROM teacher_class 
        WHERE class_id = '".escape($db_connect, $section)."' 
        AND NOT (teacher_class_id = '".escape($db_connect, $edit_id)."')
        ORDER BY LENGTH(schedule) ASC
        ";
        // 1. Validate input against existing schedules in the 
        // database with SAME SECTION
        if($result_sched = call_mysql_query($sql_sched)){
            if(call_mysql_num_rows($result_sched) !== 0){
                $all_scheds = array(
                    "schedule" => "",
                    "subject_id" => ""
                );
                
                $subjects = [];
                while($fetchSched = call_mysql_fetch_array($result_sched)){
                    $existingScheds = json_decode($fetchSched['schedule']);
                    $all_scheds['subject_id'] = trim($fetchSched['subject_id']);
                    foreach($existingScheds as $stored){
                        $e = parse_schedule_entry($stored);
                        if (!empty($e)){
                            foreach($sched as $entry){
                                $s = parse_schedule_entry($entry);
                                if($e['day'] === $s['day']){
                                    if (time_overlap($e['time'], $s['time'])) {
                                        $sql_section = "SELECT subject_title FROM subject
                                        WHERE subject_id = '".escape($db_connect, $all_scheds['subject_id'])."'
                                        ";
                                        if($result_section = call_mysql_query($sql_section)){
                                            if($fetchSection = call_mysql_fetch_array($result_section)){
                                                if (!in_array($fetchSection['subject_title'], $subjects)) {
                                                    array_push($subjects, trim($fetchSection['subject_title']));
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($subjects)) {
                    $output['code'] = 509;
                    $output['msg_response'] = "Conflict detected for the following ".count($subjects)." subject(s) of this section: " . implode(', ', $subjects) . ".";
                    echo json_encode($output);
                    exit();
                }
            }
        }

        
        // 2. fetch schedules to validate room,day,time conflicts
        $sql_sched = "SELECT cs.class_name, tc.schedule, tc.teacher_class_id, tc.class_id, tc.room FROM teacher_class AS tc
        LEFT JOIN class_section AS cs ON tc.class_id = cs.class_id
        WHERE tc.schoolyear_id = '".escape($db_connect, $school_year_id)."'
        AND NOT (teacher_class_id = '".escape($db_connect, $edit_id)."')
        ORDER BY LENGTH(room) ASC
        ";
        
        if($result_sched = call_mysql_query($sql_sched)){
            $class_names = [];
            // looping through each fetched teacher_class data
            while($fetchSched = call_mysql_fetch_array($result_sched)){
                // loop through each existing data
                $room_data = json_decode($fetchSched['room'], true);
                foreach($sched as $entry){
                    // format each existing data as[day=>"", time=>"", room=>""]
                    $e = parse_schedule_entry($entry); 
                    if(!empty($e)){
                        $room_day = $e['room'] . '_' . $e['day'];
                        $newTime = $e['time'];
                        // loop through each entered schedule to check against the stored schedules
                        if(isset($room_data[$room_day])){
                            foreach($room_data[$room_day] as $stored){
                                if(time_overlap($newTime, $stored)){
                                    if(!in_array($fetchSched['class_name'], $class_names)){
                                        array_push($class_names, trim($fetchSched['class_name']));
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($class_names)) {
                $output['code'] = 510;
                $output['msg_response'] = "Conflict detected with following ".count($class_names)." section(s): " . implode(', ', $class_names) . ".";
                echo json_encode($output);
                exit();
            }
        }

        // checks for other data if there is already an existing schedule 
        // for the same section and subject 
        $sql_section = "SELECT class_id FROM teacher_class 
        WHERE class_id = '".escape($db_connect, $section)."' AND 
        subject_id = '".escape($db_connect, $subject_code)."' 
        AND schoolyear_id = '".escape($db_connect, $school_year_id)."'
        AND NOT (teacher_class_id = '".escape($db_connect, $edit_id)."')
        ";
        // 3. Validate input against existing schedules in the database
        if($result_section = call_mysql_query($sql_section)){
            if(call_mysql_num_rows($result_section) !== 0){
                $output['code'] = 511;
                $output['msg_response'] = "Schedule already exists for this section and subject.";
                echo json_encode($output);
                exit();
            }
        }


        // 4. validation agains prof's sched
        $prof_sched = "SELECT
            tc.teacher_id, tc.schedule, tc.class_id, 
            u.f_name, u.m_name, u.l_name, u.suffix,
            cs.class_name
        FROM teacher_class AS tc
        LEFT JOIN users AS u ON tc.teacher_id = u.user_id
        LEFT JOIN class_section AS cs ON tc.class_id = cs.class_id
        WHERE tc.teacher_id = '".escape($db_connect, $prof)."' 
        AND tc.schoolyear_id = '".escape($db_connect, $school_year_id)."'
        AND NOT (tc.class_id = '".escape($db_connect, $section)."')
        ";

        // "AND NOT (tc.class_id = '".escape($db_connect, $section)."')" is the reason the current
        // data being updated is not included in the fetched data
        if($result_prof_sched = call_mysql_query($prof_sched)){
            if(call_mysql_num_rows($result_prof_sched) !== 0){
                $profName = "";
                $class_names = [];
                while($fetchProfSched = call_mysql_fetch_array($result_prof_sched)){
                    foreach ($sched as $entry){
                        $e = parse_schedule_entry($entry);
                        if (!empty($e)){
                            foreach(json_decode($fetchProfSched['schedule']) as $stored){
                                $s = parse_schedule_entry($stored);
                                if($s['day'] === $e['day']){
                                    if (time_overlap($s['time'], $e['time'])) {
                                        if (!in_array($fetchProfSched['class_name'], $class_names)) {
                                            array_push($class_names, trim($fetchProfSched['class_name']));
                                        }
                                        $profName = trim($fetchProfSched['f_name']) . ' ' .
                                                    (empty($fetchProfSched['m_name']) ? '' : trim($fetchProfSched['m_name']) . ' ') .
                                                    trim($fetchProfSched['l_name']) .
                                                    (empty($fetchProfSched['suffix']) ? '' : ' ' . trim($fetchProfSched['suffix']));
                                    }
                                }
                            }
                        }
                    }
                }

                if (!empty($class_names) && !empty($profName)) {
                    $output['code'] = 512;
                    $output['msg_response'] = "Conflict detected with existing schedule of Prof. $profName for section(s): " . implode(', ', $class_names) . ".";
                    echo json_encode($output);
                    exit();
                }
            }
        }

        $sem = "";
        $sql_sy = "SELECT sem FROM school_year 
        WHERE school_year_id = '".escape($db_connect, $school_year_id)."'
        ";
        if($result_sy = call_mysql_query($sql_sy)){
            if($num = call_mysql_num_rows($result_sy) !== 0){
                if($data_sy = call_mysql_fetch_array($result_sy)){
                    $sem = $data_sy['sem'];
                } else {
                    $output['code'] = 404;
                    $output['msg_response'] = "Connection failed";
                    echo json_encode($output);
                    exit();
                }
            } else {
                $output['code'] = 513;
                $output['msg_response'] = "This Fiscal Year is locked. Updating schedule for this Fiscal Year is not allowed.";
                echo json_encode($output);
                exit();
            }
        }

        $sched_encoded = json_encode($sched);
        $encoded_rooms = json_encode($sched_arr, JSON_FORCE_OBJECT);
        array_push($lec_lab, $lec_unit);
        array_push($lec_lab, $lab_unit);
        $encoded_units = json_encode($lec_lab);
        $db_connect->begin_transaction();
        $update_sql = "UPDATE teacher_class SET 
        teacher_id = '"      .escape($db_connect, $prof).      "',
        class_id = '"      .escape($db_connect, $section).      "',
        subject_id = '"      .escape($db_connect, $subject_code).      "',
        subject_text = '"      .escape($db_connect, $subject_title).      "',
        schedule = '"      .escape($db_connect, $sched_encoded).      "',
        sem = '"      .escape($db_connect, $sem).      "',
        schoolyear_id = '"      .escape($db_connect, $school_year_id).      "',
        program_id = '"      .escape($db_connect, $program_id).      "',
        room = '"      .escape($db_connect, $encoded_rooms).      "',
        year_level = '"      .escape($db_connect, $year_level).      "',
        unit = '"      .escape($db_connect, $unit).      "',
        lec_lab = '"      .escape($db_connect, $encoded_units).      "',
        section_limit = '"      .escape($db_connect, $limit).      "',
        total_hours = '"      .escape($db_connect, $totalHours).      "'
        WHERE teacher_class_id = '"      .escape($db_connect, $edit_id).      "'
        ";

        $result = call_mysql_query($update_sql);
        $db_connect->commit(); 

        $output['code'] = 200;
        $output['msg_status'] = true;
        $output['msg_response'] = 'Schedule updated successfully.';
        $output['msg_span'] = '';
        echo json_encode($output);
        exit();
    }

} catch (Throwable $th) {
    $db_connect->rollback();
    $output['code'] = 500;
    $output['msg_response'] = "An unexpected error occurred. If error persists consult your IT.";
    echo json_encode($output);
    exit();
}
?>