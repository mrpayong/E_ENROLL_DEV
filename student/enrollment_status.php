<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Enrollment";
$get_user_value = strtoupper($_GET['none'] ?? '');
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [];

if (!($g_user_role == "STUDENT")) {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Fetch current school year and semester using the shared helper,
// which respects the system's active fiscal year / term stored in
// session. Then, re-read the row by id from the database so that
// flag_used reflects any recent changes (the copy in session may
// be stale if the admin updated the table after the term was
// selected).
$current_sy = get_school_year();

$current_school_year_id = (int)($current_sy['school_year_id'] ?? 0);
if ($current_school_year_id > 0) {
    $fresh_sy = get_school_year($current_school_year_id);
    if (!empty($fresh_sy)) {
        $current_sy = $fresh_sy;
    }
}

$current_sem = $current_sy['sem'] ?? '';
// Treat only the term with flag_used = 1 as open for new
// enrollment actions; all other terms (past/future) are
// considered read-only when selected via the fiscal-year
// selector.
$current_flag_used = isset($current_sy['flag_used']) ? (int)$current_sy['flag_used'] : 0;
$is_term_open = ($current_flag_used === 1);
// Normalize current semester for use in queries where the underlying
// column may be shorter (e.g. enrollment.sem is VARCHAR(10)).
$current_sem_trunc = $current_sem !== ''
    ? escape($db_connect, substr($current_sem, 0, 10))
    : '';

// Fetch logged-in student info using general_id from session and
// derive academic status from curriculum vs completed subjects.
$student_id_no = '';
$student_fullname_display = '';
$student_academic_status = 'Irregular'; // default label, overridden when curriculum is available
$student_year_level = null;           // database / official year level
$student_year_level_display = null;   // UI classification based on completed units
$student_program_id = 0;
$curr_id = "";

if (!empty($g_general_id)) {
    // g_general_id stores the external student_id_no, not the
    // numeric primary key. Use student_id_no to resolve the
    // current logged-in student's record.
    $student_id_for_query = escape($db_connect, $g_general_id);
    $student_sql = "SELECT student_id_no, firstname, lastname, middle_name, suffix_name, year_level, major, program_id, curriculum_id FROM student WHERE student_id_no = '" . $student_id_for_query . "' LIMIT 1";
    $student_rows = mysqliquery_return($student_sql);
    if (!empty($student_rows)) {
        $student = $student_rows[0];
        $student_id_no = $student['student_id_no'] ?? '';

        $first = $student['firstname'] ?? '';
        $middle = $student['middle_name'] ?? '';
        $last = $student['lastname'] ?? '';
        $suffix_name = $student['suffix_name'] ?? '';
        $middle_initial = $middle !== '' ? strtoupper(substr($middle, 0, 1)) . '.' : '';
        $student_fullname_display = strtoupper(trim($last . ', ' . $first . ' ' . $middle_initial . ' ' . $suffix_name));

        $student_year_level = isset($student['year_level']) ? intVal($student['year_level']) : 0;
        $student_program_id = isset($student['program_id']) ? intVal($student['program_id']) : 0;
        $curr_id = isset($student['curriculum_id']) ? intVal($student['curriculum_id']) : 0;
        // Default UI year level mirrors the stored year level.
        $student_year_level_display = $student_year_level;

        // Determine academic classification based on completed subjects in the
        // prospectus across all previous terms (year level + semester). This
        // mirrors the backend helper used when saving enrollment so that a
        // failed subject in an earlier term of the same year level (e.g.,
        // 3rd Year 1st Sem while currently in 3rd Year 2nd Sem) also makes
        // the student Irregular.
        if ($student_id_no !== '' && $student_program_id > 0 && $student_year_level > 0) {
            $status_from_terms = determine_academic_status_from_curriculum(
                $db_connect,
                $student_id_no,
                $student_program_id,
                $student_year_level,
                $current_sem
            );

            if ($status_from_terms === 'Irregular') {
                $student_academic_status = 'Irregular';

                // Try to locate a backlog year based purely on completed
                // units in earlier year levels. When a backlog exists in
                // 1st or 2nd year, show that year level in the UI so it is
                // clear the student is still completing that stage. For
                // backlogs confined to the current year level (e.g. failed
                // 3rd Year 1st Sem), keep the displayed year level as-is.
                $backlog_year = get_backlog_year_from_units(
                    $db_connect,
                    $student_id_no,
                    $student_program_id,
                    $student_year_level
                );

                if ($backlog_year !== null) {
                    $student_year_level_display = $backlog_year;
                } else {
                    $student_year_level_display = $student_year_level;
                }
            } else {
                $student_academic_status = 'Regular';
                $student_year_level_display = $student_year_level;
            }
        }
    }
}

// Effective year level for loading sections/subjects this term. This
// follows the UI classification (student_year_level_display) so that
// students who are still completing 1st year requirements but have a
// higher stored year_level are offered the correct 1st year sections.
$effective_year_level = null;
if (!is_null($student_year_level_display) && $student_year_level_display > 0) {
    $effective_year_level = (int)$student_year_level_display;
} elseif (!is_null($student_year_level) && $student_year_level > 0) {
    $effective_year_level = (int)$student_year_level;
}

// Fetch active programs (optionally filtered by student's program)
$programs_conditions = [];
$programs_conditions[] = "status = 0";

// If the student has an associated program, limit the list to that program only
if ($student_program_id > 0) {
    $programs_conditions[] = "program_id = " . $student_program_id;
}

$programs_where = implode(' AND ', $programs_conditions);

$programs_sql = "SELECT program_id, program, short_name FROM programs WHERE " . $programs_where . " ORDER BY program";
$programs = mysqliquery_return($programs_sql);

// Fetch active sections that actually have classes (teacher_class) for the current school year
// This ties sections directly to offerings via teacher_class.program_id and teacher_class.year_level
$sections_conditions = [];
$sections_conditions[] = "tc.status = 0";
$sections_conditions[] = "cs.status = 0";

if ($current_school_year_id > 0) {
    $sections_conditions[] = "tc.schoolyear_id = " . $current_school_year_id;
}

// Limit sections to those belonging to the student's program and year level (if available)
if ($student_program_id > 0) {
    $sections_conditions[] = "tc.program_id = " . $student_program_id;
}

if (!is_null($effective_year_level) && $effective_year_level > 0) {
    $sections_conditions[] = "tc.year_level = " . $effective_year_level;
}

$sections_where = implode(' AND ', $sections_conditions);

$sections_sql = "SELECT DISTINCT cs.class_id, cs.class_name, tc.program_id, tc.year_level
                 FROM teacher_class tc
                 JOIN class_section cs ON tc.class_id = cs.class_id
                 WHERE " . $sections_where . "
                 ORDER BY cs.class_name";
$sections = mysqliquery_return($sections_sql);

// Filter out sections that clearly belong to a higher year level
// based on the class_name prefix pattern (e.g., "4-IT2").
if (!empty($sections) && !is_null($effective_year_level) && $effective_year_level > 0) {
    $filtered_sections = [];
    foreach ($sections as $section) {
        $name = $section['class_name'] ?? '';
        $sec_year = null;
        if (is_string($name) && preg_match('/^(\d{1,2})\s*[- ]/', $name, $m)) {
            $sec_year = (int)$m[1];
        }

        // If we could detect a leading year and it's greater than the
        // student's year level, skip this section from the dropdown.
        if (!is_null($sec_year) && $sec_year > $effective_year_level) {
            continue;
        }

        $filtered_sections[] = $section;
    }
    $sections = $filtered_sections;
}

// Determine per-section capacity status (e.g., whether a section is already full)
// using the unified enrollment/enrollment_subjects tables.
$section_capacity_info = [];
if (!empty($sections) && $current_school_year_id > 0 && $current_sem !== '') {
    // sem column is VARCHAR(10); longer labels like "2nd Semester" are
    // truncated when stored. Use the same truncated value when computing
    // occupancy.

    foreach ($sections as $section) {
        $cid = (int)($section['class_id'] ?? 0);
        if ($cid <= 0) {
            continue;
        }

        $tc_conditions = [];
        $tc_conditions[] = "tc.status = 0";
        $tc_conditions[] = "tc.class_id = " . $cid;
        $tc_conditions[] = "tc.schoolyear_id = " . $current_school_year_id;
        $tc_conditions[] = "tc.sem = '" . escape($db_connect, $current_sem) . "'";

        if ($student_program_id > 0) {
            $tc_conditions[] = "tc.program_id = " . $student_program_id;
        }
        if (!is_null($effective_year_level) && $effective_year_level > 0) {
            $tc_conditions[] = "tc.year_level = " . $effective_year_level;
        }

        $tc_where = implode(' AND ', $tc_conditions);
        $tc_sql = "SELECT tc.teacher_class_id, tc.section_limit
                    FROM teacher_class tc
                    WHERE " . $tc_where;
        $tc_rows = mysqliquery_return($tc_sql);

        // Default: section is NOT full. We will mark it as full as soon as we
        // find at least one class (subject) within the section that has
        // already reached its section_limit for this term.
        $is_full = false;

        if (!empty($tc_rows)) {
            foreach ($tc_rows as $tc_row) {
                $tc_id = (int)($tc_row['teacher_class_id'] ?? 0);
                $section_limit = (int)($tc_row['section_limit'] ?? 0);

                // Skip classes with no explicit limit or invalid IDs; they do
                // not contribute to a "full" section state.
                if ($section_limit <= 0 || $tc_id <= 0) {
                    continue;
                }

                                // Count occupants for this teacher_class using the unified
                                // enrollment tables:
                                //   - REGULAR + ENROLLED
                                //   - IRREGULAR + (PENDING or APPROVED)
                                $sql_tc_occ = "SELECT COUNT(DISTINCT e.student_id) AS c
                                                                FROM enrollment_subjects es
                                                                JOIN enrollment e ON es.enrollment_id = e.enrollment_id
                                                                WHERE es.teacher_class_id = $tc_id
                                                                    AND e.schoolyear_id = $current_school_year_id
                                                                    AND e.sem = '$current_sem_trunc'
                                                                    AND (
                                                                             (e.classification = 'REGULAR' AND e.status = 'ENROLLED')
                                                                        OR (e.classification = 'IRREGULAR' AND e.status IN ('PENDING','APPROVED'))
                                                                    )";
                                $occ_rows = mysqliquery_return($sql_tc_occ);
                                $current_occupancy = !empty($occ_rows) ? (int)$occ_rows[0]['c'] : 0;

                if ($current_occupancy >= $section_limit) {
                    // As soon as we detect at least one subject in this
                    // section that is at full capacity, treat the entire
                    // section as full for the dropdown.
                    $is_full = true;
                    break;
                }
            }
        }

        $section_capacity_info[$cid] = [
            'is_full' => $is_full,
        ];
    }
}

// Check if the student has a pending irregular enrollment request for the current term
$has_pending_request = false;
$pending_request_date = '';
$pending_schedule_grid = [];
$pending_time_slots = [];
$pending_time_label_map = [];
$pending_day_order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

$has_rejected_request = false;
$rejected_request_date = '';
$rejected_remarks = '';
$rejected_recommended = [];
$has_approved_request = false;
$approved_request_date = '';
$has_active_enrollment = false;
$active_enrollment_date = '';
$approved_schedule_grid = [];
$approved_time_slots = [];
$approved_time_label_map = [];
$approved_day_order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
// Regular enrollment schedule (for successfully enrolled regular students)
$regular_schedule_grid = [];
$regular_time_slots = [];
$regular_time_label_map = [];
$regular_day_order = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
if (!empty($g_general_id) && $current_school_year_id > 0) {
    // Normalize current semester for enrollment.sem (VARCHAR(10))
    $current_sem_trunc = escape($db_connect, substr($current_sem, 0, 10));

    // Latest pending irregular request (metadata only) from unified enrollment
    $pending_check_sql = "SELECT enrollment_id, created_at FROM enrollment
                          WHERE student_id = '" . escape($db_connect, $g_general_id) . "'
                          AND schoolyear_id = " . $current_school_year_id . "
                          AND sem = '" . $current_sem_trunc . "'
                          AND classification = 'IRREGULAR'
                          AND status = 'PENDING'
                          ORDER BY created_at DESC LIMIT 1";
    $pending_rows = mysqliquery_return($pending_check_sql);
    if (!empty($pending_rows)) {
        $has_pending_request = true;
        $pending_request_id = (int)($pending_rows[0]['enrollment_id'] ?? 0);
        $pending_request_date = $pending_rows[0]['created_at'] ?? '';

        // Load subjects for this pending request via normalized table
        $pending_subjects = [];
        if ($pending_request_id > 0) {
            $sub_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name
                        FROM enrollment_subjects es
                        JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id
                        JOIN subject s ON tc.subject_id = s.subject_id
                        JOIN class_section cs ON tc.class_id = cs.class_id
                        WHERE es.enrollment_id = " . $pending_request_id;
            $pending_subjects = mysqliquery_return($sub_sql);
        }

        if (!empty($pending_subjects)) {
            $day_labels = [
                'sunday'    => 'Sunday',
                'monday'    => 'Monday',
                'tuesday'   => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday'  => 'Thursday',
                'friday'    => 'Friday',
                'saturday'  => 'Saturday',
            ];

            $extract_start_minutes = function ($timeRange) {
                $parts = explode('-', $timeRange);
                $start = trim($parts[0] ?? '');
                if (strpos($start, ':') === false) {
                    return 24 * 60; // push unknowns to the end
                }
                [$h, $m] = array_pad(explode(':', $start), 2, '0');
                return ((int)$h * 60) + (int)$m;
            };

            $to_12hr = function ($time24) {
                if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time24), $m)) {
                    return $time24;
                }
                $h = (int)$m[1];
                $min = $m[2];
                $suffix = $h >= 12 ? 'PM' : 'AM';
                $h = $h % 12;
                if ($h === 0) {
                    $h = 12;
                }
                return $h . ':' . $min . ' ' . $suffix;
            };

            $format_time_range = function ($range) use ($to_12hr) {
                $parts = explode('-', $range);
                if (count($parts) < 2) {
                    return $range;
                }
                $start = trim($parts[0]);
                $end = trim($parts[1]);
                return $to_12hr($start) . ' - ' . $to_12hr($end);
            };

            foreach ($pending_subjects as $subj) {
                $code    = $subj['subject_code']  ?? '';
                $title   = $subj['subject_title'] ?? '';
                $class   = trim((string)($subj['class_name'] ?? ''));
                $schedule_raw = $subj['schedule'] ?? '';

                $entries = [];
                if (!empty($schedule_raw)) {
                    $maybe_json = json_decode($schedule_raw, true);
                    if (is_array($maybe_json)) {
                        $entries = $maybe_json;
                    } elseif (is_string($schedule_raw)) {
                        $entries = [$schedule_raw];
                    }
                }

                foreach ($entries as $entry) {
                    if (!is_string($entry)) {
                        continue;
                    }
                    $parts = explode('::', $entry);
                    if (count($parts) < 2) {
                        continue;
                    }

                    $day_key = strtolower(trim($parts[0]));
                    $time_str = trim($parts[1]);

                    if (!isset($day_labels[$day_key]) || $time_str === '') {
                        continue;
                    }
                    $day_label = $day_labels[$day_key];
                    $display_value = ($code !== '' && $title !== '') ? ($code . ' - ' . $title) : ($code !== '' ? $code : $title);
                    if ($class !== '') {
                        $display_value .= ' (' . $class . ')';
                    }

                    if (!isset($pending_schedule_grid[$time_str])) {
                        $pending_schedule_grid[$time_str] = [];
                    }
                    if (!isset($pending_schedule_grid[$time_str][$day_label])) {
                        $pending_schedule_grid[$time_str][$day_label] = [];
                    }
                    if ($display_value !== '') {
                        $pending_schedule_grid[$time_str][$day_label][] = $display_value;
                    }
                }
            }

            if (!empty($pending_schedule_grid)) {
                $pending_time_slots = array_keys($pending_schedule_grid);
                usort($pending_time_slots, function ($a, $b) use ($extract_start_minutes) {
                    return $extract_start_minutes($a) <=> $extract_start_minutes($b);
                });

                foreach ($pending_time_slots as $slot) {
                    $pending_time_label_map[$slot] = $format_time_range($slot);
                }
            }
        }
    }

    // Check if there is a rejected irregular request for the current term (latest one)
    $rejected_sql = "SELECT evaluated_at, updated_at, remarks, recommended_subjects
                     FROM enrollment
                     WHERE student_id = '" . escape($db_connect, $g_general_id) . "'
                     AND schoolyear_id = " . $current_school_year_id . "
                     AND sem = '" . $current_sem_trunc . "'
                     AND classification = 'IRREGULAR'
                     AND status = 'REJECTED'
                     ORDER BY evaluated_at DESC, updated_at DESC
                     LIMIT 1";
    $rejected_rows = mysqliquery_return($rejected_sql);
    if (!empty($rejected_rows)) {
        $has_rejected_request = true;
        $rejected_request_date = $rejected_rows[0]['evaluated_at'] ?? ($rejected_rows[0]['updated_at'] ?? '');
        $rejected_remarks = $rejected_rows[0]['remarks'] ?? '';

        // Decode any Dean-recommended subjects and resolve their titles
        $rejected_recommended = [];
        $raw_rec = $rejected_rows[0]['recommended_subjects'] ?? '';
        if (!empty($raw_rec)) {
            $tmp = json_decode($raw_rec, true);
            $codes = [];
            if (is_array($tmp)) {
                foreach ($tmp as $code) {
                    $code = trim((string)$code);
                    if ($code === '') {
                        continue;
                    }
                    $codes[] = strtoupper($code);
                }
            }

            if (!empty($codes)) {
                $codes = array_values(array_unique($codes));
                $in_list = [];
                foreach ($codes as $code) {
                    $in_list[] = "'" . escape($db_connect, $code) . "'";
                }
                $subject_title_map = [];
                if (!empty($in_list)) {
                    $sub_sql = "SELECT subject_code, subject_title FROM subject WHERE subject_code IN (" . implode(',', $in_list) . ")";
                    $sub_rows = mysqliquery_return($sub_sql);
                    if (!empty($sub_rows)) {
                        foreach ($sub_rows as $row) {
                            $s_code = strtoupper(trim((string)($row['subject_code'] ?? '')));
                            if ($s_code === '') {
                                continue;
                            }
                            $subject_title_map[$s_code] = $row['subject_title'] ?? '';
                        }
                    }
                }

                foreach ($codes as $code) {
                    $title = $subject_title_map[$code] ?? '';
                    if ($title !== '') {
                        $rejected_recommended[] = $code . ' - ' . $title;
                    } else {
                        $rejected_recommended[] = $code;
                    }
                }
            }
        }
    }

    // Check if there is an approved irregular request for the current term (latest one)
    $approved_sql = "SELECT enrollment_id, evaluated_at, updated_at
                     FROM enrollment
                     WHERE student_id = '" . escape($db_connect, $g_general_id) . "'
                     AND schoolyear_id = " . $current_school_year_id . "
                     AND sem = '" . $current_sem_trunc . "'
                     AND classification = 'IRREGULAR'
                     AND status = 'APPROVED'
                     ORDER BY evaluated_at DESC, updated_at DESC
                     LIMIT 1";
    $approved_rows = mysqliquery_return($approved_sql);
    if (!empty($approved_rows)) {
        $has_approved_request = true;
        $approved_request_id = (int)($approved_rows[0]['enrollment_id'] ?? 0);
        $approved_request_date = $approved_rows[0]['evaluated_at'] ?? ($approved_rows[0]['updated_at'] ?? '');

        // Load subjects for this approved request via normalized table
        $approved_subjects = [];
        if ($approved_request_id > 0) {
            $sub_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name
                        FROM enrollment_subjects es
                        JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id
                        JOIN subject s ON tc.subject_id = s.subject_id
                        JOIN class_section cs ON tc.class_id = cs.class_id
                        WHERE es.enrollment_id = " . $approved_request_id;
            $approved_subjects = mysqliquery_return($sub_sql);
        }

        if (!empty($approved_subjects)) {
            $day_labels = [
                'sunday'    => 'Sunday',
                'monday'    => 'Monday',
                'tuesday'   => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday'  => 'Thursday',
                'friday'    => 'Friday',
                'saturday'  => 'Saturday',
            ];

            $extract_start_minutes = function ($timeRange) {
                $parts = explode('-', $timeRange);
                $start = trim($parts[0] ?? '');
                if (strpos($start, ':') === false) {
                    return 24 * 60; // push unknowns to the end
                }
                [$h, $m] = array_pad(explode(':', $start), 2, '0');
                return ((int)$h * 60) + (int)$m;
            };

            $to_12hr = function ($time24) {
                if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time24), $m)) {
                    return $time24;
                }
                $h = (int)$m[1];
                $min = $m[2];
                $suffix = $h >= 12 ? 'PM' : 'AM';
                $h = $h % 12;
                if ($h === 0) {
                    $h = 12;
                }
                return $h . ':' . $min . ' ' . $suffix;
            };

            $format_time_range = function ($range) use ($to_12hr) {
                $parts = explode('-', $range);
                if (count($parts) < 2) {
                    return $range;
                }
                $start = trim($parts[0]);
                $end = trim($parts[1]);
                return $to_12hr($start) . ' - ' . $to_12hr($end);
            };

            foreach ($approved_subjects as $subj) {
                $code    = $subj['subject_code']  ?? '';
                $title   = $subj['subject_title'] ?? '';
                $class   = trim((string)($subj['class_name'] ?? ''));
                $schedule_raw = $subj['schedule'] ?? '';

                $entries = [];
                if (!empty($schedule_raw)) {
                    $maybe_json = json_decode($schedule_raw, true);
                    if (is_array($maybe_json)) {
                        $entries = $maybe_json;
                    } elseif (is_string($schedule_raw)) {
                        $entries = [$schedule_raw];
                    }
                }

                foreach ($entries as $entry) {
                    if (!is_string($entry)) {
                        continue;
                    }
                    $parts = explode('::', $entry);
                    if (count($parts) < 2) {
                        continue;
                    }

                    $day_key = strtolower(trim($parts[0]));
                    $time_str = trim($parts[1]);

                    if (!isset($day_labels[$day_key]) || $time_str === '') {
                        continue;
                    }
                    $day_label = $day_labels[$day_key];
                    $display_value = ($code !== '' && $title !== '') ? ($code . ' - ' . $title) : ($code !== '' ? $code : $title);
                    if ($class !== '') {
                        $display_value .= ' (' . $class . ')';
                    }

                    if (!isset($approved_schedule_grid[$time_str])) {
                        $approved_schedule_grid[$time_str] = [];
                    }
                    if (!isset($approved_schedule_grid[$time_str][$day_label])) {
                        $approved_schedule_grid[$time_str][$day_label] = [];
                    }
                    if ($display_value !== '') {
                        $approved_schedule_grid[$time_str][$day_label][] = $display_value;
                    }
                }
            }

            if (!empty($approved_schedule_grid)) {
                $approved_time_slots = array_keys($approved_schedule_grid);
                usort($approved_time_slots, function ($a, $b) use ($extract_start_minutes) {
                    return $extract_start_minutes($a) <=> $extract_start_minutes($b);
                });

                foreach ($approved_time_slots as $slot) {
                    $approved_time_label_map[$slot] = $format_time_range($slot);
                }
            }
        }
    }

    // Check if there is any active regular enrollment record for this
    // student for the CURRENT schoolyear/semester, and if so, build a
    // schedule grid from the unified enrollment_subjects table so
    // regular students see a static schedule preview after enrolling.

    $enrolled_sql = "SELECT enrollment_id, created_at AS date_enrolled
                     FROM enrollment
                     WHERE student_id = '" . escape($db_connect, $g_general_id) . "'
                     AND schoolyear_id = " . $current_school_year_id . "
                     AND sem = '" . $current_sem_trunc . "'
                     AND classification = 'REGULAR'
                     AND status = 'ENROLLED'
                     ORDER BY created_at DESC
                     LIMIT 1";
    $enrolled_rows = mysqliquery_return($enrolled_sql);
    if (!empty($enrolled_rows)) {
        $has_active_enrollment = true;
                $active_enrollment_date = $enrolled_rows[0]['date_enrolled'] ?? '';

                $enrolled_id = (int)($enrolled_rows[0]['enrollment_id'] ?? 0);

                if ($enrolled_id > 0) {
                        $regular_subjects = [];
                        $reg_sql = "SELECT s.subject_code, s.subject_title, tc.schedule, cs.class_name
                                                FROM enrollment_subjects es
                                                JOIN enrollment e ON es.enrollment_id = e.enrollment_id
                                                JOIN teacher_class tc ON es.teacher_class_id = tc.teacher_class_id
                                                JOIN subject s ON tc.subject_id = s.subject_id
                                                JOIN class_section cs ON tc.class_id = cs.class_id
                                                WHERE es.enrollment_id = " . $enrolled_id . "
                                                    AND e.classification = 'REGULAR'
                                                    AND e.status = 'ENROLLED'";
            $regular_subjects = mysqliquery_return($reg_sql);

            if (!empty($regular_subjects)) {
                $day_labels = [
                    'sunday'    => 'Sunday',
                    'monday'    => 'Monday',
                    'tuesday'   => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday'  => 'Thursday',
                    'friday'    => 'Friday',
                    'saturday'  => 'Saturday',
                ];

                $extract_start_minutes = function ($timeRange) {
                    $parts = explode('-', $timeRange);
                    $start = trim($parts[0] ?? '');
                    if (strpos($start, ':') === false) {
                        return 24 * 60; // push unknowns to the end
                    }
                    [$h, $m] = array_pad(explode(':', $start), 2, '0');
                    return ((int)$h * 60) + (int)$m;
                };

                $to_12hr = function ($time24) {
                    if (!preg_match('/^(\d{1,2}):(\d{2})$/', trim($time24), $m)) {
                        return $time24;
                    }
                    $h = (int)$m[1];
                    $min = $m[2];
                    $suffix = $h >= 12 ? 'PM' : 'AM';
                    $h = $h % 12;
                    if ($h === 0) {
                        $h = 12;
                    }
                    return $h . ':' . $min . ' ' . $suffix;
                };

                $format_time_range = function ($range) use ($to_12hr) {
                    $parts = explode('-', $range);
                    if (count($parts) < 2) {
                        return $range;
                    }
                    $start = trim($parts[0]);
                    $end = trim($parts[1]);
                    return $to_12hr($start) . ' - ' . $to_12hr($end);
                };

                foreach ($regular_subjects as $subj) {
                    $code    = $subj['subject_code']  ?? '';
                    $title   = $subj['subject_title'] ?? '';
                    $class   = trim((string)($subj['class_name'] ?? ''));
                    $schedule_raw = $subj['schedule'] ?? '';

                    $entries = [];
                    if (!empty($schedule_raw)) {
                        $maybe_json = json_decode($schedule_raw, true);
                        if (is_array($maybe_json)) {
                            $entries = $maybe_json;
                        } elseif (is_string($schedule_raw)) {
                            $entries = [$schedule_raw];
                        }
                    }

                    foreach ($entries as $entry) {
                        if (!is_string($entry)) {
                            continue;
                        }
                        $parts = explode('::', $entry);
                        if (count($parts) < 2) {
                            continue;
                        }

                        $day_key = strtolower(trim($parts[0]));
                        $time_str = trim($parts[1]);

                        if (!isset($day_labels[$day_key]) || $time_str === '') {
                            continue;
                        }
                        $day_label = $day_labels[$day_key];
                        $display_value = ($code !== '' && $title !== '') ? ($code . ' - ' . $title) : ($code !== '' ? $code : $title);
                        if ($class !== '') {
                            $display_value .= ' (' . $class . ')';
                        }

                        if (!isset($regular_schedule_grid[$time_str])) {
                            $regular_schedule_grid[$time_str] = [];
                        }
                        if (!isset($regular_schedule_grid[$time_str][$day_label])) {
                            $regular_schedule_grid[$time_str][$day_label] = [];
                        }
                        if ($display_value !== '') {
                            $regular_schedule_grid[$time_str][$day_label][] = $display_value;
                        }
                    }
                }

                if (!empty($regular_schedule_grid)) {
                    $regular_time_slots = array_keys($regular_schedule_grid);
                    usort($regular_time_slots, function ($a, $b) use ($extract_start_minutes) {
                        return $extract_start_minutes($a) <=> $extract_start_minutes($b);
                    });

                    foreach ($regular_time_slots as $slot) {
                        $regular_time_label_map[$slot] = $format_time_range($slot);
                    }
                }
            }
        }
    }
}

