<?php
/**
 * Global Header Component
 */
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/config.php';
}
requireAuth();
$pageTitle = $pageTitle ?? 'Dashboard';
$currentUser = currentUser();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> - <?= e(STORE_NAME) ?></title>
    
    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Free Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Chart.js for High-Definition Analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- App Master Stylesheet -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= file_exists(ROOT_PATH . '/assets/css/style.css') ? filemtime(ROOT_PATH . '/assets/css/style.css') : time() ?>">
    
    <script>
        window.APP_CURRENCY = '<?= e(preg_match('/[0-9]/', CURRENCY_SYMBOL) || CURRENCY_SYMBOL === '$' ? '₹' : CURRENCY_SYMBOL) ?>';
        window.APP_TAX_RATE = <?= (float)TAX_RATE ?>;
        window.BASE_URL = '<?= e(BASE_URL) ?>';
    </script>
</head>
<body>
    <div class="app-container">
        <?php include INCLUDES_PATH . '/sidebar.php'; ?>
        
        <div class="app-main">
            <?php include INCLUDES_PATH . '/navbar.php'; ?>
            <div class="page-content">
                <?php 
                $flash = getFlash();
                if ($flash): 
                ?>
                    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible">
                        <i class="fa-solid fa-circle-info"></i>
                        <span><?= e($flash['message']) ?></span>
                    </div>
                <?php endif; ?>
