<?php
/**
 * System Health Check & Status Endpoint
 * Suitable for Cloud PaaS (Render, Railway, Fly.io, AWS, GCP, Azure),
 * Docker healthcheck, and Uptime monitors.
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once __DIR__ . '/../config/config.php';

$db = Database::getInstance();
$dbConnected = $db->isConnected();

$response = [
    'status' => $dbConnected ? 'healthy' : 'degraded',
    'timestamp' => date('c'),
    'php_version' => PHP_VERSION,
    'app_name' => defined('STORE_NAME') ? STORE_NAME : 'OmniStock',
    'environment' => getenv('APP_ENV') ?: 'production',
    'database' => [
        'connected' => $dbConnected,
        'driver' => $dbConnected ? $db->getDriver() : 'none',
        'has_tables' => $dbConnected ? $db->tableExists('products') : false
    ]
];

if (!$dbConnected) {
    http_response_code(503);
    $response['error'] = 'Database connection could not be established';
} else {
    http_response_code(200);
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
