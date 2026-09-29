<?php
/**
 * Root Application Router / Redirector
 */

require_once __DIR__ . '/config/config.php';

$db = Database::getInstance();

// If database is not connected or tables not created, redirect to installer
if (!$db->isConnected()) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

// Check if products table exists
if (!$db->tableExists('products')) {
    header("Location: " . BASE_URL . "/install.php");
    exit;
}

if (isLoggedIn()) {
    header("Location: " . BASE_URL . "/modules/dashboard/index.php");
    exit;
} else {
    header("Location: " . BASE_URL . "/login.php");
    exit;
}
