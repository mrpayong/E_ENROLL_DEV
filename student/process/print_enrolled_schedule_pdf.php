<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Only students allowed
if (!isset($g_user_role) || $g_user_role !== 'STUDENT') {
    http_response_code(403);
    exit('Forbidden');
}

// --- DATA FETCHING LOGIC ---
$current_sy = get_school_year();
$current_school_year_id = (int)($current_sy['school_year_id'] ?? 0);
$current_sem = $current_sy['sem'] ?? '';
$current_sem_trunc = escape($db_connect, substr($current_sem, 0, 10));

$student_id = $g_general_id ?? '';
if ($student_id === '' || $current_school_year_id <= 0) {
    http_response_code(400);
    exit('Missing context');
}

// Find active REGULAR enrollment for the current term using the
// unified enrollment table, mirroring the logic in
// student/enrollment_status.php when building the enrolled schedule
// grid.
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
    exit('No active regular enrollment found for current term.');
}
$enrolled_id = (int)$enrolled_rows[0]['enrollment_id'];

// Fetch Subjects & Schedules from unified enrollment_subjects
$reg_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name
            FROM enrollment_subjects es
            JOIN enrollment e ON es.enrollment_id = e.enrollment_id
            JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id
            JOIN subject s ON tc.subject_id = s.subject_id
            JOIN class_section cs ON tc.class_id = cs.class_id
            WHERE es.enrollment_id = $enrolled_id
              AND e.classification = 'REGULAR'
              AND e.status = 'ENROLLED'";
$regular_subjects = mysqliquery_return($reg_sql);

// --- TIME PROCESSING (same logic as irregular schedule) ---
function ie_parse_minutes($timeStr) {
    $ts = strtotime(trim($timeStr));
    return ($ts === false) ? null : (int)date('G', $ts) * 60 + (int)date('i', $ts);
}

$events = [];
$dayOrder = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
$minStart = 480; // 8 AM
$maxEnd = 1080;  // 6 PM

foreach ($regular_subjects as $subj) {
    $decoded = json_decode($subj['schedule'], true) ?: [$subj['schedule']];
    foreach ($decoded as $entry) {
        $parts = explode('::', $entry);
        if (count($parts) < 2) continue;
        $dayKey = strtolower(trim($parts[0]));
        $times = explode('-', $parts[1]);
        
        $start = ie_parse_minutes($times[0]);
        $end = ie_parse_minutes($times[1] ?? '');
        
        if ($start !== null && $end !== null) {
            $events[] = [
                'day' => ucfirst($dayKey),
                'start' => $start,
                'end' => $end,
                'code' => $subj['subject_code'],
                'title' => $subj['subject_title'],
                'section' => $subj['class_name']
            ];
            $minStart = min($minStart, floor($start/60)*60);
            $maxEnd = max($maxEnd, ceil($end/60)*60);
        }
    }
}

// --- TCPDF OUTPUT (copied design from irregular schedule) ---
require DOMAIN_PATH . '/call_func/TCPDF/tcpdf.php';
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0); 
$pdf->AddPage();
$pdfBaseFont = 'helvetica';

// --- TOP LOGOS ---
$pageWidth = $pdf->getPageWidth();
$pageMargin = 10; // matches SetMargins left/right
$logoWidth = 22;  // logo display width in mm
$headerTopY = 15; // common Y position for logos and title

$cccLogoPath = DOMAIN_PATH . '/upload/img/ccc-logo.svg';
$enrollmentLogoPath = DOMAIN_PATH . '/upload/img/enrollment-logo.svg';

if (file_exists($cccLogoPath)) {
    $pdf->ImageSVG($cccLogoPath, $pageMargin, $headerTopY, $logoWidth, 0);
}

if (file_exists($enrollmentLogoPath)) {
    $rightX = $pageWidth - $pageMargin - $logoWidth;
    $pdf->ImageSVG($enrollmentLogoPath, $rightX, $headerTopY, $logoWidth, 0);
}

// Layout Dimensions
$timeColWidth = 20;
$chartWidth = $pdf->getPageWidth() - 20 - $timeColWidth;
$dayWidth = $chartWidth / count($dayOrder);
$hourHeight = 18; 
$headerHeight = 10;
$gridOffset = 2;

