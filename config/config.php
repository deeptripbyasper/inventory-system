<?php
/**
 * Application Global Configuration
 * Production-Ready Configuration with Environment & Security Hardening
 */

defined('APP_INIT') or define('APP_INIT', true);

// Base Paths
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
defined('ROOT_PATH') or define('ROOT_PATH', BASE_PATH);
defined('INCLUDES_PATH') or define('INCLUDES_PATH', BASE_PATH . '/includes');
defined('MODULES_PATH') or define('MODULES_PATH', BASE_PATH . '/modules');
defined('CONFIG_PATH') or define('CONFIG_PATH', BASE_PATH . '/config');

/**
 * Lightweight Zero-Dependency .env Loader
 */
if (!function_exists('loadEnvFile')) {
    function loadEnvFile($filePath) {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return;
        }
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                // Strip surrounding quotes
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }
                if (getenv($name) === false) {
                    putenv("{$name}={$value}");
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}

// Load .env file from project root if it exists
loadEnvFile(BASE_PATH . '/.env');

// Environment & Debugging Mode
$appEnv = getenv('APP_ENV') ?: 'production';
$appDebug = getenv('APP_DEBUG');
$isDebug = ($appDebug === 'true' || $appDebug === '1' || $appEnv === 'development' || $appEnv === 'local');

if ($isDebug) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
}

// Global Security Response Headers & Dynamic Cache Prevention
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
}

// Secure Session Cookie Settings
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on');

    $sessionCookieParams = [
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ];

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params($sessionCookieParams);
    } else {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.use_only_cookies', '1');
    }

    session_start();
}

// Timezone
$appTimezone = getenv('APP_TIMEZONE') ?: 'UTC';
if (@date_default_timezone_set($appTimezone) === false) {
    date_default_timezone_set('UTC');
}

// Calculate Base URL dynamically for portable hosting (Root domain, subfolder, reverse proxy)
$envAppUrl = getenv('APP_URL') ?: getenv('BASE_URL');
if (!empty($envAppUrl)) {
    // If explicit base URL or domain provided in .env
    $parsedUrl = parse_url($envAppUrl);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
    defined('BASE_URL') or define('BASE_URL', $baseUrl);
} else {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $basePath = preg_replace('/(\/modules(\/.*)?|\/api(\/.*)?)$/', '', $scriptDir);
    $baseUrl = rtrim($basePath, '/');
    defined('BASE_URL') or define('BASE_URL', $baseUrl);
}

// Include Database
require_once CONFIG_PATH . '/database.php';

// System Settings Cache
$systemSettings = [
    'store_name' => 'Bondhu Chol',
    'store_tagline' => 'Quality Dairy, Ice Creams, Cold Beverages & Chocolates',
    'currency_symbol' => '₹',
    'currency_code' => 'INR',
    'tax_rate_percent' => 5.0,
    'expiry_alert_days_critical' => 3,
    'expiry_alert_days_warning' => 7,
    'default_low_stock_threshold' => 15,
    'store_address' => '39, Satyen Roy Road, Behala, Kolkata - 700034.',
    'store_phone' => '+91 98765 00000 / +91 98300 00000',
    'store_email' => 'contact@bondhuchol.com',
    'store_gstin' => '19AAAAA0000A1Z5',
    'store_fssai' => '10019021004321',
    'store_upi_id' => 'bondhuchol@upi'
];

$db = Database::getInstance();
if ($db->isConnected()) {
    $settingsRows = $db->fetchAll("SELECT `key_name`, `value_text` FROM `settings`");
    if ($settingsRows) {
        foreach ($settingsRows as $row) {
            $systemSettings[$row['key_name']] = $row['value_text'];
        }
    }
}

// Guarantee INR / ₹ currency is always strictly enforced and auto-repaired
$rawSymbol = (string)($systemSettings['currency_symbol'] ?? '');
if (empty($rawSymbol) || preg_match('/[0-9]/', $rawSymbol) || $rawSymbol === '$' || mb_strlen($rawSymbol) > 4) {
    $systemSettings['currency_symbol'] = '₹';
    if ($db->isConnected()) {
        try {
            $db->execute("UPDATE `settings` SET `value_text` = '₹' WHERE `key_name` = 'currency_symbol'");
        } catch (Exception $e) { /* silent */ }
    }
}

$rawCode = (string)($systemSettings['currency_code'] ?? '');
if (empty($rawCode) || preg_match('/[0-9]/', $rawCode) || $rawCode === 'USD' || mb_strlen($rawCode) > 5) {
    $systemSettings['currency_code'] = 'INR';
    if ($db->isConnected()) {
        try {
            $db->execute("UPDATE `settings` SET `value_text` = 'INR' WHERE `key_name` = 'currency_code'");
        } catch (Exception $e) { /* silent */ }
    }
}

// Global Constants from Settings (Guarded against redefinition)
defined('STORE_NAME') or define('STORE_NAME', $systemSettings['store_name']);
defined('CURRENCY_SYMBOL') or define('CURRENCY_SYMBOL', $systemSettings['currency_symbol']);
defined('CURRENCY_CODE') or define('CURRENCY_CODE', $systemSettings['currency_code']);
defined('TAX_RATE') or define('TAX_RATE', (float)$systemSettings['tax_rate_percent']);
defined('EXPIRY_CRITICAL_DAYS') or define('EXPIRY_CRITICAL_DAYS', (int)$systemSettings['expiry_alert_days_critical']);
defined('EXPIRY_WARNING_DAYS') or define('EXPIRY_WARNING_DAYS', (int)$systemSettings['expiry_alert_days_warning']);

// Include Global Helper Functions
require_once INCLUDES_PATH . '/functions.php';


