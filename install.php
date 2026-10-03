<?php
/**
 * OmniStock Database Installation & Setup Wizard
 * Secure Production-Ready Installer
 */

require_once __DIR__ . '/config/config.php';

$lockFile = __DIR__ . '/config/install.lock';
$configFile = __DIR__ . '/config/db_custom.php';
$schemaFile = __DIR__ . '/database/schema.sql';
$sampleFile = __DIR__ . '/database/sample_data.sql';

$isLocked = file_exists($lockFile);
$isAdmin = isLoggedIn() && hasRole('admin');

$message = '';
$messageType = '';
$isInstalled = false;

// Default connection settings (prioritize .env, then db_custom.php, then TiDB defaults)
$host = getenv('DB_HOST') ?: 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com';
$username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'K7kGYdYzup79K6J.root');
$password = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: 'vHsQYZ4P19RRw1x7');
$database = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'test');
$port = (int)(getenv('DB_PORT') ?: 4000);

if (file_exists($configFile)) {
    $existing = include($configFile);
    if (is_array($existing)) {
        if (!getenv('DB_HOST')) $host = $existing['host'] ?? $host;
        if (!getenv('DB_USERNAME') && !getenv('DB_USER')) $username = $existing['username'] ?? $username;
        if (!getenv('DB_PASSWORD') && !getenv('DB_PASS')) $password = $existing['password'] ?? $password;
        if (!getenv('DB_DATABASE') && !getenv('DB_NAME')) $database = $existing['database'] ?? $database;
        if (!getenv('DB_PORT')) $port = (int)($existing['port'] ?? $port);
    }
}

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string)($str ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// Process Installation POST Request
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if ($isLocked && !$isAdmin) {
        $message = "Installation is locked for security. Delete 'config/install.lock' on the server to re-run.";
        $messageType = 'danger';
    } else {
        $host = trim($_POST['host'] ?? 'gateway01.ap-northeast-1.prod.aws.tidbcloud.com');
        $username = trim($_POST['username'] ?? 'K7kGYdYzup79K6J.root');
        $password = $_POST['password'] ?? '';
        $database = trim($_POST['database'] ?? 'test');
        $port = (int)($_POST['port'] ?? 4000);
        $installSample = isset($_POST['install_sample']);

        mysqli_report(MYSQLI_REPORT_OFF);
        $mysqli = mysqli_init();
        $isCloudOrTiDb = ($port == 4000 || stripos($host, 'tidb') !== false || stripos($host, 'aiven') !== false || getenv('DB_SSL') === 'true');
        if ($isCloudOrTiDb) {
            $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            $mysqli->ssl_set(null, null, null, null, null);
        }
        $clientFlags = $isCloudOrTiDb ? MYSQLI_CLIENT_SSL : 0;
        $connected = @$mysqli->real_connect($host, $username, $password, '', $port, null, $clientFlags);
        if (!$connected && $clientFlags !== 0) {
            $connected = @$mysqli->real_connect($host, $username, $password, '', $port);
        }

        if (!$connected || $mysqli->connect_error) {
            $message = "Database Connection Failed: " . ($mysqli ? $mysqli->connect_error : 'Connection timeout');
            $messageType = 'danger';
        } else {
            // Create database if not exists
            $dbNameEscaped = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
            $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbNameEscaped` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $mysqli->select_db($dbNameEscaped);

            // Read and execute Schema
            if (!file_exists($schemaFile)) {
                $message = "Schema file not found at " . $schemaFile;
                $messageType = 'danger';
            } else {
                $schemaSql = file_get_contents($schemaFile);
                $mysqli->multi_query($schemaSql);
                while ($mysqli->more_results() && $mysqli->next_result()) { /* Flush multi-query */ }

                if ($installSample && file_exists($sampleFile)) {
                    $sampleSql = file_get_contents($sampleFile);
                    $mysqli->multi_query($sampleSql);
                    while ($mysqli->more_results() && $mysqli->next_result()) { /* Flush */ }
                }

                // Write db_custom.php configuration file
                $configContent = "<?php\nreturn " . var_export([
                    'host' => $host,
                    'username' => $username,
                    'password' => $password,
                    'database' => $database,
                    'port' => $port
                ], true) . ";\n";

                file_put_contents($configFile, $configContent);

                // Create install.lock file for security
                $lockContent = "INSTALLED=" . date('Y-m-d H:i:s') . "\nHOST=" . $host . "\nDATABASE=" . $database . "\n";
                file_put_contents($lockFile, $lockContent);

                $message = "Database & Tables installed successfully!" . ($installSample ? " Realistic products, batches, and records loaded." : "");
                $messageType = 'success';
                $isInstalled = true;
                $isLocked = true;
            }
            $mysqli->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup Wizard - <?= e(STORE_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .install-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #eef2ff, #f8fafc, #f1f5f9);
            padding: 2rem 1rem;
        }
        .install-card {
            width: 100%;
            max-width: 540px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: var(--shadow-xl);
        }
    </style>
</head>
<body>
    <div class="install-wrapper">
        <div class="install-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="width: 60px; height: 60px; background: linear-gradient(135deg, #4f46e5, #06b6d4); color: #fff; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 1.8rem; margin-bottom: 0.85rem; box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);">
                    <i class="fa-solid fa-database"></i>
                </div>
                <h2>Omni<span style="color: var(--primary);">Stock</span> Database Setup</h2>
                <p style="font-size: 0.9rem; color: var(--text-secondary); margin-top: 0.25rem;">Live Production Database Installation & Migration</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert alert-<?= e($messageType) ?>" style="margin-bottom: 1.5rem;">
                    <i class="fa-solid <?= $messageType === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
                    <span><?= e($message) ?></span>
                </div>
            <?php endif; ?>

            <?php if ($isInstalled): ?>
                <div style="text-align: center; margin: 2rem 0;">
                    <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; text-align: left;">
                        <h4 style="color: #065f46; margin-bottom: 0.5rem;"><i class="fa-solid fa-shield-check"></i> Security Lock Enabled</h4>
                        <p style="font-size: 0.85rem; color: #047857; margin: 0;">
                            <code>config/install.lock</code> was generated to protect your live database from unauthorized reconfiguration.
                        </p>
                    </div>
                    <a href="login.php" class="btn btn-primary btn-lg" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-right-to-bracket"></i>
                        <span>Proceed to Login</span>
                    </a>
                </div>
            <?php elseif ($isLocked && !$isAdmin): ?>
                <div style="text-align: center; padding: 1rem 0;">
                    <div style="background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: var(--radius-md); padding: 1.5rem; margin-bottom: 1.75rem; text-align: left;">
                        <h4 style="color: #991b1b; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                            <i class="fa-solid fa-lock"></i> Installer Locked for Security
                        </h4>
                        <p style="font-size: 0.85rem; color: #7f1d1d; line-height: 1.5; margin-bottom: 0.75rem;">
                            This application has already been installed and locked. To prevent accidental data loss or security issues, the web setup wizard is disabled.
                        </p>
                        <p style="font-size: 0.8rem; color: #7f1d1d; margin: 0;">
                            <strong>To re-run the installer:</strong> Delete <code>config/install.lock</code> from your server filesystem or login with an administrator account.
                        </p>
                    </div>
                    <a href="login.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                        <i class="fa-solid fa-arrow-right"></i>
                        <span>Go to Login Page</span>
                    </a>
                </div>
            <?php else: ?>
                <?php if ($isLocked && $isAdmin): ?>
                    <div class="alert alert-warning" style="margin-bottom: 1.25rem;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span><strong>Admin Mode:</strong> You are authenticated as administrator. Re-running the installer will overwrite existing database tables.</span>
                    </div>
                <?php endif; ?>

                <form action="install.php" method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="host">MySQL Host</label>
                            <input type="text" id="host" name="host" class="form-control" value="<?= e($host) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="port">Port</label>
                            <input type="number" id="port" name="port" class="form-control" value="<?= e($port) ?>" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="username">DB Username</label>
                            <input type="text" id="username" name="username" class="form-control" value="<?= e($username) ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="password">DB Password</label>
                            <input type="password" id="password" name="password" class="form-control" value="<?= e($password) ?>" placeholder="Leave blank if none">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="database">Database Name</label>
                        <input type="text" id="database" name="database" class="form-control" value="<?= e($database) ?>" required>
                        <small style="color: var(--text-muted); font-size: 0.78rem;">Will be created automatically if it doesn't already exist.</small>
                    </div>

                    <div style="background: var(--bg-surface-alt); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin: 1.25rem 0;">
                        <label style="display: flex; align-items: center; gap: 0.65rem; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: var(--text-primary);">
                            <input type="checkbox" name="install_sample" value="1" checked style="width: 18px; height: 18px; accent-color: var(--primary);">
                            <span>Install Sample Catalog & Initial Data (Recommended)</span>
                        </label>
                        <p style="font-size: 0.78rem; color: var(--text-secondary); margin-left: 1.8rem; margin-top: 0.25rem;">
                            Seeds realistic products, expiring batches, categories, suppliers, and past sales.
                        </p>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem;">
                        <i class="fa-solid fa-play"></i>
                        <span>Run Installation & Initialize</span>
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

