<?php
/**
 * Invoices & Sales Billing History
 * Search, filter, view, and reprint bills
 */

$pageTitle = 'Bills & Invoices History';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Filters
$search = trim($_GET['search'] ?? '');
$paymentFilter = trim($_GET['payment'] ?? '');
$dateFilter = trim($_GET['date_range'] ?? 'this_month');
$startDate = trim($_GET['start_date'] ?? '');
$endDate = trim($_GET['end_date'] ?? '');

$where = ["1=1"];
$params = [];
$types = "";

if (!empty($search)) {
    $where[] = "(s.invoice_no LIKE ? OR s.customer_name LIKE ? OR s.customer_phone LIKE ?)";
    $searchParam = "%{$search}%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if (!empty($paymentFilter)) {
    $where[] = "s.payment_method = ?";
    $params[] = $paymentFilter;
    $types .= "s";
}

if ($dateFilter === 'today') {
    $where[] = "DATE(s.sale_date) = CURDATE()";
} elseif ($dateFilter === 'yesterday') {
    $where[] = "DATE(s.sale_date) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} elseif ($dateFilter === 'last_7_days') {
    $where[] = "s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
} elseif ($dateFilter === 'this_month') {
    $where[] = "YEAR(s.sale_date) = YEAR(CURDATE()) AND MONTH(s.sale_date) = MONTH(CURDATE())";
} elseif ($dateFilter === 'custom' && !empty($startDate) && !empty($endDate)) {
    $where[] = "DATE(s.sale_date) BETWEEN ? AND ?";
    $params[] = $startDate;
    $params[] = $endDate;
    $types .= "ss";
}

$whereSql = implode(" AND ", $where);

