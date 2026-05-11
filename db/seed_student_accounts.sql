-- Seed 5 student accounts per active/default section.
--
-- Databases used:
--   e_enrollment: enrollment-side users and student profile records
--   e_eguro: main eGuro users and login/system-access records
--
-- Default password for generated students: student123
-- Stored as SHA1('student123') to match the existing seed data style.
--
-- Generated IDs are deterministic:
--   STU-{class_id}-{01..05}
-- This makes the script safe to rerun; it inserts only missing rows.

START TRANSACTION;

INSERT INTO e_enrollment.users (
    general_id,
    img,
    f_name,
    m_name,
    l_name,
    suffix,
    sex,
    birth_date,
    user_role,
    username,
    password,
    email_address,
    recovery_email,
    position,
    status,
    locked,
    last_signin
)
WITH RECURSIVE student_no AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1
    FROM student_no
    WHERE n < 5
),
target_sections AS (
    SELECT
        cs.class_id,
        cs.class_name,
        cs.program_id,
        cs.year_level,
        cs.school_year_id,
        cs.sec_limit,
        p.short_name
    FROM e_enrollment.class_section cs
    INNER JOIN e_enrollment.school_year sy
        ON sy.school_year_id = cs.school_year_id
       AND sy.isDefault = 1
       AND sy.flag_used = 1
    INNER JOIN e_enrollment.programs p
        ON p.program_id = cs.program_id
       AND p.status = 0
    WHERE cs.status = 0
),
seed_students AS (
    SELECT
        CONCAT('STU-', ts.class_id, '-', LPAD(sn.n, 2, '0')) AS general_id,
        CONCAT('Student', LPAD(sn.n, 2, '0')) AS first_name,
        ELT(MOD(ts.class_id + sn.n, 10) + 1,
            'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza',
            'Torres', 'Flores', 'Ramos', 'Dela Cruz', 'Aquino'
        ) AS middle_name,
        CONCAT(
            REPLACE(ts.short_name, ' ', ''),
            'Y', ts.year_level,
            'S', sn.n
        ) AS last_name,
        CASE WHEN MOD(sn.n, 2) = 0 THEN 'female' ELSE 'male' END AS sex,
        DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL (18 + ts.year_level) YEAR), '%Y-%m-%d') AS birth_date,
        CONCAT('stu-', ts.class_id, '-', LPAD(sn.n, 2, '0'), '@ccc.edu.ph') AS email_address
    FROM target_sections ts
    CROSS JOIN student_no sn
)
SELECT
    ss.general_id,
    'profile-img.png',
    ss.first_name,
    ss.middle_name,
    ss.last_name,
    '',
    ss.sex,
    ss.birth_date,
    '[\"5\"]',
    ss.general_id,
    SHA1('student123'),
    ss.email_address,
    '',
    'Student',
    0,
    0,
    '0000-00-00 00:00:00'
FROM seed_students ss
WHERE NOT EXISTS (
    SELECT 1
    FROM e_enrollment.users u
    WHERE u.general_id = ss.general_id
);

