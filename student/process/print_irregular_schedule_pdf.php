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

// --- DATA FETCHING ---
$current_sy = get_school_year();
$current_school_year_id = (int)($current_sy['school_year_id'] ?? 0);
$current_sem = $current_sy['sem'] ?? '';
$current_sem_trunc = escape($db_connect, substr($current_sem, 0, 10));
$student_id = $g_general_id ?? '';

$approved_sql = "SELECT enrollment_id FROM enrollment 
                WHERE student_id = '" . escape($db_connect, $student_id) . "' 
                AND schoolyear_id = $current_school_year_id 
                AND sem = '$current_sem_trunc' 
                AND classification = 'IRREGULAR' 
                AND status = 'APPROVED' 
                ORDER BY evaluated_at DESC, updated_at DESC LIMIT 1";
$approved_rows = mysqliquery_return($approved_sql);
if (empty($approved_rows)) exit('No approved request found.');
$request_id = (int)$approved_rows[0]['enrollment_id'];

$reg_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name 
           FROM enrollment_subjects es 
           JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id 
           JOIN subject s ON tc.subject_id = s.subject_id 
           JOIN class_section cs ON tc.class_id = cs.class_id 
           WHERE es.enrollment_id = $request_id";
$regular_subjects = mysqliquery_return($reg_sql);

// --- TIME PROCESSING ---
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
        $roomName = isset($parts[2]) ? trim($parts[2]) : '';

        $start = ie_parse_minutes($times[0]);
        $end = ie_parse_minutes($times[1] ?? '');

        if ($start !== null && $end !== null) {
            $events[] = [
                'day' => ucfirst($dayKey),
                'start' => $start,
                'end' => $end,
                'code' => $subj['subject_code'],
                'title' => $subj['subject_title'],
                'section' => $subj['class_name'],
                'room' => $roomName,
            ];
            $minStart = min($minStart, floor($start/60)*60);
            $maxEnd = max($maxEnd, ceil($end/60)*60);
        }
    }
}

// --- TCPDF SETUP ---
require DOMAIN_PATH . '/call_func/TCPDF/tcpdf.php';
$pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetMargins(10, 10, 10);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetAutoPageBreak(false, 0); 
$pdf->AddPage();
$pdfBaseFont = 'helvetica';

// --- TOP LOGOS ---
// Note: These calls only draw images and do not affect the current cursor position,
// so the existing header and grid layout remain unchanged.
$pageWidth = $pdf->getPageWidth();
$pageMargin = 10; // matches SetMargins left/right
$logoWidth = 22;  // logo display width in mm

$cccLogoPath = DOMAIN_PATH . '/upload/img/ccc-logo.svg';
$enrollmentLogoPath = DOMAIN_PATH . '/upload/img/enrollment-logo.svg';

if (file_exists($cccLogoPath)) {
    // Top-left logo
    $pdf->ImageSVG($cccLogoPath, $pageMargin, 8, $logoWidth, 0);
}

if (file_exists($enrollmentLogoPath)) {
    // Top-right logo
    $rightX = $pageWidth - $pageMargin - $logoWidth;
    $pdf->ImageSVG($enrollmentLogoPath, $rightX, 8, $logoWidth, 0);
}

// Layout Dimensions
$timeColWidth = 20;
$chartWidth = $pdf->getPageWidth() - 20 - $timeColWidth;
$dayWidth = $chartWidth / count($dayOrder);
$hourHeight = 18; 
$headerHeight = 10;
$gridOffset = 2; // THE FIX: Small gap between header and first grid line

// Style Definitions
$lineSolid = array('width' => 0.1, 'color' => array(210, 210, 210));
$lineDashed = array('width' => 0.05, 'color' => array(235, 235, 235), 'dash' => '1,1');

// --- HEADER ---
$pdf->SetFont($pdfBaseFont, 'B', 16);
$pdf->Cell(0, 10, 'CLASS SCHEDULE', 0, 1, 'C');
$pdf->SetFont($pdfBaseFont, '', 9);
$pdf->Cell(0, 5, "SY: {$current_sy['school_year']} | $current_sem", 0, 1, 'C');
$pdf->Ln(5);

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

// --- DRAW GRID (TEAMS STYLE) ---

// 1. Draw Vertical Column Lines (Solid)
$pdf->SetLineStyle($lineSolid);
$totalGridHeight = (($maxEnd - $minStart) / 60) * $hourHeight;

// Draw the first vertical line (The Left Border)
$pdf->Line(10, $gridBodyY - $gridOffset, 10, $gridBodyY + $totalGridHeight);

// Draw the rest of the vertical lines (Separators)
for ($i = 0; $i <= count($dayOrder); $i++) {
    $currentX = 10 + $timeColWidth + ($i * $dayWidth);
    // Note: We start these from the header bottom to keep the "Time" column distinct
    $pdf->Line($currentX, $gridBodyY - $gridOffset, $currentX, $gridBodyY + $totalGridHeight);
}

// 2. Draw Horizontal Row Lines
for ($m = $minStart; $m <= $maxEnd; $m += 30) {
    $isFullHour = ($m % 60 === 0);
    $y = $gridBodyY + (($m - $minStart) / 60) * $hourHeight;
    
    if ($isFullHour) {
        $pdf->SetLineStyle($lineSolid);
        
        // Time Label: Centered on the line
        $pdf->SetFont($pdfBaseFont, 'B', 7);
        $pdf->SetTextColor(120, 120, 120);
        $timeLabel = date('g A', mktime(0, $m)); 
        
        // Nudge text up so the line points to its center
        $pdf->SetXY(10, $y - 1.5); 
        $pdf->Cell($timeColWidth - 2, 3, $timeLabel, 0, 0, 'R');
        
        // Horizontal line starts from the very left edge (X=10) for full hours
        $pdf->Line(10, $y, 10 + $timeColWidth + ($dayWidth * count($dayOrder)), $y);
    } else {
        // 30-minute marks: Dashed and start after the Time column
        $pdf->SetLineStyle($lineDashed);
        $pdf->Line(10 + $timeColWidth, $y, 10 + $timeColWidth + ($dayWidth * count($dayOrder)), $y);
    }
}