// Fetch available subjects / schedules for the student's program & year level
// for current school year/semester. Filter out subjects the student has
// already passed so they do not appear in the Available / Backlog lists.
$subjects = [];
if ($current_school_year_id > 0 && $current_sem !== '') {
    $conditions = [];
    $conditions[] = "tc.status = 0";
    $conditions[] = "cs.status = 0";
    $conditions[] = "tc.schoolyear_id = " . $current_school_year_id;
    $conditions[] = "tc.sem = '" . escape($db_connect, $current_sem) . "'";

    if ($student_program_id > 0) {
        $conditions[] = "tc.program_id = " . $student_program_id;
    }
    if (!is_null($effective_year_level) && $effective_year_level > 0) {
        $conditions[] = "tc.year_level = " . $effective_year_level;
    }

    $conditions_sql = implode(' AND ', $conditions);

    $subjects_sql = "SELECT tc.teacher_class_id, s.subject_code, s.subject_title, s.unit, cs.class_name, tc.schedule
                     FROM teacher_class tc
                     JOIN subject s ON tc.subject_id = s.subject_id
                     JOIN class_section cs ON tc.class_id = cs.class_id
                     WHERE " . $conditions_sql . "
                     ORDER BY s.subject_code, cs.class_name";

    $raw_subjects = mysqliquery_return($subjects_sql);

    // Remove any offerings for subjects the student has already passed.
    if (!empty($raw_subjects) && $student_id_no !== '') {
        foreach ($raw_subjects as $row) {
            $code = isset($row['subject_code']) ? trim((string)$row['subject_code']) : '';
            if ($code === '') {
                continue;
            }
            if (is_subject_passed_by_student($db_connect, $student_id_no, $code)) {
                continue;
            }
            $subjects[] = $row;
        }
    }
}

