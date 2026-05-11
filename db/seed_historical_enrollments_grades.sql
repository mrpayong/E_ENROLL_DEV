-- Seed historical enrollment and final-grade data.
--
-- Assumptions:
--   1. Students, sections, programs, curriculums, subjects, teachers, and fiscal years already exist.
--   2. Sections are the active/default section set, but they can be reused for historical rows.
--   3. Course/section limits are already set to 5.
--
-- What this creates:
--   1. Missing teacher_class schedules for fiscal years up to the default fiscal year.
--   2. Enrollment rows for students, beginning from 2022-2023 1st Semester.
--   3. Final-grade rows for completed fiscal years before the default fiscal year.
--
-- Data behavior:
--   - Most generated grades are PASSED.
--   - Student slot 05 in every section fails one subject in the term immediately before
--     the default term. This gives the registrar/student logic some irregular students.

START TRANSACTION;

CREATE TEMPORARY TABLE tmp_seed_section_index AS
SELECT
    cs.class_id,
    cs.class_name,
    cs.program_id,
    cs.year_level,
    cs.sec_limit,
    ROW_NUMBER() OVER (
        PARTITION BY cs.program_id, cs.year_level
        ORDER BY cs.class_id
    ) AS section_index
FROM class_section cs
WHERE cs.status = 0;

CREATE TEMPORARY TABLE tmp_seed_student_base AS
SELECT
    st.student_id,
    st.student_id_no,
    st.firstname,
    st.middle_name,
    st.lastname,
    st.suffix_name,
    st.year_level AS current_year_level,
    st.program_id,
    st.curriculum_id,
    st.major,
    st.class_id AS current_class_id,
    st.department_id,
    COALESCE(si.section_index, 1) AS section_index,
    CAST(RIGHT(st.student_id_no, 2) AS UNSIGNED) AS student_slot
FROM student st
LEFT JOIN tmp_seed_section_index si
    ON si.class_id = st.class_id
WHERE st.student_id_no LIKE 'STU-%'
  AND st.program_id > 0
  AND st.curriculum_id > 0
  AND st.year_level > 0;

CREATE TEMPORARY TABLE tmp_seed_default_sy AS
SELECT
    school_year_id,
    school_year,
    sem,
    date_from
FROM school_year
WHERE isDefault = 1
  AND flag_used = 1
ORDER BY createdAt DESC
LIMIT 1;

CREATE TEMPORARY TABLE tmp_seed_prev_default_term AS
SELECT
    sy.school_year_id,
    sy.school_year,
    sy.sem
FROM tmp_seed_default_sy dsy
INNER JOIN school_year sy
    ON (
        UPPER(dsy.sem) LIKE '1ST%'
        AND sy.school_year = CONCAT(
            CAST(SUBSTRING_INDEX(dsy.school_year, '-', 1) AS UNSIGNED) - 1,
            '-',
            CAST(SUBSTRING_INDEX(dsy.school_year, '-', -1) AS UNSIGNED) - 1
        )
        AND UPPER(sy.sem) LIKE '2ND%'
    )
    OR (
        UPPER(dsy.sem) LIKE '2ND%'
        AND sy.school_year = dsy.school_year
        AND UPPER(sy.sem) LIKE '1ST%'
    )
LIMIT 1;

