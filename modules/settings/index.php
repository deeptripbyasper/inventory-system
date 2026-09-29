<?php
/**
 * System Settings Configuration
 */

$pageTitle = 'System Settings';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        $errors[] = 'Security token invalid.';
    } elseif (isset($_POST['action']) && $_POST['action'] === 'sync_catalog') {
        if (!hasRole('admin')) {
            $errors[] = 'Only administrators can reset and synchronize the master catalog.';
        } else {
            if ($db->getDriver() === 'mysqli') {
                $db->verifyMysqlCatalog(true);
            } else {
                $db->seedSqliteDatabase(true);
            }
            setFlash('success', 'Master Catalog successfully reset and synchronized with all 83 products and 85 batches in INR (₹)!');
            header("Location: " . BASE_URL . "/modules/settings/index.php");
            exit;
        }
    } else {
        $currencySymbol = trim($_POST['currency_symbol'] ?? '₹');
        if (empty($currencySymbol) || preg_match('/[0-9]/', $currencySymbol) || $currencySymbol === '$' || mb_strlen($currencySymbol) > 4) {
            $currencySymbol = '₹';
        }
        $currencyCode = trim($_POST['currency_code'] ?? 'INR');
        if (empty($currencyCode) || preg_match('/[0-9]/', $currencyCode) || $currencyCode === 'USD' || mb_strlen($currencyCode) > 5) {
            $currencyCode = 'INR';
        }

        $settingsToUpdate = [
            'store_name' => trim($_POST['store_name'] ?? 'Bondhu Chol'),
            'store_tagline' => trim($_POST['store_tagline'] ?? ''),
            'store_email' => trim($_POST['store_email'] ?? ''),
            'store_phone' => trim($_POST['store_phone'] ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
            'currency_symbol' => $currencySymbol,
            'currency_code' => $currencyCode,
            'tax_rate_percent' => (string)max(0, (float)($_POST['tax_rate_percent'] ?? 5.0)),
            'expiry_alert_days_critical' => (string)max(1, (int)($_POST['expiry_alert_days_critical'] ?? 3)),
            'expiry_alert_days_warning' => (string)max(1, (int)($_POST['expiry_alert_days_warning'] ?? 7)),
            'default_low_stock_threshold' => (string)max(1, (int)($_POST['default_low_stock_threshold'] ?? 15))
        ];

        foreach ($settingsToUpdate as $k => $v) {
            $db->execute("
                INSERT INTO `settings` (`key_name`, `value_text`) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE `value_text` = ?
            ", "sss", [$k, $v, $v]);
        }

        setFlash('success', 'System settings saved and updated successfully.');
        header("Location: " . BASE_URL . "/modules/settings/index.php");
        exit;
    }
}

// Reload current settings
$currentSettings = $systemSettings;
$dbSettings = $db->fetchAll("SELECT * FROM `settings`");
if ($dbSettings) {
    foreach ($dbSettings as $s) {
        $currentSettings[$s['key_name']] = $s['value_text'];
    }
}
if (empty($currentSettings['currency_symbol']) || preg_match('/[0-9]/', $currentSettings['currency_symbol']) || $currentSettings['currency_symbol'] === '$') {
    $currentSettings['currency_symbol'] = '₹';
}
if (empty($currentSettings['currency_code']) || preg_match('/[0-9]/', $currentSettings['currency_code']) || $currentSettings['currency_code'] === 'USD') {
    $currentSettings['currency_code'] = 'INR';
}

?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-gear" style="color: var(--primary);"></i>
            <span>System Settings</span>
        </h1>
        <p>Configure store branding, currency symbols, tax rates, and expiry alert thresholds</p>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div>
            <?php foreach ($errors as $err): ?>
                <div><?= e($err) ?></div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<form action="<?= BASE_URL ?>/modules/settings/index.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-store" style="color: var(--primary);"></i>
                <span>Store Identity & Receipts</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="store_name">Store / Company Name <span style="color: var(--danger);">*</span></label>
                    <input type="text" id="store_name" name="store_name" class="form-control" required value="<?= e($currentSettings['store_name'] ?? 'OmniStock') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="store_tagline">Tagline / Slogan</label>
                    <input type="text" id="store_tagline" name="store_tagline" class="form-control" value="<?= e($currentSettings['store_tagline'] ?? '') ?>">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="store_email">Contact Email</label>
                    <input type="email" id="store_email" name="store_email" class="form-control" value="<?= e($currentSettings['store_email'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="store_phone">Store Phone</label>
                    <input type="text" id="store_phone" name="store_phone" class="form-control" value="<?= e($currentSettings['store_phone'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="store_address">Physical Store Address</label>
                <textarea id="store_address" name="store_address" class="form-control" rows="2"><?= e($currentSettings['store_address'] ?? '') ?></textarea>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-coins" style="color: var(--warning);"></i>
                <span>Currency & Taxation</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="currency_symbol">Currency Symbol</label>
                    <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?= e($currentSettings['currency_symbol'] ?? '₹') ?>" placeholder="e.g. ₹, INR, Rs.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="currency_code">Currency Code</label>
                    <input type="text" id="currency_code" name="currency_code" class="form-control" value="<?= e($currentSettings['currency_code'] ?? 'INR') ?>" placeholder="e.g. INR">
                </div>

                <div class="form-group">
                    <label class="form-label" for="tax_rate_percent">Sales Tax Rate (%)</label>
                    <input type="number" step="0.01" min="0" id="tax_rate_percent" name="tax_rate_percent" class="form-control" value="<?= e($currentSettings['tax_rate_percent'] ?? '5.00') ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-bell" style="color: var(--danger);"></i>
                <span>Inventory & Expiry Threshold Rules</span>
            </div>
        </div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="expiry_alert_days_critical">Critical Expiry Alert (Days Before Expiry)</label>
                    <input type="number" min="1" id="expiry_alert_days_critical" name="expiry_alert_days_critical" class="form-control" value="<?= e($currentSettings['expiry_alert_days_critical'] ?? '30') ?>">
                    <small style="color: var(--text-muted);">Triggers red/critical expiration warnings (Default: 30 days).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="expiry_alert_days_warning">Early Warning Expiry Alert (Days)</label>
                    <input type="number" min="1" id="expiry_alert_days_warning" name="expiry_alert_days_warning" class="form-control" value="<?= e($currentSettings['expiry_alert_days_warning'] ?? '60') ?>">
                    <small style="color: var(--text-muted);">Triggers yellow early advisory warnings (Default: 60 days).</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="default_low_stock_threshold">Default Low Stock Alert Threshold</label>
                    <input type="number" min="1" id="default_low_stock_threshold" name="default_low_stock_threshold" class="form-control" value="<?= e($currentSettings['default_low_stock_threshold'] ?? '10') ?>">
                    <small style="color: var(--text-muted);">Threshold for low stock warnings when creating products.</small>
                </div>
            </div>
        </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-bottom: 1.5rem;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Save Settings</span>
        </button>
    </div>
</form>

<?php if (hasRole('admin')): ?>
<div class="card" style="border: 1px solid var(--border-color); margin-top: 1.5rem;">
    <div class="card-header" style="background: rgba(239, 68, 68, 0.04);">
        <div class="card-title">
            <i class="fa-solid fa-arrows-rotate" style="color: var(--primary);"></i>
            <span>Master Catalog Resync & Reset</span>
        </div>
    </div>
    <div class="card-body">
        <p style="color: var(--text-secondary); margin-bottom: 1rem; font-size: 0.92rem;">
            If you need to refresh your inventory database with the fresh official catalog (<strong>83 Products</strong> across Fresh Milk, Amul Products & Ice Creams, Cold Drinks, and Cadbury Chocolates with standard unit prices in INR), click below.
        </p>
        <form action="<?= BASE_URL ?>/modules/settings/index.php" method="POST" onsubmit="return confirm('Are you sure you want to reset and re-synchronize all 83 master catalog products, categories, and batches? Current demo transaction history will be refreshed.');">
            <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
            <input type="hidden" name="action" value="sync_catalog">
            <button type="submit" class="btn btn-outline-danger">
                <i class="fa-solid fa-arrows-rotate"></i>
                <span>Reset & Reseed Fresh Master Catalog (INR ₹)</span>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<?php include INCLUDES_PATH . '/footer.php'; ?>
