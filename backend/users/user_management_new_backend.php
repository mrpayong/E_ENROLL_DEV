<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 2));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// Only admins may use this endpoint
if ($g_user_role !== 'ADMIN') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'fetch_users':
        fetchUsers();
        break;
    case 'get_user':
        getUser();
        break;
    case 'add_user':
        addUser();
        break;
    case 'update_user':
        updateUser();
        break;
    case 'archive_user':
        archiveUser();
        break;
    case 'restore_user':
        restoreUser();
        break;
    case 'delete_user':
        deleteUser();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

function fetchUsers()
{
    global $db_connect;

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    $size = isset($_GET['size']) ? (int) $_GET['size'] : 25;
    $sort = $_GET['sort'] ?? 'user_id';
    $dir  = strtolower($_GET['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

    $allowedSortColumns = ['user_id', 'username', 'f_name', 'l_name', 'email_address', 'position', 'status'];
    if (!in_array($sort, $allowedSortColumns, true)) {
        $sort = 'user_id';
    }

    $filters = [];
    if (isset($_GET['user_id']) && $_GET['user_id'] !== '') {
        $val = escape($db_connect, $_GET['user_id']);
        $filters[] = "u.user_id LIKE '%$val%'";
    }
    if (isset($_GET['username']) && $_GET['username'] !== '') {
        $val = escape($db_connect, $_GET['username']);
        $filters[] = "u.username LIKE '%$val%'";
    }
    if (isset($_GET['full_name']) && $_GET['full_name'] !== '') {
        $val = escape($db_connect, $_GET['full_name']);
        $filters[] = "CONCAT(TRIM(CONCAT_WS(' ', u.f_name, u.m_name, u.l_name, u.suffix))) LIKE '%$val%'";
    }
    if (isset($_GET['email_address']) && $_GET['email_address'] !== '') {
        $val = escape($db_connect, $_GET['email_address']);
        $filters[] = "u.email_address LIKE '%$val%'";
    }
    if (isset($_GET['position']) && $_GET['position'] !== '') {
        $val = escape($db_connect, $_GET['position']);
        $filters[] = "u.position LIKE '%$val%'";
    }

    $whereClause = !empty($filters) ? 'WHERE ' . implode(' AND ', $filters) : '';

    $countSql = "SELECT COUNT(*) AS total FROM users u $whereClause";
    $countResult = mysqli_query($db_connect, $countSql);
    $total = 0;
    if ($countResult) {
        $row = mysqli_fetch_assoc($countResult);
        $total = (int) ($row['total'] ?? 0);
    }

    $offset   = ($page - 1) * $size;
    $lastPage = $size > 0 ? (int) ceil($total / $size) : 1;

    $sql = "SELECT 
                u.user_id,
                u.f_name,
                u.m_name,
                u.l_name,
                u.suffix,
                u.sex,
                u.user_role,
                u.username,
                u.email_address,
                u.position,
                u.status
            FROM users u
            $whereClause
            ORDER BY $sort $dir
            LIMIT $size OFFSET $offset";

    $rows = [];
    if ($query = mysqli_query($db_connect, $sql)) {
        while ($data = mysqli_fetch_assoc($query)) {
            // Ensure status is string 'active'/'archived'
            if ($data['status'] !== 'archived') {
                $data['status'] = 'active';
            }
            $rows[] = $data;
        }
    }

    echo json_encode([
        'success'   => true,
        'data'      => $rows,
        'page'      => $page,
        'last_page' => $lastPage,
        'total'     => $total,
    ]);
}

function getUser()
{
    global $db_connect;
    $userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;

    if ($userId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    $sql = "SELECT 
                user_id,
                f_name,
                m_name,
                l_name,
                suffix,
                sex,
                user_role,
                username,
                email_address,
                position,
                status
            FROM users
            WHERE user_id = $userId";

    if ($query = mysqli_query($db_connect, $sql)) {
        if ($data = mysqli_fetch_assoc($query)) {
            if ($data['status'] !== 'archived') {
                $data['status'] = 'active';
            }
            echo json_encode(['success' => true, 'user' => $data]);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . mysqli_error($db_connect)]);
    }
}

function addUser()
{
    global $db_connect;

    $f_name   = trim($_POST['f_name'] ?? '');
    $m_name   = trim($_POST['m_name'] ?? '');
    $l_name   = trim($_POST['l_name'] ?? '');
    $suffix   = trim($_POST['suffix'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $user_role_json = $_POST['user_role'] ?? '[]';
    $status   = $_POST['status'] ?? 'active';

    if ($f_name === '' || $l_name === '' || $username === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }

    // Validate JSON roles
    $roles = json_decode($user_role_json, true);
    if (!is_array($roles)) {
        $roles = [];
    }
    $user_role_json = json_encode(array_values(array_unique($roles)));

    // Username unique
    $checkSql = "SELECT user_id FROM users WHERE username = '" . escape($db_connect, $username) . "'";
    if ($res = mysqli_query($db_connect, $checkSql)) {
        if (mysqli_num_rows($res) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }
    }

    // Email unique
    $checkEmailSql = "SELECT user_id FROM users WHERE email_address = '" . escape($db_connect, $email) . "'";
    if ($res = mysqli_query($db_connect, $checkEmailSql)) {
        if (mysqli_num_rows($res) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
    }

    $status_value = ($status === 'archived') ? 'archived' : 'active';
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO users 
                (username, email_address, password_hash,
                 f_name, m_name, l_name, suffix,
                 position, sex, user_role, status)
            VALUES (
                '" . escape($db_connect, $username) . "',
                '" . escape($db_connect, $email) . "',
                '" . escape($db_connect, $password_hash) . "',
                '" . escape($db_connect, $f_name) . "',
                '" . escape($db_connect, $m_name) . "',
                '" . escape($db_connect, $l_name) . "',
                '" . escape($db_connect, $suffix) . "',
                '" . escape($db_connect, $position) . "',
                '',
                '" . escape($db_connect, $user_role_json) . "',
                '" . escape($db_connect, $status_value) . "'
            )";

    if (mysqli_query($db_connect, $sql)) {
        echo json_encode(['success' => true, 'message' => 'User added successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add user: ' . mysqli_error($db_connect)]);
    }
}

function updateUser()
{
    global $db_connect;

    $user_id  = (int) ($_POST['user_id'] ?? 0);
    $f_name   = trim($_POST['f_name'] ?? '');
    $m_name   = trim($_POST['m_name'] ?? '');
    $l_name   = trim($_POST['l_name'] ?? '');
    $suffix   = trim($_POST['suffix'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $user_role_json = $_POST['user_role'] ?? '[]';
    $status   = $_POST['status'] ?? 'active';

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    if ($f_name === '' || $l_name === '' || $username === '' || $email === '') {
        echo json_encode(['success' => false, 'message' => 'Required fields are missing']);
        return;
    }

    $roles = json_decode($user_role_json, true);
    if (!is_array($roles)) {
        $roles = [];
    }
    $user_role_json = json_encode(array_values(array_unique($roles)));

    // Username unique (exclude current)
    $checkSql = "SELECT user_id FROM users WHERE username = '" . escape($db_connect, $username) . "' AND user_id != $user_id";
    if ($res = mysqli_query($db_connect, $checkSql)) {
        if (mysqli_num_rows($res) > 0) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }
    }

    // Email unique (exclude current)
    $checkEmailSql = "SELECT user_id FROM users WHERE email_address = '" . escape($db_connect, $email) . "' AND user_id != $user_id";
    if ($res = mysqli_query($db_connect, $checkEmailSql)) {
        if (mysqli_num_rows($res) > 0) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            return;
        }
    }

    $status_value = ($status === 'archived') ? 'archived' : 'active';

    $sql = "UPDATE users SET
                f_name = '" . escape($db_connect, $f_name) . "',
                m_name = '" . escape($db_connect, $m_name) . "',
                l_name = '" . escape($db_connect, $l_name) . "',
                suffix = '" . escape($db_connect, $suffix) . "',
                username = '" . escape($db_connect, $username) . "',
                email_address = '" . escape($db_connect, $email) . "',
                position = '" . escape($db_connect, $position) . "',
                user_role = '" . escape($db_connect, $user_role_json) . "',
                status = '" . escape($db_connect, $status_value) . "'";

    if ($password !== '') {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $sql .= ", password_hash = '" . escape($db_connect, $password_hash) . "'";
    }

    $sql .= " WHERE user_id = $user_id";

    if (mysqli_query($db_connect, $sql)) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update user: ' . mysqli_error($db_connect)]);
    }
}

function archiveUser()
{
    global $db_connect, $s_user_id;

    $user_id = (int) ($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    if ($user_id === (int) $s_user_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot archive your own account']);
        return;
    }

    $sql = "UPDATE users SET status = 'archived' WHERE user_id = $user_id";
    if (mysqli_query($db_connect, $sql)) {
        echo json_encode(['success' => true, 'message' => 'User archived successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to archive user: ' . mysqli_error($db_connect)]);
    }
}

function restoreUser()
{
    global $db_connect;

    $user_id = (int) ($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    $sql = "UPDATE users SET status = 'active' WHERE user_id = $user_id";
    if (mysqli_query($db_connect, $sql)) {
        echo json_encode(['success' => true, 'message' => 'User restored successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to restore user: ' . mysqli_error($db_connect)]);
    }
}

function deleteUser()
{
    global $db_connect, $s_user_id;

    $user_id = (int) ($_POST['user_id'] ?? 0);
    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    if ($user_id === (int) $s_user_id) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account']);
        return;
    }

    $sql = "DELETE FROM users WHERE user_id = $user_id";
    if (mysqli_query($db_connect, $sql)) {
        echo json_encode(['success' => true, 'message' => 'User deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . mysqli_error($db_connect)]);
    }
}