-- 1. Create missing schedules for all fiscal years up to the default term.
INSERT INTO teacher_class (
    teacher_id,
    class_id,
    subject_id,
    subject_text,
    schedule,
    sem,
    schoolyear_id,
    program_id,
    room,
    year_level,
    unit,
    lec_lab,
    section_limit,
    total_hours
)
WITH
teachers AS (
    SELECT
        user_id,
        ROW_NUMBER() OVER (ORDER BY user_id) AS teacher_no
    FROM users
    WHERE JSON_CONTAINS(user_role, '\"4\"')
      AND status = 0
),
teacher_count AS (
    SELECT COUNT(*) AS total_teachers FROM teachers
),
eligible_courses AS (
    SELECT
        sy.school_year_id,
        sy.sem,
        cs.class_id,
        cs.class_name,
        cs.sec_limit,
        c.program_id,
        c.year_level,
        c.subject_id,
        c.subject_title,
        c.subject_code,
        c.unit,
        c.lec_lab,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(c.lec_lab, '$[0]')) AS UNSIGNED) AS lec_hours,
        CAST(JSON_UNQUOTE(JSON_EXTRACT(c.lec_lab, '$[1]')) AS UNSIGNED) AS lab_hours,
        ROW_NUMBER() OVER (
            PARTITION BY sy.school_year_id, cs.class_id
            ORDER BY c.subject_code, c.subject_id
        ) AS section_course_no
    FROM school_year sy
    INNER JOIN tmp_seed_default_sy dsy
        ON sy.school_year_id <= dsy.school_year_id
    INNER JOIN curriculum c
        ON UPPER(c.semester) = UPPER(sy.sem)
       AND c.status = 0
    INNER JOIN subject s
        ON s.subject_id = c.subject_id
       AND s.program_id = c.program_id
       AND s.status = 0
    INNER JOIN class_section cs
        ON cs.program_id = c.program_id
       AND cs.year_level = c.year_level
       AND cs.status = 0
    WHERE NOT EXISTS (
        SELECT 1
        FROM teacher_class existing_tc
        WHERE existing_tc.class_id = cs.class_id
          AND existing_tc.subject_id = c.subject_id
          AND existing_tc.schoolyear_id = sy.school_year_id
          AND existing_tc.status = 0
    )
),
slotted_courses AS (
    SELECT
        ec.*,
        FLOOR(MOD(ec.section_course_no - 1, 12) / 2) AS day_index,
        MOD(ec.section_course_no - 1, 2) AS block_index
    FROM eligible_courses ec
),
timed_courses AS (
    SELECT
        sc.*,
        CASE sc.day_index
            WHEN 0 THEN 'Monday'
            WHEN 1 THEN 'Tuesday'
            WHEN 2 THEN 'Wednesday'
            WHEN 3 THEN 'Thursday'
            WHEN 4 THEN 'Friday'
            ELSE 'Saturday'
        END AS day_name,
        CASE sc.block_index
            WHEN 0 THEN '07:00'
            ELSE '13:00'
        END AS start_time,
        CASE
            WHEN (COALESCE(sc.lec_hours, 0) + COALESCE(sc.lab_hours, 0)) > 0
                THEN COALESCE(sc.lec_hours, 0) + COALESCE(sc.lab_hours, 0)
            ELSE sc.unit
        END AS total_hours_value,
        CONCAT('ROOM-', sc.class_id) AS room_name
    FROM slotted_courses sc
),
formatted_courses AS (
    SELECT
        tc.*,
        TIME_FORMAT(
            ADDTIME(
                CAST(CONCAT(tc.start_time, ':00') AS TIME),
                SEC_TO_TIME(CASE WHEN tc.lab_hours > 0 AND tc.lec_hours > 0 THEN tc.lec_hours ELSE tc.total_hours_value END * 3600)
            ),
            '%H:%i'
        ) AS mid_time,
        TIME_FORMAT(
            ADDTIME(
                CAST(CONCAT(tc.start_time, ':00') AS TIME),
                SEC_TO_TIME(tc.total_hours_value * 3600)
            ),
            '%H:%i'
        ) AS end_time,
        ROW_NUMBER() OVER (
            PARTITION BY tc.school_year_id, tc.day_index, tc.block_index
            ORDER BY tc.program_id, tc.year_level, tc.class_id, tc.subject_code
        ) AS slot_teacher_no
    FROM timed_courses tc
)
SELECT
    t.user_id,
    fc.class_id,
    fc.subject_id,
    fc.subject_title,
    CASE
        WHEN fc.lab_hours > 0 AND fc.lec_hours > 0 THEN JSON_ARRAY(
            CONCAT(fc.day_name, '::', fc.start_time, '-', fc.mid_time, '::', fc.room_name, '::lec'),
            CONCAT(fc.day_name, '::', fc.mid_time, '-', fc.end_time, '::', fc.room_name, '::lab')
        )
        WHEN fc.lab_hours > 0 THEN JSON_ARRAY(
            CONCAT(fc.day_name, '::', fc.start_time, '-', fc.end_time, '::', fc.room_name, '::lab')
        )
        ELSE JSON_ARRAY(
            CONCAT(fc.day_name, '::', fc.start_time, '-', fc.end_time, '::', fc.room_name, '::lec')
        )
    END,
    fc.sem,
    fc.school_year_id,
    fc.program_id,
    CASE
        WHEN fc.lab_hours > 0 AND fc.lec_hours > 0 THEN JSON_OBJECT(
            CONCAT(fc.room_name, '_', fc.day_name),
            JSON_ARRAY(CONCAT(fc.start_time, '-', fc.mid_time), CONCAT(fc.mid_time, '-', fc.end_time))
        )
        ELSE JSON_OBJECT(
            CONCAT(fc.room_name, '_', fc.day_name),
            JSON_ARRAY(CONCAT(fc.start_time, '-', fc.end_time))
        )
    END,
    fc.year_level,
    fc.unit,
    fc.lec_lab,
    fc.sec_limit,
    fc.total_hours_value
