<?php

/** 
==================================================================
 File name   : sidebar.php
 Version     : 1.0.0
 Begin       : 2026-02-26
 Last Update : 
 Author      : 
 Description : sidebar (FOR ADMINS UI).
 =================================================================
 **/

## activation on each pages
// function navigation_active($page, $class = 'active')
// {
//     global $active_page;

//     $pageArray = array_map('trim', explode(',', $page));
//     return in_array($active_page, $pageArray) ? $class : '';
// }

function navigation_active($pages, $class = 'active', $conditions = [])
{
    global $active_page;

    $pageArray = array_map('trim', explode(',', $pages));

    // Check if current page matches
    if (!in_array($active_page, $pageArray)) {
        return '';
    }

    // If no GET conditions required
    if (empty($conditions)) {
        return $class;
    }

    // Check GET conditions
    foreach ($conditions as $key => $values) {
        if (!isset($_GET[$key])) {
            return '';
        }
        $values = (array) $values; // ensure array
        if (!in_array($_GET[$key], $values)) {
            return '';
        }
    }

    return $class;
}
?>
<div class="sidebar" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
            <a href="<?php echo BASE_URL . 'index.php'; ?>" class="logo pt-2">
                <img src="<?php echo DISPLAY_LOGO; ?>" alt="navbar brand" class="navbar-brand" height="100%">
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>
        </div>
        <!-- End Logo Header -->
    </div>

    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <?php if ($g_user_role == 'ADMIN') { ?>
                    <!-- navigation section -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4>
                    </li>

                    <!-- Dashboard -->
                    <li class="nav-item <?php echo navigation_active("main_admin"); ?>">
                        <a href="<?php echo BASE_URL . "admin/main_admin.php" ?>">
                            <i class="fas fa-home"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- User Information -->
                    <li class="nav-item <?php echo navigation_active("user_information"); ?>">
                        <a href="<?php echo BASE_URL . "admin/user_information.php" ?>">
                            <i class="fas fa-users"></i>
                            <p>User Information</p>
                        </a>
                    </li>

                    <!-- User Management -->
                    <li class="nav-item <?php echo navigation_active("user_management"); ?>">
                        <a href="<?php echo BASE_URL . "admin/user_management.php" ?>">
                            <i class="fas fa-user-cog"></i>
                            <p>User Management</p>
                        </a>
                    </li>

                    <!-- User Uploads (submenu) -->
                    <li class="nav-item <?php echo navigation_active("user_upload", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#user_upload_nav">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <p>User Uploads</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("user_upload", "show"); ?>" id="user_upload_nav">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/admin_upload.php"; ?>">
                                        <span class="sub-item">Admin Uploads</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/registrar_upload.php"; ?>">
                                        <span class="sub-item">Registrar Uploads</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/dean_upload.php"; ?>">
                                        <span class="sub-item">Dean Uploads</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- Activity Logs -->
                    <li class="nav-item <?php echo navigation_active("activity_logs", "active submenu"); ?>">
                        <a class="collapsed" aria-expanded="false" data-bs-toggle="collapse" href="#activity_logs_nav">
                            <i class="fas fa-clock"></i>
                            <p>Activity Logs</p>
                            <span class="caret"></span>
                        </a>
                        <div class="collapse <?php echo navigation_active("activity_logs", "show"); ?>" id="activity_logs_nav">
                            <ul class="nav nav-collapse">
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/registrar_logs.php"; ?>">
                                        <span class="sub-item">Registrar</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/admin_logs.php"; ?>">
                                        <span class="sub-item">Admin</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/dean_logs.php"; ?>">
                                        <span class="sub-item">Dean</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="<?php echo BASE_URL . "admin/students_logs.php"; ?>">
                                        <span class="sub-item">Student</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </li>

                    <!-- User Logs (single link) -->
                    <li class="nav-item <?php echo navigation_active("user_logs"); ?>">
                        <a href="<?php echo BASE_URL . "admin/user_logs.php"; ?>">
                            <i class="fas fa-user-check"></i>
                            <p>User Logs</p>
                        </a>
                    </li>

                    <!-- Backup -->
                    <li class="nav-item <?php echo navigation_active("backup"); ?>">
                        <a href="<?php echo BASE_URL . "admin/backup.php"; ?>">
                            <i class="fas fa-cloud-download-alt"></i>
                            <p>Backup</p>
                        </a>
                    </li>

                <?php } else if ($g_user_role == 'REGISTRAR') { ?>
                    <!-- navigation section (remove if not needed) -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4> <!-- change based on role -->
                    </li>

                    <!-- start navigation -->
                    <li class="nav-item <?php echo navigation_active("main_registrar"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/main_registrar.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("student_infos"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/student_infos.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Student Information</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("fiscalYear"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/fiscalYear.php">
                            <i class="fas fa-calendar"></i>
                            <span>Fiscal Year</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("department"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/department.php">
                            <i class="fas fa-university"></i>
                            <span>Department</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("enrollment"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/enrollment.php">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Enrollment</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("curriculum"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/curriculum.php">
                            <i class="fas fa-book-open"></i>
                            <span>Curriculum</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("section"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/section.php">
                            <i class="fas fa-th"></i>
                            <span>Section</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("schedule"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/schedule.php">
                            <i class="fas fa-clock"></i>
                            <span>Schedule</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("course"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/course.php">
                            <i class="fas fa-book-reader"></i>
                            <span>Course</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("grade_master"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/grade_master.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Grade Master</span>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("grade_settings"); ?>">
                        <a href="<?php echo BASE_URL; ?>registrar/grade_settings.php">
                            <i class="fas fa-cogs"></i>
                            <span>Grade Settings</span>
                        </a>
                    </li>

                <?php } else if ($g_user_role == 'STUDENT') { ?>
                    <!-- navigation section (remove if not needed) -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4>
                    </li>

                    <!-- start navigation -->
                    <li class="nav-item <?php echo navigation_active("enrollment_status"); ?>">
                        <a href="<?php echo BASE_URL . "student/enrollment_status.php" ?>">
                            <i class="fas fa-clipboard-list"></i>
                            <p>Enrollment Status</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("grades"); ?>">
                        <a href="<?php echo BASE_URL . "student/grades.php" ?>">
                            <i class="fas fa-graduation-cap"></i>
                            <p>Grades</p>
                        </a>
                    </li>

                    <!-- Fiscal Year (Student) - opens global modal -->
                    <li class="nav-item">
                        <a href="#" id="student_fiscal_year_link">
                            <i class="fas fa-calendar-alt"></i>
                            <p>Fiscal Year</p>
                        </a>
                    </li>

                
                
                <?php } else if ($g_user_role == 'DEAN') { ?>
                    <!-- navigation section for DEAN -->
                    <li class="nav-section">
                        <span class="sidebar-mini-icon">
                            <i class="fa fa-ellipsis-h"></i>
                        </span>
                        <h4 class="text-section"><?php echo ACCESS_NAME[$g_user_role]; ?></h4>
                    </li>

                    <!-- Fiscal Year (Dean) - opens global modal -->
                    <li class="nav-item">
                        <a href="#" id="dean_fiscal_year_link">
                            <i class="fas fa-calendar-alt"></i>
                            <p>Fiscal Year</p>
                        </a>
                    </li>


                    <!-- start navigation for DEAN -->
                    <li class="nav-item <?php echo navigation_active("student_approvals"); ?>">
                        <a href="<?php echo BASE_URL . "dean/student_approvals.php" ?>">
                            <i class="fas fa-user-check"></i>
                            <p>Student Approvals</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("student_information"); ?>">
                        <a href="<?php echo BASE_URL . "dean/student_information.php" ?>">
                            <i class="fas fa-users"></i>
                            <p>Student Information</p>
                        </a>
                    </li>

                    <li class="nav-item <?php echo navigation_active("section_dean"); ?>">
                        <a href="<?php echo BASE_URL; ?>dean/section_dean.php">
                            <i class="fas fa-th"></i>
                            <span>Section</span>
                        </a>
                    </li>

                <?php } ?>
            </ul>
        </div>
    </div>
</div>