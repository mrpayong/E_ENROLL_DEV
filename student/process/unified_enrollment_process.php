<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

header('Content-Type: application/json');

// Only students allowed
if ($g_user_role !== 'STUDENT') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'fetch_subjects';

switch ($action) {
    case 'fetch_subjects':
        echo json_encode(fetch_subjects_for_enrollment($db_connect, $g_general_id));
        break;
    case 'fetch_backlog_subjects':
        echo json_encode(fetch_backlog_subjects_for_enrollment($db_connect, $g_general_id));
        break;
    case 'save_enrollment':
        echo json_encode(save_enrollment($db_connect, $g_general_id));
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
exit();

// ------------------- Enrollment -------------------
function save_enrollment($db, $student_id)
{
    if (!$student_id) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Missing student identifier.'];
    }

    $sy = get_school_year();
    $sy_id  = (int)($sy['school_year_id'] ?? 0);
    $sem    = $sy['sem'] ?? '';
    if (!$sy_id || !$sem) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Active school year/semester not found.'];
    }

    // Only allow new enrollment actions when the selected term is
    // marked as open (flag_used = 1). When a past or future fiscal
    // year is chosen via the global selector (flag_used = 0), the
    // student should be able to view records only.
    $flag_used = isset($sy['flag_used']) ? (int)$sy['flag_used'] : 0;
    if ($flag_used !== 1) {
        http_response_code(400);
        return [
            'success' => false,
            'message' => 'Enrollment is closed for this school year and semester. You can only view records for this term.',
        ];
    }

    // sanitize inputs
    $program_id = (int)trim($_POST['program_id'] ?? 0);
    $class_id   = (int)trim($_POST['class_id'] ?? 0);
    $subjects_json = trim($_POST['subjects'] ?? '');

    if (!$program_id || !$subjects_json) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Program and subjects are required.'];
    }

    $subjects = json_decode($subjects_json, true);
    if (!is_array($subjects) || empty($subjects)) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Invalid subjects payload.'];
    }

    // remove duplicate teacher_class_id
    $seen = [];
    $subjects = array_filter($subjects, function($s) use (&$seen) {
        $tcid = (int)($s['teacher_class_id'] ?? 0);
        if (!$tcid || isset($seen[$tcid])) return false;
        $seen[$tcid] = true;
        return true;
    });
    if (empty($subjects)) {
        http_response_code(400);
        return ['success' => false, 'message' => 'No valid subjects to enroll.'];
    }

    // prevent selecting multiple sections of the same subject code
    $code_seen = [];
    foreach ($subjects as $s) {
        $code = trim((string)($s['subject_code'] ?? ''));
        if ($code === '') continue;
        if (isset($code_seen[$code])) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => 'Subject "' . $code . '" is selected more than once. Please keep only one section per subject.',
            ];
        }
        $code_seen[$code] = true;
    }

    // Student info (student_id_no stores the external student ID)
    $stud_row = mysqliquery_return("SELECT student_id_no, year_level, program_id FROM student WHERE student_id_no = '" . escape($db, $student_id) . "' LIMIT 1")[0] ?? [];
    $stud_id_text = $stud_row['student_id_no'] ?? $student_id;
    $stud_year_level = (int)($stud_row['year_level'] ?? 0);
    $stud_program_id = (int)($stud_row['program_id'] ?? 0);

    // Determine backlog subject codes once so they can be used both for
    // section selection and irregular balancing.
    $backlog_codes = [];
    if ($stud_program_id > 0 && $stud_year_level > 0) {
        $backlog_codes = list_backlog_subject_codes($db, $stud_id_text, $stud_program_id, $stud_year_level, $sem);
    }

    // If no section is explicitly provided (new subject-first flow),
    // choose the section whose offerings best match the selected
    // (non-backlog) subjects and use it as the base class_id.
    if ($class_id <= 0) {
        $auto_class_id = determine_best_class_for_selection($db, $subjects, $backlog_codes);
        if ($auto_class_id > 0) {
            $class_id = $auto_class_id;
        }
    }

    if ($class_id <= 0) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Unable to determine a suitable section for the selected subjects.'];
    }

    // Total units (will be recomputed later if any subjects are auto-dropped).
    $total_units = 0;
    foreach ($subjects as $s) $total_units += max(0, (float)($s['unit'] ?? 0));

    // Already passed subjects
    $already_passed = [];
    foreach ($subjects as $s) {
        $code = trim((string)($s['subject_code'] ?? ''));
        if ($code === '') continue;
        if (is_subject_passed_by_student($db, $stud_id_text, $code)) $already_passed[] = $code;
    }
    if ($already_passed) {
        http_response_code(400);
        return ['success' => false, 'message' => 'Cannot re-enroll in already passed subjects: ' . implode(', ', array_unique($already_passed))];
    }

    // ------------------- Schedule Conflict Validation -------------------
    // Before creating either a regular enrollment or an irregular
    // request, ensure that the selected subjects do not contain any
    // overlapping schedules on the same day.
    $schedule_conflict = find_schedule_conflict_for_subjects($db, $subjects);
    if ($schedule_conflict) {
        http_response_code(400);
        return [
            'success' => false,
            'message' => $schedule_conflict['message'],
        ];
    }

    // ------------------- Determine Academic Status -------------------
    // Use the same curriculum-based logic as the Enrollment Status page:
    // a student is REGULAR only if all required previous-term subjects
    // in the default curriculum are already passed. Otherwise IRREGULAR.
    $status_label = determine_academic_status_from_curriculum(
        $db,
        $stud_id_text,
        $stud_program_id,
        $stud_year_level,
        $sem
    );

    $academic_status = strtoupper($status_label === 'Regular' ? 'REGULAR' : 'IRREGULAR');

    // Required units for this student's load.
    // - REGULAR: use current-term curriculum units not yet passed.
    // - IRREGULAR: use specialized logic that can look at the
    //   previous term when there are backlogs (see helper below).
    if ($academic_status === 'REGULAR') {
        $required_units = compute_required_units_for_term($db, $stud_id_text, $stud_program_id, $stud_year_level, $sem);
    } else {
        $required_units = compute_required_units_for_irregular($db, $stud_id_text, $stud_program_id, $stud_year_level, $sem);
    }

    // ------------------- Idempotency: existing enrollment / request -------------------
    // If an active enrollment (REGULAR) or an active irregular request already exists
    // for this student and term, treat repeated submissions as a no-op instead of
    // creating duplicates. This covers page refreshes and multiple button clicks
    // after the first successful submission has committed.
    $sem_trunc = substr((string)$sem, 0, 10);
    if ($academic_status === 'IRREGULAR') {
        // In the unified schema, irregular requests live in `enrollment`
        // with classification = 'IRREGULAR' and status in (PENDING, APPROVED)
        $existing_irreg = mysqliquery_return(
            "SELECT enrollment_id FROM enrollment " .
            "WHERE student_id = '" . escape($db, $stud_id_text) . "' " .
            "AND schoolyear_id = " . (int)$sy_id . " " .
            "AND sem = '" . escape($db, $sem_trunc) . "' " .
            "AND classification = 'IRREGULAR' " .
            "AND status IN ('PENDING','APPROVED') " .
            "LIMIT 1"
        );

        if (!empty($existing_irreg)) {
            return [
                'success' => true,
                'mode' => 'irregular',
                'message' => 'You already have an active irregular enrollment request for this term.',
                'auto_dropped_subject_codes' => [],
            ];
        }
    } else {
        // Regular enrollments in the unified schema are rows with
        // classification = 'REGULAR' and status = 'ENROLLED'
        $existing_enrolled = mysqliquery_return(
            "SELECT enrollment_id FROM enrollment " .
            "WHERE student_id = '" . escape($db, $stud_id_text) . "' " .
            "AND schoolyear_id = " . (int)$sy_id . " " .
            "AND sem = '" . escape($db, $sem_trunc) . "' " .
            "AND classification = 'REGULAR' " .
            "AND status = 'ENROLLED' " .
            "LIMIT 1"
        );

        if (!empty($existing_enrolled)) {
            return [
                'success' => true,
                'mode' => 'regular',
                'message' => 'You are already enrolled for this term.',
                'auto_dropped_subject_codes' => [],
            ];
        }
    }

    // At this point there is no existing active record yet; enforce unit rules.
    if ($academic_status === 'REGULAR' && $required_units !== null && abs($total_units - $required_units) > 0.0001) {
        http_response_code(400);
        return ['success' => false, 'message' => "Total units ($total_units) do not match required ($required_units) for this term."];
    }

    // For IRREGULAR students: if there are backlog subjects (from
    // previous terms, not yet passed) that ARE offered in the current
    // term, then the student must include one offering for each of
    // those backlog subject codes in their cart. This prevents them
    // from skipping available backlogs by enrolling only in a block
    // section that does not list them.
    $auto_dropped_subject_codes = [];

    if ($academic_status === 'IRREGULAR') {
        $backlog_offerings = build_backlog_teacher_classes(
            $db,
            $stud_id_text,
            $stud_program_id,
            $stud_year_level,
            $sem,
            $sy_id
        );

        if (!empty($backlog_offerings)) {
            $offered_codes = [];
            foreach ($backlog_offerings as $row) {
                $code = trim((string)($row['subject_code'] ?? ''));
                if ($code === '') continue;
                $offered_codes[$code] = true;
            }

            if (!empty($offered_codes)) {
                $selected_codes = [];
                foreach ($subjects as $s) {
                    $code = trim((string)($s['subject_code'] ?? ''));
                    if ($code === '') continue;
                    if (isset($offered_codes[$code])) {
                        $selected_codes[$code] = true;
                    }
                }

                // Previously, missing backlog codes caused the
                // enrollment to be rejected. The UI now treats this
                // as a guidance-only warning, so we no longer block
                // the request here; the Dean/Registrar can handle any
                // special cases during review.
                $missing_codes = array_diff(array_keys($offered_codes), array_keys($selected_codes));
            }
        }
    }

    // For IRREGULAR students, enforce that the final unit load for the
    // term does not underload or overload the curriculum-based
    // requirement (except when required_units is unknown/null). Any
    // exception cases such as graduating students can be handled by the
    // Dean/Registrar through manual adjustments.
    if ($academic_status === 'IRREGULAR' && $required_units !== null && $required_units > 0) {
        if ($total_units > $required_units + 0.0001) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => "Total units ($total_units) exceed the allowed load ($required_units) for this term. Please remove some subjects or consult the Dean.",
            ];
        }
        if ($total_units + 0.0001 < $required_units) {
            http_response_code(400);
            return [
                'success' => false,
                'message' => "Total units ($total_units) are below the required load ($required_units) for this term. Please add more subjects or consult the Dean.",
            ];
        }
    }

    // Prevent multiple clicks (if APCu is available). If APCu is not
    // installed, skip this soft lock and rely on DB transaction safety.
    $lock_key = "enroll_{$stud_id_text}_{$sy_id}_{$sem}";
    $useApcuLock = function_exists('apcu_fetch') && function_exists('apcu_store') && function_exists('apcu_delete');

    if ($useApcuLock && apcu_fetch($lock_key)) {
        http_response_code(429);
        return ['success' => false, 'message' => 'Enrollment already being processed. Please wait.'];
    }
    if ($useApcuLock) {
        $lock_time = min(30, count($subjects) * 2); // dynamic lock
        apcu_store($lock_key, true, $lock_time);
    }

    if (!mysqli_begin_transaction($db)) {
        if ($useApcuLock) {
            apcu_delete($lock_key);
        }
        http_response_code(500);
        return ['success' => false, 'message' => 'Unable to start transaction.'];
    }

    // Unified enrollment table:
    // - REGULAR rows represent final enrolled records (status = ENROLLED)
    // - IRREGULAR rows represent irregular requests (status = PENDING at creation time)
    $table = 'enrollment';
    $cols  = "(student_id, program_id, class_id, schoolyear_id, sem, classification, status, source_type, created_at, updated_at)";

    if ($academic_status === 'IRREGULAR') {
        // New irregular request: classification=IRREGULAR, status=PENDING, source_type=STUDENT
        $vals = "('$stud_id_text', $program_id, $class_id, $sy_id, '$sem', 'IRREGULAR', 'PENDING', 'STUDENT', NOW(), NOW())";
    } else {
        // Regular enrollment: classification=REGULAR, status=ENROLLED, source_type=STUDENT
        $vals = "('$stud_id_text', $program_id, $class_id, $sy_id, '$sem', 'REGULAR', 'ENROLLED', 'STUDENT', NOW(), NOW())";
    }

    if (!mysqli_query($db, "INSERT INTO $table $cols VALUES $vals")) {
        $err = mysqli_errno($db);
        mysqli_rollback($db);
        if ($useApcuLock) {
            apcu_delete($lock_key);
        }

        // For irregular requests, a duplicate-key error against the unique
        // uq_enrollment_active_per_term index (student_id, schoolyear_id,
        // sem, classification, active_flag) means another concurrent
        // request already created the active record. Treat this as an
        // idempotent success instead of a hard failure.
        if ($academic_status === 'IRREGULAR' && $err === 1062) {
            return [
                'success' => true,
                'mode' => 'irregular',
                'message' => 'You already have an active irregular enrollment request for this term.',
                'auto_dropped_subject_codes' => [],
            ];
        }

        // For regular enrollments, a duplicate-key error against the same
        // uq_enrollment_active_per_term index likewise means an active
        // REGULAR enrollment already exists for this term. Treat it as a
        // successful no-op so the student simply sees that they are
        // already enrolled instead of an error.
        if ($academic_status === 'REGULAR' && $err === 1062) {
            return [
                'success' => true,
                'mode' => 'regular',
                'message' => 'You are already enrolled for this term.',
                'auto_dropped_subject_codes' => [],
            ];
        }

        http_response_code(500);
        return ['success' => false, 'message' => 'Failed to save enrollment.'];
    }
    $main_id = (int)mysqli_insert_id($db);

    // ------------------- Build subject set for irregular backlog logic -------------------
    // We now trust the student's cart selection for both block and
    // backlog subjects. Whatever teacher_class_id entries are sent
    // from the UI are what get stored and capacity-checked.
    $subjects_for_capacity = $subjects;
    $subjects_for_insert   = $subjects;

    // ------------------- Capacity Check -------------------
    $capacity_check = check_capacity($db, $student_id, $class_id, $subjects_for_capacity, $sy_id, $sem);
    if (!$capacity_check['success']) {
        mysqli_rollback($db);
        if ($useApcuLock) {
            apcu_delete($lock_key);
        }
        http_response_code(409);
        return $capacity_check;
    }

    // ------------------- Insert Subjects -------------------
    if (!insert_subjects($db, $main_id, $stud_id_text, $subjects_for_insert, $academic_status)) {
        mysqli_rollback($db);
        if ($useApcuLock) {
            apcu_delete($lock_key);
        }
        http_response_code(500);
        return ['success' => false, 'message' => 'Failed to save enrollment subjects.'];
    }

    // ------------------- Log enrollment -------------------
    $action_payload = 'ENROLL: ' . json_encode($subjects_for_insert);
    $session_id_val = session_id();
    $user_level_val = $g_user_role ?? 'STUDENT';
    $system_id_val  = 0;

    mysqli_query($db, "INSERT INTO activity_log 
        (user_id, date_log, action, session_id, user_level, system_id)
        VALUES (
            '".escape($db,$stud_id_text)."',
            NOW(),
            '".escape($db,$action_payload)."',
            '".escape($db,$session_id_val)."',
            '".escape($db,$user_level_val)."',
            $system_id_val
        )
    ");

    // Commit the transaction so that the main enrollment and subjects
    // are actually persisted. Without an explicit COMMIT, MySQL will
    // roll back the transaction when the connection closes.
    if (!mysqli_commit($db)) {
        mysqli_rollback($db);
        if ($useApcuLock) {
            apcu_delete($lock_key);
        }
        http_response_code(500);
        return ['success' => false, 'message' => 'Failed to finalize enrollment transaction.'];
    }

    if ($useApcuLock) {
        apcu_delete($lock_key);
    }

    return [
        'success' => true,
        'mode' => strtolower($academic_status),
        'message' => $academic_status === 'IRREGULAR'
            ? 'Your enrollment request has been submitted for Dean evaluation.'
            : 'You have been successfully enrolled.',
        'auto_dropped_subject_codes' => array_values(array_unique($auto_dropped_subject_codes)),
    ];
}