FROM formatted_courses fc
CROSS JOIN teacher_count tcount
INNER JOIN teachers t
    ON t.teacher_no = MOD(fc.slot_teacher_no - 1, tcount.total_teachers) + 1
WHERE tcount.total_teachers > 0;

CREATE TEMPORARY TABLE tmp_seed_enrollment_targets AS
SELECT DISTINCT
    sb.student_id_no,
    sb.current_year_level,
    sb.program_id,
    sb.curriculum_id,
    sb.section_index,
    sb.student_slot,
    sy.school_year_id,
    sy.school_year,
    sy.sem,
    LEAST(
        GREATEST(CAST(SUBSTRING_INDEX(sy.school_year, '-', 1) AS UNSIGNED) - 2021, 1),
        sb.current_year_level
    ) AS target_year_level
FROM tmp_seed_student_base sb
INNER JOIN school_year sy
INNER JOIN tmp_seed_default_sy dsy
    ON sy.school_year_id < dsy.school_year_id
WHERE sy.flag_used != 0

UNION

SELECT DISTINCT
    sb.student_id_no,
    sb.current_year_level,
    sb.program_id,
    sb.curriculum_id,
    sb.section_index,
    sb.student_slot,
    pdt.school_year_id,
    pdt.school_year,
    pdt.sem,
    CASE
        WHEN UPPER(dsy.sem) LIKE '1ST%' THEN GREATEST(sb.current_year_level - 1, 1)
        ELSE sb.current_year_level
    END AS target_year_level
FROM tmp_seed_student_base sb
CROSS JOIN tmp_seed_default_sy dsy
INNER JOIN tmp_seed_prev_default_term pdt

UNION

SELECT DISTINCT
    sb.student_id_no,
    sb.current_year_level,
    sb.program_id,
    sb.curriculum_id,
    sb.section_index,
    sb.student_slot,
    dsy.school_year_id,
    dsy.school_year,
    dsy.sem,
    sb.current_year_level AS target_year_level
FROM tmp_seed_student_base sb
CROSS JOIN tmp_seed_default_sy dsy;

-- 2. Create enrollment rows.
INSERT INTO enrollments (
    student_id_no,
    teacher_class_id,
    subject_id,
    class_id,
    program_id,
    curriculum_id,
    section_name,
    schedule,
    school_year_id,
    sem,
    date_enrolled,
    status
)
SELECT
    et.student_id_no,
    tc.teacher_class_id,
    tc.subject_id,
    target_cs.class_id,
    et.program_id,
    et.curriculum_id,
    target_cs.class_name,
    tc.schedule,
    et.school_year_id,
    et.sem,
    DATE_ADD(COALESCE(sy.enrollment_start_date, sy.date_from, CURRENT_DATE()), INTERVAL et.student_slot DAY),
    'Enrolled'
FROM tmp_seed_enrollment_targets et
INNER JOIN curriculum c
    ON c.curriculum_id = et.curriculum_id
   AND c.program_id = et.program_id
   AND c.year_level = et.target_year_level
   AND UPPER(c.semester) = UPPER(et.sem)
   AND c.status = 0
INNER JOIN tmp_seed_section_index target_cs
    ON target_cs.program_id = et.program_id
   AND target_cs.year_level = et.target_year_level
   AND target_cs.section_index = et.section_index
INNER JOIN teacher_class tc
    ON tc.schoolyear_id = et.school_year_id
   AND tc.class_id = target_cs.class_id
   AND tc.subject_id = c.subject_id
   AND tc.program_id = et.program_id
   AND UPPER(tc.sem) = UPPER(et.sem)
   AND tc.status = 0
INNER JOIN school_year sy
    ON sy.school_year_id = et.school_year_id
WHERE NOT EXISTS (
    SELECT 1
    FROM enrollments existing_e
    WHERE existing_e.student_id_no = et.student_id_no
      AND existing_e.teacher_class_id = tc.teacher_class_id
      AND existing_e.school_year_id = et.school_year_id
      AND UPPER(existing_e.sem) = UPPER(et.sem)
);

