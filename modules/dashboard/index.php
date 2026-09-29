<?php
/**
 * Dashboard & Executive Overview
 */

$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// 1. Total Active Products Count
$productsCount = 0;
$res = $db->fetchOne("SELECT COUNT(*) as cnt FROM `products` WHERE `status` = 'active'");
$productsCount = $res['cnt'] ?? 0;

// 2. Leftover Stock & Inventory Valuation
$totalLeftoverStock = 0;
$totalCostValuation = 0.00;
$totalRetailValuation = 0.00;
$res = $db->fetchOne("
    SELECT 
        COALESCE(SUM(current_quantity), 0) as total_units,
        COALESCE(SUM(current_quantity * purchase_price), 0) as total_cost,
        COALESCE(SUM(current_quantity * selling_price), 0) as total_retail
    FROM `product_batches`
    WHERE `current_quantity` > 0
");
if ($res) {
    $totalLeftoverStock = (int)$res['total_units'];
    $totalCostValuation = (float)$res['total_cost'];
    $totalRetailValuation = (float)$res['total_retail'];
}

// 3. Today's Sales & Profit
$todaySalesRevenue = 0.00;
$todaySalesCount = 0;
$todayGrossProfit = 0.00;
$res = $db->fetchOne("
    SELECT 
        COALESCE(SUM(grand_total), 0) as revenue,
        COUNT(id) as total_orders
    FROM `sales`
    WHERE DATE(sale_date) = CURDATE() AND `status` = 'completed'
");
if ($res) {
    $todaySalesRevenue = (float)$res['revenue'];
    $todaySalesCount = (int)$res['total_orders'];
}

// Today's Profit Calculation
$res = $db->fetchOne("
    SELECT 
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as gross_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) = CURDATE() AND s.status = 'completed'
");
if ($res) {
    $todayGrossProfit = (float)$res['gross_profit'];
}

// 4. This Month's Sales Revenue
$monthSalesRevenue = 0.00;
$monthSalesCount = 0;
$monthGrossProfit = 0.00;
$res = $db->fetchOne("
    SELECT 
        COALESCE(SUM(grand_total), 0) as revenue,
        COUNT(id) as total_orders
    FROM `sales`
    WHERE YEAR(sale_date) = YEAR(CURDATE()) AND MONTH(sale_date) = MONTH(CURDATE()) AND `status` = 'completed'
");
if ($res) {
    $monthSalesRevenue = (float)$res['revenue'];
    $monthSalesCount = (int)$res['total_orders'];
}

$res = $db->fetchOne("
    SELECT 
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as gross_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    WHERE YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE()) AND s.status = 'completed'
");
if ($res) {
    $monthGrossProfit = (float)$res['gross_profit'];
}

// 5. Expiry Counts (Expired + Expiring within 30 days)
$critDays = defined('EXPIRY_CRITICAL_DAYS') ? EXPIRY_CRITICAL_DAYS : 30;
$expiredItemsCount = 0;
$expiringSoonCount = 0;

$res = $db->fetchOne("SELECT COUNT(*) as cnt FROM `product_batches` WHERE `current_quantity` > 0 AND `expiry_date` < CURDATE()");
$expiredItemsCount = $res['cnt'] ?? 0;

$res = $db->fetchOne("SELECT COUNT(*) as cnt FROM `product_batches` WHERE `current_quantity` > 0 AND `expiry_date` BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)", "i", [$critDays]);
$expiringSoonCount = $res['cnt'] ?? 0;

// 6. Urgent Expiration Watch (Top 5 batches expiring soonest)
$urgentBatches = $db->fetchAll("
    SELECT 
        b.id as batch_id,
        b.batch_no,
        b.expiry_date,
        b.current_quantity,
        p.id as product_id,
        p.name as product_name,
        p.unit,
        c.name as category_name
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    WHERE b.current_quantity > 0 AND b.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 60 DAY)
    ORDER BY b.expiry_date ASC
    LIMIT 6
");

// 7. Recent 5 Sales Transactions
$recentSales = $db->fetchAll("
    SELECT 
        s.id,
        s.invoice_no,
        s.customer_name,
        s.sale_date,
        s.grand_total,
        s.payment_method,
        s.status
    FROM `sales` s
    ORDER BY s.sale_date DESC
    LIMIT 5
");

// 8. Past 7 Days Sales Trend for Chart
$past7Days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $past7Days[$d] = [
        'label' => date('D, M d', strtotime($d)),
        'revenue' => 0.00,
        'profit' => 0.00
    ];
}

$chartSales = $db->fetchAll("
    SELECT 
        DATE(sale_date) as sdate,
        COALESCE(SUM(grand_total), 0) as total_rev
    FROM `sales`
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) AND `status` = 'completed'
    GROUP BY DATE(sale_date)
");

if ($chartSales) {
    foreach ($chartSales as $cs) {
        if (isset($past7Days[$cs['sdate']])) {
            $past7Days[$cs['sdate']]['revenue'] = (float)$cs['total_rev'];
        }
    }
}

// 9. Top 5 Best Selling Products This Month
$topProducts = $db->fetchAll("
    SELECT 
        p.name as product_name,
        COALESCE(SUM(si.quantity), 0) as total_qty_sold,
        COALESCE(SUM(si.subtotal), 0) as total_revenue
    FROM `sale_items` si
    JOIN `products` p ON si.product_id = p.id
    JOIN `sales` s ON si.sale_id = s.id
    WHERE YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE()) AND s.status = 'completed'
    GROUP BY p.id, p.name
    ORDER BY total_qty_sold DESC
    LIMIT 5
");
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-gauge-high" style="color: var(--primary);"></i>
            <span>Executive Dashboard</span>
        </h1>
        <p>Live inventory valuations, daily sales intelligence & expiry alerts</p>
    </div>
    <div class="page-header-actions">
        <a href="<?= BASE_URL ?>/modules/pos/index.php" class="btn btn-primary">
            <i class="fa-solid fa-cart-plus"></i>
            <span>Start POS Sale</span>
        </a>
        <a href="<?= BASE_URL ?>/modules/batches/restock.php" class="btn btn-secondary">
            <i class="fa-solid fa-truck-ramp-box"></i>
            <span>Restock Inventory</span>
        </a>
    </div>
</div>

<!-- KPI Summary Stat Cards Grid -->
<div class="stats-grid">
    <!-- Today's Sales -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Today's Sales</div>
            <div class="stat-value"><?= formatCurrency($todaySalesRevenue) ?></div>
            <div class="stat-subtext">
                <span style="color: var(--success); font-weight: 600;">+<?= formatCurrency($todayGrossProfit) ?></span> gross profit (<?= $todaySalesCount ?> orders)
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-cash-register"></i>
        </div>
    </div>

    <!-- Month's Sales -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Monthly Sales (<?= date('F') ?>)</div>
            <div class="stat-value"><?= formatCurrency($monthSalesRevenue) ?></div>
            <div class="stat-subtext">
                <span style="color: var(--success); font-weight: 600;">+<?= formatCurrency($monthGrossProfit) ?></span> profit (<?= $monthSalesCount ?> orders)
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-chart-line"></i>
        </div>
    </div>

    <!-- Total Leftover Stock -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Leftover Stock</div>
            <div class="stat-value"><?= number_format($totalLeftoverStock) ?> <span style="font-size: 1rem; font-weight: 500; color: var(--text-muted);">units</span></div>
            <div class="stat-subtext">
                Cost: <strong><?= formatCurrency($totalCostValuation) ?></strong> | Retail: <strong><?= formatCurrency($totalRetailValuation) ?></strong>
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <!-- Expiry Alerts Watch -->
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Expiry Date Alerts</div>
            <div class="stat-value" style="color: <?= ($expiredItemsCount > 0) ? 'var(--danger)' : 'var(--warning-critical)' ?>;">
                <?= $expiredItemsCount + $expiringSoonCount ?> <span style="font-size: 1rem; font-weight: 500; color: var(--text-muted);">batches</span>
            </div>
            <div class="stat-subtext">
                <span style="color: var(--danger); font-weight: 700;"><?= $expiredItemsCount ?> Expired</span> | 
                <span style="color: var(--warning-critical); font-weight: 600;"><?= $expiringSoonCount ?> Expiring Soon</span>
            </div>
        </div>
        <div class="stat-icon <?= ($expiredItemsCount > 0) ? 'danger' : 'orange' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>
</div>

<!-- Interactive Analytics Charts Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- 7-Day Revenue Trend -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-chart-area" style="color: var(--primary);"></i>
                <span>Past 7 Days Sales Trend</span>
            </div>
            <a href="<?= BASE_URL ?>/modules/reports/daily_sales.php" class="btn btn-secondary btn-sm">
                <span>View Daily Report</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div style="height: 280px; position: relative;">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-trophy" style="color: var(--warning);"></i>
                <span>Top Selling Products this Month</span>
            </div>
            <a href="<?= BASE_URL ?>/modules/reports/monthly_sales.php" class="btn btn-secondary btn-sm">
                <span>Monthly Insights</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="card-body">
            <div style="height: 280px; position: relative;">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Two Column Section: Expiry Alert Watch & Recent Sales -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(460px, 1fr)); gap: 1.5rem;">
    <!-- Urgent Expiry Date Watch -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-calendar-xmark" style="color: var(--danger);"></i>
                <span>Urgent Expiration Watch</span>
            </div>
            <a href="<?= BASE_URL ?>/modules/reports/expiry_report.php" class="btn btn-secondary btn-sm">
                <span>Full Expiry Tracker</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product & Batch</th>
                        <th>Leftover Stock</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($urgentBatches)): ?>
                        <?php foreach ($urgentBatches as $batch): 
                            $expInfo = getExpiryStatus($batch['expiry_date']);
                            $isExp = $expInfo['status'] === 'expired';
                        ?>
                            <tr class="<?= $isExp ? 'highlight-expired' : 'highlight-critical' ?>">
                                <td>
                                    <strong><?= e($batch['product_name']) ?></strong><br>
                                    <small style="color: var(--text-muted);">Batch: <?= e($batch['batch_no']) ?> (<?= e($batch['category_name'] ?? 'General') ?>)</small>
                                </td>
                                <td>
                                    <span style="font-weight: 700;"><?= $batch['current_quantity'] ?></span> <?= e($batch['unit']) ?>
                                </td>
                                <td>
                                    <?= formatDate($batch['expiry_date']) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $expInfo['badge_class'] ?>">
                                        <i class="fa-solid <?= $expInfo['icon'] ?>"></i>
                                        <?= $expInfo['label'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/batches/adjust.php?batch_id=<?= $batch['batch_id'] ?>" 
                                       class="btn btn-secondary btn-sm" title="Adjust / Write-off Stock">
                                        <i class="fa-solid fa-sliders"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                <i class="fa-solid fa-shield-check" style="color: var(--success); font-size: 1.5rem; margin-bottom: 0.5rem; display: block;"></i>
                                No critical batch expirations in the next 60 days. All stock is safe!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Sales Transactions -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-receipt" style="color: var(--info);"></i>
                <span>Recent Transactions</span>
            </div>
            <a href="<?= BASE_URL ?>/modules/reports/daily_sales.php" class="btn btn-secondary btn-sm">
                <span>All Sales</span>
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Payment</th>
                        <th>Grand Total</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentSales)): ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td>
                                    <strong><?= e($sale['invoice_no']) ?></strong>
                                </td>
                                <td><?= e($sale['customer_name']) ?></td>
                                <td>
                                    <small><?= formatDateTime($sale['sale_date'], 'M d, h:i A') ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-secondary" style="text-transform: uppercase;">
                                        <?= e($sale['payment_method']) ?>
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: var(--primary);"><?= formatCurrency($sale['grand_total']) ?></strong>
                                </td>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/pos/receipt.php?id=<?= $sale['id'] ?>" 
                                       target="_blank" class="btn btn-secondary btn-sm" title="View & Print Invoice">
                                        <i class="fa-solid fa-print"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                No sales recorded yet. Click <strong>Start POS Sale</strong> to create your first invoice!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // 1. Sales Trend Chart
    const trendCtx = document.getElementById('salesTrendChart');
    if (trendCtx) {
        const trendLabels = <?= json_encode(array_column($past7Days, 'label')) ?>;
        const trendData = <?= json_encode(array_column($past7Days, 'revenue')) ?>;

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Daily Revenue (<?= e(CURRENCY_SYMBOL) ?>)',
                    data: trendData,
                    borderColor: '#4f46e5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#4f46e5',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' Revenue: <?= e(CURRENCY_SYMBOL) ?>' + context.parsed.y.toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                        ticks: {
                            callback: function(value) { return '<?= e(CURRENCY_SYMBOL) ?>' + value; }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Top Selling Products Chart
    const topCtx = document.getElementById('topProductsChart');
    if (topCtx) {
        const topLabels = <?= json_encode(array_column($topProducts, 'product_name')) ?>;
        const topData = <?= json_encode(array_column($topProducts, 'total_qty_sold')) ?>;

        new Chart(topCtx, {
            type: 'bar',
            data: {
                labels: topLabels.length ? topLabels : ['No Sales Recorded'],
                datasets: [{
                    label: 'Units Sold',
                    data: topData.length ? topData : [0],
                    backgroundColor: [
                        '#4f46e5',
                        '#0ea5e9',
                        '#10b981',
                        '#f59e0b',
                        '#ec4899'
                    ],
                    borderRadius: 6
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.15)' },
                        ticks: { stepSize: 1 }
                    },
                    y: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
