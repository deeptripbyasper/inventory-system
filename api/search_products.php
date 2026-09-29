<?php
/**
 * API: Instant Product Search Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$query = trim($_GET['q'] ?? '');
$db = Database::getInstance();

if (empty($query)) {
    echo json_encode([]);
    exit;
}

$searchTerm = "%{$query}%";
$sql = "
    SELECT 
        p.id,
        p.name,
        p.sku,
        p.barcode,
        p.unit,
        p.default_selling_price,
        c.name as category_name,
        COALESCE(SUM(b.current_quantity), 0) as total_leftover_stock,
        MIN(CASE WHEN b.current_quantity > 0 THEN b.expiry_date ELSE NULL END) as nearest_expiry
    FROM `products` p
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `product_batches` b ON p.id = b.product_id
    WHERE p.status = 'active' AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)
    GROUP BY p.id, p.name, p.sku, p.barcode, p.unit, p.default_selling_price, c.name
    LIMIT 20
";

$results = $db->fetchAll($sql, "sss", [$searchTerm, $searchTerm, $searchTerm]);
echo json_encode($results ?: []);
