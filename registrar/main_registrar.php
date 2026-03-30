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
                    <header class="card-header bg-primary border-bottom-0 pb-0">
                        <h2 class=" text-black fw-semibold fs-5">Annual Enrolled Students</h2>
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