// ------------------- Helper Functions -------------------

function insert_subjects($db, $main_id, $student_id, $subjects, $academic_status) {
    if (!$subjects) return false;

    // Unified enrollment_subjects table; both REGULAR and IRREGULAR rows
    // reference the same parent enrollment_id in `enrollment`.
    $stmt = mysqli_prepare($db, "INSERT INTO enrollment_subjects (enrollment_id, teacher_class_id) VALUES (?, ?)");
    if (!$stmt) return false;

    foreach ($subjects as $s) {
        $tc_id = (int)($s['teacher_class_id'] ?? 0);
        if (!$tc_id) continue;
        mysqli_stmt_bind_param($stmt, "ii", $main_id, $tc_id);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            return false;
        }
    }
    mysqli_stmt_close($stmt);
    return true;
}

function fetch_subjects_for_enrollment($db, $student_id) {
    $response = ['success' => true, 'subjects' => [], 'meta' => ['required_units' => null, 'backlog_subject_codes' => []]];
    $sy = get_school_year();
    $sy_id = (int)($sy['school_year_id'] ?? 0);
    $sem   = $sy['sem'] ?? '';
    if (!$sy_id || !$sem) return ['success' => false, 'message' => 'Active school year/semester not found.'];

    // Normalize semester for teacher_class.sem (VARCHAR(20)). Unlike
    // enrollment.sem (which may be shorter), teacher_class stores the
    // full label (e.g. "2nd Semester"), so we use the exact value
    // from get_school_year when filtering offerings.
    $sem_esc = escape($db, substr($sem, 0, 20));

    $student_row = mysqliquery_return("SELECT student_id_no, year_level, program_id FROM student WHERE student_id_no='" . escape($db,$student_id) . "' LIMIT 1")[0] ?? null;
    if (!$student_row) return ['success' => false, 'message' => 'Student info not found.'];
    $year_level = (int)$student_row['year_level'];
    $program_id = (int)$student_row['program_id'];

    // Determine backlog subject codes for this student based on the
    // default curriculum (previous terms, not yet passed). These will
    // be used only to label subjects as backlog in the UI and to
    // control ordering (current term subjects first, then backlogs).
    $backlog_codes = list_backlog_subject_codes($db, $student_id, $program_id, $year_level, $sem);
    $response['meta']['backlog_subject_codes'] = $backlog_codes;
    $response['meta']['has_unoffered_backlogs'] = false;
    $backlog_map = [];
    foreach ($backlog_codes as $code) {
        $backlog_map[$code] = true;
    }

    // If the student has backlog codes but none of them are actually
    // offered this term, expose a flag so the frontend can optionally
    // offer higher-year, no-pre-req subjects instead. Additionally,
    // if there are higher-year offerings available for this term,
    // surface the same flag so the UI can load them for irregular
    // students even when some backlogs are offered.
    if (!empty($backlog_codes)) {
        $backlog_offerings = build_backlog_teacher_classes($db, $student_id, $program_id, $year_level, $sem, $sy_id);
        if (empty($backlog_offerings)) {
            $response['meta']['has_unoffered_backlogs'] = true;
        }

        $advanced_offerings = build_future_no_prereq_teacher_classes($db, $student_id, $program_id, $year_level, $sem, $sy_id);
        if (!empty($advanced_offerings)) {
            $response['meta']['has_unoffered_backlogs'] = true;
        }
    }

    $selected_program = (int)($_GET['program_id'] ?? 0);
    if ($selected_program <= 0) {
        $selected_program = $program_id;
    }
    $selected_class   = (int)($_GET['class_id'] ?? 0);

    // Mirror the main Enrollment Status page logic for discovering
    // active offerings: limit by current school year, semester,
    // program and year level, and rely on teacher_class/class_section
    // status flags. We intentionally do NOT filter on subject.status
    // here so that all offerings tied to active teacher_class rows
    // are considered.
    $conds = [
        "tc.status = 0",
        "cs.status = 0",
        "tc.schoolyear_id = $sy_id",
        "tc.sem = '" . $sem_esc . "'",
        "tc.program_id = $selected_program",
        "tc.year_level = $year_level",
    ];
    if ($selected_class) {
        $conds[] = "tc.class_id = $selected_class";
    }

    $sql = "SELECT tc.teacher_class_id, s.subject_code, s.subject_title, s.unit, cs.class_id, cs.class_name, tc.schedule, tc.room
            FROM teacher_class tc
            JOIN subject s ON tc.subject_id = s.subject_id
            JOIN class_section cs ON tc.class_id = cs.class_id
            WHERE " . implode(' AND ', $conds) . "
            ORDER BY s.subject_code, cs.class_name";

    $rows = mysqliquery_return($sql);
    if (!$rows) return ['success' => false, 'message' => 'No subjects found.'];

    foreach ($rows as $row) {
        $code = (string)$row['subject_code'];

        // Do not expose subjects the student has already passed in the
        // Available list; they should not be re-enrolled or even shown.
        if ($code !== '' && is_subject_passed_by_student($db, $student_id, $code)) {
            continue;
        }

        $is_backlog = isset($backlog_map[$code]);

        $response['subjects'][] = [
            'teacher_class_id' => (int)$row['teacher_class_id'],
            'subject_code'     => $code,
            'subject_title'    => $row['subject_title'],
            'unit'             => (float)$row['unit'],
            'class_id'         => (int)$row['class_id'],
            'class_name'       => $row['class_name'],
            'schedule'         => $row['schedule'],
            'room'             => $row['room'],
            'is_backlog'       => $is_backlog ? 1 : 0,
        ];
    }

    // Sort so that regular current-term subjects come first, then
    // backlog subjects, to visually match the intended flow.
    usort($response['subjects'], function ($a, $b) {
        $ab = (int)($a['is_backlog'] ?? 0);
        $bb = (int)($b['is_backlog'] ?? 0);
        if ($ab !== $bb) return $ab - $bb;
        $ac = (string)($a['subject_code'] ?? '');
        $bc = (string)($b['subject_code'] ?? '');
        return strcmp($ac, $bc);
    });

    // Determine academic status so we can choose the appropriate
    // required-units logic for the label shown in the cart.
    $status_label = determine_academic_status_from_curriculum(
        $db,
        $student_id,
        $program_id,
        $year_level,
        $sem
    );
    $academic_status = strtoupper($status_label === 'Regular' ? 'REGULAR' : 'IRREGULAR');

    if ($academic_status === 'REGULAR') {
        $response['meta']['required_units'] = compute_required_units_for_term($db, $student_id, $program_id, $year_level, $sem);
    } else {
        $response['meta']['required_units'] = compute_required_units_for_irregular($db, $student_id, $program_id, $year_level, $sem);
    }
    return $response;
}