/**
 * Normalize semester values (e.g. "1", "1st Semester") to an integer order.
 */
function normalize_sem_order($sem)
{
    if ($sem === null) {
        return null;
    }
    $val = trim((string)$sem);
    if ($val === '') {
        return null;
    }
    // Direct numeric ("1", "2")
    if (ctype_digit($val)) {
        return (int)$val;
    }
    $val_l = strtolower($val);
    $map = [
        '1st semester' => 1,
        'first semester' => 1,
        '2nd semester' => 2,
        'second semester' => 2,
        '3rd semester' => 3,
        'third semester' => 3,
    ];
    return $map[$val_l] ?? null;
}

/**
 * Determine if a subject is passed based on final_grade records.
 *
 * Uses student_id_text + subject_code as the primary key and
 * evaluates pass/fail via remarks or converted_grade numeric value.
 */
function is_subject_passed_by_student($db_connect, $student_id_text, $subject_code)
{
    $student_id_text = trim((string)$student_id_text);
    $subject_code = trim((string)$subject_code);
    if ($student_id_text === '' || $subject_code === '') {
        return false;
    }

    $stud_esc = escape($db_connect, $student_id_text);
    $subj_esc = escape($db_connect, $subject_code);

    $sql = "SELECT remarks, converted_grade FROM final_grade
            WHERE student_id_text = '" . $stud_esc . "'
              AND subject_code = '" . $subj_esc . "'
            ORDER BY date_updated DESC, final_id DESC
            LIMIT 1";
    $rows = mysqliquery_return($sql);
    if (empty($rows)) {
        return false; // never taken or no grade yet
    }

    $row = $rows[0];
    $remarks = isset($row['remarks']) ? strtolower(trim((string)$row['remarks'])) : '';
    if ($remarks !== '') {
        if (strpos($remarks, 'pass') !== false || $remarks === 'p') {
            return true;
        }
        if (strpos($remarks, 'fail') !== false || $remarks === 'f') {
            return false;
        }
    }

    $conv = isset($row['converted_grade']) ? trim((string)$row['converted_grade']) : '';
    if ($conv === '') {
        return false;
    }
    $conv_num = (float)$conv;
    if ($conv_num <= 0) {
        return false;
    }

    // Common Philippine grading: 1.0 - 3.0 passing; >3.0 failing
    return $conv_num <= 3.0;
}