INSERT INTO e_enrollment.student (
    student_id_no,
    firstname,
    lastname,
    email_address,
    ccc_email,
    middle_name,
    suffix_name,
    contact,
    barangay,
    address,
    gender,
    dob,
    year_level,
    major,
    class_id,
    username,
    password,
    course_id,
    status,
    curriculum_id,
    emergency_data,
    graduated_data,
    additional_data,
    flag_update,
    program_id,
    department_id
)
WITH RECURSIVE student_no AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1
    FROM student_no
    WHERE n < 5
),
target_sections AS (
    SELECT
        cs.class_id,
        cs.class_name,
        cs.program_id,
        cs.year_level,
        cs.school_year_id,
        p.department_id,
        p.short_name,
        cm.curriculum_id
    FROM e_enrollment.class_section cs
    INNER JOIN e_enrollment.school_year sy
        ON sy.school_year_id = cs.school_year_id
       AND sy.isDefault = 1
       AND sy.flag_used = 1
    INNER JOIN e_enrollment.programs p
        ON p.program_id = cs.program_id
       AND p.status = 0
    INNER JOIN e_enrollment.curriculum_master cm
        ON cm.program_id = cs.program_id
       AND cm.status_allowable = 0
    WHERE cs.status = 0
),
seed_students AS (
    SELECT
        CONCAT('STU-', ts.class_id, '-', LPAD(sn.n, 2, '0')) AS general_id,
        CONCAT('Student', LPAD(sn.n, 2, '0')) AS first_name,
        ELT(MOD(ts.class_id + sn.n, 10) + 1,
            'Santos', 'Reyes', 'Cruz', 'Garcia', 'Mendoza',
            'Torres', 'Flores', 'Ramos', 'Dela Cruz', 'Aquino'
        ) AS middle_name,
        CONCAT(
            REPLACE(ts.short_name, ' ', ''),
            'Y', ts.year_level,
            'S', sn.n
        ) AS last_name,
        CASE WHEN MOD(sn.n, 2) = 0 THEN 'female' ELSE 'male' END AS sex,
        DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL (18 + ts.year_level) YEAR), '%Y-%m-%d') AS birth_date,
        CONCAT('stu-', ts.class_id, '-', LPAD(sn.n, 2, '0'), '@ccc.edu.ph') AS email_address,
        ts.year_level,
        ts.class_id,
        ts.program_id,
        ts.department_id,
        ts.curriculum_id
    FROM target_sections ts
    CROSS JOIN student_no sn
)
SELECT
    ss.general_id,
    ss.first_name,
    ss.last_name,
    ss.email_address,
    ss.email_address,
    ss.middle_name,
    '',
    CONCAT('09', LPAD(MOD(CRC32(ss.general_id), 1000000000), 9, '0')),
    'Sample Barangay',
    'Sample Address',
    ss.sex,
    ss.birth_date,
    ss.year_level,
    '',
    ss.class_id,
    ss.general_id,
    SHA1('student123'),
    ss.program_id,
    '0',
    ss.curriculum_id,
    '{}',
    '[]',
    '{}',
    NOW(6),
    ss.program_id,
    ss.department_id
FROM seed_students ss
WHERE NOT EXISTS (
    SELECT 1
    FROM e_enrollment.student st
    WHERE st.student_id_no = ss.general_id
);

INSERT INTO e_eguro.users (
    general_id,
    card_id,
    first_name,
    middle_name,
    last_name,
    suffix,
    birth_date,
    sex,
    email,
    recovery_email,
    img,
    position,
    online
)
SELECT
    eu.general_id,
    '',
    eu.f_name,
    eu.m_name,
    eu.l_name,
    eu.suffix,
    eu.birth_date,
    eu.sex,
    eu.email_address,
    eu.recovery_email,
    'profile-img.png',
    'Student',
    0
FROM e_enrollment.users eu
WHERE eu.general_id LIKE 'STU-%'
  AND NOT EXISTS (
      SELECT 1
      FROM e_eguro.users gu
      WHERE gu.general_id = eu.general_id
  );

INSERT INTO e_eguro.login (
    user_id,
    ref_id,
    username,
    password,
    status,
    locked,
    system_type,
    system_role
)
SELECT
    gu.id,
    eu.user_id,
    eu.username,
    eu.password,
    0,
    0,
    'E-ENROLL',
    5
FROM e_eguro.users gu
INNER JOIN e_enrollment.users eu
    ON eu.general_id = gu.general_id
WHERE eu.general_id LIKE 'STU-%'
  AND JSON_CONTAINS(eu.user_role, '\"5\"')
  AND NOT EXISTS (
      SELECT 1
      FROM e_eguro.login gl
      WHERE gl.user_id = gu.id
        AND gl.ref_id = eu.user_id
        AND gl.system_type = 'E-ENROLL'
        AND gl.system_role = 5
  );

COMMIT;

SELECT
    COUNT(*) AS seeded_enrollment_students
FROM e_enrollment.student
WHERE student_id_no LIKE 'STU-%';

SELECT
    COUNT(*) AS seeded_eguro_student_logins
FROM e_eguro.login
WHERE system_type = 'E-ENROLL'
  AND system_role = 5
  AND username LIKE 'STU-%';
