-- Seed teacher_class schedules for the active/default fiscal year.
-- Source tables used:
--   school_year.isDefault = 1 and flag_used = 1
--   class_section rows matching the fiscal year, program, and year level
--   curriculum rows matching the fiscal semester
--   subject rows matching the curriculum subject
--   users with instructor role ["4"]
--
-- The generated schedule uses one 6-hour block per course slot:
--   Morning block:   07:00-13:00
--   Afternoon block: 13:00-19:00
-- The actual course end time is based on lec_lab hours when available,
-- otherwise it falls back to curriculum.unit.

START TRANSACTION;

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
active_sy AS (
    SELECT school_year_id, sem
    FROM school_year
    WHERE isDefault = 1
      AND flag_used = 1
    ORDER BY createdAt DESC
    LIMIT 1
),
teachers AS (
    SELECT
        user_id,
        ROW_NUMBER() OVER (ORDER BY user_id) AS teacher_no
    FROM users
    WHERE JSON_CONTAINS(user_role, '\"4\"')
      AND status = 0
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
            PARTITION BY cs.class_id
            ORDER BY c.subject_code, c.subject_id
        ) AS section_course_no
    FROM active_sy sy
    INNER JOIN curriculum c
        ON c.semester = sy.sem
       AND c.status = 0
    INNER JOIN subject s
        ON s.subject_id = c.subject_id
       AND s.program_id = c.program_id
       AND s.status = 0
    INNER JOIN class_section cs
        ON cs.program_id = c.program_id
       AND cs.year_level = c.year_level
       AND cs.school_year_id = sy.school_year_id
       AND cs.status = 0
    WHERE NOT EXISTS (
        SELECT 1
        FROM teacher_class tc
        WHERE tc.class_id = cs.class_id
          AND tc.subject_id = c.subject_id
          AND tc.schoolyear_id = sy.school_year_id
          AND tc.status = 0
    )
),
slotted_courses AS (
    SELECT
        ec.*,
        MOD(ec.section_course_no - 1, 12) AS slot_index,
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
            PARTITION BY tc.day_index, tc.block_index
            ORDER BY tc.program_id, tc.year_level, tc.class_id, tc.subject_code
        ) AS slot_teacher_no
    FROM timed_courses tc
),
teacher_count AS (
    SELECT COUNT(*) AS total_teachers
    FROM teachers
)
SELECT
    t.user_id AS teacher_id,
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
    END AS schedule,
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
    END AS room,
    fc.year_level,
    fc.unit,
    fc.lec_lab,
    fc.sec_limit,
    fc.total_hours_value
FROM formatted_courses fc
CROSS JOIN teacher_count tcount
INNER JOIN teachers t
    ON t.teacher_no = MOD(fc.slot_teacher_no - 1, tcount.total_teachers) + 1
WHERE tcount.total_teachers > 0
ORDER BY fc.program_id, fc.year_level, fc.class_id, fc.subject_code;

COMMIT;
