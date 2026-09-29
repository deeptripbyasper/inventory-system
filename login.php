<?php
/**
 * User Login Authentication Page
 */

require_once __DIR__ . '/config/config.php';

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/modules/dashboard/index.php");
    exit;
}

$error = '';
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $error = 'Security session expired. Please refresh and try again.';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($username) || empty($password)) {
            $error = 'Please enter both username and password.';
        } elseif (!$db->isConnected()) {
            $error = 'Database connection error. Please run the installer first.';
        } else {
            $user = $db->fetchOne("SELECT * FROM `users` WHERE `username` = ? AND `status` = 'active' LIMIT 1", "s", [$username]);

            if ($user && password_verify($password, $user['password'])) {
                // If password hash needs upgrading to stronger algorithm
                if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->execute("UPDATE `users` SET `password` = ? WHERE `id` = ?", "si", [$newHash, $user['id']]);
                }

                // Successful Authentication
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                // Update last login
                $db->execute("UPDATE `users` SET `last_login` = NOW() WHERE `id` = ?", "i", [$user['id']]);

                setFlash('success', 'Welcome back, ' . $user['full_name'] . '!');
                header("Location: " . BASE_URL . "/modules/dashboard/index.php");
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= e(STORE_NAME) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- App Master Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at top left, #eef2ff, #f8fafc, #f1f5f9);
            padding: 1.5rem;
        }
        [data-theme="dark"] .login-wrapper {
            background: radial-gradient(circle at top left, #1e1b4b, #0f172a, #0b0f19);
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow-xl);
        }
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        .login-brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary), #818cf8);
            color: #fff;
            border-radius: var(--radius-lg);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            box-shadow: 0 8px 16px rgba(79, 70, 229, 0.3);
            margin-bottom: 0.75rem;
        }
        .demo-box {
            background: var(--bg-surface-alt);
            border: 1px dashed var(--border-color);
            border-radius: var(--radius-md);
            padding: 0.75rem 1rem;
            margin-top: 1.5rem;
            font-size: 0.82rem;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-brand">
                <div class="login-brand-icon">
                    <i class="fa-solid fa-boxes-stacked"></i>
                </div>
                <h2>Bondhu <span style="color: var(--primary);">Chol</span></h2>
                <p style="font-size: 0.88rem; margin-top: 0.25rem;">Real-time Inventory, Expiry & Sales System</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?= e($error) ?></span>
                </div>
            <?php endif; ?>

            <form action="<?= BASE_URL ?>/login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

                <div class="form-group">
                    <label class="form-label" for="username">Username</label>
                    <div style="position: relative;">
                        <input type="text" id="username" name="username" class="form-control" 
                               placeholder="e.g. admin" required autofocus 
                               value="<?= e($_POST['username'] ?? 'admin') ?>" style="padding-left: 2.5rem;">
                        <i class="fa-solid fa-user" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 1.75rem;">
                    <label class="form-label" for="password">Password</label>
                    <div style="position: relative;">
                        <input type="password" id="password" name="password" class="form-control" 
                               placeholder="••••••••" required value="admin123" style="padding-left: 2.5rem;">
                        <i class="fa-solid fa-lock" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 0.85rem;">
                    <i class="fa-solid fa-right-to-bracket"></i>
                    <span>Sign In to Dashboard</span>
                </button>
            </form>

            <div class="demo-box">
                <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 0.35rem;">
                    <i class="fa-solid fa-key" style="color: var(--warning);"></i> Demo Credentials:
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.2rem;">
                    <span>Admin: <code>admin</code></span>
                    <span>Password: <code>admin123</code></span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span>Cashier: <code>cashier</code></span>
                    <span>Password: <code>admin123</code></span>
                </div>
            </div>

            <div style="text-align: center; margin-top: 1.25rem;">
                <a href="<?= BASE_URL ?>/install.php" style="font-size: 0.82rem; color: var(--text-muted);">
                    <i class="fa-solid fa-database"></i> Database Installer / Reset
                </a>
            </div>
        </div>
    </div>
</body>
</html>