// Fetch backlog subjects (from previous terms, not yet passed) that
// are actually offered in the current school year/semester. This is
// used for the second step in the irregular enrollment UI so the
// student can clearly see which backlog subjects will be taken.
function fetch_backlog_subjects_for_enrollment($db, $student_id) {
    $response = ['success' => true, 'subjects' => [], 'meta' => ['mode' => 'none']];

    $sy = get_school_year();
    $sy_id = (int)($sy['school_year_id'] ?? 0);
    $sem   = $sy['sem'] ?? '';
    if (!$sy_id || !$sem) {
        return ['success' => false, 'message' => 'Active school year/semester not found.'];
    }

    $student_row = mysqliquery_return("SELECT student_id_no, year_level, program_id FROM student WHERE student_id_no='" . escape($db,$student_id) . "' LIMIT 1")[0] ?? null;
    if (!$student_row) {
        return ['success' => false, 'message' => 'Student info not found.'];
    }

    $year_level = (int)$student_row['year_level'];
    $program_id = (int)$student_row['program_id'];

    // Determine which backlog subject codes exist for this student
    // based on the default curriculum (previous terms, not yet passed).
    $backlog_codes = list_backlog_subject_codes($db, $student_id, $program_id, $year_level, $sem);

    // First, try to resolve these backlog codes into concrete
    // teacher_class offerings for the current term. This is the
    // normal backlog flow used when the failed subjects are actually
    // being offered.
    $backlog = [];
    if (!empty($backlog_codes)) {
        $backlog = build_backlog_teacher_classes($db, $student_id, $program_id, $year_level, $sem, $sy_id);
    }

    if (!empty($backlog)) {
        foreach ($backlog as $row) {
            $response['subjects'][] = [
                'teacher_class_id' => (int)$row['teacher_class_id'],
                // program_id / class_id are included so the client can
                // filter backlog offerings per program/section in Step 2
                // without changing the student's base block section.
                'program_id'       => (int)($row['program_id'] ?? 0),
                'class_id'         => (int)($row['class_id'] ?? 0),
                'subject_code'     => (string)$row['subject_code'],
                'subject_title'    => (string)$row['subject_title'],
                'unit'             => (float)$row['unit'],
                'class_name'       => (string)$row['class_name'],
                'schedule'         => $row['schedule'],
                'room'             => $row['room'],
                'is_backlog'       => 1,
            ];
        }

        // In addition to real backlog offerings, also surface any
        // higher-year, no-pre-req subjects that exist for this term
        // so irregular students can optionally take them alongside
        // their backlogs. These are tagged as higher-year so the
        // frontend can place them under the Higher-Year tab.
        $advanced = build_future_no_prereq_teacher_classes($db, $student_id, $program_id, $year_level, $sem, $sy_id);
        if (!empty($advanced)) {
            foreach ($advanced as $row) {
                $response['subjects'][] = [
                    'teacher_class_id' => (int)$row['teacher_class_id'],
                    'program_id'       => (int)($row['program_id'] ?? 0),
                    'class_id'         => (int)($row['class_id'] ?? 0),
                    'subject_code'     => (string)$row['subject_code'],
                    'subject_title'    => (string)$row['subject_title'],
                    'unit'             => (float)$row['unit'],
                    'class_name'       => (string)$row['class_name'],
                    'schedule'         => $row['schedule'],
                    'room'             => $row['room'],
                    'is_backlog'       => 0,
                    'is_higher_year'   => 1,
                ];
            }
        }

        $response['meta']['mode'] = 'backlog';
        return $response;
    }

    // At this point either the student has no backlog codes, or they
    // do have backlog codes but none of those subjects are offered in
    // any program/section this term.
    if (empty($backlog_codes)) {
        // No backlog at all – nothing special to show.
        return $response;
    }

    // Special irregular-case: the student has backlog subjects from
    // previous terms, but those subjects are not offered anywhere in
    // the current term. Allow them to take higher-year subjects that
    // have no curriculum pre-requisites, provided those offerings
    // exist in teacher_class for this term.
    $advanced = build_future_no_prereq_teacher_classes($db, $student_id, $program_id, $year_level, $sem, $sy_id);
    if (empty($advanced)) {
        // Still nothing we can offer; leave subjects empty.
        return $response;
    }

    foreach ($advanced as $row) {
        $response['subjects'][] = [
            'teacher_class_id' => (int)$row['teacher_class_id'],
            'program_id'       => (int)($row['program_id'] ?? 0),
            'class_id'         => (int)($row['class_id'] ?? 0),
            'subject_code'     => (string)$row['subject_code'],
            'subject_title'    => (string)$row['subject_title'],
            'unit'             => (float)$row['unit'],
            'class_name'       => (string)$row['class_name'],
            'schedule'         => $row['schedule'],
            'room'             => $row['room'],
            // Treat these like backlog subjects in the UI so they
            // participate in Step 2, but they are not enforced by the
            // server as required backlogs.
            'is_backlog'       => 1,
        ];
    }

    $response['meta']['mode'] = 'advanced';
    return $response;
}