// Style Definitions
$lineSolid = array('width' => 0.1, 'color' => array(210, 210, 210));
$lineDashed = array('width' => 0.05, 'color' => array(235, 235, 235), 'dash' => '1,1');

// --- HEADER ---
$pdf->SetY($headerTopY);
$pdf->SetFont($pdfBaseFont, 'B', 16);
$pdf->Cell(0, 10, 'DAILY CLASS SCHEDULE', 0, 1, 'C');
$pdf->SetFont($pdfBaseFont, '', 9);
$pdf->Cell(0, 5, "SY: {$current_sy['school_year']} | $current_sem", 0, 1, 'C');
$pdf->Ln(10);

$gridTopY = $pdf->GetY();

// Headers Row
$pdf->SetFillColor(250, 250, 250);
$pdf->SetTextColor(78, 115, 223);
$pdf->SetFont($pdfBaseFont, 'B', 8);
$pdf->SetLineStyle($lineSolid);
$pdf->Cell($timeColWidth, $headerHeight, 'TIME', 1, 0, 'C', 1);
foreach ($dayOrder as $day) {
    $pdf->Cell($dayWidth, $headerHeight, strtoupper($day), 1, 0, 'C', 1);
}

// Set the baseline for where the actual grid (lines/chips) starts
$gridBodyY = $gridTopY + $headerHeight + $gridOffset;

// --- DRAW GRID ---
$pdf->SetLineStyle($lineSolid);
$totalGridHeight = (($maxEnd - $minStart) / 60) * $hourHeight;

// Left border
$pdf->Line(10, $gridBodyY - $gridOffset, 10, $gridBodyY + $totalGridHeight);

// Vertical separators
for ($i = 0; $i <= count($dayOrder); $i++) {
    $currentX = 10 + $timeColWidth + ($i * $dayWidth);
    $pdf->Line($currentX, $gridBodyY - $gridOffset, $currentX, $gridBodyY + $totalGridHeight);
}

// Horizontal lines
for ($m = $minStart; $m <= $maxEnd; $m += 30) {
    $isFullHour = ($m % 60 === 0);
    $y = $gridBodyY + (($m - $minStart) / 60) * $hourHeight;
    
    if ($isFullHour) {
        $pdf->SetLineStyle($lineSolid);
        $pdf->SetFont($pdfBaseFont, 'B', 7);
        $pdf->SetTextColor(120, 120, 120);
        $timeLabel = date('g A', mktime(0, $m)); 
        $pdf->SetXY(10, $y - 1.5); 
        $pdf->Cell($timeColWidth - 2, 3, $timeLabel, 0, 0, 'R');
        $pdf->Line(10, $y, 10 + $timeColWidth + ($dayWidth * count($dayOrder)), $y);
    } else {
        $pdf->SetLineStyle($lineDashed);
        $pdf->Line(10 + $timeColWidth, $y, 10 + $timeColWidth + ($dayWidth * count($dayOrder)), $y);
    }
}

// Bottom border
$pdf->SetLineStyle($lineSolid);
$pdf->Line(10, $gridBodyY + $totalGridHeight, 10 + $timeColWidth + ($dayWidth * count($dayOrder)), $gridBodyY + $totalGridHeight);

// --- DRAW EVENTS (CHIPS) ---
$pdf->setPage(1); 
foreach ($events as $e) {
    $dayIdx = array_search($e['day'], $dayOrder);
    if ($dayIdx === false) continue;

    $x = 10 + $timeColWidth + ($dayIdx * $dayWidth) + 1;
    $y = $gridBodyY + (($e['start'] - $minStart) / 60) * $hourHeight + 0.5;
    $w = $dayWidth - 2;
    $h = (($e['end'] - $e['start']) / 60) * $hourHeight - 1;

    $pdf->SetFillColor(235, 242, 255);
    $pdf->SetLineStyle(array('width' => 0.05, 'color' => array(180, 190, 230), 'dash' => 0));
    $pdf->RoundedRect($x, $y, $w, $h, 1.2, '1111', 'DF');
    
    $pdf->SetFillColor(78, 115, 223);
    $pdf->Rect($x, $y, 1.2, $h, 'F');

    $pdf->SetTextColor(40, 60, 120);
    $contentHeight = 8; 
    $verticalGap = ($h - $contentHeight) / 2;
    if ($verticalGap < 1) $verticalGap = 1.5;

    $pdf->SetXY($x + 1.2, $y + $verticalGap);
    $pdf->SetFont($pdfBaseFont, 'B', 7);
    $pdf->MultiCell($w - 1.2, 3, $e['code'], 0, 'C');
    
    $pdf->SetFont($pdfBaseFont, '', 6);
    $pdf->SetX($x + 1.2);
    $pdf->MultiCell($w - 1.2, 2.5, $e['title'] . "\n[" . $e['section'] . "]", 0, 'C');
}

