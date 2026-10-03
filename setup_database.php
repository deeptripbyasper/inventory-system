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

$force = in_array('--force', $argv ?? []) || in_array('-f', $argv ?? []);

echo "1. Initializing MySQL Database..." . PHP_EOL;
mysqli_report(MYSQLI_REPORT_OFF);
$isCloudOrTiDb = ($port == 4000 || stripos($host, 'tidb') !== false || stripos($host, 'aiven') !== false || getenv('DB_SSL') === 'true');

$connected = false;
$mysqli = null;
for ($attempt = 1; $attempt <= 3; $attempt++) {
    $mysqli = mysqli_init();
    if (!$mysqli) break;
    $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
    if ($isCloudOrTiDb) {
        $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        $mysqli->ssl_set(null, null, null, null, null);
    }
    $clientFlags = $isCloudOrTiDb ? MYSQLI_CLIENT_SSL : 0;
    $connected = @$mysqli->real_connect($host, $username, $password, '', $port, null, $clientFlags);
    if (!$connected && $clientFlags !== 0) {
        $connected = @$mysqli->real_connect($host, $username, $password, '', $port);
    } elseif (!$connected && $clientFlags === 0 && $host !== 'localhost' && $host !== '127.0.0.1') {
        $connected = @$mysqli->real_connect($host, $username, $password, '', $port, null, MYSQLI_CLIENT_SSL);
    }

    if ($connected && !$mysqli->connect_error) {
        break;
    } else {
        if ($attempt < 3) usleep(500000);
    }
}

if (!$connected || !$mysqli || $mysqli->connect_error) {
    echo "MySQL connection failed: " . ($mysqli ? $mysqli->connect_error : 'Connection timeout') . PHP_EOL;
    echo "Falling back to SQLite only." . PHP_EOL;
} else {
    $dbNameEscaped = preg_replace('/[^a-zA-Z0-9_]/', '', $database);
    $mysqli->query("CREATE DATABASE IF NOT EXISTS `$dbNameEscaped` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $mysqli->select_db($dbNameEscaped);

    echo "Running Schema on MySQL..." . PHP_EOL;
    $schemaSql = file_get_contents(__DIR__ . '/database/schema.sql');
    $mysqli->multi_query($schemaSql);
    while ($mysqli->more_results() && $mysqli->next_result()) { /* flush */ }

    // Check if products exist; only seed sample data if empty or forced
    $prodCheck = @$mysqli->query("SELECT COUNT(*) as c FROM `products`");
    $prodRow = $prodCheck ? $prodCheck->fetch_assoc() : null;
    $isProdEmpty = (!$prodRow || (int)($prodRow['c'] ?? 0) === 0);

    if ($force || $isProdEmpty) {
        echo "Running Sample Data on MySQL..." . PHP_EOL;
        $sampleSql = file_get_contents(__DIR__ . '/database/sample_data.sql');
        $mysqli->multi_query($sampleSql);
        while ($mysqli->more_results() && $mysqli->next_result()) { /* flush */ }
        echo "MySQL initialized with master catalog dataset!" . PHP_EOL;
    } else {
        echo "MySQL tables exist with active data (" . ($prodRow['c'] ?? 0) . " products). Preserving live data." . PHP_EOL;
    }
    $mysqli->close();
}

// 2. Initialize SQLite Database (Only if explicitly in sqlite mode or local dev without MySQL)
$envDriver = getenv('DB_DRIVER');
$isRemoteHost = (!empty($host) && $host !== 'localhost' && $host !== '127.0.0.1');

if ($envDriver === 'sqlite' || (!$isRemoteHost && empty(getenv('DB_HOST')))) {
    echo "2. Initializing SQLite Database..." . PHP_EOL;
    $sqlitePath = getenv('DB_PATH') ?: (__DIR__ . '/database/inventory_db.sqlite');
    $sqliteExists = file_exists($sqlitePath) && filesize($sqlitePath) > 0;

    if ($force && $sqliteExists) {
        echo "Force reseed specified: resetting SQLite database." . PHP_EOL;
        @unlink($sqlitePath);
        $sqliteExists = false;
    }

    putenv('DB_DRIVER=sqlite');
    $_ENV['DB_DRIVER'] = 'sqlite';

    $refProp = new ReflectionProperty('Database', 'instance');
    $refProp->setAccessible(true);
    $refProp->setValue(null, null);

    $sqliteDb = Database::getInstance();
    if ($force || !$sqliteExists) {
        $sqliteDb->seedSqliteDatabase(true);
        echo "SQLite database successfully seeded with master catalog at: {$sqlitePath}" . PHP_EOL;
    } else {
        $sqliteDb->ensureSqliteTables(false);
        echo "SQLite database verified and active data preserved at: {$sqlitePath}" . PHP_EOL;
    }

    if ($envDriver !== false && !empty($envDriver)) {
        putenv("DB_DRIVER={$envDriver}");
        $_ENV['DB_DRIVER'] = $envDriver;
        $refProp->setValue(null, null);
    }
}

echo "=== DATABASE SETUP COMPLETED ===" . PHP_EOL;
