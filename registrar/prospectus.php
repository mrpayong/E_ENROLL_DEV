<?php
// filepath: c:\xampp\htdocs\enroll\registrar\prospectus.php
include '../config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

if (!($g_user_role == "REGISTRAR")) {
    header("Location: " . BASE_URL);
    exit();
}

// Dummy data for smoother table rendering
$student = [
    'program' => 'Bachelor of Science in Information Technology',
    'name' => 'John K. Doe',
    'student_no' => '2023-02569',
    'rv' => '2024-2025'
];

$prospectus = [
    'First Year' => [
        'First Semester' => [
            [
                'code' => 'ENG101',
                'title' => 'English Communication',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => '—',
                'grade' => ['label' => 'Passed', 'class' => 'text-success']
            ],
            [
                'code' => 'MATH101',
                'title' => 'College Algebra',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => '—',
                'grade' => ['label' => 'Passed', 'class' => 'text-success']
            ],
            [
                'code' => 'CS101',
                'title' => 'Intro to Computing',
                'lec' => 2,
                'lab' => 1,
                'units' => 3,
                'pre_req' => '—',
                'grade' => ['label' => 'Failed', 'class' => 'text-danger']
            ],
        ],
        'Second Semester' => [
            [
                'code' => 'ENG102',
                'title' => 'New English',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => 'ENG101',
                'grade' => ['label' => 'Passed', 'class' => 'text-success']
            ],
            [
                'code' => 'MATH102',
                'title' => 'Trigonometry',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => 'MATH101',
                'grade' => ['label' => 'Passed', 'class' => 'text-success']
            ],
            [
                'code' => 'CS102',
                'title' => 'Programming',
                'lec' => 2,
                'lab' => 1,
                'units' => 3,
                'pre_req' => 'CS101',
                'grade' => ['label' => 'Pending', 'class' => 'text-warning']
            ],
        ]
    ],
    'Second Year' => [
        'First Semester' => [
            [
                'code' => 'CS201',
                'title' => 'Data Structures',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => 'CS102',
                'grade' => ['label' => 'Pending', 'class' => 'text-warning']
            ],
            [
                'code' => 'STAT101',
                'title' => 'Statistics',
                'lec' => 3,
                'lab' => 0,
                'units' => 3,
                'pre_req' => 'MATH102',
                'grade' => ['label' => 'Passed', 'class' => 'text-success']
            ],
            [
                'code' => 'CS101',
                'title' => 'Intro to Computing',
                'lec' => 2,
                'lab' => 1,
                'units' => 3,
                'pre_req' => '—',
                'grade' => ['label' => 'Pending', 'class' => 'text-warning']
            ],
        ],
        'Second Semester' => [
            [
                'code' => 'CS202',
                'title' => 'Data Infra',
                'lec' => 2,
                'lab' => 1,
                'units' => 3,
                'pre_req' => 'CS201',
                'grade' => ['label' => 'Pending', 'class' => 'text-warning']
            ],
            [
                'code' => 'DB101',
                'title' => 'Database Models',
                'lec' => 2,
                'lab' => 1,
                'units' => 3,
                'pre_req' => 'CS201',
                'grade' => ['label' => 'Pending', 'class' => 'text-warning']
            ],
        ]
    ]
];
?>
<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
</head>
<body>
<div class="wrapper">
    <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>
    <div class="main-panel">
        <?php include_once DOMAIN_PATH . '/global/header.php';?>
        <div id="main" class="container">
            <main id="main" class="main">
                <section class="">
                    <div class="row justify-content-center">
                        <div class="col-12">
                            <div class="card border-0 shadow-sm prospectus-main-card">
                                <div class="card-body pt-3">
                                    <h4 class="fw-semibold mb-2">Student Prospectus</h4>

                                    <div class="row mb-3">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label mb-1">Student ID</label>
                                            <input type="search" class="form-control" value="2023-02569">
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label mb-1">Program</label>
                                            <select class="form-select">
                                                <option value="">Select Proram</option>
                                                <option>Bacheclors of Science in Information Technology</option>
                                                <option>Bacheclors of Science in Computer Science</option>
                                                <option>Bacheclors of Science in Accounting</option>
                                                <option>Bacheclors of Science in Accounting Information System</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label mb-1">Academic Year</label>
                                            <select class="form-select">
                                                <option value="">Select Academic Year</option>
                                                <option>2025-2026</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label mb-1">Year Level</label>
                                            <select class="form-select">
                                                <option value="">Select Year Level</option>
                                                <option>1st Year</option>
                                                <option>2nd Year</option>
                                                <option>3rd Year</option>
                                                <option>4th Year</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="rounded-4 mb-2">
                                        <div class="mb-2 d-flex flex-column">
                                            <span class="fw-semibold fs-5"><?= htmlspecialchars($student['program']) ?></span>
                                            
                                            <span>Name: <?= htmlspecialchars($student['name']) ?></span>
                                            <span>Student No.: <?= htmlspecialchars($student['student_no']) ?></span>
                                            <span>A.Y. <?= htmlspecialchars($student['rv']) ?></span>
                                        </div>
                                    </div>
                                    <?php foreach ($prospectus as $year => $semesters): ?>
                                        <div class="mb-4">
                                            <div class="py-1 px-4 fs-5 text-center" style="color:white;background:black; font-weigt:600;"><?= htmlspecialchars($year) ?></div>
                                            <div class="table-responsive">
                                                <table class="table prospectus-table mb-0">
                                                    <thead style="background:#D9D3C6">
                                                        <tr>
                                                            <th colspan="7" class="border border-bottom border-start-0 border-end-0 border-secondary p-1 text-center"><?= htmlspecialchars('FIRST SEMESTER') ?></th>
                                                            <th colspan="7" class="border border-bottom border-start-0 border-end-0 border-secondary p-1 text-center"><?= htmlspecialchars('SECOND SEMESTER') ?></th>
                                                        </tr>
                                                        <tr>
                                                            <?php for ($i = 0; $i < 2; $i++): ?>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Code</th>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Course Title</th>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Lec</th>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Lab</th>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Units</th>
                                                                <th class="border-start-0 border-end-0 border-top border-bottom text-center">Pre-Req</th>
                                                                <th class="border-secondary border-start-0 border-end-0 border-top-0 border-bottom-0 text-center">Grade</th>
                                                            <?php endfor; ?>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $maxRows = max(count($semesters['First Semester']), count($semesters['Second Semester']));
                                                        for ($i = 0; $i < $maxRows; $i++):
                                                            $first = $semesters['First Semester'][$i] ?? null;
                                                            $second = $semesters['Second Semester'][$i] ?? null;
                                                        ?>
                                                        <tr style="background: #dbddd054;">
                                                            <?php foreach ([$first, $second] as $subject): ?>
                                                                <?php if ($subject): ?>
                                                                    <td class="fw-semibold text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom"><?= htmlspecialchars($subject['code']) ?></td>
                                                                    <td class="text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom"><?= htmlspecialchars($subject['title']) ?></td>
                                                                    <td class="text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom"><?= htmlspecialchars($subject['lec']) ?></td>
                                                                    <td class="text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom"><?= htmlspecialchars($subject['lab']) ?></td>
                                                                    <td class="text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom" class="fw-semibold"><?= htmlspecialchars($subject['units']) ?></td>
                                                                    <td class="text-center border-start-0 border-secondary border-end-0 border-top-0 border-bottom"><?= htmlspecialchars($subject['pre_req']) ?></td>
                                                                    <td class="<?= $subject['grade']['class'] ?> text-center border-secondary border-start-0 border-end-0 border-top-0 border-bottom">
                                                                        <?= htmlspecialchars($subject['grade']['label']) ?>
                                                                    </td>
                                                                <?php else: ?>
                                                                    <td colspan="7"></td>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </tr>
                                                        <?php endfor; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button class="btn btn-success px-4 py-2 fs-5 fw-semibold rounded-3">
                                            <i class="bi bi-download me-2"></i> Generate Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </main>
        </div>
        <?php include_once FOOTER_PATH; ?>
    </div>
</div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
</html>