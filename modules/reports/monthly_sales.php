<?php
/**
 * Monthly & Date-Wise Sales Report
 * Supports Month-Year Selection, Custom From Date - To Date filtering,
 * Product Name/SKU Search, Day-by-Day Breakdown, and Revenue Trends.
 */

$pageTitle = 'Monthly & Date-Wise Sales Report';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// 1. Resolve Date Range (From Date -> To Date & Month/Year Fallback)
$inputFromDate = trim($_GET['from_date'] ?? '');
$inputToDate = trim($_GET['to_date'] ?? '');
$productSearch = trim($_GET['product_search'] ?? '');

$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$selectedMonth = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
if ($selectedMonth < 1 || $selectedMonth > 12) {
    $selectedMonth = (int)date('m');
}
if ($selectedYear < 2000 || $selectedYear > 2100) {
    $selectedYear = (int)date('Y');
}

$isCustomDateRange = false;

if (!empty($inputFromDate) && !empty($inputToDate) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputFromDate) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $inputToDate)) {
    
    $startTs = strtotime($inputFromDate);
    $endTs = strtotime($inputToDate);
    
    if ($startTs && $endTs) {
        if ($startTs > $endTs) {
            // Auto swap if user selected inverted dates
            $temp = $startTs; $startTs = $endTs; $endTs = $temp;
        }
        $startDate = date('Y-m-d', $startTs);
        $endDate = date('Y-m-d', $endTs);
        $selectedYear = (int)date('Y', $startTs);
        $selectedMonth = (int)date('m', $startTs);
        $isCustomDateRange = true;
    } else {
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
        $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
        $endDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $daysInMonth);
    }
} else {
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
    $startDate = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
    $endDate = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $daysInMonth);
}

// Check if range matches exact single calendar month
$calDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
$expectedMonthStart = sprintf('%04d-%02d-01', $selectedYear, $selectedMonth);
$expectedMonthEnd = sprintf('%04d-%02d-%02d', $selectedYear, $selectedMonth, $calDays);

if ($startDate === $expectedMonthStart && $endDate === $expectedMonthEnd) {
    $isSingleMonth = true;
    $periodTitle = date('F Y', strtotime($startDate));
} else {
    $isSingleMonth = false;
    $periodTitle = formatDate($startDate, 'M d, Y') . ' — ' . formatDate($endDate, 'M d, Y');
}

$startDateTime = $startDate . ' 00:00:00';
$endDateTime = $endDate . ' 23:59:59';

