<?php
/**
 * Global Helper Functions & Utilities
 */

defined('APP_INIT') or define('APP_INIT', true);

/**
 * Escape HTML output to prevent XSS
 */
function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Format currency with configured symbol
 */
function formatCurrency($amount, $decimals = 2) {
    $symbol = defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '₹';
    return $symbol . number_format((float)$amount, $decimals);
}

/**
 * Format date nicely
 */
function formatDate($dateStr, $format = 'M d, Y') {
    if (empty($dateStr) || $dateStr === '0000-00-00') return 'N/A';
    $time = strtotime($dateStr);
    return $time ? date($format, $time) : 'N/A';
}

/**
 * Format date & time nicely
 */
function formatDateTime($dateTimeStr, $format = 'M d, Y h:i A') {
    if (empty($dateTimeStr) || $dateTimeStr === '0000-00-00 00:00:00') return 'N/A';
    $time = strtotime($dateTimeStr);
    return $time ? date($format, $time) : 'N/A';
}

/**
 * Calculate Expiry Status, Days Left, and Badge Styling
 * 
 * @param string $expiryDate YYYY-MM-DD
 * @return array ['status', 'days_left', 'badge_class', 'label', 'icon']
 */
function getExpiryStatus($expiryDate) {
    if (empty($expiryDate) || $expiryDate === '0000-00-00') {
        return [
            'status' => 'unknown',
            'days_left' => null,
            'badge_class' => 'badge-secondary',
            'label' => 'No Expiry',
            'icon' => 'fa-circle-question'
        ];
    }

    $today = new DateTime('today');
    $exp = new DateTime($expiryDate);
    $diff = $today->diff($exp);
    $days = (int)$diff->format("%r%a"); // Signed difference: negative if in past

    $criticalDays = defined('EXPIRY_CRITICAL_DAYS') ? EXPIRY_CRITICAL_DAYS : 30;
    $warningDays = defined('EXPIRY_WARNING_DAYS') ? EXPIRY_WARNING_DAYS : 60;

    if ($days < 0) {
        $absDays = abs($days);
        return [
            'status' => 'expired',
            'days_left' => $days,
            'badge_class' => 'badge-danger',
            'label' => "Expired ({$absDays}d ago)",
            'icon' => 'fa-triangle-exclamation'
        ];
    } elseif ($days === 0) {
        return [
            'status' => 'critical',
            'days_left' => 0,
            'badge_class' => 'badge-danger',
            'label' => 'Expires Today!',
            'icon' => 'fa-skull-crossbones'
        ];
    } elseif ($days <= $criticalDays) {
        return [
            'status' => 'critical',
            'days_left' => $days,
            'badge_class' => 'badge-warning-critical',
            'label' => "Expires in {$days} days",
            'icon' => 'fa-clock'
        ];
    } elseif ($days <= $warningDays) {
        return [
            'status' => 'warning',
            'days_left' => $days,
            'badge_class' => 'badge-warning',
            'label' => "Expires in {$days} days",
            'icon' => 'fa-hourglass-half'
        ];
    } else {
        return [
            'status' => 'safe',
            'days_left' => $days,
            'badge_class' => 'badge-success',
            'label' => "Safe ({$days}d left)",
            'icon' => 'fa-circle-check'
        ];
    }
}

/**
 * Get Stock Status Badge
 */
function getStockStatusBadge($currentStock, $minStockAlert = 10) {
    if ($currentStock <= 0) {
        return '<span class="badge badge-danger"><i class="fa-solid fa-box-archive"></i> Out of Stock</span>';
    } elseif ($currentStock <= $minStockAlert) {
        return '<span class="badge badge-warning"><i class="fa-solid fa-triangle-exclamation"></i> Low Stock (' . $currentStock . ')</span>';
    } else {
        return '<span class="badge badge-success"><i class="fa-solid fa-check"></i> In Stock (' . $currentStock . ')</span>';
    }
}

/**
 * Authentication Helpers
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'role' => $_SESSION['role'] ?? 'cashier'
    ];
}

function requireAuth() {
    if (!isLoggedIn()) {
        $loginUrl = BASE_URL . '/login.php';
        header("Location: " . $loginUrl);
        exit;
    }
}

function hasRole($roles) {
    if (!isLoggedIn()) return false;
    $userRole = $_SESSION['role'] ?? 'cashier';
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($userRole, $roles);
}

/**
 * Flash Messages
 */
function setFlash($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message
    ];
}

function getFlash() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $msg;
    }
    return null;
}

/**
 * CSRF Protection
 */
function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

/**
 * Clean & Sanitize Input
 */
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return trim((string)$input);
}

/**
 * Convert Number to Words (Indian Numbering System Format for Invoices)
 */
function numberToWordsINR($num) {
    $num = (float)$num;
    $rupees = floor($num);
    $paise = round(($num - $rupees) * 100);

    $words = [
        0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
        6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
        11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
        16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen', 19 => 'Nineteen', 20 => 'Twenty',
        30 => 'Thirty', 40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty', 70 => 'Seventy',
        80 => 'Eighty', 90 => 'Ninety'
    ];

    $convertBelowThousand = function($n) use ($words, &$convertBelowThousand) {
        if ($n < 20) return $words[$n];
        if ($n < 100) return $words[floor($n / 10) * 10] . ($n % 10 > 0 ? ' ' . $words[$n % 10] : '');
        return $words[floor($n / 100)] . ' Hundred' . ($n % 100 > 0 ? ' and ' . $convertBelowThousand($n % 100) : '');
    };

    if ($rupees == 0) {
        $rupeesText = 'Zero Rupees';
    } else {
        $crores = floor($rupees / 10000000);
        $rupees %= 10000000;
        $lakhs = floor($rupees / 100000);
        $rupees %= 100000;
        $thousands = floor($rupees / 1000);
        $remaining = $rupees % 1000;

        $parts = [];
        if ($crores > 0) $parts[] = $convertBelowThousand($crores) . ' Crore';
        if ($lakhs > 0) $parts[] = $convertBelowThousand($lakhs) . ' Lakh';
        if ($thousands > 0) $parts[] = $convertBelowThousand($thousands) . ' Thousand';
        if ($remaining > 0) $parts[] = $convertBelowThousand($remaining);

        $rupeesText = implode(' ', $parts) . ' Rupees';
    }

    if ($paise > 0) {
        $paiseText = ' and ' . $convertBelowThousand($paise) . ' Paise';
    } else {
        $paiseText = '';
    }

    return $rupeesText . $paiseText . ' Only';
}