// Fetch Sales Summary Stats
$summary = $db->fetchOne("
    SELECT 
        COUNT(s.id) as total_bills,
        COALESCE(SUM(s.grand_total), 0) as total_revenue,
        COALESCE(SUM(CASE WHEN s.payment_method = 'cash' THEN s.grand_total ELSE 0 END), 0) as cash_total,
        COALESCE(SUM(CASE WHEN s.payment_method = 'upi' THEN s.grand_total ELSE 0 END), 0) as upi_total,
        COALESCE(SUM(CASE WHEN s.payment_method = 'card' THEN s.grand_total ELSE 0 END), 0) as card_total
    FROM `sales` s
    WHERE {$whereSql}
", $types, $params);

// Fetch Sales Invoices List
$sales = $db->fetchAll("
    SELECT 
        s.*,
        u.full_name as cashier_name,
        COUNT(si.id) as item_count,
        COALESCE(SUM(si.quantity), 0) as total_units
    FROM `sales` s
    LEFT JOIN `users` u ON s.user_id = u.id
    LEFT JOIN `sale_items` si ON s.id = si.sale_id
    WHERE {$whereSql}
    GROUP BY s.id
    ORDER BY s.sale_date DESC, s.id DESC
", $types, $params);
?>

<div class="page-header">
    <div class="header-title">
        <div class="header-icon">
            <i class="fa-solid fa-receipt"></i>
        </div>
        <div>
            <h1>Invoices & Billing History</h1>
            <p>Review customer receipts, filter transactions, and reprint bills</p>
        </div>
    </div>
    <div class="header-actions">
        <a href="<?= BASE_URL ?>/modules/pos/index.php" class="btn btn-primary">
            <i class="fa-solid fa-cash-register"></i>
            <span>Open POS Register</span>
        </a>
    </div>
</div>

<!-- Summary Metric Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); margin-bottom: 1.5rem;">
    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <div class="stat-label">Total Bills Found</div>
            <div class="stat-value"><?= number_format($summary['total_bills'] ?? 0) ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
            <i class="fa-solid fa-indian-rupee-sign"></i>
        </div>
        <div>
            <div class="stat-label">Total Revenue</div>
            <div class="stat-value"><?= formatCurrency($summary['total_revenue'] ?? 0) ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
            <i class="fa-solid fa-money-bill-wave"></i>
        </div>
        <div>
            <div class="stat-label">Cash Collected</div>
            <div class="stat-value"><?= formatCurrency($summary['cash_total'] ?? 0) ?></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon" style="background: rgba(14, 165, 233, 0.1); color: var(--secondary);">
            <i class="fa-solid fa-qrcode"></i>
        </div>
        <div>
            <div class="stat-label">UPI / Digital Payments</div>
            <div class="stat-value"><?= formatCurrency($summary['upi_total'] ?? 0) ?></div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="filter-card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);">
    <form method="GET" action="invoices.php">
        <div style="display: grid; grid-template-columns: 2fr 1.2fr 1.2fr auto; gap: 1rem; align-items: end;">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.82rem;">Search Invoice / Customer</label>
                <div class="search-input-wrapper">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" name="search" class="form-control" placeholder="Search by INV #, customer name, mobile..." value="<?= e($search) ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.82rem;">Date Filter</label>
                <select name="date_range" id="dateRangeSelect" class="form-select" onchange="toggleCustomDates(this.value)">
                    <option value="today" <?= $dateFilter === 'today' ? 'selected' : '' ?>>Today</option>
                    <option value="yesterday" <?= $dateFilter === 'yesterday' ? 'selected' : '' ?>>Yesterday</option>
                    <option value="last_7_days" <?= $dateFilter === 'last_7_days' ? 'selected' : '' ?>>Last 7 Days</option>
                    <option value="this_month" <?= $dateFilter === 'this_month' ? 'selected' : '' ?>>This Month</option>
                    <option value="all_time" <?= $dateFilter === 'all_time' ? 'selected' : '' ?>>All Time</option>
                    <option value="custom" <?= $dateFilter === 'custom' ? 'selected' : '' ?>>Custom Dates</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="font-size: 0.82rem;">Payment Method</label>
                <select name="payment" class="form-select">
                    <option value="">All Methods</option>
                    <option value="cash" <?= $paymentFilter === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="upi" <?= $paymentFilter === 'upi' ? 'selected' : '' ?>>UPI / QR</option>
                    <option value="card" <?= $paymentFilter === 'card' ? 'selected' : '' ?>>Card</option>
                    <option value="credit" <?= $paymentFilter === 'credit' ? 'selected' : '' ?>>Credit / Khata</option>
                </select>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                <button type="submit" class="btn btn-primary" style="padding: 0.55rem 1rem;">
                    <i class="fa-solid fa-filter"></i> Apply
                </button>
                <a href="invoices.php" class="btn btn-secondary" style="padding: 0.55rem 0.85rem;" title="Reset Filters">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </div>

        <!-- Custom Date Range Sub-row -->
        <div id="customDateRow" style="display: <?= $dateFilter === 'custom' ? 'flex' : 'none' ?>; gap: 1rem; margin-top: 1rem; border-top: 1px dashed var(--border-color); padding-top: 1rem;">
            <div style="flex: 1;">
                <label class="form-label" style="font-size: 0.78rem;">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= e($startDate) ?>">
            </div>
            <div style="flex: 1;">
                <label class="form-label" style="font-size: 0.78rem;">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= e($endDate) ?>">
            </div>
        </div>
    </form>
</div>

<!-- Invoices Table Card -->
<div class="card">
    <div class="table-responsive">
        <table class="table">
            <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Date & Time</th>
                    <th>Customer Info</th>
                    <th style="text-align: center;">Items / Units</th>
                    <th>Payment Mode</th>
                    <th style="text-align: right;">Grand Total</th>
                    <th style="text-align: center; width: 160px;">Reprint & View</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($sales)): ?>
                    <?php foreach ($sales as $sale): 
                        $payClass = 'badge-primary';
                        if ($sale['payment_method'] === 'cash') $payClass = 'badge-success';
                        elseif ($sale['payment_method'] === 'upi') $payClass = 'badge-info';
                        elseif ($sale['payment_method'] === 'card') $payClass = 'badge-warning';
                    ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; font-size: 0.95rem; color: var(--primary);">
                                    <?= e($sale['invoice_no']) ?>
                                </strong>
                                <br>
                                <small style="color: var(--text-muted);">Cashier: <?= e($sale['cashier_name'] ?? 'Counter') ?></small>
                            </td>
                            <td>
                                <div><?= formatDate($sale['sale_date'], 'M d, Y') ?></div>
                                <small style="color: var(--text-muted);"><?= date('h:i A', strtotime($sale['sale_date'])) ?></small>
                            </td>
                            <td>
                                <strong><?= e($sale['customer_name']) ?></strong>
                                <?php if (!empty($sale['customer_phone'])): ?>
                                    <br><small style="color: var(--text-muted);"><i class="fa-solid fa-phone" style="font-size: 0.7rem;"></i> <?= e($sale['customer_phone']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge badge-secondary" style="font-size: 0.78rem;">
                                    <?= $sale['item_count'] ?> items (<?= $sale['total_units'] ?> units)
                                </span>
                            </td>
                            <td>
                                <span class="badge <?= $payClass ?>" style="text-transform: uppercase;">
                                    <?= e($sale['payment_method']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="font-size: 1.05rem; font-weight: 800; color: var(--text-primary);">
                                    <?= formatCurrency($sale['grand_total']) ?>
                                </div>
                                <?php if ($sale['discount'] > 0): ?>
                                    <small style="color: var(--success);">-<?= formatCurrency($sale['discount']) ?> Disc</small>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; justify-content: center; gap: 0.35rem;">
                                    <!-- Print Thermal -->
                                    <a href="<?= BASE_URL ?>/modules/pos/receipt.php?id=<?= $sale['id'] ?>&mode=thermal&autoprint=1" target="_blank" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.6rem;" title="Print Thermal Receipt (80mm)">
                                        <i class="fa-solid fa-receipt" style="color: var(--primary);"></i>
                                    </a>
                                    <!-- Print A4 Invoice -->
                                    <a href="<?= BASE_URL ?>/modules/pos/receipt.php?id=<?= $sale['id'] ?>&mode=standard&autoprint=1" target="_blank" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.6rem;" title="Print A4 Tax Invoice">
                                        <i class="fa-solid fa-print" style="color: var(--info);"></i>
                                    </a>
                                    <!-- View Details -->
                                    <a href="<?= BASE_URL ?>/modules/pos/receipt.php?id=<?= $sale['id'] ?>" class="btn btn-secondary btn-sm" style="padding: 0.35rem 0.6rem;" title="View Bill">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 3rem 1rem; color: var(--text-muted);">
                            <i class="fa-solid fa-file-circle-xmark" style="font-size: 2.5rem; margin-bottom: 0.5rem; opacity: 0.4;"></i>
                            <p style="font-size: 1rem; font-weight: 600;">No sales or invoices match your filter criteria.</p>
                            <a href="invoices.php" class="btn btn-secondary btn-sm" style="margin-top: 0.5rem;">Clear Filters</a>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleCustomDates(val) {
    const row = document.getElementById('customDateRow');
    if (row) {
        row.style.display = (val === 'custom') ? 'flex' : 'none';
    }
}
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>
