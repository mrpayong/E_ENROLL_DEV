<?php
defined('DOMAIN_PATH') || define('DOMAIN_PATH', dirname(__DIR__, 1));
require DOMAIN_PATH . '/config/config.php';
require GLOBAL_FUNC;
require CL_SESSION_PATH;
require CONNECT_PATH;
require VALIDATOR_PATH;
require ISLOGIN;

// ADMIN only
if ($g_user_role !== 'ADMIN') {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

// Page settings
$general_page_title  = 'System Backup';
$page_header_title   = $general_page_title;
$header_breadcrumbs  = [];
$active_page         = 'backup';

// 1. DIRECTORY SETUP
$backupBaseDir    = DOMAIN_PATH . '/upload/backups';
$dbFullDir        = $backupBaseDir . '/db_full';
$dbIncrementalDir = $backupBaseDir . '/db_incremental';
$systemDir        = $backupBaseDir . '/system_full';

foreach ([$backupBaseDir, $dbFullDir, $dbIncrementalDir, $systemDir] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 2. HELPER FUNCTIONS
function collect_backup_files(string $dir, string $webBase): array {
    if (!is_dir($dir)) return [];
    $files = [];
    foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $path) {
        if (!is_file($path)) continue;
        $basename = basename($path);
        $files[] = [
            'name'       => $basename,
            'size'       => function_exists('formatBytes') ? formatBytes(filesize($path)) : filesize($path) . ' bytes',
            'created_at' => date('Y-m-d H:i:s', filemtime($path)),
            'url'        => rtrim($webBase, '/') . '/' . rawurlencode($basename),
        ];
    }
    usort($files, function ($a, $b) { return strcmp($b['created_at'], $a['created_at']); });
    return $files;
}

/**
 * Register a created backup file into user_upload so it appears in Admin Uploads.
 */
function register_backup_as_upload(mysqli $db, string $absolutePath, string $description, string $targetRole = 'ADMIN'): void {
    if (!defined('DOMAIN_PATH')) {
        return;
    }

    if (!is_file($absolutePath)) {
        return;
    }

    // Build relative web path (e.g. "upload/backups/db_full/file.sql")
    $domainReal = realpath(DOMAIN_PATH);
    $fileReal   = realpath($absolutePath);
    if ($domainReal === false || $fileReal === false) {
        return;
    }

    if (strpos($fileReal, $domainReal) !== 0) {
        return;
    }

    $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', substr($fileReal, strlen($domainReal))), '/');

    $filename          = basename($fileReal);
    $originalFilename  = $filename;
    $fileSize          = filesize($fileReal) ?: 0;

    // Simple mime/type mapping based on extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $fileType = match ($ext) {
        'sql' => 'application/sql',
        'zip' => 'application/zip',
        default => 'application/octet-stream',
    };

    // Use current logged-in name if available
    $uploadedBy = '';
    if (isset($GLOBALS['g_user_name']) && $GLOBALS['g_user_name'] !== '') {
        $uploadedBy = $GLOBALS['g_user_name'];
    } elseif (isset($GLOBALS['g_fullname']) && $GLOBALS['g_fullname'] !== '') {
        $uploadedBy = $GLOBALS['g_fullname'];
    }

    $sql = "INSERT INTO user_upload (filename, original_filename, file_path, file_size, file_type, target_role, uploaded_by, description, is_archived, download_count)\n" .
           "VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 0)";

    if ($stmt = $db->prepare($sql)) {
        $stmt->bind_param(
            'sssissss',
            $filename,
            $originalFilename,
            $relativePath,
            $fileSize,
            $fileType,
            $targetRole,
            $uploadedBy,
            $description
        );
        $stmt->execute();
        $stmt->close();
    }
}

