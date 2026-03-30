<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Authorization check (ADMIN only)
if ($g_user_role !== 'ADMIN') {
	header('Location: ' . BASE_URL . 'index.php');
	exit();
}

// Page header / sidebar active
$general_page_title  = 'Registrar Activity Log';
$page_header_title   = $general_page_title;
$header_breadcrumbs  = [];
$active_page         = 'activity_logs';
?>

<!DOCTYPE html>
<html lang="en" class="h-100">

<head>
	<?php
	include_once DOMAIN_PATH . '/global/meta_data.php';
	include_once DOMAIN_PATH . '/global/include_top.php';
	?>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/css/activity_logs.css?v=<?php echo FILE_VERSION; ?>">
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
							<div class="card log-card">
								<div class="card-header text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="background-color: #2563EB; font-size: large;">
									<div>
										<i class="bi bi-person-workspace"></i>&ensp;Registrar Activity Log
									</div>
								</div>
								<div class="card-body mt-3 bg-white">
									<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
										<div class="d-flex flex-wrap align-items-center gap-2">
											<label for="registrar-date-range" class="mb-0">Date range:</label>
											<input type="text" id="registrar-date-range" class="form-control form-control-sm" data-toggle="date-picker-range" />
											<button type="button" id="registrar-clear-date" class="btn btn-outline-secondary btn-sm">Clear</button>
										</div>
										<div id="registrar-log-summary" class="small text-muted"></div>
									</div>
									<div id="registrar-log-table"></div>
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
		window.registrarLogsConfig = {
			baseUrl: '<?php echo BASE_URL; ?>',
		};
	</script>
	<script src="<?php echo BASE_URL; ?>admin/js/registar_logs.js?v=<?php echo FILE_VERSION; ?>"></script>
</body>

</html>