/**
 * Compute total required vs. earned units for all 1st year curriculum
 * subjects in the program's default curriculum.
 *
 * Returns an array [$required_units, $earned_units]. If no default
 * curriculum is configured, both values will be 0.
 */
function compute_year_unit_totals($db_connect, $student_id_text, $program_id, $year_level)
{
    $student_id_text = trim((string)$student_id_text);
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($student_id_text === '' || $program_id <= 0 || $year_level <= 0) {
        return [0, 0];
    }

    // Use the default curriculum for this program (status_allowable = 0)
    $sqlCur = "SELECT curriculum_id FROM curriculum_master
               WHERE program_id = " . $program_id . " AND status_allowable = 0
               ORDER BY curriculum_id DESC
               LIMIT 1";
    $curRows = mysqliquery_return($sqlCur);
    if (empty($curRows)) {
        return [0, 0];
    }

    $curriculum_id = (int)($curRows[0]['curriculum_id'] ?? 0);
    if ($curriculum_id <= 0) {
        return [0, 0];
    }

    $sqlSubj = "SELECT subject_code, unit
                 FROM curriculum
                 WHERE curriculum_id = " . $curriculum_id . "
                   AND status = 1
                   AND year_level = " . $year_level;
    $subjRows = mysqliquery_return($sqlSubj);
    if (empty($subjRows)) {
        return [0, 0];
    }

    $required_units = 0;
    $earned_units   = 0;

    foreach ($subjRows as $row) {
        $code = trim((string)($row['subject_code'] ?? ''));
        $unit = (float)($row['unit'] ?? 0);
        if ($code === '' || $unit <= 0) {
            continue;
        }

        $required_units += $unit;

        if (is_subject_passed_by_student($db_connect, $student_id_text, $code)) {
            $earned_units += $unit;
        }
    }

    return [$required_units, $earned_units];
}

