<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Authorization check (ADMIN only, same pattern as other admin pages)
if ($g_user_role !== 'ADMIN') {
	header('Location: ' . BASE_URL . 'index.php');
	exit();
}

// Page header / sidebar active
$general_page_title  = 'Registrar Uploads';
$page_header_title   = $general_page_title;
$header_breadcrumbs  = [];
$active_page         = 'user_upload';

// Fetch uploads directly from user_upload table (no helper class)
$uploads = [];

// Date range filtering (from GET)
$fromDate = isset($_GET['from_date']) ? trim($_GET['from_date']) : '';
$toDate   = isset($_GET['to_date']) ? trim($_GET['to_date']) : '';

// Basic validation for date format (YYYY-MM-DD)
if ($fromDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) {
	$fromDate = '';
}
if ($toDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate)) {
	$toDate = '';
}

$sql   = "SELECT * FROM user_upload WHERE target_role = 'REGISTRAR' AND is_archived = 0";
$types = '';
$params = [];

if ($fromDate !== '') {
	$sql      .= " AND upload_date >= ?";
	$types    .= 's';
	$params[] = $fromDate . ' 00:00:00';
}

if ($toDate !== '') {
	$sql      .= " AND upload_date <= ?";
	$types    .= 's';
	$params[] = $toDate . ' 23:59:59';
}

$sql .= " ORDER BY upload_date DESC";

if ($stmt = $db_connect->prepare($sql)) {
	if (!empty($params)) {
		$stmt->bind_param($types, ...$params);
	}

	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$sizeBytes    = isset($row['file_size']) ? (int) $row['file_size'] : 0;
		$relativePath = ltrim($row['file_path'] ?? '', '/');
		$downloadUrl  = $relativePath !== '' ? BASE_URL . $relativePath : '';

		$uploads[] = [
			'id'                => (int) ($row['user_upload_id'] ?? $row['id'] ?? 0),
			'filename'          => $row['filename'] ?? '',
			'original_filename' => $row['original_filename'] ?? ($row['filename'] ?? ''),
			'file_type'         => $row['file_type'] ?? '',
			'file_size_bytes'   => $sizeBytes,
			'file_size_human'   => $sizeBytes ? formatBytes($sizeBytes) : '',
			'target_role'       => $row['target_role'] ?? '',
			'uploaded_by'       => $row['uploaded_by'] ?? '',
			'upload_date'       => $row['upload_date'] ?? '',
			'is_archived'       => (bool) ($row['is_archived'] ?? false),
			'download_count'    => (int) ($row['download_count'] ?? 0),
			'file_path'         => $row['file_path'] ?? '',
			'download_url'      => $downloadUrl,
		];
	}

	$result->free();
	$stmt->close();
}

// Summary for activity logs
$totalUploads   = count($uploads);
$totalDownloads = 0;
foreach ($uploads as $uploadRow) {
	$totalDownloads += (int) ($uploadRow['download_count'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="en" class="h-100">
<head>
	<?php
	include_once DOMAIN_PATH . '/global/meta_data.php';
	include_once DOMAIN_PATH . '/global/include_top.php';
	?>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>admin/css/admin_upload.css?v=<?php echo time(); ?>">
</head>
<body>
	<div class="wrapper">
		<?php
		include_once DOMAIN_PATH . '/global/sidebar.php';
		?>

		<div class="main-panel">
			<?php
			include_once DOMAIN_PATH . '/global/header.php';
			?>

			<div class="container-fluid">
				<div class="page-inner">
					<?php require DOMAIN_PATH . '/global/page_header.php'; ?>

						<div class="row">
						<div class="col-12">
							<div class="card upload-card">
								<div class="card-header text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="background-color: #2563EB; font-size: large;">
									<div>
										<i class="bi bi-folder-fill"></i>&ensp;Registrar Uploads
									</div>
									<div>
										<button class="btn btn-light btn-sm" onclick="location.reload();">
											<i class="bi bi-arrow-clockwise"></i> Refresh
										</button>
									</div>
								</div>
									<div class="card-body mt-3 bg-white">
										<form method="get" class="row g-2 align-items-end mb-3">
											<div class="col-md-4 col-sm-6">
												<label for="registrar-date-range" class="form-label mb-1 small">Date range</label>
												<input type="text" id="registrar-date-range" class="form-control form-control-sm" autocomplete="off">
												<input type="hidden" name="from_date" id="registrar-from-date-hidden" value="<?php echo htmlspecialchars($fromDate); ?>">
												<input type="hidden" name="to_date" id="registrar-to-date-hidden" value="<?php echo htmlspecialchars($toDate); ?>">
											</div>
											<div class="col-md-4 col-sm-6">
												<button type="submit" class="btn btn-primary btn-sm me-1">
													<i class="bi bi-filter"></i> Filter
												</button>
												<a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="btn btn-outline-secondary btn-sm">
													Clear
												</a>
											</div>
											<div class="col-md-4 col-12 text-md-end mt-2 mt-md-0">
												<div class="small text-muted">
													Total uploads: <?php echo (int) $totalUploads; ?><br>
													Total downloads: <?php echo (int) $totalDownloads; ?>
												</div>
											</div>
										</form>

										<div id="registrar-uploads-table"></div>
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
		window.registrarUploadsConfig = {
			tableData: <?php echo json_encode($uploads, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>,
		};
	</script>
	<script src="<?php echo BASE_URL; ?>admin/js/registrar_upload.js?v=<?php echo FILE_VERSION; ?>"></script>
	<script>
		jQuery(function($) {
			if (typeof $.fn.daterangepicker === 'undefined' || typeof moment === 'undefined') {
				return;
			}

			var $input = $('#registrar-date-range');
			var $fromHidden = $('#registrar-from-date-hidden');
			var $toHidden = $('#registrar-to-date-hidden');

			if (!$input.length) {
				return;
			}

			var fromVal = $fromHidden.val();
			var toVal = $toHidden.val();
			var options = {
				autoUpdateInput: false,
				locale: {
					cancelLabel: 'Clear'
				}
			};

			if (fromVal && toVal) {
				var start = moment(fromVal, 'YYYY-MM-DD');
				var end = moment(toVal, 'YYYY-MM-DD');
				options.startDate = start;
				options.endDate = end;
				$input.val(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
			}

			$input.daterangepicker(options);

			$input.on('apply.daterangepicker', function(ev, picker) {
				$fromHidden.val(picker.startDate.format('YYYY-MM-DD'));
				$toHidden.val(picker.endDate.format('YYYY-MM-DD'));
				$input.val(picker.startDate.format('MMM D, YYYY') + ' - ' + picker.endDate.format('MMM D, YYYY'));
			});

			$input.on('cancel.daterangepicker', function() {
				$fromHidden.val('');
				$toHidden.val('');
				$input.val('');
			});
		});
	</script>
</body>
</html>