-- 3. Create completed-term final grades.
INSERT INTO final_grade (
    teacher_class_id,
    student_id,
    student_name,
    student_id_text,
    program_id,
    program_code,
    major,
    yr_level,
    section_name,
    subject_code,
    course_desc,
    units,
    prelimterm_grade,
    midterm_grade,
    finalterm_grade,
    final_grade,
    final_grade_text,
    converted_grade,
    completion,
    remarks,
    school_year_id,
    school_year,
    sem,
    flag_fixed,
    status,
    date_added,
    date_updated,
    school_name,
    credit_code
)
WITH grade_source AS (
    SELECT
        e.enrollment_id,
        e.teacher_class_id,
        st.student_id,
        CONCAT(st.lastname, ', ', st.firstname, ' ', COALESCE(st.middle_name, '')) AS student_name,
        st.student_id_no,
        e.program_id,
        COALESCE(NULLIF(p.short_name, ''), p.program) AS program_code,
        st.major,
        tc.year_level,
        e.section_name,
        s.subject_code,
        s.subject_title,
        COALESCE(NULLIF(c.unit, 0), NULLIF(s.unit, 0), NULLIF(tc.unit, 0), 0) AS units,
        e.school_year_id,
        sy.school_year,
        e.sem,
        e.date_enrolled,
        CAST(RIGHT(st.student_id_no, 2) AS UNSIGNED) AS student_slot,
        ROW_NUMBER() OVER (
            PARTITION BY st.student_id_no, e.school_year_id, UPPER(e.sem)
            ORDER BY s.subject_code, e.enrollment_id
        ) AS term_subject_no
    FROM enrollments e
    INNER JOIN tmp_seed_default_sy dsy
        ON e.school_year_id < dsy.school_year_id
    INNER JOIN student st
        ON st.student_id_no = e.student_id_no
    INNER JOIN teacher_class tc
        ON tc.teacher_class_id = e.teacher_class_id
    INNER JOIN subject s
        ON s.subject_id = e.subject_id
    INNER JOIN programs p
        ON p.program_id = e.program_id
    INNER JOIN school_year sy
        ON sy.school_year_id = e.school_year_id
    LEFT JOIN curriculum c
        ON c.curriculum_id = e.curriculum_id
       AND UPPER(TRIM(c.subject_code)) = UPPER(TRIM(s.subject_code))
    WHERE e.status = 'Enrolled'
      AND e.student_id_no LIKE 'STU-%'
)
SELECT
    gs.teacher_class_id,
    gs.student_id,
    gs.student_name,
    gs.student_id_no,
    gs.program_id,
    gs.program_code,
    gs.major,
    gs.year_level,
    gs.section_name,
    gs.subject_code,
    gs.subject_title,
    gs.units,
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN 54 ELSE 88 END,
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN 54 ELSE 90 END,
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN 54 ELSE 92 END,
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN 54 ELSE 90 END,
    '',
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN '5' ELSE '1.50' END,
    '',
    CASE WHEN gs.student_slot = 5 AND gs.school_year_id = (SELECT school_year_id FROM tmp_seed_prev_default_term) AND gs.term_subject_no = 1 THEN 'FAILED' ELSE 'PASSED' END,
    gs.school_year_id,
    gs.school_year,
    gs.sem,
    0,
    1,
    DATE_ADD(gs.date_enrolled, INTERVAL 120 DAY),
    DATE_ADD(gs.date_enrolled, INTERVAL 120 DAY),
    '',
    ''
FROM grade_source gs
WHERE NOT EXISTS (
    SELECT 1
    FROM final_grade fg
    WHERE fg.student_id_text = gs.student_id_no
      AND fg.teacher_class_id = gs.teacher_class_id
      AND fg.subject_code = gs.subject_code
      AND fg.school_year_id = gs.school_year_id
      AND UPPER(fg.sem) = UPPER(gs.sem)
);

COMMIT;

SELECT COUNT(*) AS teacher_class_rows FROM teacher_class;
SELECT COUNT(*) AS enrollment_rows FROM enrollments;
SELECT COUNT(*) AS final_grade_rows FROM final_grade;
SELECT COUNT(*) AS seeded_failed_grades FROM final_grade WHERE student_id_text LIKE 'STU-%' AND remarks = 'FAILED';
