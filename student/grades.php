<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

$general_page_title = "Grades & Prospectus";
$get_user_value = strtoupper($_GET['none'] ?? '');
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;

if ($g_user_role !== "STUDENT") {
    header("Location: " . BASE_URL . "index.php");
    exit();
}

// Prospectus structure (will be built from curriculum)
$prospectus_data = [];
// Total earned units (from final_grade only)
$total_units_earned = 0;

// For displaying the student's program and prospectus/curriculum info
$student_program_name = '';
$student_program_short_name = '';
$student_prospectus_label = '';
$student_curriculum_code = '';
$student_curriculum_units = null;
// For Dean-style prospectus header
$student_fullname = '';
$units_to_be_earned = null;
// Track total curriculum units from 1st to 4th year
$curriculum_units_total = 0;

if (!empty($g_general_id)) {
    $student_id_for_query = escape($db_connect, $g_general_id);

    // Resolve student's program and curriculum/prospectus from curriculum_master
    $student_sql = "
        SELECT s.program_id, s.curriculum_id, p.program, p.short_name, s.firstname, s.lastname, s.middle_name, s.suffix_name
        FROM student s
        LEFT JOIN programs p ON s.program_id = p.program_id
        WHERE s.student_id_no = '$student_id_for_query'
        LIMIT 1
    ";

    $student_rows = mysqliquery_return($student_sql);
    $student_program_id = 0;
    $student_curriculum_id = 0;

    if (!empty($student_rows)) {
        $student_row = $student_rows[0];
        $student_program_id = (int)($student_row['program_id'] ?? 0);
        $student_curriculum_id = (int)($student_row['curriculum_id'] ?? 0);
        $student_program_name = $student_row['program'] ?? '';
        $student_program_short_name = $student_row['short_name'] ?? '';
        $student_fullname = $student_row['lastname'] . ", " .$student_row['firstname']. " ".$student_row['middle_name']. " ".$student_row['suffix_name'];

        $curriculum_sql = '';
        if ($student_curriculum_id > 0) {
            $curriculum_sql = "SELECT curriculum_id, curriculum_code, header, units
                              FROM curriculum_master
                              WHERE curriculum_id = " . (int)$student_curriculum_id . "
                              LIMIT 1";
        } elseif ($student_program_id > 0) {
            // Fall back to the program's default curriculum as marked in
            // curriculum_master via status_allowable = 0.
            $curriculum_sql = "SELECT curriculum_id, header, units
                              FROM curriculum_master
                              WHERE program_id = " . (int)$student_program_id . " AND status_allowable = 0
                              LIMIT 1";
        }

        if ($curriculum_sql !== '') {
            $curr_rows = mysqliquery_return($curriculum_sql);
            if (!empty($curr_rows)) {
                $cur = $curr_rows[0];
                // Ensure we have the resolved curriculum_id (may come from default)
                $student_curriculum_id = (int)($cur['curriculum_id'] ?? $student_curriculum_id);

                $student_curriculum_code = $cur['curriculum_code'];
                $student_curriculum_units = isset($cur['units']) ? (int)$cur['units'] : null;

                $student_prospectus_label = trim($cur['header']);
            }
        }
    }

    // Fetch all final grades for the student (used for Summary tab and to overlay on curriculum)
    $final_sql = "
        SELECT 
            subject_code,
            course_desc,
            converted_grade,
            completion,
            units,
            school_year,
            sem,
            yr_level,
            student_name,
            student_id_text
        FROM final_grade
        WHERE student_id_text = '$student_id_for_query'
        ORDER BY yr_level ASC, sem ASC, subject_code ASC
    ";

    $final_rows = mysqliquery_return($final_sql);

    // Map of latest grade per subject_code
    $grades_by_subject = [];

    if (!empty($final_rows)) {
        // Use first row's name for header display
        $first_row = $final_rows[0];
        // $student_fullname = $first_row['student_name'] ?? '';

        foreach ($final_rows as $row) {
            $unit_val = (int)($row['units'] ?? 0);
            $total_units_earned += $unit_val;

            $code = trim($row['subject_code'] ?? '');
            if ($code !== '') {
                // Last occurrence wins (sufficient for display)
                $grades_by_subject[$code] = $row;
            }
        }
    }

    // Build prospectus view from curriculum; overlay any existing grades
    if ($student_curriculum_id > 0) {
        $curriculum_sql = "
            SELECT curriculum_id, subject_code, subject_title, unit, pre_req, semester, year_level, lec_lab
            FROM curriculum
            WHERE curriculum_id = " . escape($db_connect, intVal($student_curriculum_id)) . "
            ORDER BY year_level ASC, semester ASC, subject_code ASC
        ";

        $curriculum_rows = mysqliquery_return($curriculum_sql);

        if (!empty($curriculum_rows)) {
            foreach ($curriculum_rows as $c_row) {
                $year_level_int = (int)($c_row['year_level'] ?? 0);
                switch ($year_level_int) {
                    case 1:
                        $year_level = '1st Year';
                        break;
                    case 2:
                        $year_level = '2nd Year';
                        break;
                    case 3:
                        $year_level = '3rd Year';
                        break;
                    case 4:
                        $year_level = '4th Year';
                        break;
                    default:
                        $year_level = $year_level_int > 0 ? $year_level_int . ' Year' : 'UNSPECIFIED YEAR LEVEL';
                        break;
                }

                $sem_label = trim($c_row['semester'] ?? '') ?: 'UNSPECIFIED SEMESTER';
                $unit_val = (int)($c_row['unit'] ?? 0);
                $code = trim($c_row['subject_code'] ?? '');
                $preReq = trim($c_row['pre_req'] ?? '');
                $lec_lab_val = json_decode($c_row['pre_req']);
                $lec = isset($lec_lab_val[0]) ? $lec_lab_val[0] : '';
                $lab = isset($lec_lab_val[1]) ? $lec_lab_val[1] : '';

                if (!isset($prospectus_data[$year_level])) {
                    $prospectus_data[$year_level] = [];
                }
                if (!isset($prospectus_data[$year_level][$sem_label])) {
                    $prospectus_data[$year_level][$sem_label] = ['subjects' => [], 'total_units' => 0];
                }

                $grade_row = ($code !== '' && isset($grades_by_subject[$code])) ? $grades_by_subject[$code] : null;

                $prospectus_data[$year_level][$sem_label]['subjects'][] = [
                    'subject_code'    => $code,
                    'course_desc'     => $c_row['subject_title'] ?? '',
                    'converted_grade' => $grade_row['converted_grade'] ?? '',
                    'completion'      => $grade_row['completion'] ?? '',
                    'units'           => $unit_val,
                    'pre_req'         => $preReq,
                    'lec' => $lec,
                    'lab' => $lab
                ];

                $prospectus_data[$year_level][$sem_label]['total_units'] += $unit_val;

                // Count all curriculum units (regardless of grade) for Units to be Earned
                $curriculum_units_total += $unit_val;
            }
        }
    }
}