// Draw the very bottom border line to close the grid
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

    // Chip Background
    $pdf->SetFillColor(235, 242, 255);
    $pdf->SetLineStyle(array('width' => 0.05, 'color' => array(180, 190, 230), 'dash' => 0));
    $pdf->RoundedRect($x, $y, $w, $h, 1.2, '1111', 'DF');
    
    // Left Accent Stripe
    $pdf->SetFillColor(78, 115, 223);
    $pdf->Rect($x, $y, 1.2, $h, 'F');

    // Text (Subject Code & Details)
    $pdf->SetTextColor(40, 60, 120);
    $pdf->SetXY($x + 2.2, $y + 1.5);
    $pdf->SetFont($pdfBaseFont, 'B', 7);
    $pdf->MultiCell($w - 4, 3, $e['code'], 0, 'L');
    
    $pdf->SetFont($pdfBaseFont, '', 6);
    $pdf->SetX($x + 2.2);
    $pdf->MultiCell($w - 4, 2.5, $e['title'] . "\n[" . $e['section'] . "]", 0, 'L');
}

// --- START PAGE 2: LIST VIEW ---
$pdf->AddPage();
$pdfBaseFont = 'helvetica';

// 1. RE-DRAW LOGOS (Same position as Page 1)
if (file_exists($cccLogoPath)) {
    $pdf->ImageSVG($cccLogoPath, $pageMargin, 8, $logoWidth, 0);
}
if (file_exists($enrollmentLogoPath)) {
    $pdf->ImageSVG($enrollmentLogoPath, $pageWidth - $pageMargin - $logoWidth, 8, $logoWidth, 0);
}

// Header style mirrors the main CLASS SCHEDULE header
$pdf->SetY(20);
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont($pdfBaseFont, 'B', 16);
$pdf->Cell(0, 10, 'DAILY SUBJECT LIST', 0, 1, 'C');
$pdf->SetFont($pdfBaseFont, '', 9);
$pdf->Cell(0, 5, "SY: {$current_sy['school_year']} | $current_sem", 0, 1, 'C');
$pdf->Ln(8);

// 2. TWO-COLUMN LAYOUT PREP
$colWidth = ($pdf->getPageWidth() - 30) / 2; // Split page into two columns with gutter
$startX = 10;
$currentCol = 0; // 0 for Left, 1 for Right
$yStart = $pdf->GetY();
$maxColY = $yStart;

foreach ($dayOrder as $dayName) {
    $dayEvents = array_values(array_filter($events, function($e) use ($dayName) {
        return $e['day'] === $dayName;
    }));

    if (empty($dayEvents)) continue;

    // Ensure AM slots appear before PM by sorting by start minutes
    usort($dayEvents, function($a, $b) {
        return $a['start'] <=> $b['start'];
    });

    // Calculate X position based on current column
    $xPos = $startX + ($currentCol * ($colWidth + 10));
    
    // If the left column was very long, we don't want the right column to start too low
    // But if we are starting a new "row" of two days, we move to the max Y reached
    if ($currentCol == 0) {
        $pdf->SetXY($xPos, $maxColY);
    } else {
        $pdf->SetXY($xPos, $yBeforeDay); // Start right col at same height as left col
    }

    $yBeforeDay = $pdf->GetY();

    // Day Header
    $pdf->SetFillColor(245, 247, 251);
    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont($pdfBaseFont, 'B', 10);
    $pdf->Cell($colWidth, 8, "  " . strtoupper($dayName), 0, 1, 'L', 1);
    $pdf->Ln(1);

    // List Subjects: one row per subject (Time | Code | Subject | Room)
    foreach ($dayEvents as $de) {
        $timeRange = date('g:i A', mktime(0, $de['start'])) . " - " . date('g:i A', mktime(0, $de['end']));

        $pdf->SetX($xPos);
        $pdf->SetFont($pdfBaseFont, 'B', 8);
        $pdf->SetTextColor(78, 115, 223);
        $pdf->Cell(32, 6, $timeRange, 0, 0, 'L');

        $pdf->SetTextColor(40, 40, 40);
        $pdf->Cell(15, 6, $de['code'], 0, 0, 'L');

        $pdf->SetFont($pdfBaseFont, '', 8);
        $roomLabel = isset($de['room']) && $de['room'] !== '' ? $de['room'] : '';
        $roomWidth = 18;
        $remainingWidth = $colWidth - 32 - 15 - $roomWidth;
        $pdf->Cell($remainingWidth, 6, $de['title'] . " [" . $de['section'] . "]", 0, 0, 'L', 0, '', 1);
        $pdf->Cell($roomWidth, 6, $roomLabel, 0, 1, 'L');
    }
    
    $pdf->Ln(4);
    
    // Update the max Y reached so far
    if ($pdf->GetY() > $maxColY) $maxColY = $pdf->GetY();

    // Switch columns
    if ($currentCol == 0) {
        $currentCol = 1;
    } else {
        $currentCol = 0;
        $pdf->Ln(5); // Gap between "rows" of days
    }
}

// 3. FOOTER
$pdf->SetY(-15);
$pdf->SetFont($pdfBaseFont, 'I', 7);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 5, "Generated on: " . date('l, F j, Y | h:i A'), 0, 0, 'R');

$pdf->Output('Irregular_Schedule.pdf', 'I');
