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
    } else {
        $settingsToUpdate = [
            'store_name' => trim($_POST['store_name'] ?? 'Bandhu Chol'),
            'store_tagline' => trim($_POST['store_tagline'] ?? ''),
            'store_email' => trim($_POST['store_email'] ?? ''),
            'store_phone' => trim($_POST['store_phone'] ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
            'currency_symbol' => trim($_POST['currency_symbol'] ?? '₹'),
            'currency_code' => trim($_POST['currency_code'] ?? 'INR'),
            'tax_rate_percent' => (string)max(0, (float)($_POST['tax_rate_percent'] ?? 5.0)),
            'expiry_alert_days_critical' => (string)max(1, (int)($_POST['expiry_alert_days_critical'] ?? 30)),
            'expiry_alert_days_warning' => (string)max(1, (int)($_POST['expiry_alert_days_warning'] ?? 60)),
            'default_low_stock_threshold' => (string)max(1, (int)($_POST['default_low_stock_threshold'] ?? 10))
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
                    <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?= e($currentSettings['currency_symbol'] ?? '$') ?>" placeholder="e.g. $, ₹, €, £">
                </div>

                <div class="form-group">
                    <label class="form-label" for="currency_code">Currency Code</label>
                    <input type="text" id="currency_code" name="currency_code" class="form-control" value="<?= e($currentSettings['currency_code'] ?? 'USD') ?>" placeholder="e.g. USD, INR, EUR, GBP">
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

    <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fa-solid fa-floppy-disk"></i>
            <span>Save Settings</span>
        </button>
    </div>
</form>

<?php include INCLUDES_PATH . '/footer.php'; ?>