// Units to be Earned = total curriculum units from 1st to 4th year
if ($curriculum_units_total > 0) {
    $units_to_be_earned = $curriculum_units_total;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>dean/css/student_approvals.css?v=<?php echo time(); ?>">
    <link href="https://unpkg.com/tabulator-tables@5.5.0/dist/css/tabulator_bootstrap5.min.css" rel="stylesheet">
    <script type="text/javascript" src="https://oss.sheetjs.com/sheetjs/xlsx.full.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.20/jspdf.plugin.autotable.min.js"></script>


    <style>
        #summary-table .tabulator-header {
            background: #000 !important;
        }

        #summary-table .tabulator-col,
        #summary-table .tabulator-col-group,
        #summary-table .tabulator-header .tabulator-col {
            background: #000 !important;
            color: #fff !important;
            border-right: 1px solid #fff !important;
            border-bottom: 1px solid #fff !important;
        }

        #summary-table .tabulator-col:last-child {
            border-right: 1px solid #fff !important;
        }

        #summary-table .tabulator-col .tabulator-col-title {
            color: #fff !important;
            text-align: center !important;
        }

        #summary-table .tabulator-header .tabulator-header-filter input {
            width: 100% !important;
            background: #fff !important;
            border: 2px solid #2f2f2f !important;
            border-radius: 0 !important;
            box-sizing: border-box !important;
        }

        #summary-table .tabulator-col-group-cols {
            border-top: 1px solid #fff !important;
        }

        #summary-table .tabulator-group {
            background: #b8b8b8 !important;
            color: #000 !important;
            border-top: 1px solid #fff !important;
            border-bottom: 1px solid #fff !important;
        }

    </style>
</head>