// ------------------- Utilities -------------------

function is_subject_passed_by_student($db, $student_id, $subject_code) {
    $sql = "SELECT remarks, converted_grade FROM final_grade WHERE student_id_text='".escape($db,$student_id)."' AND subject_code='".escape($db,$subject_code)."' ORDER BY date_updated DESC, final_id DESC LIMIT 1";
    $row = mysqliquery_return($sql)[0] ?? null;
    if (!$row) return false;
    $remarks = strtolower(trim($row['remarks'] ?? ''));
    if ($remarks) return strpos($remarks,'pass')!==false || $remarks==='p';
    $conv = trim((string)($row['converted_grade'] ?? ''));
    return $conv !== '' && ((float)$conv > 0 && (float)$conv <= 3.0);
}

function compute_required_units_for_term($db, $student_id, $program_id, $year_level, $sem) {
    $cur_sem_order = normalize_sem_order($sem);
    $curriculum_id = get_default_curriculum($db, $program_id);
    if (!$curriculum_id) return null;
    $sql = "SELECT subject_code, unit, semester, year_level FROM curriculum WHERE curriculum_id=$curriculum_id AND status=0";
    $subjRows = mysqliquery_return($sql);
    $total_units = 0;
    foreach ($subjRows as $row) {
        if ((int)($row['year_level'] ?? 0) !== (int)$year_level) continue;
        if (normalize_sem_order($row['semester'] ?? '') !== $cur_sem_order) continue;
        if (!is_subject_passed_by_student($db, $student_id, $row['subject_code'] ?? '')) {
            $total_units += (float)($row['unit'] ?? 0);
        }
    }
    return $total_units > 0 ? $total_units : null;
}

