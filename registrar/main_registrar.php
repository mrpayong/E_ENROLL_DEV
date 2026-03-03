<?php
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

## table
$table_array = array();
$select = "SELECT user_id,general_id,f_name,m_name,l_name,suffix,birth_date,sex,user_role as roles,username,email_address,position,status,locked FROM users ORDER BY user_id DESC";
if ($query = call_mysql_query($select)) {
    if ($num = mysqli_num_rows($query)) {
        while ($data = call_mysql_fetch_array($query)) {
            $data['name'] = get_full_name($data['f_name'],$data['m_name'],$data['l_name'],$data['suffix']);

            $user_roles = [];
            foreach (json_decode($data['roles']) as $role) {
                if (isset(SYSTEM_ACCESS['E-ENROLL']['role'][$role])) {
                    $user_roles[] = SYSTEM_ACCESS['E-ENROLL']['role'][$role];
                }
            }
            $data['user_role'] = !empty($user_roles) ? implode(', ', $user_roles) : '';

            if ($data['status'] == 1) {
                $data['account_status'] = 'Deactivated';
            } elseif ($data['locked'] == 1) {
                $data['account_status'] = 'Locked';
            } elseif ($data['status'] == 0 && $data['locked'] == 0) {
                $data['account_status'] = 'Active';
            }
            array_push($table_array, $data);
        }
    }
}

$approval_list = [
    [
        "course_code" => "CSC-101",
        "course_name" => "Introduction to Computer Science",
        "status" => "Approved"
    ],
    [
        "course_code" => "MATH-201",
        "course_name" => "Calculus I",
        "status" => "Pending"
    ],
    [
        "course_code" => "ENG-102",
        "course_name" => "English Composition",
        "status" => "Disapproved"
    ]
];


$json_approval_list = json_encode($approval_list);
?>
<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
</head>

<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php';?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php';?>

            <div id="main" class="container">
                <div class="row mx-4 m-4 flex justify-content-center card">
                    <header class="card-header bg-eclearance border-bottom-0 pb-0">
                        <h2 class=" text-black fw-semibold fs-5">Recent Enrollments</h2>
                    </header>
                    <div class="pt-2">
                        <canvas id="enrollmentAreaChart"></canvas>
                    </div>
                </div>
            </div>

            <?php include_once FOOTER_PATH; ?>
        </div>

    </div>
</body>
<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Dummy data for the area chart
    const labels = [
        "BSIT", "BSCS", "BSA", "BSAIS", "BSEE"
    ];

    const data1stSem = [320, 410, 380, 250, 300];
    const data2ndSem = [230, 410, 382, 204, 234];


    const ctx = document.getElementById('enrollmentAreaChart').getContext('2d');
    const enrollmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: '1st Semester',
                    data: data1stSem,
                    fill: true,
                    backgroundColor: 'rgb(59, 131, 246)',
                    borderColor: 'rgba(59,130,246,1)',
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(59,130,246,1)'
                },
                {
                    label: '2nd Semester',
                    data: data2ndSem,
                    fill: true,
                    backgroundColor: 'rgb(16, 185, 129)',
                    borderColor: 'rgba(16,185,129,1)',
                    tension: 0.4,
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(16,185,129,1)'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        boxWidth: 16,
                        font: { size: 14 }
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            interaction: {
                mode: 'nearest',
                axis: 'x',
                intersect: false
            },
            scales: {
                x: {
                    title: { display: true, text: 'Program' },
                    grid: { display: false }
                },
                y: {
                    title: { display: true, text: 'Number of Enrollments' },
                    beginAtZero: true,
                    grid: { color: '#f3f4f6' }
                }
            }
        }
    });

    // Responsive height adjustment
    function resizeChart() {
        const chartContainer = document.getElementById('enrollmentAreaChart').parentElement;
        if (window.innerWidth < 576) {
            enrollmentChart.canvas.parentNode.style.height = '220px';
        } else if (window.innerWidth < 992) {
            enrollmentChart.canvas.parentNode.style.height = '300px';
        } else {
            enrollmentChart.canvas.parentNode.style.height = '350px';
        }
        enrollmentChart.resize();
    }
    window.addEventListener('resize', resizeChart);
    resizeChart();
});
</script>

</html>