<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php'; ?>

        <div class="container">
            <div class="page-inner">
                <div class="card card-round">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#summary">Summary Reports</button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#prospectus">Prospectus</button>
                            </li>
                        </ul>
                    </div>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="summary">
                                <div class="d-flex justify-content-end mb-2">
                                    <div class="btn-group">
                                        <button id="download-csv" class="btn btn-dark btn-sm" style="font-size: 10px;">CSV</button>
                                        <button id="download-xlsx" class="btn btn-dark btn-sm" style="font-size: 10px;">Excel</button>
                                        <button id="download-pdf" class="btn btn-dark btn-sm" style="font-size: 10px;">PDF</button>
                                    </div>
                                </div>
                                
                                <div id="summary-table"></div>
                                
                                <div class="units-footer">
                                    <span>UNITS EARNED: &nbsp;&nbsp;&nbsp; <?php echo $total_units_earned; ?></span>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="prospectus">
                                <div class="prospectus-wrapper">
                                    <div class="prospectus-sheet">
                                        <div class="prospectus-header text-center mb-3">
                                            <div class="school-name">CITY COLLEGE OF CALAMBA</div>
                                            <div class="school-office">OFFICE OF THE COLLEGE REGISTRAR</div>
                                            <div class="school-address">Calamba City</div>
                                            <div class="prospectus-title">
                                                <?php echo htmlspecialchars($student_program_name); ?>
                                            </div>
                                            <?php if (!empty($student_prospectus_label)): ?>
                                                <div class="prospectus-revision"><?php echo htmlspecialchars($student_prospectus_label); ?></div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="prospectus-meta mb-2">
                                            <div class="d-flex flex-row justify-content-between">
                                                <span class="fw-bold">Name: <?php echo htmlspecialchars($student_fullname); ?></span>
                                                <span class="fw-bold">Units Earned: <?php echo (int)$total_units_earned; ?></span>
                                            </div>
                                            <div class="d-flex flex-row justify-content-between">
                                                <span class="fw-bold">STUDENT NO. :  <?php echo htmlspecialchars($g_general_id ?? ''); ?></span>
                                                <span class="fw-bold">Units to be Earned: <?php echo intVal($units_to_be_earned) ?></span>
                                            </div>
                                        </div>

                                        <div class="prospectus-body">
                                            <?php if (!empty($prospectus_data)): ?>
                                                <?php foreach ($prospectus_data as $year_level => $semesters): ?>
                                                    <div class="year-block mb-4">
                                                        <div class="year-heading"><?php echo htmlspecialchars($year_level); ?></div>
                                                        <div class="row g-2">
                                                            <?php foreach ($semesters as $sem_key => $sem_data): ?>
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="sem-heading"><?php echo htmlspecialchars($sem_key); ?></div>
                                                                    <table class="prospectus-table">
                                                                        <thead>
                                                                            <tr>
                                                                                <th>Grade</th>
                                                                                <th>Code</th>
                                                                                <th>Course Title</th>
                                                                                <th>Lec</th>
                                                                                <th>Lab</th>
                                                                                <th>Units</th>
                                                                                <th>Pre-Req</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody>
                                                                            <?php foreach ($sem_data['subjects'] as $subject): ?>
                                                                                <tr>
                                                                                    <td><?php echo htmlspecialchars($subject['converted_grade']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['subject_code']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['course_desc']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['lec']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['lab']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['units']); ?></td>
                                                                                    <td><?php echo htmlspecialchars($subject['pre_req']); ?></td>
                                                                                </tr>
                                                                            <?php endforeach; ?>
                                                                            <tr class="total-row">
                                                                                <td colspan="5">&nbsp;</td>
                                                                                <td><?php echo (int)($sem_data['total_units'] ?? 0); ?></td>
                                                                                <td class="text-center">Total Units</td>
                                                                            </tr>
                                                                        </tbody>
                                                                    </table>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="p-3 text-center text-muted">No curriculum data found for this student.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="grading-legend mt-3 small">
                                    <div class="grading-legend-icon">i</div>
                                    <div class="grading-legend-text">
                                        <strong>GRADING SYSTEM:</strong>
                                        1 (PASSED) - 96-100%,
                                        1.25 (PASSED) - 92-95%,
                                        1.5 (PASSED) - 88-91%,
                                        1.75 (PASSED) - 84-87%,
                                        2 (PASSED) - 80-83%,
                                        2.25 (PASSED) - 75-79%,
                                        2.5 (PASSED) - 70-74%,
                                        2.75 (PASSED) - 65-69%,
                                        3 (PASSED) - 60-64%,
                                        4 (FAILED) - 55-59%,
                                        FAILED - 0-54%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
<script type="text/javascript" src="https://unpkg.com/tabulator-tables@5.5.0/dist/js/tabulator.min.js"></script>

