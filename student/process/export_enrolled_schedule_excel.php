<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!isset($g_user_role) || $g_user_role !== 'STUDENT') {
    http_response_code(403);
    exit('Forbidden');
}

// --- DATA FETCHING (mirrors enrolled schedule grid logic) ---
$current_sy = get_school_year();
$current_school_year_id = (int)($current_sy['school_year_id'] ?? 0);
$current_sem = $current_sy['sem'] ?? '';
$current_sem_trunc = escape($db_connect, substr($current_sem, 0, 10));
$student_id = $g_general_id ?? '';

if ($student_id === '' || $current_school_year_id <= 0) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Missing context for enrolled schedule export.';
    exit;
}

$enrolled_sql = "SELECT enrollment_id FROM enrollment 
                WHERE student_id = '" . escape($db_connect, $student_id) . "' 
                AND schoolyear_id = $current_school_year_id 
                AND sem = '$current_sem_trunc' 
                AND classification = 'REGULAR' 
                AND status = 'ENROLLED' 
                ORDER BY created_at DESC 
                LIMIT 1";
$enrolled_rows = mysqliquery_return($enrolled_sql);
if (empty($enrolled_rows)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No active regular enrollment found for the current school year and semester.';
    exit;
}
$enrolled_id = (int)$enrolled_rows[0]['enrollment_id'];

$reg_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name 
           FROM enrollment_subjects es 
           JOIN enrollment e ON es.enrollment_id = e.enrollment_id 
           JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id 
           JOIN subject s ON tc.subject_id = s.subject_id 
           JOIN class_section cs ON tc.class_id = cs.class_id 
           WHERE es.enrollment_id = $enrolled_id 
             AND e.classification = 'REGULAR' 
             AND e.status = 'ENROLLED'";
$rows = mysqliquery_return($reg_sql);

// --- SCHEDULE PARSING ---
function ee_parse_time_str($timeStr)
{
    $ts = strtotime(trim($timeStr));
    if ($ts === false) {
        return [null, null, null];
    }
    $minutes = (int)date('G', $ts) * 60 + (int)date('i', $ts);
    $time24 = date('H:i', $ts);
    $time12 = date('g:i A', $ts);
    return [$minutes, $time24, $time12];
}

$exportRows = [];

foreach ($rows as $subj) {
    $scheduleRaw = $subj['schedule'] ?? '';
    if ($scheduleRaw === '') {
        continue;
    }

    $decoded = json_decode($scheduleRaw, true);
    if (!is_array($decoded)) {
        $decoded = [$scheduleRaw];
    }

    foreach ($decoded as $entry) {
        if (!is_string($entry) || trim($entry) === '') {
            continue;
        }
        $parts = explode('::', $entry);
        if (count($parts) < 2) {
            continue;
        }

        $dayLabel = trim($parts[0]);
        $timeRangeStr = trim($parts[1]);
        $roomName = isset($parts[2]) ? trim($parts[2]) : '';

        $times = explode('-', $timeRangeStr);
        $startStr = $times[0] ?? '';
        $endStr = $times[1] ?? '';

        list($startMinutes, $start24, $start12) = ee_parse_time_str($startStr);
        list($endMinutes, $end24, $end12) = ee_parse_time_str($endStr);

        if ($startMinutes === null || $endMinutes === null) {
            continue;
        }

        $exportRows[] = [
            'day'            => $dayLabel,
            'start_minutes'  => $startMinutes,
            'end_minutes'    => $endMinutes,
            'start_24'       => $start24,
            'end_24'         => $end24,
            'start_12'       => $start12,
            'end_12'         => $end12,
            'subject_code'   => $subj['subject_code'],
            'subject_title'  => $subj['subject_title'],
            'section'        => $subj['class_name'],
            'room'           => $roomName,
        ];
    }
}

if (empty($exportRows)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'No schedule entries found for your enrolled subjects.';
    exit;
}

// --- SORT BY DAY THEN TIME ---
$dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$dayIndex = array_flip($dayOrder);

usort($exportRows, function ($a, $b) use ($dayIndex) {
    $dayA = $dayIndex[$a['day']] ?? 999;
    $dayB = $dayIndex[$b['day']] ?? 999;
    if ($dayA === $dayB) {
        return $a['start_minutes'] <=> $b['start_minutes'];
    }
    return $dayA <=> $dayB;
});

// --- OUTPUT CSV (Excel-friendly) ---
$filename = 'Enrolled_Schedule_' . ($student_id !== '' ? $student_id . '_' : '') . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

fputcsv($out, [
    'School Year',
    'Semester',
    'Day',
    'Time Range (12H)',
    'Time Start (24H)',
    'Time End (24H)',
    'Subject Code',
    'Subject Title',
    'Section',
    'Room',
]);

foreach ($exportRows as $row) {
    $timeRange12 = $row['start_12'] . ' - ' . $row['end_12'];

    fputcsv($out, [
        $current_sy['school_year'] ?? '',
        $current_sem,
        $row['day'],
        $timeRange12,
        $row['start_24'],
        $row['end_24'],
        $row['subject_code'],
        $row['subject_title'],
        $row['section'],
        $row['room'],
    ]);
}

fclose($out);
exit;