// For IRREGULAR students, determine the required unit load by
// first looking at the immediately previous term. If that term has
// any backlog subjects (not yet passed), use the TOTAL units of
// that previous term as the required load (e.g., 3rd Year 1st Sem
// = 9 units when three 3-unit subjects exist there). If there are
// no backlogs in the previous term, fall back to the current-term
// computation used for regular students.
function compute_required_units_for_irregular($db, $student_id, $program_id, $year_level, $sem) {
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($program_id <= 0 || $year_level <= 0) {
        return null;
    }

    $cur_sem_order = normalize_sem_order($sem);
    if ($cur_sem_order === null) {
        return null;
    }

    $curriculum_id = get_default_curriculum($db, $program_id);
    if (!$curriculum_id) {
        return null;
    }

    // Determine the immediately previous term relative to the
    // student's current year level and semester order.
    $prev_year  = $year_level;
    $prev_sem_o = $cur_sem_order - 1;
    if ($prev_sem_o <= 0) {
        $prev_year  = $year_level - 1;
        $prev_sem_o = 2; // assume 2-semester system
    }

    if ($prev_year <= 0) {
        // No meaningful previous term; fall back to current term.
        return compute_required_units_for_term($db, $student_id, $program_id, $year_level, $sem);
    }

    $sql = "SELECT subject_code, unit, semester, year_level
            FROM curriculum
            WHERE curriculum_id = $curriculum_id
              AND status = 1";
    $rows = mysqliquery_return($sql);
    if (empty($rows)) {
        return null;
    }

    $prev_term_units   = 0.0;
    $prev_term_backlog = false;

    foreach ($rows as $row) {
        $yl = (int)($row['year_level'] ?? 0);
        if ($yl !== $prev_year) {
            continue;
        }

        $semOrder = normalize_sem_order($row['semester'] ?? '');
        if ($semOrder !== $prev_sem_o) {
            continue;
        }

        $unit = (float)($row['unit'] ?? 0);
        if ($unit <= 0) {
            continue;
        }

        $prev_term_units += $unit;

        $code = trim((string)($row['subject_code'] ?? ''));
        if ($code !== '' && !is_subject_passed_by_student($db, $student_id, $code)) {
            $prev_term_backlog = true;
        }
    }

    if ($prev_term_backlog && $prev_term_units > 0) {
        // Example: Dongfang failed a 3rd Year 1st Sem subject; if
        // that semester now has three 3-unit subjects (9 units
        // total), irregular required_units becomes 9 even though we
        // are currently in 3rd Year 2nd Sem (6 units in curriculum).
        return $prev_term_units;
    }

    // If there is no backlog in the immediately previous term,
    // default to the same rule as regular students for the current
    // term.
    return compute_required_units_for_term($db, $student_id, $program_id, $year_level, $sem);
}

