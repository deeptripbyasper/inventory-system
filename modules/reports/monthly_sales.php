<?php
/**
 * Monthly Sales Report (Month-Wise Aggregation & Multi-Month Analytics)
 */

$pageTitle = 'Monthly Sales Report';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Selected Month and Year
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');

$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$monthStartDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$monthEndDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $daysInMonth);
$monthName = date('F Y', strtotime($monthStartDate));

// 1. Monthly Summary KPIs
$monthlySummary = $db->fetchOne("
    SELECT 
        COUNT(id) as total_orders,
        COALESCE(SUM(subtotal), 0) as total_subtotal,
        COALESCE(SUM(discount), 0) as total_discount,
        COALESCE(SUM(tax), 0) as total_tax,
        COALESCE(SUM(grand_total), 0) as total_revenue
    FROM `sales`
    WHERE sale_date BETWEEN ? AND ? AND `status` = 'completed'
", "ss", [$monthStartDate . ' 00:00:00', $monthEndDate . ' 23:59:59']);

// 2. Monthly Cost & Profit
$monthlyProfitRes = $db->fetchOne("
    SELECT 
        COALESCE(SUM(si.quantity), 0) as total_units_sold,
        COALESCE(SUM(si.quantity * si.unit_cost_price), 0) as total_cogs,
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as gross_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    WHERE s.sale_date BETWEEN ? AND ? AND s.status = 'completed'
", "ss", [$monthStartDate . ' 00:00:00', $monthEndDate . ' 23:59:59']);

$monthOrders = (int)($monthlySummary['total_orders'] ?? 0);
$monthRevenue = (float)($monthlySummary['total_revenue'] ?? 0);
$monthUnitsSold = (int)($monthlyProfitRes['total_units_sold'] ?? 0);
$monthCOGS = (float)($monthlyProfitRes['total_cogs'] ?? 0);
$monthGrossProfit = (float)($monthlyProfitRes['gross_profit'] ?? 0) - (float)($monthlySummary['total_discount'] ?? 0);
$monthProfitMargin = $monthRevenue > 0 ? ($monthGrossProfit / $monthRevenue) * 100 : 0;
$averageOrderValue = $monthOrders > 0 ? ($monthRevenue / $monthOrders) : 0;

// 3. Day-by-Day Aggregation for the Selected Month
$daysMap = [];
for ($d = 1; $d <= $daysInMonth; $d++) {
    $dateKey = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $d);
    $daysMap[$dateKey] = [
        'day' => $d,
        'date' => $dateKey,
        'invoices' => 0,
        'units_sold' => 0,
        'revenue' => 0.00,
        'profit' => 0.00
    ];
}

$dailyBreakdown = $db->fetchAll("
    SELECT 
        DATE(s.sale_date) as sdate,
        COUNT(DISTINCT s.id) as orders_count,
        COALESCE(SUM(s.grand_total), 0) as daily_revenue,
        COALESCE(SUM(si.quantity), 0) as daily_units,
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as daily_profit
    FROM `sales` s
    LEFT JOIN `sale_items` si ON s.id = si.sale_id
    WHERE s.sale_date BETWEEN ? AND ? AND s.status = 'completed'
    GROUP BY DATE(s.sale_date)
    ORDER BY sdate ASC
", "ss", [$monthStartDate . ' 00:00:00', $monthEndDate . ' 23:59:59']);

if ($dailyBreakdown) {
    foreach ($dailyBreakdown as $db_row) {
        $k = $db_row['sdate'];
        if (isset($daysMap[$k])) {
            $daysMap[$k]['invoices'] = (int)$db_row['orders_count'];
            $daysMap[$k]['units_sold'] = (int)$db_row['daily_units'];
            $daysMap[$k]['revenue'] = (float)$db_row['daily_revenue'];
            $daysMap[$k]['profit'] = (float)$db_row['daily_profit'];
        }
    }
}

// 4. Top Selling Products for the Selected Month
$monthlyTopProducts = $db->fetchAll("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.sku,
        p.unit,
        c.name as category_name,
        SUM(si.quantity) as total_qty_sold,
        SUM(si.subtotal) as total_product_revenue,
        SUM(si.quantity * (si.unit_price - si.unit_cost_price)) as total_product_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    JOIN `products` p ON si.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    WHERE s.sale_date BETWEEN ? AND ? AND s.status = 'completed'
    GROUP BY p.id, p.name, p.sku, p.unit, c.name
    ORDER BY total_product_revenue DESC
    LIMIT 10
", "ss", [$monthStartDate . ' 00:00:00', $monthEndDate . ' 23:59:59']);
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i>
            <span>Monthly Sales Report (<?= e($monthName) ?>)</span>
        </h1>
        <p>Month-wide revenue trends, daily profit distribution, and top performing product metrics</p>
    </div>
    <div class="page-header-actions">
        <!-- Month & Year Selector -->
        <form action="<?= BASE_URL ?>/modules/reports/monthly_sales.php" method="GET" style="display: flex; gap: 0.5rem; align-items: center;">
            <select name="month" class="form-select" style="width: auto; padding: 0.45rem 0.65rem;" onchange="this.form.submit()">
                <?php for ($m = 1; $m <= 12; $m++): ?>
                    <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>>
                        <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                    </option>
                <?php endfor; ?>
            </select>

            <select name="year" class="form-select" style="width: auto; padding: 0.45rem 0.65rem;" onchange="this.form.submit()">
                <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                    <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-filter"></i> Go
            </button>
        </form>

        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('monthlyDayTable', 'monthly_sales_<?= $selectedYear ?>_<?= $selectedMonth ?>.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export CSV</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Monthly KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Monthly Gross Revenue</div>
            <div class="stat-value"><?= formatCurrency($monthRevenue) ?></div>
            <div class="stat-subtext">
                <?= $monthOrders ?> Total Orders in <?= date('M Y', strtotime($monthStartDate)) ?>
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-sack-dollar"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Monthly Gross Profit</div>
            <div class="stat-value" style="color: var(--success);"><?= formatCurrency($monthGrossProfit) ?></div>
            <div class="stat-subtext">
                Profit Margin: <strong><?= number_format($monthProfitMargin, 1) ?>%</strong>
            </div>
        </div>
        <div class="stat-icon success">
            <i class="fa-solid fa-arrow-trend-up"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Total Units Sold</div>
            <div class="stat-value"><?= number_format($monthUnitsSold) ?> <span style="font-size: 0.95rem; font-weight: 500;">units</span></div>
            <div class="stat-subtext">
                Inventory Cost: <?= formatCurrency($monthCOGS) ?>
            </div>
        </div>
        <div class="stat-icon info">
            <i class="fa-solid fa-boxes-stacked"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label">Average Order Value</div>
            <div class="stat-value"><?= formatCurrency($averageOrderValue) ?></div>
            <div class="stat-subtext">
                Per checkout basket
            </div>
        </div>
        <div class="stat-icon warning">
            <i class="fa-solid fa-receipt"></i>
        </div>
    </div>
</div>

<!-- Monthly Daily Revenue & Profit Trend Chart -->
<div class="card no-print">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-chart-column" style="color: var(--primary);"></i>
            <span>Daily Revenue & Gross Profit in <?= e($monthName) ?></span>
        </div>
    </div>
    <div class="card-body">
        <div style="height: 280px; position: relative;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Grid: Day-by-Day Table & Top Products of the Month -->
<div style="display: grid; grid-template-columns: 1fr; gap: 1.5rem;">
    <!-- Top Products This Month -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-trophy" style="color: var(--warning);"></i>
                <span>Top Selling Products in <?= e($monthName) ?></span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Product & Category</th>
                        <th>SKU</th>
                        <th>Units Sold</th>
                        <th>Total Revenue</th>
                        <th>Gross Profit</th>
                        <th>Revenue Contribution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($monthlyTopProducts)): ?>
                        <?php foreach ($monthlyTopProducts as $tp): 
                            $contrib = $monthRevenue > 0 ? ($tp['total_product_revenue'] / $monthRevenue) * 100 : 0;
                        ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $tp['product_id'] ?>" style="font-weight: 700;">
                                        <?= e($tp['product_name']) ?>
                                    </a><br>
                                    <small style="color: var(--text-muted);"><?= e($tp['category_name'] ?? 'General') ?></small>
                                </td>
                                <td><code><?= e($tp['sku']) ?></code></td>
                                <td><strong><?= $tp['total_qty_sold'] ?></strong> <?= e($tp['unit']) ?></td>
                                <td><strong style="color: var(--primary);"><?= formatCurrency($tp['total_product_revenue']) ?></strong></td>
                                <td><strong style="color: var(--success);"><?= formatCurrency($tp['total_product_profit']) ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                                        <div style="flex: 1; height: 6px; background: var(--border-color); border-radius: 4px; overflow: hidden;">
                                            <div style="width: <?= min(100, $contrib) ?>%; height: 100%; background: var(--primary);"></div>
                                        </div>
                                        <small style="font-weight: 700; width: 45px;"><?= number_format($contrib, 1) ?>%</small>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                No sales recorded in <?= e($monthName) ?>.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Day-by-Day Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i>
                <span>Day-by-Day Breakdown for <?= e($monthName) ?></span>
            </div>
            <div class="search-input-wrapper" style="max-width: 240px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="form-control" placeholder="Search day..." data-table-search="monthlyDayTable">
            </div>
        </div>
        <div class="table-responsive">
            <table class="table" id="monthlyDayTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Orders Count</th>
                        <th>Units Sold</th>
                        <th>Gross Revenue</th>
                        <th>Gross Profit</th>
                        <th>Profit Margin %</th>
                        <th class="no-export" style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daysMap as $dItem): 
                        $margin = $dItem['revenue'] > 0 ? ($dItem['profit'] / $dItem['revenue']) * 100 : 0;
                    ?>
                        <tr style="<?= $dItem['revenue'] > 0 ? 'font-weight: 500;' : 'opacity: 0.7;' ?>">
                            <td>
                                <strong><?= formatDate($dItem['date'], 'D, M d, Y') ?></strong>
                            </td>
                            <td><?= $dItem['invoices'] ?></td>
                            <td><?= $dItem['units_sold'] ?></td>
                            <td><strong style="color: <?= $dItem['revenue'] > 0 ? 'var(--primary)' : 'inherit' ?>;"><?= formatCurrency($dItem['revenue']) ?></strong></td>
                            <td><strong style="color: <?= $dItem['profit'] > 0 ? 'var(--success)' : 'inherit' ?>;"><?= formatCurrency($dItem['profit']) ?></strong></td>
                            <td>
                                <?php if ($dItem['revenue'] > 0): ?>
                                    <span class="badge <?= $margin >= 30 ? 'badge-success' : 'badge-warning' ?>">
                                        <?= number_format($margin, 1) ?>%
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="no-export" style="text-align: right;">
                                <?php if ($dItem['revenue'] > 0): ?>
                                    <a href="<?= BASE_URL ?>/modules/reports/daily_sales.php?date=<?= $dItem['date'] ?>" class="btn btn-secondary btn-sm" title="View Everyday Sales Details">
                                        <i class="fa-solid fa-arrow-right"></i>
                                        <span>Daily Breakdown</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        const labels = <?= json_encode(array_map(function($d) { return 'Day ' . $d['day']; }, array_values($daysMap))) ?>;
        const revenueData = <?= json_encode(array_column(array_values($daysMap), 'revenue')) ?>;
        const profitData = <?= json_encode(array_column(array_values($daysMap), 'profit')) ?>;

        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Gross Revenue (<?= e(CURRENCY_SYMBOL) ?>)',
                        data: revenueData,
                        backgroundColor: '#4f46e5',
                        borderRadius: 4
                    },
                    {
                        label: 'Gross Profit (<?= e(CURRENCY_SYMBOL) ?>)',
                        data: profitData,
                        backgroundColor: '#10b981',
                        borderRadius: 4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
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