<script>
    // const table = new Tabulator("#summary-table", {
    //     data: tableData,
    //     layout: "fitColumns",
    //     // Nested grouping: Year Level then Semester
    //     // groupBy: ["yr_level", "sem"],
    //     // groupHeader: [
    //     //     (value) => value,
    //     //     (value, count, data) => value + ", S.Y." + data[0].school_year
    //     // ],
    //     columns: [
    //         {title: "Course Code", field: "subject_code", width: 140, headerFilter: "input", hozAlign: "center", headerHozAlign: "center"},
    //         {title: "Course Title", field: "course_desc", widthGrow: 3, headerFilter: "input", headerHozAlign: "center"},
    //         {
    //             title: "Grades",
    //             headerHozAlign: "center",
    //             columns: [
    //                 {title: "Final", field: "converted_grade", hozAlign: "center", headerFilter: "input", width: 100, headerHozAlign: "center"},
    //                 {title: "Re-Exam", field: "completion", hozAlign: "center", headerFilter: "input", width: 100, headerHozAlign: "center"},
    //             ],
    //         },
    //         {title: "Credits", field: "units", hozAlign: "center", headerFilter: "input", width: 90, headerHozAlign: "center"},
    //     ],
    // });

    // const table = new Tabulator("#summary-table", {
    //     ajaxURL: "<?php echo BASE_URL; ?>student/actions/fetchGrades.php",
    //     ajaxConfig: "GET",
    //     layout: "fitColumns",
    //     columnHeaderVertAlign: "bottom",
    //     groupBy: function(row){
    //         return row.sem + ", S.Y." + row.school_year;
    //     },
    //     groupStartOpen: true,
    //     groupHeader: function(value){
    //         return value;
    //     },
    //     columns: [
    //         {
    //             title: "Course Code",
    //             field: "subject_code",
    //             width: 220,
    //             headerFilter: "input",
    //             hozAlign: "left",
    //             headerHozAlign: "center"
    //         },
    //         {
    //             title: "Course Title",
    //             field: "course_desc",
    //             widthGrow: 4,
    //             headerFilter: "input",
    //             hozAlign: "left",
    //             headerHozAlign: "center"
    //         },
    //         {
    //             title: "Grades",
    //             headerHozAlign: "center",
    //             columns: [
    //                 {
    //                     title: "Final",
    //                     field: "converted_grade",
    //                     width: 160,
    //                     hozAlign: "center",
    //                     headerFilter: "input",
    //                     headerHozAlign: "center"
    //                 },
    //                 {
    //                     title: "Re-Exam",
    //                     field: "completion",
    //                     width: 160,
    //                     hozAlign: "center",
    //                     headerFilter: "input",
    //                     headerHozAlign: "center"
    //                 }
    //             ]
    //         },
    //         {
    //             title: "Credits",
    //             field: "units",
    //             width: 120,
    //             hozAlign: "center",
    //             headerHozAlign: "center",
    //             headerFilter: false
    //         }
    //     ]
    // });
    const table = new Tabulator("#summary-table", {
        ajaxURL: "<?php echo BASE_URL; ?>student/actions/fetchGrades.php",
        ajaxConfig: "GET",
        layout: "fitColumns",
        placeholder: "No Data Available",
        ajaxFiltering: true,
        ajaxSorting: true,
        ajaxResponse: function(url, params, response){
            return Array.isArray(response.data) ? response.data : [];
        },
        groupBy: function(row){
            return row.sem + ", S.Y." + row.school_year;
        },
        groupStartOpen: true,
        groupHeader: function(value){
            return value;
        },
        columns: [
            {
                title: "Course Code",
                field: "subject_code",
                width: 220,
                headerFilter: "input",
                hozAlign: "left",
                headerHozAlign: "center"
            },
            {
                title: "Course Title",
                field: "course_desc",
                widthGrow: 4,
                headerFilter: "input",
                hozAlign: "left",
                headerHozAlign: "center"
            },
            {
                title: "Grades",
                headerHozAlign: "center",
                columns: [
                    {
                        title: "Final",
                        field: "converted_grade",
                        width: 160,
                        hozAlign: "center",
                        headerFilter: "input",
                        headerHozAlign: "center"
                    },
                    {
                        title: "Re-Exam",
                        field: "completion",
                        width: 160,
                        hozAlign: "center",
                        headerFilter: "input",
                        headerHozAlign: "center",
                        formatter: function(cell){
                            return cell.getValue() || "";
                        }
                    }
                ]
            },
            {
                title: "Credits",
                field: "units",
                width: 120,
                hozAlign: "center",
                headerHozAlign: "center"
            }
        ]
    });



    document.querySelector('button[data-bs-target="#summary"]').addEventListener('shown.bs.tab', () => table.redraw());

    document.getElementById("download-csv").addEventListener("click", () => table.download("csv", "Grades.csv"));
    document.getElementById("download-xlsx").addEventListener("click", () => table.download("xlsx", "Grades.xlsx"));
    document.getElementById("download-pdf").addEventListener("click", () => table.download("pdf", "Grades.pdf", { orientation: "landscape" }));
</script>
</body>
</html>