// Determine academic status (Regular/Irregular) from curriculum vs. completed subjects.
// A student is REGULAR if all required curriculum subjects from previous
// terms (including earlier semesters of the same year level) are passed;
// otherwise IRREGULAR. This mirrors the logic used on the Enrollment Status
// page and on the backlog listing helper below.
function determine_academic_status_from_curriculum($db, $student_id_text, $program_id, $year_level, $current_sem) {
    $student_id_text = trim((string)$student_id_text);
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($student_id_text === '' || $program_id <= 0 || $year_level <= 0) {
        return 'Irregular';
    }

    $cur_sem_order = normalize_sem_order($current_sem);

    $curriculum_id = get_default_curriculum($db, $program_id);
    if (!$curriculum_id) {
        return 'Irregular';
    }

    $sqlSubj = "SELECT subject_code, semester, year_level
                FROM curriculum
                WHERE curriculum_id = " . (int)$curriculum_id . "
                  AND status = 1";
    $subjRows = mysqliquery_return($sqlSubj);
    if (empty($subjRows)) {
        return 'Regular';
    }

    foreach ($subjRows as $row) {
        $code = trim((string)($row['subject_code'] ?? ''));
        if ($code === '') {
            continue;
        }

        $yl = isset($row['year_level']) ? (int)$row['year_level'] : 0;
        $semOrder = normalize_sem_order($row['semester'] ?? '');

        // Only consider subjects from terms that are strictly before the
        // student's current term. Example for a 3rd Year 2nd Sem student:
        //  - all 1st & 2nd year subjects are "previous terms";
        //  - 3rd Year 1st Sem subjects are also previous terms;
        //  - 3rd Year 2nd Sem subjects are current term (not yet required
        //    to be fully completed).
        $mustCheck = false;
        if ($yl < $year_level) {
            $mustCheck = true;
        } elseif ($yl === $year_level && $cur_sem_order !== null && $semOrder !== null && $semOrder < $cur_sem_order) {
            $mustCheck = true;
        }

        if (!$mustCheck) {
            continue;
        }

        if (!is_subject_passed_by_student($db, $student_id_text, $code)) {
            return 'Irregular';
        }
    }

    return 'Regular';
}

// List backlog subject codes for a student based on the default
// curriculum: all required subjects from previous terms that are not
// yet passed.
function list_backlog_subject_codes($db, $student_id_text, $program_id, $year_level, $current_sem) {
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($program_id <= 0 || $year_level <= 0) return [];

    $cur_sem_order = normalize_sem_order($current_sem);
    $curriculum_id = get_default_curriculum($db, $program_id);
    if (!$curriculum_id) return [];

    $sqlSubj = "SELECT subject_code, semester, year_level
                FROM curriculum
                WHERE curriculum_id = " . (int)$curriculum_id . "
                  AND status = 1";
    $subjRows = mysqliquery_return($sqlSubj);
    if (empty($subjRows)) return [];

    $backlogs = [];
    foreach ($subjRows as $row) {
        $subjCode = trim((string)($row['subject_code'] ?? ''));
        if ($subjCode === '') continue;

        $yl = isset($row['year_level']) ? (int)$row['year_level'] : 0;
        $semOrder = normalize_sem_order($row['semester'] ?? '');

        // Only consider subjects from previous terms
        if ($yl < $year_level) {
            $mustCheck = true;
        } elseif ($yl === $year_level && $cur_sem_order !== null && $semOrder !== null && $semOrder < $cur_sem_order) {
            $mustCheck = true;
        } else {
            $mustCheck = false;
        }

        if (!$mustCheck) continue;

        if (!is_subject_passed_by_student($db, $student_id_text, $subjCode)) {
            $backlogs[] = $subjCode;
        }
    }

    return array_values(array_unique($backlogs));
}

function normalize_sem_order($sem) {
    if ($sem===null) return null;
    $map=['1st semester'=>1,'first semester'=>1,'2nd semester'=>2,'second semester'=>2,'3rd semester'=>3,'third semester'=>3];
    $val = strtolower(trim($sem));
    return ctype_digit($val)?(int)$val:($map[$val] ?? null);
}

function get_default_curriculum($db, $program_id) {
    $program_id = (int)$program_id;
    if ($program_id <= 0) {
        return 0;
    }

    $row = mysqliquery_return(
        "SELECT curriculum_id FROM curriculum_master " .
        "WHERE program_id = " . $program_id . " AND status_allowable = 0 " .
        "ORDER BY curriculum_id DESC LIMIT 1",
        $db
    )[0] ?? null;

    return (int)($row['curriculum_id'] ?? 0);
}

function get_curriculum_pre_reqs($db, $curriculum_id) {
    $rows = mysqliquery_return("SELECT subject_code, pre_req FROM curriculum WHERE curriculum_id=$curriculum_id AND status=1");
    $map = [];
    foreach ($rows as $r) $map[trim($r['subject_code'])] = trim($r['pre_req']);
    return $map;
}

// Given a set of selected subjects (each with teacher_class_id and
// subject_code), determine the section (class_id) that has the
// highest overlap with the selected non-backlog subjects. Backlog
// subjects are ignored for matching so that the base block section is
// chosen primarily from current-term offerings.
function determine_best_class_for_selection($db, $subjects, $backlog_codes) {
    if (empty($subjects)) return 0;

    $backlog_map = [];
    foreach ((array)$backlog_codes as $code) {
        $code = trim((string)$code);
        if ($code === '') continue;
        $backlog_map[$code] = true;
    }

    $tc_ids = [];
    $code_by_tc = [];
    foreach ($subjects as $s) {
        $tc_id = (int)($s['teacher_class_id'] ?? 0);
        $code  = trim((string)($s['subject_code'] ?? ''));
        if ($tc_id <= 0 || $code === '') continue;
        $tc_ids[$tc_id] = true;
        $code_by_tc[$tc_id] = $code;
    }

    if (empty($tc_ids)) return 0;

    $tc_list = implode(',', array_keys($tc_ids));
    $rows = mysqliquery_return("SELECT teacher_class_id, class_id FROM teacher_class WHERE teacher_class_id IN ($tc_list)");
    if (empty($rows)) return 0;

    $score_by_class = [];
    foreach ($rows as $row) {
        $tc_id   = (int)($row['teacher_class_id'] ?? 0);
        $classId = (int)($row['class_id'] ?? 0);
        if ($tc_id <= 0 || $classId <= 0) continue;

        $code = $code_by_tc[$tc_id] ?? '';
        if ($code === '') continue;

        // Count only non-backlog subjects towards the section match.
        if (isset($backlog_map[$code])) {
            continue;
        }

        if (!isset($score_by_class[$classId])) {
            $score_by_class[$classId] = 0;
        }
        $score_by_class[$classId]++;
    }

    if (empty($score_by_class)) {
        // Fallback: if everything looks like backlog, choose the
        // first class_id associated with any selected teacher_class.
        foreach ($rows as $row) {
            $classId = (int)($row['class_id'] ?? 0);
            if ($classId > 0) return $classId;
        }
        return 0;
    }

    // Pick the class_id with the highest score; break ties by
    // choosing the numerically smallest class_id for stability.
    $best_class_id = 0;
    $best_score = -1;
    foreach ($score_by_class as $classId => $score) {
        if ($score > $best_score || ($score === $best_score && ($best_class_id === 0 || $classId < $best_class_id))) {
            $best_class_id = (int)$classId;
            $best_score = (int)$score;
        }
    }

    return $best_class_id;
}

