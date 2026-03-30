<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;
require DOMAIN_PATH . '/dean/process/dean_backlog_helper.php';

$general_page_title = "Student Information";
$page_header_title = "Dean's Student Information";
$header_breadcrumbs = [];
$active_page = 'student_information';

// Guard: Only allow DEAN or ADMIN
if (!in_array($g_user_role, ["DEAN", "ADMIN"])) {
	header("Location: " . BASE_URL . "index.php");
	exit();
}

// Determine Dean's department via users -> departments mapping
$dean_department_id = null;
$dean_department_name = '';

$sql_dept = "SELECT d.department_id, d.department
			 FROM departments d
			 INNER JOIN users u ON d.user_id = u.user_id
			 WHERE u.general_id = '" . escape($db_connect, $g_general_id) . "'
			 LIMIT 1";

if ($res_dept = mysqli_query($db_connect, $sql_dept)) {
	if ($row_dept = mysqli_fetch_assoc($res_dept)) {
		$dean_department_id = (int) $row_dept['department_id'];
		$dean_department_name = $row_dept['department'];
	}
}

// Fetch students under the Dean's department/programs
$students_data = [];

$where_clause = '';
if ($dean_department_id !== null) {
	// New student schema no longer stores department_id on student;
	// filter via the program's department_id instead.
	$where_clause = "WHERE p.department_id = " . (int) $dean_department_id;
}

// Current school year/semester is needed to compute academic
// status (Regular/Irregular) based on backlogs.
$sy = get_school_year();
$current_sem = $sy['sem'] ?? '';

$sql_students = "SELECT 
		s.student_id_no AS student_id,
		s.firstname,
		s.lastname,
		s.middle_name,
		s.year_level,
		s.status,
		s.program_id,
		p.program,
		p.short_name,
		d.department
	FROM student s
	LEFT JOIN programs p ON s.program_id = p.program_id
	LEFT JOIN departments d ON p.department_id = d.department_id
	$where_clause
	ORDER BY s.lastname, s.firstname";

$result_students = mysqli_query($db_connect, $sql_students);
if ($result_students) {
	while ($row = mysqli_fetch_assoc($result_students)) {
		$middle_initial = '';
		if (!empty($row['middle_name'])) {
			$middle_initial = ' ' . strtoupper(substr($row['middle_name'], 0, 1)) . '.';
		}

		$row['fullname'] = strtoupper($row['lastname'] . ', ' . $row['firstname'] . $middle_initial);

		// Compute academic status (Regular/Irregular) from curriculum
		// vs. completed subjects using the dean helper.
		$student_id_text = $row['student_id'] ?? '';
		$program_id = (int)($row['program_id'] ?? 0);
		$year_level = (int)($row['year_level'] ?? 0);
		$academic_status = 'Irregular';
		if ($student_id_text !== '' && $program_id > 0 && $year_level > 0) {
			$academic_status = dean_determine_academic_status_from_curriculum(
				$db_connect,
				$student_id_text,
				$program_id,
				$year_level,
				$current_sem
			);
		}
		$row['academic_status'] = $academic_status;
		$students_data[] = $row;
	}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php
	include_once DOMAIN_PATH . '/global/meta_data.php';
	include_once DOMAIN_PATH . '/global/include_top.php';
	?>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>dean/css/student_information.css?v=<?php echo FILE_VERSION; ?>">
</head>
<body>
	<div class="wrapper">
		<?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>

		<div class="main-panel">
			<?php include_once DOMAIN_PATH . '/global/header.php'; ?>

			<div class="container">
				<div class="page-inner">
					<?php include_once DOMAIN_PATH . '/global/page_header.php'; ?>

					<div class="row">
						<div class="col-12">
							<div class="card student-info-card">
								<div class="card-header text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="background-color: #2563EB; font-size: large;">
									<div>
										<i class="bi bi-people"></i>&ensp;Student Information
									</div>
									<div class="text-white-50 small">
										<?php echo $dean_department_name ? ''
										 . htmlspecialchars($dean_department_name) : 'All Departments'; ?>
									</div>
								</div>
								<div class="card-body mt-3 bg-white">
									<div id="student-info-table"></div>
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

	<script>
		window.deanStudentInfoConfig = {
			tableData: <?php echo json_encode($students_data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?> || [],
			baseUrl: '<?php echo BASE_URL; ?>'
		};
	</script>
	<script type="text/javascript" src="<?php echo BASE_URL; ?>dean/js/student_information.js?v=<?php echo FILE_VERSION; ?>"></script>
</body>
</html>

