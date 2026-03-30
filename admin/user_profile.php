<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require ISLOGIN;

if (!defined('ACTIVE_PAGE')) {
	define('ACTIVE_PAGE', 'user_profile');
}

$active_page = 'user_profile';

// Fetch user info (template based on enroll/views/user_profile.php)
$db_general_id = $db_full_name = $db_birth_date = $db_sex = $db_email = $db_recovery_email = $db_position = $db_username = $db_password = '';

$users_sql = "SELECT general_id,f_name,m_name,l_name,suffix,birth_date,sex,email_address,recovery_email,position,img FROM users WHERE general_id='" . $g_general_id . "' LIMIT 1";
if ($users_query = mysqli_query($db_connect, $users_sql)) {
	if (mysqli_num_rows($users_query)) {
		$u = call_mysql_fetch_array($users_query);
		$u = array_html($u);
		$db_general_id     = $u['general_id'];
		$db_full_name      = trim($u['f_name'] . ' ' . ($u['m_name'] ? $u['m_name'] . ' ' : '') . $u['l_name'] . ' ' . $u['suffix']);
		$db_birth_date     = $u['birth_date'] ? date('M j, Y', strtotime($u['birth_date'])) : '';
		$db_sex            = $u['sex'];
		$db_email          = $u['email_address'];
		$db_recovery_email = $u['recovery_email'];
		$db_position       = $u['position'];
		$g_photo           = $u['img'];
	}
	mysqli_free_result($users_query);
}

$login_sql = "SELECT username,password FROM users WHERE user_id='" . $s_user_id . "' LIMIT 1";
if ($login_query = mysqli_query($db_connect, $login_sql)) {
	if (mysqli_num_rows($login_query)) {
		$l = call_mysql_fetch_array($login_query);
		$l = array_html($l);
		$db_username = $l['username'];
		$db_password = $l['password'];
	}
	mysqli_free_result($login_query);
}

// Heading based on role
$profile_heading = match ($g_user_role) {
	'ADMIN' => 'ADMIN PROFILE',
	'REGISTRAR' => 'REGISTRAR PROFILE',
	'DEAN' => 'DEAN PROFILE',
	'FACULTY' => 'FACULTY PROFILE',
	default => 'MY PROFILE',
};

// Page header configuration (for admin layout)
$general_page_title = 'My Profile';
$page_header_title  = $profile_heading;
$header_breadcrumbs = [];

?>
<!DOCTYPE html>
<html lang="<?php echo LANG; ?>">

<head>
	<?php
	include_once DOMAIN_PATH . '/global/meta_data.php';
	include_once DOMAIN_PATH . '/global/include_top.php';
	?>
	<style>
		/* Vivid blue palette (matches draft custom-style.css) */
		:root {
			--color-accent-blue: hsl(223, 98%, 43%);
			--color-accent-blue-text: hsl(223, 98%, 43%);
			--color-accent-blue-btn-bg: hsl(223, 98%, 55%);
			--color-accent-blue-btn-bg-hover: hsl(223, 98%, 48%);
			--color-accent-blue-border: hsl(223, 98%, 82%);
			--color-accent-blue-subtle-bg: hsl(223deg, 98%, 96%);
		}

		.btn-blue {
			--bs-btn-color: #fff;
			--bs-btn-bg: var(--color-accent-blue);
			--bs-btn-border-color: var(--color-accent-blue);
			--bs-btn-hover-color: #fff;
			--bs-btn-hover-bg: var(--color-accent-blue-btn-bg-hover);
			--bs-btn-hover-border-color: var(--color-accent-blue-btn-bg);
			--bs-btn-focus-shadow-rgb: 49, 132, 253;
			--bs-btn-active-color: #fff;
			--bs-btn-active-bg: var(--color-accent-blue-btn-bg);
			--bs-btn-active-border-color: #0a53be;
			--bs-btn-active-shadow: inset 0 3px 5px rgba(0, 0, 0, 0.125);
			--bs-btn-disabled-color: #fff;
			--bs-btn-disabled-bg: var(--color-accent-blue);
			--bs-btn-disabled-border-color: var(--color-accent-blue);
		}

		.color-accent-blue-bg {
			background-color: var(--color-accent-blue);
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
					<?php include_once DOMAIN_PATH . '/global/page_header.php'; ?>

					<div class="row">
						<div class="col-xl-4">
							<div class="card">
								<div class="card-body profile-card pt-4 d-flex flex-column align-items-center">
									<?php
									// Always prefer the latest image from the database for this page
									if (!empty($u['img'])) {
										$g_photo = $u['img'];
									}
									if (empty($g_photo)) {
										$g_photo = 'upload/img/' . DEFAULT_IMG;
									}
									$photo_url = BASE_URL . 'view-image.php?file=' . urlencode($g_photo);
									?>
									<div style="position:relative;">
										<img id="profileImage" src="<?php echo $photo_url; ?>" style="width:120px; aspect-ratio:1/1; object-fit:cover;" alt="Profile" class="rounded-circle border">
										<button type="button" id="cameraTrigger" class="btn btn-sm btn-blue" title="Change Photo" style="position:absolute; right:-6px; bottom:-6px; border-radius:50%; width:36px; height:36px; display:flex; align-items:center; justify-content:center;">
											<i class="bi bi-camera"></i>
										</button>
									</div>
									<input type="file" id="profileInput" accept="image/jpeg,image/png,image/webp" class="d-none">
									<button type="button" id="uploadBtn" class="btn btn-sm btn-success mt-2" disabled>
										<i class="bi bi-upload"></i> Upload
									</button>
									<br>
									<h4><?php echo $db_full_name; ?></h4>
									<h6><?php echo $db_position; ?></h6>
									<span class="badge bg-primary mt-2"><?php echo $g_user_role; ?></span>
								</div>
							</div>
						</div>

						<div class="col-xl-8">
							<div class="card">
								<div class="card-body pt-3">
									<ul class="nav nav-tabs nav-tabs-bordered">
										<li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-view">Profile</button></li>
									</ul>
									<div class="tab-content pt-2">
										<div class="tab-pane fade show active" id="profile-view">
											<br>
											<form>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">General ID</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_general_id; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Full Name</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_full_name; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Birth Date</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_birth_date; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Sex</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_sex; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Position</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_position; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Username</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_username; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Email Address</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_email; ?>" disabled></div>
												</div>
												<div class="row mb-3">
													<label class="col-md-4 col-lg-3 col-form-label">Recovery Email</label>
													<div class="col-md-8 col-lg-9"><input type="text" class="form-control bg-light" value="<?php echo $db_recovery_email; ?>" disabled></div>
												</div>
												<div class="text-center">
													<button type="button" class="btn btn-blue float-end" id="openProfileEdit">Update Profile</button>
												</div>
											</form>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<?php include __DIR__ . '/modals/user_profile_modal.php'; ?>

				</div>
			</div>

			<?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
		</div>
	</div>

	<?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
	<script>
		// Absolute endpoint for profile image upload
		window.profileUploadEndpoint = '<?php echo BASE_URL; ?>backend/users/upload_profile.php';
	</script>
	<script src="<?php echo BASE_URL; ?>assets/js/user_profile.js"></script>
</body>

</html>