/**
 * Find the earliest year level where the student has not yet completed all
 * required units based on the prospectus. Returns that year level (1,2,3,...)
 * or null when there are no backlogs up to the given current year level.
 */
function get_backlog_year_from_units($db_connect, $student_id_text, $program_id, $current_year_level)
{
    $student_id_text = trim((string)$student_id_text);
    $program_id = (int)$program_id;
    $current_year_level = (int)$current_year_level;

    if ($student_id_text === '' || $program_id <= 0 || $current_year_level <= 0) {
        return null;
    }

    // Only look at years strictly BEFORE the student's current database
    // year level. Example: a 3rd Year student is REGULAR as long as all
    // 1st and 2nd year units are complete; in-progress 3rd year units do
    // not make them irregular yet.
    for ($yl = 1; $yl < $current_year_level; $yl++) {
        [$required, $earned] = compute_year_unit_totals($db_connect, $student_id_text, $program_id, $yl);
        if ($required > 0 && $earned < $required) {
            return $yl;
        }
    }

    return null;
}

/**
 * Determine academic status (Regular/Irregular) from curriculum vs. completed subjects.
 *
 * A student is REGULAR if all required curriculum subjects from
 * previous terms are passed; otherwise IRREGULAR.
 */
function determine_academic_status_from_curriculum($db_connect, $student_id_text, $program_id, $year_level, $current_sem)
{
    $student_id_text = trim((string)$student_id_text);
    $program_id = (int)$program_id;
    $year_level = (int)$year_level;
    if ($student_id_text === '' || $program_id <= 0 || $year_level <= 0) {
        return 'Irregular';
    }

    // Use semester ordering when available so that a failed subject in an
    // earlier semester of the same year level (e.g., 3rd Year 1st Sem) also
    // makes a 3rd Year 2nd Sem student Irregular.
    $cur_sem_order = normalize_sem_order($current_sem);

    // Resolve the default curriculum for this program (same rule as
    // compute_year_unit_totals).
    $sqlCur = "SELECT curriculum_id FROM curriculum_master
               WHERE program_id = " . $program_id . " AND status_allowable = 0
               ORDER BY curriculum_id DESC
               LIMIT 1";
    $curRows = mysqliquery_return($sqlCur);
    if (empty($curRows)) {
        return 'Irregular';
    }

    $curriculum_id = (int)($curRows[0]['curriculum_id'] ?? 0);
    if ($curriculum_id <= 0) {
        return 'Irregular';
    }

    $sqlSubj = "SELECT subject_code, semester, year_level
                 FROM curriculum
                 WHERE curriculum_id = " . $curriculum_id . "
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

        // Identify curriculum subjects that belong to terms strictly before
        // the student's current term (year level + semester).
        $mustCheck = false;
        if ($yl < $year_level) {
            $mustCheck = true;
        } elseif ($yl === $year_level && $cur_sem_order !== null && $semOrder !== null && $semOrder < $cur_sem_order) {
            $mustCheck = true;
        }

        if (!$mustCheck) {
            continue;
        }

        if (!is_subject_passed_by_student($db_connect, $student_id_text, $code)) {
            return 'Irregular';
        }
    }

    return 'Regular';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>student/css/enrollment_status.css?v=<?php echo time(); ?>">