// 2. Summary KPIs for the Selected Date Range
$monthlySummary = $db->fetchOne("
    SELECT 
        COUNT(id) as total_orders,
        COALESCE(SUM(subtotal), 0) as total_subtotal,
        COALESCE(SUM(discount), 0) as total_discount,
        COALESCE(SUM(tax), 0) as total_tax,
        COALESCE(SUM(grand_total), 0) as total_revenue
    FROM `sales`
    WHERE sale_date BETWEEN ? AND ? AND `status` = 'completed'
", "ss", [$startDateTime, $endDateTime]);

// Cost & Gross Profit
$monthlyProfitRes = $db->fetchOne("
    SELECT 
        COALESCE(SUM(si.quantity), 0) as total_units_sold,
        COALESCE(SUM(si.quantity * si.unit_cost_price), 0) as total_cogs,
        COALESCE(SUM(si.quantity * (si.unit_price - si.unit_cost_price)), 0) as gross_profit
    FROM `sale_items` si
    JOIN `sales` s ON si.sale_id = s.id
    WHERE s.sale_date BETWEEN ? AND ? AND s.status = 'completed'
", "ss", [$startDateTime, $endDateTime]);

$monthOrders = (int)($monthlySummary['total_orders'] ?? 0);
$monthRevenue = (float)($monthlySummary['total_revenue'] ?? 0);
$monthUnitsSold = (int)($monthlyProfitRes['total_units_sold'] ?? 0);
$monthCOGS = (float)($monthlyProfitRes['total_cogs'] ?? 0);
$monthGrossProfit = (float)($monthlyProfitRes['gross_profit'] ?? 0) - (float)($monthlySummary['total_discount'] ?? 0);
$monthProfitMargin = $monthRevenue > 0 ? ($monthGrossProfit / $monthRevenue) * 100 : 0;
$averageOrderValue = $monthOrders > 0 ? ($monthRevenue / $monthOrders) : 0;

// 3. Day-by-Day Aggregation for Date Range (Up to 366 days)
$daysMap = [];
$rangeStartTs = strtotime($startDate);
$rangeEndTs = strtotime($endDate);
$loopTs = $rangeStartTs;
$dayLimit = 366;
$dayCount = 0;

while ($loopTs <= $rangeEndTs && $dayCount < $dayLimit) {
    $dateKey = date('Y-m-d', $loopTs);
    $dayLabel = $isSingleMonth ? ('Day ' . date('j', $loopTs)) : date('M d', $loopTs);
    $daysMap[$dateKey] = [
        'day' => (int)date('j', $loopTs),
        'label' => $dayLabel,
        'date' => $dateKey,
        'invoices' => 0,
        'units_sold' => 0,
        'revenue' => 0.00,
        'profit' => 0.00
    ];
    $loopTs = strtotime('+1 day', $loopTs);
    $dayCount++;
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
", "ss", [$startDateTime, $endDateTime]);

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

// 4. Product Sales Performance (With Product Name / SKU Search)
$prodSql = "
    SELECT 
        p.id as product_id,
        p.name as product_name,
        p.sku,
        p.barcode,
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
";

$prodTypes = "ss";
$prodParams = [$startDateTime, $endDateTime];

if (!empty($productSearch)) {
    $prodSql .= " AND (p.name LIKE ? OR p.sku LIKE ? OR p.barcode LIKE ?)";
    $prodTypes .= "sss";
    $searchWildcard = "%{$productSearch}%";
    $prodParams[] = $searchWildcard;
    $prodParams[] = $searchWildcard;
    $prodParams[] = $searchWildcard;
}

$prodSql .= "
    GROUP BY p.id, p.name, p.sku, p.barcode, p.unit, c.name
    ORDER BY total_product_revenue DESC
";

if (empty($productSearch)) {
    $prodSql .= " LIMIT 30";
} else {
    $prodSql .= " LIMIT 200";
}

$monthlyTopProducts = $db->fetchAll($prodSql, $prodTypes, $prodParams);

// Calculate search-specific statistics if search is active
$searchTotalUnits = 0;
$searchTotalRevenue = 0.00;
$searchTotalProfit = 0.00;
if (!empty($productSearch) && !empty($monthlyTopProducts)) {
    foreach ($monthlyTopProducts as $tp) {
        $searchTotalUnits += (int)$tp['total_qty_sold'];
        $searchTotalRevenue += (float)$tp['total_product_revenue'];
        $searchTotalProfit += (float)$tp['total_product_profit'];
    }
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h1>
            <i class="fa-solid fa-chart-line" style="color: var(--primary);"></i>
            <span>Monthly & Date-Wise Sales Report</span>
        </h1>
        <p>Sales revenue trends, product velocity audit, and daily profit distribution for <strong><?= e($periodTitle) ?></strong></p>
    </div>

    <div class="page-header-actions no-print">
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('monthlyProductTable', 'product_sales_<?= $startDate ?>_to_<?= $endDate ?>.csv')">
            <i class="fa-solid fa-file-csv"></i>
            <span>Export Products CSV</span>
        </button>
        <button type="button" class="btn btn-secondary" onclick="exportTableToCSV('monthlyDayTable', 'daily_breakdown_<?= $startDate ?>_to_<?= $endDate ?>.csv')">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Export Daily CSV</span>
        </button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
            <i class="fa-solid fa-print"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<!-- Filter Bar: From Date - To Date, Quick Month Picker, and Product Name Search -->
<div class="filter-bar no-print">
    <form action="<?= BASE_URL ?>/modules/reports/monthly_sales.php" method="GET" id="reportFilterForm" style="display: flex; gap: 0.85rem; flex-wrap: wrap; align-items: flex-end; width: 100%;">
        
        <!-- Quick Month Selector -->
        <div style="display: flex; flex-direction: column; gap: 0.3rem;">
            <label style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                <i class="fa-regular fa-calendar-check" style="color: var(--primary);"></i> Quick Month
            </label>
            <div style="display: flex; gap: 0.35rem;">
                <select id="quickMonth" class="form-select" style="width: auto; padding: 0.5rem 0.65rem;" onchange="applyQuickMonth()">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $selectedMonth == $m ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 10)) ?>
                        </option>
                    <?php endfor; ?>
                </select>

                <select id="quickYear" class="form-select" style="width: auto; padding: 0.5rem 0.65rem;" onchange="applyQuickMonth()">
                    <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                        <option value="<?= $y ?>" <?= $selectedYear == $y ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>

        <!-- From Date -->
        <div style="display: flex; flex-direction: column; gap: 0.3rem;">
            <label for="from_date" style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                <i class="fa-solid fa-calendar-day" style="color: var(--primary);"></i> From Date
            </label>
            <input type="date" id="from_date" name="from_date" class="form-control" value="<?= e($startDate) ?>" style="padding: 0.5rem 0.75rem; width: 155px;">
        </div>

        <!-- To Date -->
        <div style="display: flex; flex-direction: column; gap: 0.3rem;">
            <label for="to_date" style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                <i class="fa-solid fa-calendar-day" style="color: var(--primary);"></i> To Date
            </label>
            <input type="date" id="to_date" name="to_date" class="form-control" value="<?= e($endDate) ?>" style="padding: 0.5rem 0.75rem; width: 155px;">
        </div>

        <!-- Product Name / SKU Search -->
        <div style="display: flex; flex-direction: column; gap: 0.3rem; flex: 1; min-width: 220px;">
            <label for="product_search" style="font-size: 0.78rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.04em;">
                <i class="fa-solid fa-magnifying-glass" style="color: var(--primary);"></i> Product Name / SKU Search
            </label>
            <div class="search-input-wrapper" style="width: 100%;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="product_search" name="product_search" class="form-control" placeholder="Type product name, SKU, or barcode..." value="<?= e($productSearch) ?>">
            </div>
        </div>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1.15rem;">
                <i class="fa-solid fa-filter"></i>
                <span>Filter Report</span>
            </button>

            <?php if ($startDate !== sprintf('%04d-%02d-01', (int)date('Y'), (int)date('m')) || $endDate !== sprintf('%04d-%02d-%02d', (int)date('Y'), (int)date('m'), cal_days_in_month(CAL_GREGORIAN, (int)date('m'), (int)date('Y'))) || !empty($productSearch)): ?>
                <a href="<?= BASE_URL ?>/modules/reports/monthly_sales.php" class="btn btn-secondary" style="padding: 0.55rem 0.95rem;" title="Reset to Current Month">
                    <i class="fa-solid fa-rotate-left"></i>
                    <span>Reset</span>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Summary KPI Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label"><?= $isSingleMonth ? 'Monthly Gross Revenue' : 'Period Gross Revenue' ?></div>
            <div class="stat-value"><?= formatCurrency($monthRevenue) ?></div>
            <div class="stat-subtext">
                <?= $monthOrders ?> Total Orders in <?= e($periodTitle) ?>
            </div>
        </div>
        <div class="stat-icon primary">
            <i class="fa-solid fa-sack-dollar"></i>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <div class="stat-label"><?= $isSingleMonth ? 'Monthly Gross Profit' : 'Period Gross Profit' ?></div>
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

<!-- Daily Revenue & Profit Trend Chart -->
<div class="card no-print">
    <div class="card-header">
        <div class="card-title">
            <i class="fa-solid fa-chart-column" style="color: var(--primary);"></i>
            <span>Daily Revenue & Gross Profit Trend (<?= e($periodTitle) ?>)</span>
        </div>
    </div>
    <div class="card-body">
        <div style="height: 280px; position: relative;">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>
</div>

<!-- Product Sales Performance Table -->
<div class="card" style="margin-bottom: 1.5rem;">
    <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem;">
        <div class="card-title">
            <?php if (!empty($productSearch)): ?>
                <i class="fa-solid fa-magnifying-glass" style="color: var(--primary);"></i>
                <span>Product Sales for "<?= e($productSearch) ?>" (<?= e($periodTitle) ?>)</span>
            <?php else: ?>
                <i class="fa-solid fa-trophy" style="color: var(--warning);"></i>
                <span>Top Selling Products (<?= e($periodTitle) ?>)</span>
            <?php endif; ?>
        </div>

        <div style="display: flex; gap: 0.5rem; align-items: center; margin-left: auto;">
            <!-- Live Instant Client-Side Table Filter -->
            <div class="search-input-wrapper" style="max-width: 250px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="form-control" placeholder="Quick filter table..." data-table-search="monthlyProductTable">
            </div>

            <button type="button" class="btn btn-secondary btn-sm" onclick="exportTableToCSV('monthlyProductTable', 'products_report_<?= $startDate ?>_to_<?= $endDate ?>.csv')" title="Export Table as CSV">
                <i class="fa-solid fa-file-csv"></i>
                <span>CSV</span>
            </button>
        </div>
    </div>

    <!-- Active Search Summary Banner -->
    <?php if (!empty($productSearch)): ?>
        <div style="padding: 0.85rem 1.25rem; background: var(--bg-hover, #f1f5f9); border-bottom: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
            <div style="font-size: 0.92rem;">
                <i class="fa-solid fa-circle-check" style="color: var(--success); margin-right: 0.35rem;"></i>
                Showing results for product keyword: <strong style="color: var(--primary);"><?= e($productSearch) ?></strong>
                <span style="color: var(--text-muted); margin-left: 0.5rem;">
                    (<?= count($monthlyTopProducts) ?> product<?= count($monthlyTopProducts) !== 1 ? 's' : '' ?> found)
                </span>
                <?php if (!empty($monthlyTopProducts)): ?>
                    <span style="margin-left: 1rem; font-weight: 600;">
                        Units Sold: <span style="color: var(--primary);"><?= number_format($searchTotalUnits) ?></span> |
                        Revenue: <span style="color: var(--primary);"><?= formatCurrency($searchTotalRevenue) ?></span> |
                        Gross Profit: <span style="color: var(--success);"><?= formatCurrency($searchTotalProfit) ?></span>
                    </span>
                <?php endif; ?>
            </div>
            <a href="<?= BASE_URL ?>/modules/reports/monthly_sales.php?from_date=<?= urlencode($startDate) ?>&to_date=<?= urlencode($endDate) ?>" class="btn btn-secondary btn-sm" title="Remove keyword filter">
                <i class="fa-solid fa-xmark"></i> Clear Product Search
            </a>
        </div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table" id="monthlyProductTable">
            <thead>
                <tr>
                    <th>Product & Category</th>
                    <th>SKU / Barcode</th>
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
                                <a href="<?= BASE_URL ?>/modules/products/view.php?id=<?= $tp['product_id'] ?>" style="font-weight: 700; color: var(--text-primary); text-decoration: none;">
                                    <?= e($tp['product_name']) ?>
                                </a><br>
                                <small style="color: var(--text-muted);"><?= e($tp['category_name'] ?? 'General') ?></small>
                            </td>
                            <td>
                                <code><?= e($tp['sku']) ?></code>
                                <?php if (!empty($tp['barcode'])): ?>
                                    <br><small style="color: var(--text-muted); font-size: 0.75rem;"><i class="fa-solid fa-barcode"></i> <?= e($tp['barcode']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= number_format($tp['total_qty_sold']) ?></strong> <?= e($tp['unit']) ?></td>
                            <td><strong style="color: var(--primary);"><?= formatCurrency($tp['total_product_revenue']) ?></strong></td>
                            <td><strong style="color: var(--success);"><?= formatCurrency($tp['total_product_profit']) ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <div style="flex: 1; height: 6px; background: var(--border-color); border-radius: 4px; overflow: hidden; min-width: 60px;">
                                        <div style="width: <?= min(100, $contrib) ?>%; height: 100%; background: var(--primary);"></div>
                                    </div>
                                    <small style="font-weight: 700; width: 45px;"><?= number_format($contrib, 1) ?>%</small>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2.5rem; color: var(--text-muted);">
                            <div style="margin-bottom: 0.5rem; font-size: 1.5rem; opacity: 0.5;">
                                <i class="fa-solid fa-box-open"></i>
                            </div>
                            <?php if (!empty($productSearch)): ?>
                                No sales recorded for product matching "<strong><?= e($productSearch) ?></strong>" between <?= formatDate($startDate) ?> and <?= formatDate($endDate) ?>.
                                <div style="margin-top: 0.75rem;">
                                    <a href="<?= BASE_URL ?>/modules/reports/monthly_sales.php?from_date=<?= urlencode($startDate) ?>&to_date=<?= urlencode($endDate) ?>" class="btn btn-secondary btn-sm">
                                        View all products in this period
                                    </a>
                                </div>
                            <?php else: ?>
                                No product sales recorded between <?= formatDate($startDate) ?> and <?= formatDate($endDate) ?>.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Day-by-Day Table -->
<div class="card">
    <div class="card-header" style="flex-wrap: wrap; gap: 0.75rem;">
        <div class="card-title">
            <i class="fa-solid fa-calendar-days" style="color: var(--primary);"></i>
            <span>Day-by-Day Breakdown (<?= e($periodTitle) ?>)</span>
        </div>
        <div style="display: flex; gap: 0.5rem; align-items: center; margin-left: auto;">
            <div class="search-input-wrapper" style="max-width: 240px;">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" class="form-control" placeholder="Search day..." data-table-search="monthlyDayTable">
            </div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="exportTableToCSV('monthlyDayTable', 'daily_breakdown_<?= $startDate ?>_to_<?= $endDate ?>.csv')" title="Export Table as CSV">
                <i class="fa-solid fa-file-csv"></i>
                <span>CSV</span>
            </button>
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

<script>
// Helper to synchronize quick month select with from_date & to_date inputs
function applyQuickMonth() {
    const m = document.getElementById('quickMonth').value.padStart(2, '0');
    const y = document.getElementById('quickYear').value;
    const lastDay = new Date(y, parseInt(m, 10), 0).getDate();
    document.getElementById('from_date').value = `${y}-${m}-01`;
    document.getElementById('to_date').value = `${y}-${m}-${String(lastDay).padStart(2, '0')}`;
    document.getElementById('reportFilterForm').submit();
}

document.addEventListener('DOMContentLoaded', () => {
    const monthlyCtx = document.getElementById('monthlyChart');
    if (monthlyCtx) {
        const labels = <?= json_encode(array_map(function($d) { return $d['label']; }, array_values($daysMap))) ?>;
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
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ' + Number(context.raw).toLocaleString('en-IN', {
                                    maximumFractionDigits: 2
                                });
                            }
                        }
                    }
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
