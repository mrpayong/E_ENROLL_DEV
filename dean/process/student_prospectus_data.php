<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');

// Allow only authorized roles (same or stricter than dean page)
if (!in_array($g_user_role, ['DEAN', 'ADMIN', 'REGISTRAR'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access.'
    ]);
    exit();
}

$student_id = trim($_POST['student_id'] ?? '');

if ($student_id === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Missing student ID.'
    ]);
    exit();
}

$student_id_for_query = escape($db_connect, $student_id);

// --------------------
// Resolve student's program and curriculum/prospectus
// (mirrors student/grades.php logic so the Dean sees the
//  same 1st-4th year layout and "Units to be Earned" value)
// --------------------

$student_sql = "
        SELECT s.program_id, s.curriculum_id
        FROM student s
        WHERE s.student_id_no = '$student_id_for_query'
        LIMIT 1
    ";

$student_rows = mysqliquery_return($student_sql);
$student_program_id = 0;
$student_curriculum_id = 0;

if (!empty($student_rows)) {
    $student_row = $student_rows[0];
    $student_program_id = (int)($student_row['program_id'] ?? 0);
    $student_curriculum_id = (int)($student_row['curriculum_id'] ?? 0);
}

// If curriculum_id is not set on the student, fall back to the
// default curriculum for the program (status_allowable = 0).
if ($student_curriculum_id <= 0 && $student_program_id > 0) {
    $curr_rows = mysqliquery_return(
        "SELECT curriculum_id FROM curriculum_master WHERE program_id = " . (int)$student_program_id . " AND status_allowable = 0 LIMIT 1"
    );
    if (!empty($curr_rows)) {
        $student_curriculum_id = (int)($curr_rows[0]['curriculum_id'] ?? 0);
    }
}

// --------------------
// Pull all final_grade rows (summary + overlay on curriculum)
// --------------------

$sql = "
    SELECT 
        subject_code,
        course_desc,
        converted_grade,
        completion,
        units,
        school_year,
        sem,
        yr_level,
        remarks
    FROM final_grade
    WHERE student_id_text = '$student_id_for_query'
    ORDER BY yr_level ASC, sem ASC, subject_code ASC
";

$final_rows = mysqliquery_return($sql);
$total_units_earned = 0;
$grades_by_subject = [];

if (!empty($final_rows)) {
    foreach ($final_rows as $row) {
        $unit_val = (int)($row['units'] ?? 0);
        $total_units_earned += $unit_val;

        $code = trim($row['subject_code'] ?? '');
        if ($code !== '') {
            // Last occurrence wins (sufficient for overlay)
            $grades_by_subject[$code] = $row;
        }
    }
}

// --------------------
// Build prospectus structure from curriculum 1st-4th year,
// overlaying any existing grades (same as grades.php)
// --------------------

$prospectus_data = [];
$curriculum_units_total = 0;

if ($student_curriculum_id > 0) {
    $curriculum_sql = "
            SELECT curriculum_id, subject_code, subject_title, unit, pre_req, semester, year_level
            FROM curriculum
            WHERE curriculum_id = " . (int)$student_curriculum_id . "
            ORDER BY year_level ASC, semester ASC, subject_code ASC
        ";

    $curriculum_rows = mysqliquery_return($curriculum_sql);

    if (!empty($curriculum_rows)) {
        foreach ($curriculum_rows as $c_row) {
            $year_level_int = (int)($c_row['year_level'] ?? 0);
            switch ($year_level_int) {
                case 1:
                    $year_level = '1st Year';
                    break;
                case 2:
                    $year_level = '2nd Year';
                    break;
                case 3:
                    $year_level = '3rd Year';
                    break;
                case 4:
                    $year_level = '4th Year';
                    break;
                default:
                    $year_level = $year_level_int > 0 ? ($year_level_int . ' Year') : 'UNSPECIFIED YEAR LEVEL';
                    break;
            }

            $sem_label = trim($c_row['semester'] ?? '');
            if ($sem_label === '') {
                $sem_label = 'UNSPECIFIED SEMESTER';
            }

            $unit_val = (int)($c_row['unit'] ?? 0);
            $code = trim($c_row['subject_code'] ?? '');

            if (!isset($prospectus_data[$year_level])) {
                $prospectus_data[$year_level] = [];
            }
            if (!isset($prospectus_data[$year_level][$sem_label])) {
                $prospectus_data[$year_level][$sem_label] = [
                    'subjects' => [],
                    'total_units' => 0,
                ];
            }

            $grade_row = ($code !== '' && isset($grades_by_subject[$code]))
                ? $grades_by_subject[$code]
                : null;

            // Determine if the subject is already passed based on
            // final_grade remarks / converted_grade (same logic
            // used elsewhere in enrollment helpers).
            $is_passed = false;
            if ($grade_row) {
                $remarks = isset($grade_row['remarks']) ? strtolower(trim((string)$grade_row['remarks'])) : '';
                if ($remarks !== '') {
                    if (strpos($remarks, 'pass') !== false || $remarks === 'p') {
                        $is_passed = true;
                    } elseif (strpos($remarks, 'fail') !== false || $remarks === 'f') {
                        $is_passed = false;
                    }
                }
                if (!$is_passed) {
                    $conv = isset($grade_row['converted_grade']) ? trim((string)$grade_row['converted_grade']) : '';
                    if ($conv !== '') {
                        $val = (float)$conv;
                        if ($val > 0 && $val <= 3.0) {
                            $is_passed = true;
                        }
                    }
                }
            }

            $prospectus_data[$year_level][$sem_label]['subjects'][] = [
                'subject_code'    => $code,
                'course_desc'     => $c_row['subject_title'] ?? '',
                'converted_grade' => $grade_row['converted_grade'] ?? '',
                'completion'      => $grade_row['completion'] ?? '',
                'units'           => $unit_val,
                'is_passed'       => $is_passed ? 1 : 0,
            ];

            $prospectus_data[$year_level][$sem_label]['total_units'] += $unit_val;

            // Count all curriculum units (regardless of grade) for Units to be Earned
            $curriculum_units_total += $unit_val;
        }
    }
}

$units_to_be_earned = $curriculum_units_total > 0 ? $curriculum_units_total : null;

echo json_encode([
    'success' => true,
    'prospectus' => $prospectus_data,
    'total_units_earned' => $total_units_earned,
    'units_to_be_earned' => $units_to_be_earned,
]);
