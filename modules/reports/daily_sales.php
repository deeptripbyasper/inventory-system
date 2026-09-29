<?php
/**
 * Everyday Sales Report (Daily Sales Analytics & Breakdown)
 */

$pageTitle = 'Everyday Sales Report';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Selected date (defaults to today)
$selectedDate = $_GET['date'] ?? date('Y-m-d');

// 1. Daily Aggregated Totals
$dailySummary = $db->fetchOne("
    SELECT 
        COUNT(s.id) as total_invoices,
        COALESCE(SUM(s.subtotal), 0) as subtotal,
        COALESCE(SUM(s.discount), 0) as total_discount,
        COALESCE(SUM(s.tax), 0) as total_tax,
        COALESCE(SUM(s.grand_total), 0) as total_revenue
    FROM `sales` s
    WHERE DATE(s.sale_date) = ? AND s.status = 'completed'
", "s", [$selectedDate]);

// 2. Daily Cost of Goods Sold & Profit
$dailyProfitRes = $db->fetchOne("
    SELECT 
        COALESCE(SUM(si.quantity), 0) as total_units_sold,
        COALESCE(SUM(si.quantity * si.unit_cost_price), 0) as total_cogs,
        COALESCE(SUM(si.subtotal), 0) as items_revenue,
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as gross_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) = ? AND s.status = 'completed'
", "s", [$selectedDate]);

$totalInvoices = (int)($dailySummary['total_invoices'] ?? 0);
$totalRevenue = (float)($dailySummary['total_revenue'] ?? 0);
$totalUnitsSold = (int)($dailyProfitRes['total_units_sold'] ?? 0);
$totalCOGS = (float)($dailyProfitRes['total_cogs'] ?? 0);
$grossProfit = (float)($dailyProfitRes['gross_profit'] ?? 0) - (float)($dailySummary['total_discount'] ?? 0);
$profitMargin = $totalRevenue > 0 ? ($grossProfit / $totalRevenue) * 100 : 0;

// 3. Product-Wise Daily Sales Breakdown
$productSales = $db->fetchAll("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.sku,
        p.unit,
        c.name as category_name,
        b.batch_no,
        b.expiry_date,
        SUM(si.quantity) as qty_sold,
        AVG(si.unit_price) as avg_price,
        SUM(si.subtotal) as total_product_revenue,
        SUM(si.quantity * si.unit_cost_price) as product_cogs,
        SUM(si.quantity * (si.unit_price - si.unit_cost_price)) as product_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    JOIN `products` p ON si.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `product_batches` b ON si.batch_id = b.id
    WHERE DATE(s.sale_date) = ? AND s.status = 'completed'
    GROUP BY p.id, p.name, p.sku, p.unit, c.name, b.batch_no, b.expiry_date
    ORDER BY total_product_revenue DESC
", "s", [$selectedDate]);

// 4. Invoices / Transactions List for the selected date
$invoices = $db->fetchAll("
    SELECT 
        s.*,
        u.full_name as cashier_name,
        (SELECT COUNT(*) FROM `sale_items` WHERE `sale_id` = s.id) as item_count
    FROM `sales` s
    LEFT JOIN `users` u ON s.user_id = u.id
    WHERE DATE(s.sale_date) = ? AND s.status = 'completed'
    ORDER BY s.sale_date DESC
", "s", [$selectedDate]);

// 5. Hourly Distribution for the selected date
$hourlyMap = array_fill(0, 24, 0.00);
$hourlySales = $db->fetchAll("
    SELECT 
        HOUR(sale_date) as hr,
        COALESCE(SUM(grand_total), 0) as hr_rev
    FROM `sales`
    WHERE DATE(sale_date) = ? AND status = 'completed'
    GROUP BY HOUR(sale_date)
", "s", [$selectedDate]);

if ($hourlySales) {
    foreach ($hourlySales as $hs) {
        $hourlyMap[(int)$hs['hr']] = (float)$hs['hr_rev'];
    }
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-calendar-day" style="color: var(--primary);"></i>
            <span>Everyday Sales Report</span>
        </h1>
        <p>Daily transactional revenue, product-wise sales velocity, and gross profit audit</p>
    </div>
    <div class="page-header-actions">
        <!-- Date Selector Form -->
        <form action="<?= BASE_URL ?>/modules/reports/daily_sales.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <input type="date" name="date" class="form-control" value="<?= e($selectedDate) ?>" onchange="this.form.submit()" style="padding: 0.45rem 0.75rem;">
            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-filter"></i> Go
            </button>
            <?php if ($selectedDate !== date('Y-m-d')): ?>
                <a href="<?= BASE_URL ?>/modules/reports/daily_sales.php" class="btn btn-secondary btn-sm" title="Jump to Today">
                    Today
                </a>
            <?php endif; ?>
        </form>

        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('dailyProductTable', 'daily_sales_<?= $selectedDate ?>.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Daily Summary KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Revenue (<?= formatDate($selectedDate) ?>)</div>
            <div class="stat-value"><?= formatCurrency($totalRevenue) ?></div>
            <div class="stat-subtext">
                <?= $totalInvoices ?> Invoices processed
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-cash-register"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Gross Profit</div>
            <div class="stat-value" style="color: var(--success);"><?= formatCurrency($grossProfit) ?></div>
            <div class="stat-subtext">
                Net Margin: <strong><?= number_format($profitMargin, 1) ?>%</strong>
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Items Sold Today</div>
            <div class="stat-value"><?= number_format($totalUnitsSold) ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Cost of Goods: <?= formatCurrency($totalCOGS) ?>
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Average Order Value (AOV)</div>
            <div class="stat-value">
                <?= formatCurrency($totalInvoices > 0 ? $totalRevenue / $totalInvoices : 0) ?>
            </div>
            <div class="stat-subtext">
                Per checkout transaction
            </div>
        </div>
        <div class="stat-icon warning">
            <i class="fa-solid fa-receipt"></i>
        </div>
    </div>
</div>

<!-- Hourly Sales Chart for the Day -->
<div class="card no-print">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-clock" style="color: var(--primary);"></i>
            <span>Hourly Sales Distribution (<?= formatDate($selectedDate) ?>)</span>
        </div>
    </div>
    <div class="card-body">
        <div style="height: 220px; position: relative;">
            <canvas id="dailyHourlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Table 1: Product-Wise Daily Sales Breakdown -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-list-check" style="color: var(--primary);"></i>
            <span>Product-Wise Sales Breakdown for <?= formatDate($selectedDate) ?></span>
        </div>
        <div class="search-input-wrapper" style="max-width: 260px;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" class="form-control" placeholder="Search product..." data-table-search="dailyProductTable">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table" id="dailyProductTable">
            <thead>
                <tr>
                    <th>Product & Category</th>
                    <th>SKU</th>
                    <th>Batch Sold</th>
                    <th>Units Sold</th>
                    <th>Avg Price</th>
                    <th>Total Revenue</th>
                    <th>Cost (COGS)</th>
                    <th>Gross Profit</th>
                    <th>Margin %</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($productSales)): ?>
                    <?php foreach ($productSales as $ps): 
                        $margin = $ps['total_product_revenue'] > 0 
                            ? (($ps['product_profit'] / $ps['total_product_revenue']) * 100) 
                            : 0;
                    ?>
                        <tr>
                            <td>
                                <strong><?= e($ps['product_name']) ?></strong><br>
                                <small style="color: var(--text-muted);"><?= e($ps['category_name'] ?? 'General') ?></small>
                            </td>
                            <td><code><?= e($ps['sku']) ?></code></td>
                            <td>
                                <code><?= e($ps['batch_no'] ?? 'N/A') ?></code><br>
                                <small style="color: var(--text-muted);">Exp: <?= formatDate($ps['expiry_date'], 'M y') ?></small>
                            </td>
                            <td>
                                <strong><?= $ps['qty_sold'] ?></strong> <?= e($ps['unit']) ?>
                            </td>
                            <td><?= formatCurrency($ps['avg_price']) ?></td>
                            <td><strong style="color: var(--primary);"><?= formatCurrency($ps['total_product_revenue']) ?></strong></td>
                            <td><?= formatCurrency($ps['product_cogs']) ?></td>
                            <td>
                                <strong style="color: var(--success);"><?= formatCurrency($ps['product_profit']) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $margin >= 30 ? 'badge-success' : 'badge-warning' ?>">
                                    <?= number_format($margin, 1) ?>%
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            No product sales recorded on <?= formatDate($selectedDate) ?>.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Table 2: Daily Invoices / Orders List -->
<div class="card">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-receipt" style="color: var(--info);"></i>
            <span>All Transactions & Invoices (<?= formatDate($selectedDate) ?>)</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Time</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Payment Method</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Grand Total</th>
                    <th class="no-export" style="text-align: right;">Receipt</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($invoices)): ?>
                    <?php foreach ($invoices as $inv): ?>
                        <tr>
                            <td><strong><?= e($inv['invoice_no']) ?></strong></td>
                            <td><?= formatDateTime($inv['sale_date'], 'h:i A') ?></td>
                            <td><?= e($inv['customer_name']) ?></td>
                            <td><?= $inv['item_count'] ?> items</td>
                            <td>
                                <span class="badge badge-secondary" style="text-transform: uppercase;">
                                    <?= e($inv['payment_method']) ?>
                                </span>
                            </td>
                            <td><?= formatCurrency($inv['subtotal']) ?></td>
                            <td><?= formatCurrency($inv['tax']) ?></td>
                            <td><strong style="color: var(--primary); font-size: 1rem;"><?= formatCurrency($inv['grand_total']) ?></strong></td>
                            <td class="no-export" style="text-align: right;">
                                <a href="<?= BASE_URL ?>/modules/pos/receipt.php?id=<?= $inv['id'] ?>" 
                                   target="_blank" class="btn btn-secondary btn-sm" title="View & Print Receipt">
                                    <i class="fa-solid fa-print"></i>
                                    <span>Print</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No sales transactions recorded on this date.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const hourlyCtx = document.getElementById('dailyHourlyChart');
    if (hourlyCtx) {
        const labels = ['12AM', '1AM', '2AM', '3AM', '4AM', '5AM', '6AM', '7AM', '8AM', '9AM', '10AM', '11AM', '12PM', '1PM', '2PM', '3PM', '4PM', '5PM', '6PM', '7PM', '8PM', '9PM', '10PM', '11PM'];
        const data = <?= json_encode(array_values($hourlyMap)) ?>;

        new Chart(hourlyCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Hourly Sales (<?= e(CURRENCY_SYMBOL) ?>)',
                    data: data,
                    backgroundColor: '#4f46e5',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(148, 163, 184, 0.15)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