</head>

<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>

            <div class="container">
                <div class="page-inner">
                    
                    <?php if ($has_active_enrollment): ?>
                    <div class="alert alert-success bg-success text-white d-flex align-items-center p-4 mb-4 border-0" role="alert">
                        <i class="fas fa-check-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">You Are Successfully Enrolled</h5>
                            <p class="mb-0">
                                Your regular enrollment<?php echo $active_enrollment_date ? ' on <strong>' . date('F d, Y', strtotime($active_enrollment_date)) . '</strong>' : ''; ?> has been recorded.
                                You are now officially enrolled for the current term.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_pending_request): ?>
                    <div class="alert alert-warning d-flex align-items-center p-4 mb-3" role="alert">
                        <i class="fas fa-clock fa-2x me-3"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Pending</h5>
                            <p class="mb-0">Your enrollment request has been submitted<?php echo $pending_request_date ? ' on <strong>' . date('F d, Y', strtotime($pending_request_date)) . '</strong>' : ''; ?>. Please wait for Dean approval. You will be notified once your request has been reviewed.</p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && $has_approved_request): ?>
                    <div class="alert alert-success bg-success text-white d-flex align-items-center p-4 mb-3 border-0" role="alert">
                        <i class="fas fa-check-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Approved</h5>
                            <p class="mb-0">
                                Your irregular enrollment request<?php echo $approved_request_date ? ' on <strong>' . date('F d, Y', strtotime($approved_request_date)) . '</strong>' : ''; ?> has been <strong>approved</strong> by the Dean.
                                Please monitor your official enrollment record for any further updates.
                            </p>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && !$has_approved_request && $has_rejected_request): ?>
                    <div class="alert alert-danger bg-danger text-white d-flex align-items-center p-4 mb-3 border-0" role="alert">
                        <i class="fas fa-times-circle fa-2x me-3 text-white"></i>
                        <div>
                            <h5 class="alert-heading mb-1 fw-bold">Enrollment Request Rejected</h5>
                            <p class="mb-1">
                                Your previous enrollment request<?php echo $rejected_request_date ? ' on <strong>' . date('F d, Y', strtotime($rejected_request_date)) . '</strong>' : ''; ?> has been <strong>rejected</strong> by the Dean.
                                You may review the subjects and submit a new request.
                            </p>
                            <?php if (!empty($rejected_remarks)): ?>
                            <p class="mb-0 small"><strong>Dean's Remarks:</strong> <?php echo htmlspecialchars($rejected_remarks); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($rejected_recommended)): ?>
                            <p class="mb-0 small mt-1">
                                <strong>Dean's Recommended Subjects:</strong>
                                <?php echo htmlspecialchars(implode(', ', $rejected_recommended)); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php
                    // Global banner indicating whether the currently
                    // selected term is open for enrollment or
                    // read-only. Uses $is_term_open and $current_sy.
                    include DOMAIN_PATH . '/global/term_status_banner.php';
                    ?>


                    <div class="row mb-4">
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">ID Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($student_id_no ?: 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Student Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student_fullname_display ?: 'N/A'); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Academic Status</div>
                                <div class="info-value text-primary"><?php echo htmlspecialchars($student_academic_status); ?></div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 mb-3">
                            <div class="card student-info-card p-2">
                                <div class="info-label">Year Level</div>
                                <div class="info-value">
                                    <?php
                                    if (!is_null($student_year_level_display) && $student_year_level_display > 0) {
                                        $y_level = $student_year_level_display;
                                        $suffix = ['th', 'st', 'nd', 'rd'];
                                        $val = $y_level % 100;
                                        echo $y_level . ($suffix[($val - 20) % 10] ?? $suffix[$val] ?? $suffix[0]) . " Year";
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($has_active_enrollment && empty($has_pending_request) && empty($has_approved_request) && !empty($regular_time_slots)): ?>
                    <div class="card card-round mb-4" id="enrolled_schedule_print_area">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold mb-0">Enrolled Subjects Schedule</h4>
                                <div class="btn-group no-print" role="group" aria-label="Enrolled schedule actions">
                                    <a href="<?php echo BASE_URL; ?>student/process/print_enrolled_schedule_pdf.php" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-print me-1"></i> Print / Save as PDF
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>student/process/export_enrolled_schedule_excel.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i> Save as Excel
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-success"></i>
                                This is your current enrolled class schedule for the term.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($regular_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($regular_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($regular_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($regular_day_order as $day_label):
                                                $subjects_cell = $regular_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects_cell) ? implode('<br>', array_map('htmlspecialchars', $subjects_cell)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($has_pending_request && !empty($pending_time_slots)): ?>
                    <div class="card card-round mb-4">
                        <div class="card-body">
                            <h4 class="fw-bold mb-3">Requested Subjects Schedule</h4>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-primary"></i>
                                Time shows in AM/PM; each cell displays subject code with its name for that day/time.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($pending_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pending_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($pending_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($pending_day_order as $day_label):
                                                $subjects = $pending_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects) ? implode('<br>', array_map('htmlspecialchars', $subjects)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && $has_approved_request && !empty($approved_time_slots)): ?>
                    <div class="card card-round mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="fw-bold mb-0">Approved Subjects Schedule</h4>
                                <div class="btn-group" role="group" aria-label="Approved schedule actions">
                                    <a href="<?php echo BASE_URL; ?>student/process/print_irregular_schedule_pdf.php" target="_blank" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-print me-1"></i> Print / Save as PDF
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>student/process/export_irregular_schedule_excel.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-file-excel me-1"></i> Save as Excel
                                    </a>
                                </div>
                            </div>
                            <p class="text-muted small mb-3">
                                <i class="fas fa-info-circle text-success"></i>
                                This is the schedule of your <strong>approved</strong> irregular enrollment request.
                            </p>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Time</th>
                                            <?php foreach ($approved_day_order as $day_label): ?>
                                                <th><?php echo htmlspecialchars($day_label); ?></th>
                                            <?php endforeach; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($approved_time_slots as $time_slot): ?>
                                        <tr>
                                            <td class="fw-semibold text-nowrap"><?php echo htmlspecialchars($approved_time_label_map[$time_slot] ?? $time_slot); ?></td>
                                            <?php foreach ($approved_day_order as $day_label):
                                                $subjects = $approved_schedule_grid[$time_slot][$day_label] ?? [];
                                                $cell_value = !empty($subjects) ? implode('<br>', array_map('htmlspecialchars', $subjects)) : '';
                                            ?>
                                                <td><?php echo $cell_value; ?></td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (!$has_pending_request && !$has_approved_request && !$has_active_enrollment): ?>
                    <div class="row subjects-container-scroll">
                        <div class="col-md-8">
                            <!-- Container 1: Available Subjects -->
                            <div class="card card-round">
                                <div class="card-body">
                                    <ul class="nav nav-tabs mb-3" id="subjects_tabs" role="tablist">
                                        <li class="nav-item" role="presentation" id="subjects_tab_available_item">
                                            <button class="nav-link active" id="subjects_tab_available" type="button" role="tab">
                                                Available&nbsp;<span class="badge bg-secondary rounded-pill" id="available_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                                        <li class="nav-item" role="presentation" id="subjects_tab_backlog_item">
                                            <button class="nav-link" id="subjects_tab_backlog" type="button" role="tab">
                                                Backlog&nbsp;<span class="badge bg-secondary rounded-pill" id="backlog_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation" id="subjects_tab_higher_item">
                                            <button class="nav-link" id="subjects_tab_higher" type="button" role="tab">
                                                Higher-Year&nbsp;<span class="badge bg-secondary rounded-pill" id="higher_year_subjects_count">0</span>
                                            </button>
                                        </li>
                                        <?php endif; ?>
                                    </ul>

                                    <div id="available_subjects_panel" class="subjects-tab-panel">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 id="available_subjects_title" class="fw-bold mb-0">Available Subjects for Your Program</h4>
                                        </div>
                                        <p id="available_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-primary"></i> Choose your current-term subjects. The system will assign the best section automatically based on your chosen subjects.
                                        </p>

                                        <div class="row mb-4">
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <?php
                                                // For students with an assigned program, show a simple
                                                // read-only text field instead of a disabled dropdown
                                                // to make it clear that the program cannot be changed.
                                                if ($student_program_id > 0 && !empty($programs)) {
                                                    $program_label = '';
                                                    foreach ($programs as $program) {
                                                        if ((int)$program['program_id'] === (int)$student_program_id) {
                                                            $program_label = !empty($program['short_name']) ? $program['short_name'] : $program['program'];
                                                            break;
                                                        }
                                                    }
                                                    if ($program_label === '' && !empty($programs[0])) {
                                                        $program_label = !empty($programs[0]['short_name']) ? $programs[0]['short_name'] : $programs[0]['program'];
                                                    }
                                                ?>
                                                    <label for="program_name_display" class="form-labe fw-bold">Program</label>
                                                    <input type="text" class="form-control" title="Program" id="program_name_display" value="<?php echo htmlspecialchars($program_label); ?>" readonly>
                                                    <input type="hidden" name="program_id" id="program_id" value="<?php echo (int)$student_program_id; ?>">
                                                <?php } else { ?>
                                                    <label for="program_id" class="form-labe fw-bold">Program</label>
                                                    <select class="form-select" name="program_id" id="program_id">
                                                        <?php if ($student_program_id <= 0): ?>
                                                            <option value="">All Programs</option>
                                                        <?php endif; ?>
                                                        <?php
                                                        if (!empty($programs)) {
                                                            $seen_programs = [];
                                                            foreach ($programs as $program) {
                                                                $pid = (int)$program['program_id'];
                                                                $label = !empty($program['short_name']) ? $program['short_name'] : $program['program'];

                                                                // Avoid duplicate entries with the same label
                                                                if (isset($seen_programs[$label])) {
                                                                    continue;
                                                                }
                                                                $seen_programs[$label] = true;

                                                                $selected = ($pid === $student_program_id) ? 'selected' : '';
                                                        ?>
                                                                <option value="<?php echo $pid; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($label); ?></option>
                                                        <?php
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                <?php } ?>
                                            </div>

                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <label for="fiscal_year_display" class="form-labe fw-bold">School Year</label>
                                                <?php
                                                // Normalize current school year and semester into separate labels
                                                $sy_text  = $current_sy['school_year'] ?? '';
                                                $sem_text = $current_sy['sem'] ?? '';
                                                ?>
                                                <input type="text" class="form-control" title="School Year" id="fiscal_year_display" value="<?php echo htmlspecialchars($sy_text); ?>" readonly>
                                            </div>

                                            <div class="col-md-4">
                                                <label for="semester_display" class="form-labe fw-bold">Semester</label>
                                                <input type="text" class="form-control" title="Semester" id="semester_display" value="<?php echo htmlspecialchars($sem_text); ?>" readonly>
                                            </div>
                                        </div>

                                        <?php if (strcasecmp($student_academic_status, 'Regular') === 0): ?>
                                        <div class="row mb-3">
                                            <div class="col-md-4" id="section_select_group">
                                                <label for="class_id" class="form-labe fw-bold">Section</label>
                                                <select class="form-select" name="class_id" id="class_id">
                                                    <?php
                                                    if (!empty($sections)) {
                                                        $seen_sections = [];
                                                        foreach ($sections as $section) {
                                                            $cid = (int)$section['class_id'];

                                                            if (isset($seen_sections[$cid])) {
                                                                continue;
                                                            }
                                                            $seen_sections[$cid] = true;
                                                            $is_full = !empty($section_capacity_info[$cid]['is_full']);
                                                            $disabled_attr = $is_full ? ' disabled' : '';
                                                            $label = $section['class_name'] . ($is_full ? ' (Full)' : '');
                                                    ?>
                                                            <option value="<?php echo $cid; ?>"<?php echo $disabled_attr; ?>><?php echo htmlspecialchars($label); ?></option>
                                                    <?php
                                                        }
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <?php if (strcasecmp($student_academic_status, 'Regular') === 0): ?>
                                        <div id="autoFillRow" class="d-flex justify-content-start align-items-center mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="autoFill" checked>
                                                <label class="form-check-label" for="autoFill">Auto-fill <b>"My Subject Cart"</b> with all subjects.</label>
                                            </div>
                                        </div>
                                        <?php endif; ?>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="subjects_prereq_header">Pre-Req</th>
                                                        <th id="subjects_action_header">Action</th>
                                                    </tr>
                                                    <tr id="subjects_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_subject_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_subject_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">Loading subjects...</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card subject-cart-card">
                                <div class="card-header d-flex justify-content-between align-items-center bg-white">
                                    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-book-open me-2"></i> My Subject Cart</h5>
                                    <span class="badge bg-primary rounded-pill" id="cart_count">0</span>
                                </div>
                                
                                <div class="cart-body-container">
                                    <div class="empty-cart-state" id="cart_empty_state">
                                        <i class="fas fa-book fa-3x mb-2"></i>
                                        <p class="mb-0">Subject is empty.</p>
                                    </div>
                                    
                                    <div id="cart_list" style="display:none;">
                                        <div id="cart_items"></div>
                                    </div>
                                </div>
                                
                                <div class="btn-proceed-container">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="fw-bold">Required Units:</span>
                                        <span class="fw-bold" id="required_units_label">N/A</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="fw-bold">Total Units:</span>
                                        <span class="fw-bold text-primary" id="cart_total_units">0.0</span>
                                    </div>
                                    <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-3" id="cart_section_row">
                                        <span class="fw-bold">Your Section Classification:</span>
                                        <span id="cart_section_label" class="ms-2">Not yet determined</span>
                                    </div>
                                    <?php endif; ?>
                                    <button class="btn btn-proceed">ENROLL IN THIS CLASS SECTION</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-12">
                            <?php if (strcasecmp($student_academic_status, 'Regular') !== 0): ?>
                            <!-- Container 2: Backlog / Higher-Year Subjects (shown only when applicable) -->
                            <div class="card card-round" id="backlog_container_card">
                                <div class="card-body">
                                    <div class="subjects-tab-panel" id="backlog_subjects_card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="fw-bold mb-0">Backlog Subjects Offered This Term</h4>
                                        </div>
                                        <p id="backlog_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-warning"></i>
                                            These are backlog subject(s) from previous term(s) that are offered this term. You may add them to your cart as needed.
                                        </p>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="backlog_subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="backlog_schedule_header">Schedule</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    <tr id="backlog_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_backlog_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="backlog_subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No backlog subjects available for this term.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Container 3: Higher-Year Subjects (shown only for irregular students when applicable) -->
                            <div class="card card-round mt-4" id="higher_year_container_card">
                                <div class="card-body">
                                    <div class="subjects-tab-panel" id="higher_year_subjects_card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h4 class="fw-bold mb-0">Higher-Year Subjects Offered This Term</h4>
                                        </div>
                                        <p id="higher_year_subjects_hint" class="text-muted small mb-3">
                                            <i class="fas fa-info-circle text-info"></i>
                                            These are higher-year subject(s) without pre-requisites that are offered this term.
                                        </p>

                                        <div class="table-responsive subject-table-wrapper">
                                            <table class="table" id="higher_year_subjects_table">
                                                <thead>
                                                    <tr>
                                                        <th>Code</th>
                                                        <th>Subject</th>
                                                        <th>Section</th>
                                                        <th>Units</th>
                                                        <th id="higher_year_schedule_header">Schedule</th>
                                                        <th>Action</th>
                                                    </tr>
                                                    <tr id="higher_year_filter_row">
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_code" placeholder="Search code"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_title" placeholder="Search subject"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_section" placeholder="Search section"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_units" placeholder="Units"></th>
                                                        <th><input type="text" class="form-control form-control-sm" id="filter_higher_schedule" placeholder="Search schedule"></th>
                                                        <th></th>
                                                    </tr>
                                                </thead>
                                                <tbody id="higher_year_subjects_tbody">
                                                    <tr>
                                                        <td colspan="6" class="text-center text-muted">No higher-year subjects available for this term.</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="card card-round mt-4" id="schedule_preview_card">
                                <div class="card-body">
                                    <h4 class="fw-bold mb-4">Schedule Preview</h4>
                                    <div class="table-responsive">
                                        <table class="table table-bordered schedule-table">
                                            <thead>
                                                <tr>
                                                    <th style="width: 10%;"></th>
                                                    <?php
                                                    // Days shown in the schedule preview (no Sunday classes).
                                                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                                    foreach ($days as $day): ?>
                                                        <th style="width: 15%;" data-day="<?php echo $day; ?>"><?php echo strtoupper($day); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody id="schedule_table_body">
                                                <?php 
                                                $timeSlots = [
                                                    '07:00' => '7am',
                                                    '08:00' => '8am',
                                                    '09:00' => '9am',
                                                    '10:00' => '10am',
                                                    '11:00' => '11am',
                                                    '12:00' => '12pm',
                                                    '13:00' => '1pm',
                                                    '14:00' => '2pm',
                                                    '15:00' => '3pm',
                                                    '16:00' => '4pm',
                                                    '17:00' => '5pm',
                                                    '18:00' => '6pm',
                                                    '19:00' => '7pm',
                                                    '20:00' => '8pm',
                                                    '21:00' => '9pm',
                                                    '22:00' => '10pm',
                                                ];

                                                foreach ($timeSlots as $time24 => $label): ?>
                                                <tr data-time="<?php echo $time24; ?>">
                                                    <td class="text-center small"><?php echo $label; ?></td>
                                                    <?php foreach ($days as $day): ?>
                                                        <td data-day="<?php echo $day; ?>"></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

    <?php if (!$has_pending_request && !$has_approved_request): ?>
    <script>
        window.enrollmentPageConfig = {
            sections: <?php echo json_encode($sections ?: []); ?>,
            studentYearLevel: <?php echo !is_null($student_year_level) ? (int)$student_year_level : 'null'; ?>,
            studentAcademicStatus: "<?php echo htmlspecialchars($student_academic_status, ENT_QUOTES, 'UTF-8'); ?>",
            baseProcessUrl: "<?php echo BASE_URL; ?>",
            isTermOpen: <?php echo $is_term_open ? 'true' : 'false'; ?>
        };
    </script>
    <script src="<?php echo BASE_URL; ?>student/js/enrollment_status.js?v=<?php echo time(); ?>"></script>
    <?php endif; ?>
</body>
</html>