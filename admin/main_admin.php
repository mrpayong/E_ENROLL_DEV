<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Verify user access
if ($g_user_role !== 'ADMIN') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// AJAX endpoint for user statistics
if (isset($_GET['action']) && $_GET['action'] === 'fetch_stats') {
    header('Content-Type: application/json');

    $sex = !empty($_GET['sex']) ? strtolower(trim($_GET['sex'])) : null;
    $target_role = !empty($_GET['target_role']) ? (int)$_GET['target_role'] : 0;

    // Standardized role mapping
    $roleLabels = [1 => 'Admin', 2 => 'Registrar', 3 => 'Dean', 4 => 'Faculty', 5 => 'Student'];
    $roleCounts = array_fill_keys(array_keys($roleLabels), 0);

    $conditions = [];
    if ($sex) {
        $first = substr($sex, 0, 1);
        $conditions[] = "LEFT(LOWER(TRIM(u.sex)),1)='" . addslashes($first) . "'";
    }

    if ($target_role) {
        $conditions[] = "JSON_CONTAINS(u.user_role, '\"$target_role\"')";
    }

    $condSql = count($conditions) ? (' WHERE ' . implode(' AND ', $conditions)) : '';
    $sql = "SELECT u.user_role FROM users u $condSql";

    if ($q = call_mysql_query($sql)) {
        while ($row = call_mysql_fetch_array($q)) {
            $roles = json_decode($row['user_role'], true);
            if (is_array($roles)) {
                foreach ($roles as $r) {
                    if (isset($roleCounts[$r])) {
                        $roleCounts[$r]++;
                    }
                }
            }
        }
    }

    // Filter output based on focus role
    $filteredData = ($target_role && isset($roleCounts[$target_role]))
        ? [$target_role => $roleCounts[$target_role]]
        : array_filter($roleCounts, function($v) { return $v >= 0; });

    $labelsOut = [];
    $dataOut = [];
    foreach ($filteredData as $id => $count) {
        if (isset($roleLabels[$id])) {
            $labelsOut[] = $roleLabels[$id];
            $dataOut[] = $count;
        }
    }

    echo json_encode([
        'success' => true,
        'labels'  => $labelsOut,
        'data'    => $dataOut,
        'raw'     => $filteredData, // Keep IDs for color matching
    ]);
    exit();
}

// Page header title
$general_page_title = "Admin Dashboard";
$page_header_title = $general_page_title;
$header_breadcrumbs = [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
    <style>
        .filter-bar .form-select { min-width: 150px; }
        .stat-card { border-left: 5px solid #2563EB; transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-3px); }
        .stat-label { font-size: 0.85rem; color: #64748b; font-weight: 600; }
    </style>
</head>
<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>

            <div class="container">
                <div class="page-inner">
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center text-white" style="background:#2563EB;">
                                    <span class="fw-bold"><i class="fas fa-chart-bar me-2"></i> User Overview</span>
                                    <button class="btn btn-light btn-sm" id="refreshBtn">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>

                                <div class="card-body">
                                    <div class="row g-3 mb-4 align-items-end">
                                        <div class="col-auto">
                                            <label class="form-label small fw-bold">Sex</label>
                                            <select id="sexFilter" class="form-select form-select-sm">
                                                <option value="">All</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <label class="form-label small fw-bold">Focus Role</label>
                                            <select id="roleFocus" class="form-select form-select-sm">
                                                <option value="">All Roles</option>
                                                <option value="1">Admin</option>
                                                <option value="2">Registrar</option>
                                                <option value="3">Dean</option>
                                                <option value="4">Faculty</option>
                                                <option value="5">Student</option>
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <button class="btn btn-sm btn-primary px-3" id="applyFilters">
                                                Apply
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4" id="statCards"></div>

                                    <div style="position: relative; height:300px;">
                                        <canvas id="usersChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
        </div>
    </div>

    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        (function() {
            const ctx = document.getElementById('usersChart').getContext('2d');
            const colors = {
                1: '#10b981', // Admin - Green
                2: '#3b82f6', // Registrar - Blue
                3: '#8b5cf6', // Dean - Purple
                4: '#f59e0b', // Faculty - Orange
                5: '#06b6d4'  // Student - Cyan
            };
            const roleLabels = { 1: 'Admin', 2: 'Registrar', 3: 'Dean', 4: 'Faculty', 5: 'Student' };

            let chartRef = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [],
                        borderRadius: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });

            function fetchStats() {
                const sex = document.getElementById('sexFilter').value;
                const roleFocus = document.getElementById('roleFocus').value;
                
                const params = new URLSearchParams({ action: 'fetch_stats' });
                if (sex) params.append('sex', sex);
                if (roleFocus) params.append('target_role', roleFocus);

                fetch('main_admin.php?' + params.toString())
                    .then(r => r.json())
                    .then(res => {
                        if (!res.success) return;

                        // Update Chart
                        chartRef.data.labels = res.labels;
                        chartRef.data.datasets[0].data = res.data;
                        
                        // Map colors based on the raw IDs returned
                        chartRef.data.datasets[0].backgroundColor = Object.keys(res.raw).map(id => colors[id] || '#cbd5e1');
                        chartRef.update();

                        // Update Cards
                        renderCards(res.raw);
                    });
            }

            function renderCards(raw) {
                const container = document.getElementById('statCards');
                container.innerHTML = '';
                
                Object.keys(raw).forEach(id => {
                    if (raw[id] === 0 && document.getElementById('roleFocus').value === "") return; // Hide empty cards in "All" view
                    
                    const card = document.createElement('div');
                    card.className = 'col-6 col-md-2';
                    card.innerHTML = `
                        <div class="card stat-card shadow-sm mb-0" style="border-left-color:${colors[id]}">
                            <div class="card-body p-3 text-center">
                                <div class="stat-label mb-1">${roleLabels[id] || 'Unknown'}</div>
                                <div class="h4 mb-0 fw-bold" style="color:${colors[id]}">${raw[id]}</div>
                            </div>
                        </div>`;
                    container.appendChild(card);
                });
            }

            document.getElementById('applyFilters').addEventListener('click', fetchStats);
            document.getElementById('refreshBtn').addEventListener('click', fetchStats);
            fetchStats(); // Initial load
        })();
    </script>
</body>
</html>