function backup_database_mysqli(mysqli $db, string $dbName, string $outputFile): bool {
    $tables = [];
    $result = $db->query('SHOW TABLES');
    if (!$result) return false;
    while ($row = $result->fetch_array(MYSQLI_NUM)) { $tables[] = $row[0]; }
    $result->free();

    $sqlDump = "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $res = $db->query('SHOW CREATE TABLE `' . $db->real_escape_string($table) . '`');
        if (!$res) continue;
        $row = $res->fetch_assoc();
        $res->free();
        $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n" . ($row['Create Table'] ?? '') . ";\n\n";
        $res = $db->query('SELECT * FROM `' . $db->real_escape_string($table) . '`');
        if ($res && $res->num_rows > 0) {
            while ($row = $res->fetch_row()) {
                $values = array_map(fn($v) => ($v === null) ? 'NULL' : "'" . $db->real_escape_string($v) . "'", $row);
                $sqlDump .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
            }
            $res->free();
        }
        $sqlDump .= "\n";
    }
    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return (bool) file_put_contents($outputFile, $sqlDump);
}

function backup_system_codebase(string $sourceDir, string $zipFilePath): bool {
    if (!extension_loaded('zip') || !class_exists('ZipArchive')) {
        return false;
    }

    $zip = new ZipArchive();
    if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return false;
    }

    $sourceDirReal = realpath($sourceDir);
    if ($sourceDirReal === false) {
        $zip->close();
        return false;
    }

    $baseLen = strlen($sourceDirReal) + 1;
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDirReal, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($files as $file) {
        $filePath = $file->getRealPath();
        if ($filePath === false) {
            continue;
        }

        // Skip the backup directory itself to avoid nesting backups in backups
        if (strpos($filePath, DIRECTORY_SEPARATOR . 'upload' . DIRECTORY_SEPARATOR . 'backups') !== false) {
            continue;
        }

        $relativePath = substr($filePath, $baseLen);
        if ($relativePath === '' || $relativePath === false) {
            continue;
        }

        if ($file->isDir()) {
            $zip->addEmptyDir($relativePath);
        } else {
            $zip->addFile($filePath, $relativePath);
        }
    }

    return $zip->close();
}

function backup_database_incremental_mysqli(mysqli $db, string $dbName, string $outputFile, int $hours = 24): bool {
    $tables = [];
    $result = $db->query('SHOW TABLES');
    if (!$result) return false;
    while ($row = $result->fetch_array(MYSQLI_NUM)) { $tables[] = $row[0]; }
    $result->free();

    $sqlDump = "SET FOREIGN_KEY_CHECKS=0;\n\n";
    $intervalHours = max(1, (int) $hours);

    foreach ($tables as $table) {
        $tableEsc = $db->real_escape_string($table);

        // Detect date/time columns for incremental filter
        $colsRes = $db->query('SHOW COLUMNS FROM `' . $tableEsc . '`');
        if (!$colsRes) continue;

        $dateCols = [];
        while ($col = $colsRes->fetch_assoc()) {
            $type = strtolower($col['Type']);
            if (strpos($type, 'timestamp') === 0 || strpos($type, 'datetime') === 0 || strpos($type, 'date') === 0) {
                $dateCols[] = $col['Field'];
            }
        }
        $colsRes->free();

        if (empty($dateCols)) {
            // Skip tables without any date/time columns for incremental backup
            continue;
        }

        $conditions = [];
        foreach ($dateCols as $colName) {
            $conditions[] = '`' . $db->real_escape_string($colName) . '` >= (NOW() - INTERVAL ' . $intervalHours . ' HOUR)';
        }
        $where = implode(' OR ', $conditions);

        $res = $db->query('SELECT * FROM `' . $tableEsc . '` WHERE ' . $where);
        if ($res && $res->num_rows > 0) {
            $sqlDump .= "-- Incremental data for table `$table`\n";
            while ($row = $res->fetch_row()) {
                $values = array_map(fn($v) => ($v === null) ? 'NULL' : "'" . $db->real_escape_string($v) . "'", $row);
                $sqlDump .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
            }
            $res->free();
            $sqlDump .= "\n";
        }
    }

    $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";

    // If nothing was added besides the FK statements, treat as no-op
    if ($sqlDump === "SET FOREIGN_KEY_CHECKS=0;\n\nSET FOREIGN_KEY_CHECKS=1;\n") {
        return false;
    }

    return (bool) file_put_contents($outputFile, $sqlDump);
}