// For a given student and term, resolve backlog subject codes into
// concrete teacher_class offerings in the current school year/semester.
// This returns ALL available offerings per subject code across
// programs/sections so the UI can let the student choose.
function build_backlog_teacher_classes($db, $student_id_text, $program_id, $year_level, $current_sem, $sy_id, $filter_program_id = null, $filter_class_id = null) {
    $codes = list_backlog_subject_codes($db, $student_id_text, $program_id, $year_level, $current_sem);
    if (empty($codes)) return [];

    $sem_esc = escape($db, $current_sem);
    $result = [];

    foreach ($codes as $code) {
                $code_esc = escape($db, $code);

                $whereParts = [];
                $whereParts[] = "s.subject_code = '" . $code_esc . "'";
                $whereParts[] = "tc.schoolyear_id = " . (int)$sy_id;
                $whereParts[] = "tc.sem = '" . $sem_esc . "'";
                $whereParts[] = "tc.status = 0";

                if (!is_null($filter_program_id) && (int)$filter_program_id > 0) {
                        $whereParts[] = "tc.program_id = " . (int)$filter_program_id;
                }
                if (!is_null($filter_class_id) && (int)$filter_class_id > 0) {
                        $whereParts[] = "tc.class_id = " . (int)$filter_class_id;
                }

                $whereSql = implode(' AND ', $whereParts);

                $sql = "SELECT tc.teacher_class_id, tc.program_id, tc.class_id, s.subject_code, s.subject_title, s.unit, cs.class_name, tc.schedule, tc.room
                                FROM teacher_class tc
                                JOIN subject s ON tc.subject_id = s.subject_id
                                JOIN class_section cs ON tc.class_id = cs.class_id
                                WHERE " . $whereSql . "
                                ORDER BY cs.class_name ASC, tc.teacher_class_id ASC";

        $rows = mysqliquery_return($sql);
        if (empty($rows)) {
            continue; // backlog subject not offered this term
        }

        foreach ($rows as $row) {
            $result[] = [
                'teacher_class_id' => (int)$row['teacher_class_id'],
                'program_id'       => (int)($row['program_id'] ?? 0),
                'class_id'         => (int)($row['class_id'] ?? 0),
                'subject_code'     => $row['subject_code'],
                'subject_title'    => $row['subject_title'],
                'unit'             => (float)$row['unit'],
                'class_name'       => $row['class_name'],
                'schedule'         => $row['schedule'],
                'room'             => $row['room'],
            ];
        }
    }

    return $result;
}

// For irregular students whose backlog subjects are not offered in
// the current term, allow them to take higher-year subjects that have
// no curriculum pre-requisites, as long as those subjects are offered
// in teacher_class for the same school year / semester.
function build_future_no_prereq_teacher_classes($db, $student_id_text, $program_id, $year_level, $current_sem, $sy_id) {
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($program_id <= 0 || $year_level <= 0) return [];

    $curriculum_id = get_default_curriculum($db, $program_id);
    if (!$curriculum_id) return [];

    $sem_esc = escape($db, $current_sem);

    // Identify higher-year curriculum subjects with no pre-requisite
    // and that the student has not yet passed.
    $sql = "SELECT subject_code, pre_req, year_level
            FROM curriculum
            WHERE curriculum_id = " . (int)$curriculum_id . "
              AND status = 1";
    $rows = mysqliquery_return($sql);
    if (empty($rows)) return [];

    $codes = [];
    foreach ($rows as $row) {
        $code = trim((string)($row['subject_code'] ?? ''));
        if ($code === '') continue;

        $yl = isset($row['year_level']) ? (int)$row['year_level'] : 0;
        if ($yl <= $year_level) continue; // only higher-year subjects

        $pre = trim((string)($row['pre_req'] ?? ''));
        if ($pre !== '') continue; // must have no pre-requisite

        if (is_subject_passed_by_student($db, $student_id_text, $code)) continue;

        $codes[$code] = true;
    }

    if (empty($codes)) return [];

    $result = [];
    foreach (array_keys($codes) as $code) {
        $code_esc = escape($db, $code);

        $whereParts = [];
        $whereParts[] = "s.subject_code = '" . $code_esc . "'";
        $whereParts[] = "tc.schoolyear_id = " . (int)$sy_id;
        $whereParts[] = "tc.sem = '" . $sem_esc . "'";
        $whereParts[] = "tc.status = 0";
        $whereParts[] = "tc.program_id = " . $program_id;

        $whereSql = implode(' AND ', $whereParts);

        $sqlOffer = "SELECT tc.teacher_class_id, tc.program_id, tc.class_id, s.subject_code, s.subject_title, s.unit, cs.class_name, tc.schedule, tc.room
                     FROM teacher_class tc
                     JOIN subject s ON tc.subject_id = s.subject_id
                     JOIN class_section cs ON tc.class_id = cs.class_id
                     WHERE " . $whereSql . "
                     ORDER BY cs.class_name ASC, tc.teacher_class_id ASC";

        $rowsOffer = mysqliquery_return($sqlOffer);
        if (empty($rowsOffer)) {
            continue;
        }

        foreach ($rowsOffer as $r) {
            $result[] = [
                'teacher_class_id' => (int)$r['teacher_class_id'],
                'program_id'       => (int)($r['program_id'] ?? 0),
                'class_id'         => (int)($r['class_id'] ?? 0),
                'subject_code'     => $r['subject_code'],
                'subject_title'    => $r['subject_title'],
                'unit'             => (float)$r['unit'],
                'class_name'       => $r['class_name'],
                'schedule'         => $r['schedule'],
                'room'             => $r['room'],
            ];
        }
    }

    return $result;
}

