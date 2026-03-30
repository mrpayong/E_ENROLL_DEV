<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Restrict to admin users only
if ($g_user_role !== 'ADMIN') {
	header('Location: ' . BASE_URL . 'index.php');
	exit();
}

// Page header title
$general_page_title = 'Notifications';
$page_header_title = $general_page_title;
$header_breadcrumbs = [];

// Load notifications from the notifications table
$notifications = [];

try {
	// You can later add WHERE recipient_id = ? if you want per-user filtering.
	$notifSql = "SELECT notif_id, sender_id, recipient_id, content, created_At, unread
				 FROM notification
				 ORDER BY created_At DESC";

	if ($nq = call_mysql_query($notifSql)) {
		while ($row = call_mysql_fetch_array($nq)) {
			$notifications[] = $row;
		}
	}
} catch (\Throwable $e) {
	$notifications = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<?php
	include_once DOMAIN_PATH . '/global/meta_data.php';
	include_once DOMAIN_PATH . '/global/include_top.php';
	?>
	<style>
		.notif-status-badge {
			font-size: 0.75rem;
		}
		.notif-unread {
			background-color: #eff6ff;
		}
		.notif-unread .notif-status-badge {
			background-color: #2563EB;
			color: #fff;
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
					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header d-flex justify-content-between align-items-center text-white" style="background:#2563EB;">
									<span class="fw-bold"><i class="fas fa-bell me-2"></i> Notifications</span>
								</div>

								<div class="card-body">
									<div class="table-responsive">
										<table class="table table-hover align-middle mb-0">
											<thead class="table-light">
												<tr>
													<th style="width: 70px;">ID</th>
													<th>Content</th>
													<th style="width: 180px;">Recipient</th>
													<th style="width: 200px;">Created At</th>
													<th style="width: 120px;">Status</th>
												</tr>
											</thead>
											<tbody>
												<?php if (!empty($notifications)): ?>
													<?php foreach ($notifications as $n): ?>
														<?php
															$isUnread = isset($n['unread']) ? ((int)$n['unread'] === 0) : false;
															$rowClass = $isUnread ? 'notif-unread' : '';
															$createdAt = isset($n['created_At']) ? $n['created_At'] : (isset($n['created_at']) ? $n['created_at'] : '');
															$timeLabel = $createdAt ? date('M j, Y g:i A', strtotime($createdAt)) : '';
														?>
														<tr class="<?php echo $rowClass; ?>">
															<td>#<?php echo (int)$n['notif_id']; ?></td>
															<td><?php echo htmlspecialchars($n['content'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
															<td>
																<?php
																	$recipient = isset($n['recipient_id']) ? (int)$n['recipient_id'] : 0;
																	echo $recipient > 0 ? 'User ID: ' . $recipient : '—';
																?>
															</td>
															<td><?php echo htmlspecialchars($timeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
															<td>
																<?php if ($isUnread): ?>
																	<span class="badge notif-status-badge">Unread</span>
																<?php else: ?>
																	<span class="badge bg-success notif-status-badge">Read</span>
																<?php endif; ?>
															</td>
														</tr>
													<?php endforeach; ?>
												<?php else: ?>
													<tr>
														<td colspan="5" class="text-center text-muted py-4">No notifications found</td>
													</tr>
												<?php endif; ?>
											</tbody>
										</table>
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
</body>
</html>

