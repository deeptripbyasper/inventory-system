<?php
/**
 * Database Initializer & Syncer for both MySQL and SQLite
 * Security: CLI execution only or authenticated admin
 */
require_once __DIR__ . '/config/config.php';

if (php_sapi_name() !== 'cli') {
    if (!isLoggedIn() || !hasRole('admin')) {
        http_response_code(403);
        die('Access Denied: This script can only be executed via CLI or by an authenticated Administrator.');
    }
}

$host = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USERNAME') ?: (getenv('DB_USER') ?: 'root');
$password = getenv('DB_PASSWORD') ?: (getenv('DB_PASS') ?: '');
$database = getenv('DB_DATABASE') ?: (getenv('DB_NAME') ?: 'inventory_db');
$port = (int)(getenv('DB_PORT') ?: 3306);

$configFile = __DIR__ . '/config/db_custom.php';
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

echo "1. Initializing MySQL Database..." . PHP_EOL;
mysqli_report(MYSQLI_REPORT_OFF);
$mysqli = @new mysqli($host, $username, $password, '', $port);

if ($mysqli->connect_error) {
    echo "MySQL connection failed: " . $mysqli->connect_error . PHP_EOL;
    echo "Falling back to SQLite only." . PHP_EOL;
} else {
    $dbNameEscaped = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbNameEscaped` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($dbNameEscaped);

    echo "Running Schema on MySQL..." . PHP_EOL;
    $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
    $mysqli->multi_query($schemaSql);
    while ($mysqli->more_results() && $mysqli->next_result()) { /* flush */ }

    echo "Running Sample Data on MySQL..." . PHP_EOL;
    $sampleSql = file_get_contents(__DIR__ . '/database/sample_data.sql');
    $mysqli->multi_query($sampleSql);
    while ($mysqli->more_results() && $mysqli->next_result()) { /* flush */ }

    echo "MySQL initialized successfully with Amul, Cold Drinks, and Cadbury dataset!" . PHP_EOL;
    $mysqli->close();
}

// Re-initialize SQLite too
echo "2. Initializing SQLite Database..." . PHP_EOL;
$sqlitePath = __DIR__ . '/database/inventory_db.sqlite';
if (file_exists($sqlitePath)) {
    @unlink($sqlitePath);
}

// Instantiate fresh Database object in SQLite mode and seed
$prevDriver = getenv('DB_DRIVER');
putenv('DB_DRIVER=sqlite');
$_ENV['DB_DRIVER'] = 'sqlite';

// Reset Singleton instance so it connects cleanly to SQLite
$refProp = new ReflectionProperty('Database', 'instance');
$refProp->setAccessible(true);
$refProp->setValue(null, null);

$sqliteDb = Database::getInstance();
$sqliteDb->seedSqliteDatabase();
echo "SQLite database successfully seeded with all 83 products and 85 batches at: {$sqlitePath}" . PHP_EOL;

if ($prevDriver !== false && !empty($prevDriver)) {
    putenv("DB_DRIVER={$prevDriver}");
    $_ENV['DB_DRIVER'] = $prevDriver;
    $refProp->setValue(null, null);
}

echo "=== DATABASE SETUP COMPLETED ===" . PHP_EOL;
