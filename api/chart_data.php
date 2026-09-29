<?php
/**
 * API: Dynamic Sales Chart Data Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$type = $_GET['type'] ?? 'daily_trend';
$db = Database::getInstance();

if ($type === 'daily_trend') {
    $days = 14;
    $trend = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $trend[$d] = [
            'date' => $d,
            'label' => date('M d', strtotime($d)),
            'revenue' => 0.00
        ];
    }

    $sales = $db->fetchAll("
        SELECT DATE(sale_date) as sdate, COALESCE(SUM(grand_total), 0) as rev
        FROM `sales`
        WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY) AND `status` = 'completed'
        GROUP BY DATE(sale_date)
    ");

    if ($sales) {
        foreach ($sales as $s) {
            if (isset($trend[$s['sdate']])) {
                $trend[$s['sdate']]['revenue'] = (float)$s['rev'];
            }
        }
    }

    echo json_encode(array_values($trend));
    exit;
}

echo json_encode(['status' => 'ok']);