// 3. POST HANDLERS
$backupMessage = '';
$alertType     = 'alert-info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle Deletion
    if (isset($_POST['delete_file'])) {
        $fileName = basename($_POST['delete_file']);
        $category = $_POST['category'];
        $targetDir = match($category) {
            'full_db' => $dbFullDir,
            'incremental_db' => $dbIncrementalDir,
            'full_system' => $systemDir,
            default => ''
        };
        $filePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
        if (!empty($targetDir) && file_exists($filePath)) {
            if (unlink($filePath)) {
                $backupMessage = "Backup file removed successfully.";
                $alertType = "alert-success";
                // Log delete action to activity log
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Deleted {$category} backup file '{$fileName}'";
                    activity_log_new($logAction);
                }
            }
        }
    }
    // Handle Generation
    if (isset($_POST['backup_type'])) {
        $type = $_POST['backup_type'];
        $ts = date('Ymd_His');
        if ($type === 'full_db') {
            $targetFile = $dbFullDir . "/db_full_$ts.sql";
            if (backup_database_mysqli($db_connect, DB_NAME, $targetFile)) {
                $backupMessage = "Full Database backup created.";
                $alertType = "alert-success";
                register_backup_as_upload($db_connect, $targetFile, 'System Backup: Full Database');
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Full Database backup created ({$targetFile})";
                    activity_log_new($logAction);
                }
            } else {
                $backupMessage = "Failed to create Full Database backup.";
                $alertType = "alert-danger";
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Failed to create Full Database backup";
                    activity_log_new($logAction);
                }
            }
        } elseif ($type === 'incremental_db') {
            $targetFile = $dbIncrementalDir . "/db_inc_$ts.sql";
            if (backup_database_incremental_mysqli($db_connect, DB_NAME, $targetFile, 24)) {
                $backupMessage = "Incremental backup for the last 24 hours created.";
                $alertType = "alert-success";
                register_backup_as_upload($db_connect, $targetFile, 'System Backup: Incremental Database (last 24 hours)');
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Incremental DB backup (last 24 hours) created ({$targetFile})";
                    activity_log_new($logAction);
                }
            } else {
                $backupMessage = "No new data in the last 24 hours or incremental backup failed.";
                $alertType = "alert-warning";
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Incremental DB backup attempt had no new data or failed";
                    activity_log_new($logAction);
                }
            }
        } elseif ($type === 'full_system') {
            if (!extension_loaded('zip')) {
                $backupMessage = "Failed to create system backup: PHP Zip extension is not enabled. Enable extension=zip in php.ini and restart your web server.";
                $alertType = "alert-danger";
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Failed to create System Code backup - Zip extension not enabled";
                    activity_log_new($logAction);
                }
            } elseif (!is_dir($systemDir) || !is_writable($systemDir)) {
                $backupMessage = "Failed to create system backup: backup folder is not writable (upload/backups/system_full). Check folder permissions.";
                $alertType = "alert-danger";
                if (function_exists('activity_log_new')) {
                    $logAction = "System Backup: Failed to create System Code backup - backup folder not writable";
                    activity_log_new($logAction);
                }
            } else {
                $targetFile = $systemDir . "/system_full_$ts.zip";
                if (backup_system_codebase(DOMAIN_PATH, $targetFile)) {
                    $backupMessage = "System codebase backup created.";
                    $alertType = "alert-success";
                    register_backup_as_upload($db_connect, $targetFile, 'System Backup: Full System Code');
                    if (function_exists('activity_log_new')) {
                        $logAction = "System Backup: System Code backup created ({$targetFile})";
                        activity_log_new($logAction);
                    }
                } else {
                    $backupMessage = "Failed to create system backup due to an unexpected Zip error.";
                    $alertType = "alert-danger";
                    if (function_exists('activity_log_new')) {
                        $logAction = "System Backup: Failed to create System Code backup - unexpected Zip error";
                        activity_log_new($logAction);
                    }
                }
            }
        }
    }
}

// 4. PREPARE DATA
$fullBackups = collect_backup_files($dbFullDir, BASE_URL . 'upload/backups/db_full');
$incrementalBackups = collect_backup_files($dbIncrementalDir, BASE_URL . 'upload/backups/db_incremental');
$systemBackups = collect_backup_files($systemDir, BASE_URL . 'upload/backups/system_full');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once DOMAIN_PATH . '/global/meta_data.php'; ?>
    <?php include_once DOMAIN_PATH . '/global/include_top.php'; ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