// --- FOOTER (BELOW THE GRID) ---
$footerY = $gridBodyY + $totalGridHeight + 5;
$pdf->SetY($footerY); 

$pdf->SetFont($pdfBaseFont, 'I', 7);
$pdf->SetTextColor(150, 150, 150);
$genDateTime = "Generated on: " . date('l, F j, Y | h:i A');
$pdf->Cell(10 + $timeColWidth + ($dayWidth * count($dayOrder)) - 10, 5, $genDateTime, 0, 0, 'R');

// --- PAGE 2: LIST VIEW (same UI as irregular) ---
$pdf->AddPage();
$pdfBaseFont = 'helvetica';

if (file_exists($cccLogoPath)) {
    $pdf->ImageSVG($cccLogoPath, $pageMargin, $headerTopY, $logoWidth, 0);
}
if (file_exists($enrollmentLogoPath)) {
    $pdf->ImageSVG($enrollmentLogoPath, $pageWidth - $pageMargin - $logoWidth, $headerTopY, $logoWidth, 0);
}

$pdf->SetY($headerTopY);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont($pdfBaseFont, 'B', 16);
$pdf->Cell(0, 10, 'DAILY CLASS SCHEDULE', 0, 1, 'C');
$pdf->SetFont($pdfBaseFont, '', 9);
$pdf->Cell(0, 5, "SY: {$current_sy['school_year']} | $current_sem", 0, 1, 'C');
$pdf->Ln(10);

$colWidth = ($pdf->getPageWidth() - 30) / 2;
$startX = 10;
$currentCol = 0;
$yStart = $pdf->GetY();
$maxColY = $yStart;

foreach ($dayOrder as $dayName) {
    $dayEvents = array_values(array_filter($events, function($e) use ($dayName) {
        return $e['day'] === $dayName;
    }));

    if (empty($dayEvents)) continue;

    // Sort by start time so AM slots come before PM
    usort($dayEvents, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    $xPos = $startX + ($currentCol * ($colWidth + 10));
    if ($currentCol == 0) {
        $pdf->SetXY($xPos, $maxColY);
    } else {
        $pdf->SetXY($xPos, $yBeforeDay);
    }

    $yBeforeDay = $pdf->GetY();

    $pdf->SetFillColor(245, 247, 251);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont($pdfBaseFont, 'B', 10);
    $pdf->Cell($colWidth, 8, "  " . strtoupper($dayName), 0, 1, 'L', 1);
    $pdf->Ln(1);

    foreach ($dayEvents as $de) {
        $timeRange = date('g:i A', mktime(0, $de['start'])) . " - " . date('g:i A', mktime(0, $de['end']));
        
        $pdf->SetX($xPos);
        $pdf->SetFont($pdfBaseFont, 'B', 8);
        $pdf->SetTextColor(78, 115, 223);
        $pdf->Cell(32, 6, $timeRange, 0, 0, 'L');
        
        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(15, 6, $de['code'], 0, 0, 'L');
        
        $pdf->SetFont($pdfBaseFont, '', 8);
        $remainingWidth = $colWidth - 32 - 15;
        $pdf->Cell($remainingWidth, 6, $de['title'] . " [" . $de['section'] . "]", 0, 1, 'L', 0, '', 1);
    }
    
    $pdf->Ln(4);
    if ($pdf->GetY() > $maxColY) $maxColY = $pdf->GetY();

    if ($currentCol == 0) {
        $currentCol = 1;
    } else {
        $currentCol = 0;
        $pdf->Ln(5);
    }
}

$pdf->SetY(-15);
$pdf->SetFont($pdfBaseFont, 'I', 7);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, "Generated on: " . date('l, F j, Y | h:i A'), 0, 0, 'R');

$pdf->Output('Enrolled_Schedule_' . $student_id . '.pdf', 'I');
exit;