// ------------------- Schedule Conflicts -------------------
// Given a set of selected subjects (each with teacher_class_id),
// verify that there are no overlapping time ranges on the same day
// based on the authoritative schedules stored in teacher_class.
//
// Returns null when there is no conflict, or an associative array
// with a human-readable 'message' key when a conflict is found.
function find_schedule_conflict_for_subjects($db, $subjects) {
    if (empty($subjects)) {
        return null;
    }

    // Collect unique teacher_class_ids from the payload.
    $tc_ids = [];
    foreach ($subjects as $s) {
        $tcid = (int)($s['teacher_class_id'] ?? 0);
        if ($tcid > 0) {
            $tc_ids[$tcid] = true;
        }
    }

    if (empty($tc_ids)) {
        return null;
    }

    $tc_list = implode(',', array_keys($tc_ids));
    $sql = "SELECT tc.teacher_class_id, tc.schedule, s.subject_code, s.subject_title
            FROM teacher_class tc
            JOIN subject s ON tc.subject_id = s.subject_id
            WHERE tc.teacher_class_id IN ($tc_list)";
    $rows = mysqliquery_return($sql);
    if (empty($rows)) {
        return null;
    }

    $day_labels = [
        'sunday'    => 'Sunday',
        'monday'    => 'Monday',
        'tuesday'   => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday'  => 'Thursday',
        'friday'    => 'Friday',
        'saturday'  => 'Saturday',
    ];

    // day_label => list of slots
    $slots_by_day = [];

    foreach ($rows as $row) {
        $tcid   = (int)($row['teacher_class_id'] ?? 0);
        $code   = trim((string)($row['subject_code'] ?? ''));
        $title  = trim((string)($row['subject_title'] ?? ''));
        $label  = $code !== '' && $title !== '' ? ($code . ' - ' . $title) : ($code !== '' ? $code : $title);
        $schedule_raw = $row['schedule'] ?? '';

        if ($schedule_raw === null || $schedule_raw === '') {
            continue;
        }

        $entries = [];
        $decoded = json_decode($schedule_raw, true);
        if (is_array($decoded)) {
            $entries = $decoded;
        } elseif (is_string($schedule_raw)) {
            $entries = [$schedule_raw];
        }

        foreach ($entries as $entry) {
            if (!is_string($entry)) {
                continue;
            }

            $parts = explode('::', $entry);
            if (count($parts) < 2) {
                continue;
            }

            $day_key  = strtolower(trim($parts[0] ?? ''));
            $time_str = trim($parts[1] ?? '');

            if ($time_str === '' || !isset($day_labels[$day_key])) {
                continue;
            }

            // Parse time range into minutes-from-midnight
            $time_parts = explode('-', $time_str);
            if (count($time_parts) < 2) {
                continue;
            }

            $start_raw = trim($time_parts[0]);
            $end_raw   = trim($time_parts[1]);

            if (strpos($start_raw, ':') === false || strpos($end_raw, ':') === false) {
                continue;
            }

            [$sh, $sm] = array_pad(explode(':', $start_raw), 2, '0');
            [$eh, $em] = array_pad(explode(':', $end_raw), 2, '0');

            $start_min = ((int)$sh * 60) + (int)$sm;
            $end_min   = ((int)$eh * 60) + (int)$em;

            if ($end_min <= $start_min) {
                continue;
            }

            $day_label = $day_labels[$day_key];
            if (!isset($slots_by_day[$day_label])) {
                $slots_by_day[$day_label] = [];
            }

            $slots_by_day[$day_label][] = [
                'tcid'      => $tcid,
                'label'     => $label,
                'day_label' => $day_label,
                'time_str'  => $time_str,
                'start_min' => $start_min,
                'end_min'   => $end_min,
            ];
        }
    }

    foreach ($slots_by_day as $day_label => $slots) {
        if (count($slots) < 2) {
            continue;
        }

        // Sort by start time to detect overlaps efficiently.
        usort($slots, function ($a, $b) {
            return $a['start_min'] <=> $b['start_min'];
        });

        for ($i = 1, $n = count($slots); $i < $n; $i++) {
            $prev = $slots[$i - 1];
            $curr = $slots[$i];

            // Ignore duplicate slots from the same class.
            if ($prev['tcid'] === $curr['tcid']) {
                continue;
            }

            if ($curr['start_min'] < $prev['end_min']) {
                $first_label  = $prev['label'] ?: 'another subject';
                $second_label = $curr['label'] ?: 'another subject';

                $time_info = $prev['time_str'];
                if ($curr['time_str'] !== $prev['time_str']) {
                    $time_info = $prev['time_str'] . ' vs ' . $curr['time_str'];
                }

                return [
                    'message' => "Schedule conflict detected on {$day_label} between {$first_label} and {$second_label} ({$time_info}). Please adjust your selection.",
                ];
            }
        }
    }

    return null;
}

// ------------------- Capacity -------------------
function check_capacity($db, $student_id, $class_id, $subjects, $sy_id, $sem) {
    $tc_ids = array_filter(array_map(fn($s) => (int)($s['teacher_class_id'] ?? 0), $subjects));
    if (!$tc_ids) return ['success'=>true];
    $tc_list = implode(',', $tc_ids);

    // sem column is VARCHAR(10); longer labels like "2nd Semester" are
    // truncated when stored, so use the same truncated value when querying.
    $sem_trunc = substr((string)$sem, 0, 10);
    $sem_esc   = escape($db, $sem_trunc);

    $tc_rows = mysqliquery_return("SELECT tc.teacher_class_id, tc.section_limit, s.subject_code, s.subject_title FROM teacher_class tc JOIN subject s ON tc.subject_id=s.subject_id WHERE tc.teacher_class_id IN ($tc_list) FOR UPDATE");
    $tc_map=[]; foreach($tc_rows as $row) $tc_map[(int)$row['teacher_class_id']]=$row;

    // Unified occupancy: count students per teacher_class from the
    // unified enrollment/enrollment_subjects tables. A seat is taken when:
    // - REGULAR enrollment with status = ENROLLED
    // - IRREGULAR enrollment with status in (PENDING, APPROVED)
    $sql = "SELECT es.teacher_class_id, COUNT(DISTINCT e.student_id) AS cnt
            FROM enrollment_subjects es
            JOIN enrollment e ON es.enrollment_id = e.enrollment_id
            WHERE es.teacher_class_id IN ($tc_list)
              AND e.schoolyear_id = $sy_id
              AND e.sem = '$sem_esc'
              AND (
                    (e.classification = 'REGULAR'   AND e.status = 'ENROLLED')
                 OR (e.classification = 'IRREGULAR' AND e.status IN ('PENDING','APPROVED'))
                  )
            GROUP BY es.teacher_class_id";
    $rows = mysqliquery_return($sql);
    $counts=[]; foreach($rows as $r) { $id=(int)$r['teacher_class_id']; $counts[$id]=($counts[$id]??0)+(int)$r['cnt']; }

    foreach($tc_ids as $id) {
        $limit=(int)($tc_map[$id]['section_limit']??0);
        if($limit && (($counts[$id]??0)+1)>$limit) return ['success'=>false,'message'=>"Subject {$tc_map[$id]['subject_code']} is already full."];
    }
    return ['success'=>true];
}