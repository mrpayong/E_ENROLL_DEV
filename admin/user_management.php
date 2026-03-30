<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Authorization check
if ($g_user_role !== 'ADMIN') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Page header / sidebar active
$general_page_title = 'User Management';
$get_user_value = strtoupper($_GET['none'] ?? '');
$page_header_title = ACCESS_NAME[$get_user_value] ?? $general_page_title;
$header_breadcrumbs = [];
$active_page = 'user_management';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
    include_once DOMAIN_PATH . '/global/meta_data.php';
    include_once DOMAIN_PATH . '/global/include_top.php';
    ?>
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

            <div class="container">
                <div class="page-inner">
                    <?php
                    include_once DOMAIN_PATH . '/global/page_header.php';
                    ?>

                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header text-white fw-semibold d-flex align-items-center justify-content-between flex-wrap" style="background-color: #2563EB; font-size: large;">
                                    <div>
                                        <i class="bi bi-person-gear"></i>&ensp;User Management
                                    </div>
                                </div>
                                <div class="card-body mt-3 bg-white">
                                    <div id="users-management-table"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php
            include_once DOMAIN_PATH . '/global/footer.php';
            ?>
        </div>
    </div>

    <?php
    include_once DOMAIN_PATH . '/global/include_bottom.php';
    ?>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="userForm">
                        <input type="hidden" id="userId" name="user_id">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="f_name" name="f_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="l_name" name="l_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="m_name" name="m_name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Suffix</label>
                                <input type="text" class="form-control" id="suffix" name="suffix">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="text-muted">Leave blank to keep current password</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" id="position" name="position">
                            </div>
                            <div class="col-12">
                                <label class="form-label">User Roles</label>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role_admin" name="user_role[]" value="1">
                                            <label class="form-check-label" for="role_admin">Admin</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role_registrar" name="user_role[]" value="2">
                                            <label class="form-check-label" for="role_registrar">Registrar</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role_dean" name="user_role[]" value="4">
                                            <label class="form-check-label" for="role_dean">Dean</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role_faculty" name="user_role[]" value="5">
                                            <label class="form-check-label" for="role_faculty">Faculty</label>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="role_student" name="user_role[]" value="6">
                                            <label class="form-check-label" for="role_student">Student</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="status_active" name="status" value="active" checked>
                                    <label class="form-check-label" for="status_active">Active</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" id="status_archived" name="status" value="archived">
                                    <label class="form-check-label" for="status_archived">Archived</label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveUser()">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Tabulator table
        const table = new Tabulator("#users-management-table", {
            ajaxURL: "<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php",
            ajaxParams: { action: "fetch_users" },
            pagination: "remote",
            paginationSize: 25,
            paginationSizeSelector: [25, 50, 100],
            height: "700px",
            layout: "fitDataStretch",
            placeholder: "No Users Found",
            headerHozAlign: "center",
            ajaxLoader: true,
            ajaxLoaderLoading: "Loading users...",
            initialSort: [{ column: "user_id", dir: "desc" }],
            columns: [
                { title: "ID", field: "user_id", headerFilter: "input", width: 80 },
                { title: "Username", field: "username", headerFilter: "input", width: 120 },
                { 
                    title: "Full Name", 
                    field: "full_name", 
                    headerFilter: "input", 
                    width: 200,
                    formatter: function(cell) {
                        const data = cell.getRow().getData();
                        return data.f_name + " " + (data.m_name ? data.m_name + " " : "") + data.l_name + (data.suffix ? " " + data.suffix : "");
                    }
                },
                { title: "Email", field: "email_address", headerFilter: "input", width: 200 },
                { title: "Position", field: "position", headerFilter: "input", width: 150 },
                { 
                    title: "Roles", 
                    field: "user_role", 
                    width: 150,
                    formatter: function(cell) {
                        const roles = cell.getValue();
                        if (!roles) return "";
                        try {
                            const roleArray = JSON.parse(roles);
                            const roleLabels = {1:"Admin",2:"Registrar",4:"Dean",5:"Faculty",6:"Student"};
                            return roleArray.map(r => roleLabels[r] || r).join(", ");
                        } catch(e) {
                            return roles;
                        }
                    }
                },
                { 
                    title: "Status", 
                    field: "status", 
                    width: 100,
                    formatter: function(cell) {
                        const value = cell.getValue();
                        const status = value === "archived" ? "archived" : "active";
                        const color = value === "archived" ? "danger" : "success";
                        return '<span class="badge bg-' + color + '">' + status.toUpperCase() + '</span>';
                    }
                }
            ]
        });

        // User management functions
        function addNewUser() {
            document.getElementById("modalTitle").textContent = "Add New User";
            document.getElementById("userForm").reset();
            document.getElementById("userId").value = "";
            document.getElementById("password").required = true;
            const modal = new bootstrap.Modal(document.getElementById("userModal"));
            modal.show();
        }

        function editUser(userId) {
            document.getElementById("modalTitle").textContent = "Edit User";
            document.getElementById("userId").value = userId;
            document.getElementById("password").required = false;
            
            // Fetch user data
            fetch("<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php?action=get_user&user_id=" + userId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;
                        document.getElementById("f_name").value = user.f_name || "";
                        document.getElementById("l_name").value = user.l_name || "";
                        document.getElementById("m_name").value = user.m_name || "";
                        document.getElementById("suffix").value = user.suffix || "";
                        document.getElementById("username").value = user.username || "";
                        document.getElementById("email").value = user.email_address || "";
                        document.getElementById("position").value = user.position || "";
                        
                        // Set roles
                        try {
                            const roles = JSON.parse(user.user_role || "[]");
                            document.querySelectorAll('input[name="user_role[]"]').forEach(checkbox => {
                                checkbox.checked = roles.includes(parseInt(checkbox.value));
                            });
                        } catch(e) {
                            console.log("Error parsing roles:", e);
                        }
                        
                        // Set status
                        try {
                            document.querySelector('input[name="status"][value="' + (user.status || "active") + '"]').checked = true;
                        } catch(e) {
                            console.log("Error setting status:", e);
                        }
                        
                        const modal = new bootstrap.Modal(document.getElementById("userModal"));
                        modal.show();
                    } else {
                        alert("Error loading user data: " + (data.message || "Unknown error"));
                    }
                })
                .catch(error => {
                    console.error("Error fetching user:", error);
                    alert("Error loading user data. Please check console for details.");
                });
        }

        function saveUser() {
            const form = document.getElementById("userForm");
            const formData = new FormData(form);
            
            // Collect roles
            const roles = [];
            document.querySelectorAll('input[name="user_role[]"]:checked').forEach(checkbox => {
                roles.push(checkbox.value);
            });
            formData.set("user_role", JSON.stringify(roles));
            
            const action = formData.get("user_id") ? "update_user" : "add_user";
            formData.set("action", action);
            
            // Remove user_id from formData if it's empty (for new users)
            if (!formData.get("user_id")) {
                formData.delete("user_id");
            }
            
            fetch("<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById("userModal")).hide();
                    table.replaceData();
                    alert(data.message || "User saved successfully!");
                } else {
                    alert(data.message || "Error saving user");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("An error occurred while saving user");
            });
        }

        function archiveUser(userId) {
            if (confirm("Are you sure you want to archive this user?")) {
                const formData = new FormData();
                formData.append("action", "archive_user");
                formData.append("user_id", userId);
                
                fetch("<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        table.replaceData();
                        alert("User archived successfully!");
                    } else {
                        alert(data.message || "Error archiving user");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while archiving user");
                });
            }
        }

        function restoreUser(userId) {
            if (confirm("Are you sure you want to restore this user?")) {
                const formData = new FormData();
                formData.append("action", "restore_user");
                formData.append("user_id", userId);
                
                fetch("<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        table.replaceData();
                        alert("User restored successfully!");
                    } else {
                        alert(data.message || "Error restoring user");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while restoring user");
                });
            }
        }

        function deleteUser(userId) {
            if (confirm("Are you sure you want to permanently delete this user? This action cannot be undone!")) {
                const formData = new FormData();
                formData.append("action", "delete_user");
                formData.append("user_id", userId);
                
                fetch("<?php echo BASE_URL; ?>backend/users/user_management_new_backend.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        table.replaceData();
                        alert("User deleted successfully!");
                    } else {
                        alert(data.message || "Error deleting user");
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while deleting user");
                });
            }
        }
    </script>
</body>
</html>
