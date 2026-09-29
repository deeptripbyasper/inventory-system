<?php
/**
 * Top Navbar Component
 */
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../config/config.php';
}
?>
<header class="app-navbar">
    <div class="navbar-left">
        <button type="button" class="menu-toggle-btn" id="mobileMenuBtn" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="navbar-title" style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="font-weight: 500; color: var(--text-muted);">Store:</span>
            <span style="color: var(--text-primary); font-weight: 700;"><?= e(STORE_NAME) ?></span>
            <span class="badge badge-primary" style="font-size: 0.72rem; padding: 0.18rem 0.45rem; border-radius: 4px;" title="Active Live Build Version">v2.5</span>
        </div>
    </div>

    <div class="navbar-right">
        <!-- Quick Action Buttons -->
        <a href="<?= BASE_URL ?>/modules/pos/index.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-cash-register"></i>
            <span>New Sale (POS)</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/products/add.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-plus"></i>
            <span>Add Product</span>
        </a>

        <!-- Dark / Light Theme Toggle -->
        <button type="button" class="theme-toggle-btn" id="themeToggleBtn" title="Toggle Dark/Light Mode" aria-label="Toggle Theme">
            <i class="fa-solid fa-moon"></i>
        </button>
    </div>
</header>