</head>
<body>
    <div class="wrapper">
        <?php include_once DOMAIN_PATH . '/global/sidebar.php'; ?>
        <div class="main-panel">
            <?php include_once DOMAIN_PATH . '/global/header.php'; ?>
            <div class="container">
                <div class="page-inner">
                    <div class="card">
                        <div class="card-header" style="background-color: #1572E8;">
                            <div class="card-title text-white"><i class="fas fa-server"></i> System Backup Center</div>
                        </div>
                        <div class="card-body">
                            <?php if ($backupMessage): ?>
                                <div class="alert <?php echo $alertType; ?> alert-dismissible fade show">
                                    <?php echo $backupMessage; ?>
                                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <button type="submit" name="backup_type" value="full_db" class="btn btn-primary w-100 py-3 shadow-sm">
                                        <i class="fas fa-database fa-lg mb-2 d-block"></i> Full Database
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="backup_type" value="incremental_db" class="btn btn-primary w-100 py-3 shadow-sm">
                                        <i class="fas fa-history fa-lg mb-2 d-block"></i> Incremental
                                    </button>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" name="backup_type" value="full_system" class="btn btn-primary w-100 py-3 shadow-sm" style="background-color: #1269DB !important;">
                                        <i class="fas fa-file-archive fa-lg mb-2 d-block"></i> System Code
                                    </button>
                                </div>
                            </form>

                            <hr>

                            <?php 
                            $tables = [
                                ['label' => 'Full Database', 'icon' => 'fa-database', 'data' => $fullBackups, 'cat' => 'full_db', 'color' => '#1572E8'],
                                ['label' => 'Incremental', 'icon' => 'fa-history', 'data' => $incrementalBackups, 'cat' => 'incremental_db', 'color' => '#48ABF7'],
                                ['label' => 'System Full', 'icon' => 'fa-archive', 'data' => $systemBackups, 'cat' => 'full_system', 'color' => '#1269DB']
                            ];

                            foreach ($tables as $t): ?>
                                <h6 class="fw-bold mt-4" style="color: <?php echo $t['color']; ?>;"><i class="fas <?php echo $t['icon']; ?> mr-2"></i> <?php echo $t['label']; ?></h6>
                                <div class="table-responsive">
                                    <table class="table table-hover mt-2 backup-table">
                                        <thead class="text-white" style="background-color: <?php echo $t['color']; ?>;">
                                            <tr>
                                                <th>Filename</th>
                                                <th>Size</th>
                                                <th>Date Created</th>
                                                <th class="text-end">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($t['data'] as $file): ?>
                                                <tr>
                                                    <td><?php echo $file['name']; ?></td>
                                                    <td><span class="badge badge-count"><?php echo $file['size']; ?></span></td>
                                                    <td><?php echo $file['created_at']; ?></td>
                                                    <td class="text-end">
                                                        <div class="form-button-action d-flex justify-content-end align-items-center">
                                                            <a href="<?php echo $file['url']; ?>" class="btn btn-link btn-primary btn-lg p-1" download data-bs-toggle="tooltip" title="Download">
                                                                <i class="fa fa-download"></i>
                                                            </a>
                                                            <form method="POST" class="m-0" onsubmit="return confirm('Delete this backup?');">
                                                                <input type="hidden" name="delete_file" value="<?php echo $file['name']; ?>">
                                                                <input type="hidden" name="category" value="<?php echo $t['cat']; ?>">
                                                                <button type="submit" class="btn btn-link btn-danger btn-lg p-1" data-bs-toggle="tooltip" title="Delete">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php include_once DOMAIN_PATH . '/global/footer.php'; ?>
        </div>
    </div>
    <?php include_once DOMAIN_PATH . '/global/include_bottom.php'; ?>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.backup-table').DataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50],
                "ordering": false,
                "language": {
                    "search": "Search File:",
                    "emptyTable": "No backup files available"
                }
            });
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();
        });
    </script>
</body>
</html>