/*
    Backfill enrollment rows for BSA test students whose grades already exist.

    Students:
      - STUDENT-001 / ADRIAN MICHAEL SANTOS
      - STUDENT-002 / JOBERT SANTOS MAKATINDIG

    Purpose:
      Create enrollments from existing final_grade rows so these students are
      treated as actually enrolled in the historical terms where their grades
      were uploaded.
*/

CREATE TEMPORARY TABLE tmp_student_001_002_enrollment_source AS
SELECT
    fg.final_id,
    fg.student_id_text,
    st.program_id,
    st.curriculum_id,
    sy.school_year_id,
    sy.sem,
    COALESCE(
        (
            SELECT tc_exact.teacher_class_id
            FROM teacher_class tc_exact
            INNER JOIN subject s_exact
                ON s_exact.subject_id = tc_exact.subject_id
            INNER JOIN class_section cs_exact
                ON cs_exact.class_id = tc_exact.class_id
            WHERE tc_exact.schoolyear_id = sy.school_year_id
              AND UPPER(tc_exact.sem) = UPPER(sy.sem)
              AND tc_exact.program_id = st.program_id
              AND cs_exact.program_id = st.program_id
              AND UPPER(TRIM(s_exact.subject_code)) = UPPER(TRIM(fg.subject_code))
              AND LOWER(REPLACE(TRIM(cs_exact.class_name), ' ', '')) = LOWER(REPLACE(TRIM(fg.section_name), ' ', ''))
              AND tc_exact.status = 0
              AND cs_exact.status = 0
            ORDER BY tc_exact.teacher_class_id ASC
            LIMIT 1
        ),
        (
            SELECT tc_fallback.teacher_class_id
            FROM teacher_class tc_fallback
            INNER JOIN subject s_fallback
                ON s_fallback.subject_id = tc_fallback.subject_id
            INNER JOIN class_section cs_fallback
                ON cs_fallback.class_id = tc_fallback.class_id
            WHERE tc_fallback.schoolyear_id = sy.school_year_id
              AND UPPER(tc_fallback.sem) = UPPER(sy.sem)
              AND tc_fallback.program_id = st.program_id
              AND cs_fallback.program_id = st.program_id
              AND UPPER(TRIM(s_fallback.subject_code)) = UPPER(TRIM(fg.subject_code))
              AND tc_fallback.status = 0
              AND cs_fallback.status = 0
            ORDER BY tc_fallback.teacher_class_id ASC
            LIMIT 1
        )
    ) AS teacher_class_id
FROM final_grade fg
INNER JOIN student st
    ON st.student_id_no = fg.student_id_text
INNER JOIN school_year sy
    ON sy.school_year = fg.school_year
   AND UPPER(sy.sem) = UPPER(fg.sem)
WHERE fg.student_id_text IN ('STUDENT-001', 'STUDENT-002')
  AND st.program_id = 53
  AND st.curriculum_id = 29
  AND TRIM(fg.subject_code) <> '';

DELETE FROM tmp_student_001_002_enrollment_source
WHERE teacher_class_id IS NULL;

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
    src.student_id_text,
    tc.teacher_class_id,
    tc.subject_id,
    tc.class_id,
    src.program_id,
    src.curriculum_id,
    cs.class_name,
    tc.schedule,
    src.school_year_id,
    src.sem,
    COALESCE(sy.enrollment_start_date, DATE_SUB(sy.date_from, INTERVAL 14 DAY), NOW()),
    'Enrolled'
FROM tmp_student_001_002_enrollment_source src
INNER JOIN teacher_class tc
    ON tc.teacher_class_id = src.teacher_class_id
INNER JOIN class_section cs
    ON cs.class_id = tc.class_id
INNER JOIN school_year sy
    ON sy.school_year_id = src.school_year_id
WHERE NOT EXISTS (
    SELECT 1
    FROM enrollments e
    WHERE e.student_id_no = src.student_id_text
      AND e.teacher_class_id = src.teacher_class_id
      AND e.school_year_id = src.school_year_id
      AND UPPER(e.sem) = UPPER(src.sem)
      AND e.status = 'Enrolled'
)
GROUP BY
    src.student_id_text,
    tc.teacher_class_id,
    tc.subject_id,
    tc.class_id,
    src.program_id,
    src.curriculum_id,
    cs.class_name,
    tc.schedule,
    src.school_year_id,
    src.sem,
    sy.enrollment_start_date,
    sy.date_from;

SELECT
    e.student_id_no,
    e.school_year_id,
    e.sem,
    e.section_name,
    COUNT(*) AS enrolled_subject_count
FROM enrollments e
WHERE e.student_id_no IN ('STUDENT-001', 'STUDENT-002')
  AND e.status = 'Enrolled'
GROUP BY
    e.student_id_no,
    e.school_year_id,
    e.sem,
    e.section_name
ORDER BY
    e.student_id_no,
    e.school_year_id,
    e.sem,
    e.section_name;

DROP TEMPORARY TABLE IF EXISTS tmp_student_001_002_enrollment_source;
