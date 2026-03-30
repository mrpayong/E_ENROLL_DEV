<?php
// Helper functions for Dean views to compute backlog subjects
// based on curriculum vs. grades. Kept inside the dean folder
// as requested, separate from student-facing enrollment code.

if (!function_exists('dean_normalize_sem_order')) {
    function dean_normalize_sem_order($sem)
    {
        if ($sem === null) return null;
        $map = [
            '1st semester'   => 1,
            'first semester' => 1,
            '2nd semester'   => 2,
            'second semester'=> 2,
            '3rd semester'   => 3,
            'third semester' => 3,
        ];
        $val = strtolower(trim($sem));
        if ($val === '') return null;
        return ctype_digit($val) ? (int)$val : ($map[$val] ?? null);
    }
}

if (!function_exists('dean_get_default_curriculum')) {
    function dean_get_default_curriculum($db, $program_id)
    {
        $program_id = (int)$program_id;
        if ($program_id <= 0) return 0;

        $row = mysqliquery_return(
            "SELECT curriculum_id FROM curriculum_master " .
            "WHERE program_id = $program_id AND status_allowable = 0 " .
            "ORDER BY curriculum_id DESC LIMIT 1"
        )[0] ?? null;

        return (int)($row['curriculum_id'] ?? 0);
    }
}

if (!function_exists('dean_is_subject_passed_by_student')) {
    function dean_is_subject_passed_by_student($db, $student_id_text, $subject_code)
    {
        $sql = "SELECT remarks, converted_grade FROM final_grade " .
               "WHERE student_id_text='" . escape($db, $student_id_text) . "' " .
               "AND subject_code='" . escape($db, $subject_code) . "' " .
               "ORDER BY date_updated DESC, final_id DESC LIMIT 1";

        $row = mysqliquery_return($sql)[0] ?? null;
        if (!$row) return false;

        $remarks = strtolower(trim($row['remarks'] ?? ''));
        if ($remarks !== '') {
            if (strpos($remarks, 'pass') !== false || $remarks === 'p') {
                return true;
            }
        }

        $conv = trim((string)($row['converted_grade'] ?? ''));
        if ($conv === '') return false;

        $val = (float)$conv;
        return ($val > 0 && $val <= 3.0);
    }
}

if (!function_exists('dean_list_backlog_subject_codes')) {
    // List backlog subject codes for a student based on the default
    // curriculum: all required subjects from previous terms that are not yet passed.
    function dean_list_backlog_subject_codes($db, $student_id_text, $program_id, $year_level, $current_sem)
    {
        $program_id = (int)$program_id;
        $year_level = (int)$year_level;
        if ($program_id <= 0 || $year_level <= 0) return [];

        $cur_sem_order = dean_normalize_sem_order($current_sem);
        $curriculum_id = dean_get_default_curriculum($db, $program_id);
        if (!$curriculum_id) return [];

        $sqlSubj = "SELECT subject_code, semester, year_level " .
                   "FROM curriculum " .
                   "WHERE curriculum_id = " . (int)$curriculum_id . " " .
                   "AND status = 1";
        $subjRows = mysqliquery_return($sqlSubj);
        if (empty($subjRows)) return [];

        $backlogs = [];
        foreach ($subjRows as $row) {
            $subjCode = trim((string)($row['subject_code'] ?? ''));
            if ($subjCode === '') continue;

            $yl = isset($row['year_level']) ? (int)$row['year_level'] : 0;
            $semOrder = dean_normalize_sem_order($row['semester'] ?? '');

            // Only consider subjects from previous terms
            if ($yl < $year_level) {
                $mustCheck = true;
            } elseif ($yl === $year_level && $cur_sem_order !== null && $semOrder !== null && $semOrder < $cur_sem_order) {
                $mustCheck = true;
            } else {
                $mustCheck = false;
            }

            if (!$mustCheck) continue;

            if (!dean_is_subject_passed_by_student($db, $student_id_text, $subjCode)) {
                $backlogs[] = $subjCode;
            }
        }

        return array_values(array_unique($backlogs));
    }
}

if (!function_exists('dean_determine_academic_status_from_curriculum')) {
    // Determine academic status (Regular/Irregular) from curriculum vs.
    // completed subjects by checking whether there are any backlog
    // subjects from previous terms.
    function dean_determine_academic_status_from_curriculum($db, $student_id_text, $program_id, $year_level, $current_sem)
    {
        $backlogs = dean_list_backlog_subject_codes($db, $student_id_text, $program_id, $year_level, $current_sem);
        return empty($backlogs) ? 'Regular' : 'Irregular';
    }
}
