<?php
/**
 * Dual-Mode Printable Bill & Receipt System
 * Supports:
 * 1. 80mm / 58mm Thermal Roll Receipt
 * 2. Full A4 / A5 Standard Tax Invoice
 */

require_once __DIR__ . '/../../config/config.php';
requireAuth();

$saleId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$mode = isset($_GET['mode']) && $_GET['mode'] === 'standard' ? 'standard' : 'thermal';
$autoPrint = isset($_GET['autoprint']) && $_GET['autoprint'] == '1';

$db = Database::getInstance();

$sale = $db->fetchOne("
    SELECT 
        s.*,
        u.full_name as cashier_name
    FROM `sales` s
    LEFT JOIN `users` u ON s.user_id = u.id
    WHERE s.id = ?
", "i", [$saleId]);

if (!$sale) {
    setFlash('danger', 'Invoice not found.');
    header("Location: " . BASE_URL . "/modules/pos/index.php");
    exit;
}

$items = $db->fetchAll("
    SELECT 
        si.*,
        p.name as product_name,
        p.sku,
        p.unit,
        p.barcode,
        c.name as category_name,
        b.batch_no,
        b.expiry_date
    FROM `sale_items` si
    JOIN `products` p ON si.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    LEFT JOIN `product_batches` b ON si.batch_id = b.id
    WHERE si.sale_id = ?
    ORDER BY si.id ASC
", "i", [$saleId]);

// Calculations for Tax Breakdown
$subtotal = (float)$sale['subtotal'];
$discount = (float)$sale['discount'];
$taxTotal = (float)$sale['tax'];
$grandTotal = (float)$sale['grand_total'];
$amountPaid = isset($sale['amount_paid']) ? (float)$sale['amount_paid'] : $grandTotal;
$changeReturned = isset($sale['change_returned']) ? (float)$sale['change_returned'] : 0.00;

$cgstRate = TAX_RATE / 2;
$sgstRate = TAX_RATE / 2;
$cgstAmount = $taxTotal / 2;
$sgstAmount = $taxTotal / 2;

$totalUnits = 0;
foreach ($items as $it) {
    $totalUnits += (int)$it['quantity'];
}

$amountInWords = numberToWordsINR($grandTotal);
$upiId = $systemSettings['store_upi_id'] ?? 'bondhuchol@upi';
$storeName = STORE_NAME;
$storeAddress = $systemSettings['store_address'] ?? '39, Satyen Roy Road, Behala, Kolkata - 700034.';
$storePhone = $systemSettings['store_phone'] ?? '';
$storeGstin = $systemSettings['store_gstin'] ?? '19AAAAA0000A1Z5';
$storeFssai = $systemSettings['store_fssai'] ?? '10019021004321';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill <?= e($sale['invoice_no']) ?> - <?= e($storeName) ?></title>
    
    <!-- Google Fonts: Inter & Roboto Mono for receipts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Roboto+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <style>
        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --text-main: #0f172a;
            --text-sub: #475569;
            --border: #e2e8f0;
            --bg-page: #f1f5f9;
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-main);
            padding-bottom: 3rem;
            -webkit-font-smoothing: antialiased;
        }

        /* Top Non-printing Action Bar */
        .no-print-bar {
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            padding: 0.85rem 1.5rem;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .bar-container {
            max-width: 900px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .btn-group {
            display: inline-flex;
            border-radius: 8px;
            background: #f1f5f9;
            padding: 3px;
        }

        .btn-toggle {
            padding: 0.45rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 600;
            border: none;
            background: transparent;
            color: var(--text-sub);
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            text-decoration: none;
        }

        .btn-toggle.active {
            background: #ffffff;
            color: var(--primary);
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .action-btns {
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.55rem 1.15rem;
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #10b981;
            color: #ffffff;
        }
        .btn-primary:hover {
            background: #059669;
        }

        .btn-secondary {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-secondary:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        /* Container View */
        .bill-render-area {
            max-width: 900px;
            margin: 2rem auto 0;
            padding: 0 1rem;
            display: flex;
            justify-content: center;
        }

        /* =========================================================
           1. THERMAL 80MM RECEIPT STYLING
           ========================================================= */
        .thermal-receipt {
            width: 320px;
            max-width: 100%;
            background: #ffffff;
            padding: 24px 18px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            border-radius: 8px;
            font-family: 'Roboto Mono', monospace, -apple-system, BlinkMacSystemFont;
            font-size: 12px;
            color: #000000;
            line-height: 1.4;
        }

        .thermal-receipt * {
            color: #000000;
        }

        .th-header {
            text-align: center;
            margin-bottom: 12px;
        }

        .th-store-title {
            font-size: 16px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .th-meta {
            font-size: 10.5px;
            line-height: 1.35;
        }

        .th-divider {
            border-top: 1px dashed #000000;
            margin: 10px 0;
        }

        .th-double-divider {
            border-top: 2px solid #000000;
            margin: 10px 0;
        }

        .th-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            margin-bottom: 3px;
        }

        .th-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
            font-size: 11px;
        }

        .th-table th {
            text-align: left;
            border-bottom: 1px dashed #000000;
            border-top: 1px dashed #000000;
            padding: 5px 0;
            font-size: 10.5px;
            text-transform: uppercase;
        }

        .th-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .th-totals-table {
            width: 100%;
            font-size: 11.5px;
            margin-top: 6px;
        }

        .th-totals-table td {
            padding: 2px 0;
        }

        .th-grand-total {
            font-size: 14.5px;
            font-weight: 800;
            border-top: 1px dashed #000000;
            border-bottom: 1px dashed #000000;
            padding: 6px 0;
            margin: 6px 0;
            display: flex;
            justify-content: space-between;
        }

        .th-footer {
            text-align: center;
            margin-top: 14px;
            font-size: 10.5px;
        }

        .th-barcode-box {
            text-align: center;
            margin: 10px 0 6px;
        }

        .barcode-font {
            font-family: monospace;
            font-size: 20px;
            letter-spacing: 4px;
            font-weight: 700;
        }

        /* =========================================================
           2. STANDARD A4 TAX INVOICE STYLING
           ========================================================= */
        .standard-invoice {
            width: 100%;
            max-width: 820px;
            background: #ffffff;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            border-radius: 12px;
            border: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .inv-logo-area h1 {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.35rem;
        }

        .inv-tagline {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }

        .inv-badge-title {
            text-align: right;
        }

        .inv-badge {
            display: inline-block;
            background: #4f46e5;
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 0.35rem 0.85rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .inv-meta-box {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem;
            margin-bottom: 1.5rem;
        }

        .inv-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1.5rem;
        }

        .inv-table th {
            background: #0f172a;
            color: #ffffff;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.65rem 0.75rem;
            text-align: left;
        }

        .inv-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.88rem;
        }

        .inv-table tr:last-child td {
            border-bottom: 2px solid #0f172a;
        }

        .inv-totals-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 2rem;
            margin-bottom: 1.5rem;
        }

        .inv-amount-words {
            background: #f1f5f9;
            border-radius: 8px;
            padding: 1rem;
            font-size: 0.82rem;
        }

        .inv-calc-table {
            width: 100%;
            font-size: 0.9rem;
        }

        .inv-calc-table td {
            padding: 0.35rem 0;
        }

        .inv-grand-row {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            border-top: 2px solid #0f172a;
            padding-top: 0.5rem;
            margin-top: 0.5rem;
        }

        .inv-footer {
            border-top: 1px dashed #cbd5e1;
            padding-top: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            font-size: 0.8rem;
            color: #64748b;
        }

        /* =========================================================
           PRINT MEDIA QUERIES (@media print)
           ========================================================= */
        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print-bar, .no-print {
                display: none !important;
            }

            .bill-render-area {
                margin: 0 !important;
                padding: 0 !important;
                max-width: 100% !important;
            }

            /* For thermal roll printers */
            body.print-mode-thermal .thermal-receipt {
                width: 100% !important;
                max-width: 80mm !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 4mm 2mm !important;
                margin: 0 !important;
            }

            /* For standard A4 printers */
            body.print-mode-standard .standard-invoice {
                width: 100% !important;
                max-width: 100% !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }

            @page {
                margin: 0;
            }
        }
    </style>
</head>
<body class="print-mode-<?= $mode ?>">

    <!-- Interactive Top Bar (Hidden on Print) -->
    <header class="no-print-bar no-print">
        <div class="bar-container">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <a href="<?= BASE_URL ?>/modules/pos/index.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>New Sale (POS)</span>
                </a>
                
                <div class="btn-group">
                    <a href="?id=<?= $saleId ?>&mode=thermal" class="btn-toggle <?= $mode === 'thermal' ? 'active' : '' ?>">
                        <i class="fa-solid fa-receipt"></i>
                        <span>Thermal POS (80mm)</span>
                    </a>
                    <a href="?id=<?= $saleId ?>&mode=standard" class="btn-toggle <?= $mode === 'standard' ? 'active' : '' ?>">
                        <i class="fa-solid fa-file-invoice"></i>
                        <span>A4 Tax Invoice</span>
                    </a>
                </div>
            </div>

            <div class="action-btns">
                <a href="<?= BASE_URL ?>/modules/pos/invoices.php" class="btn btn-secondary btn-sm">
                    <i class="fa-solid fa-list"></i>
                    <span>All Bills</span>
                </a>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fa-solid fa-print"></i>
                    <span>Print Bill Now</span>
                </button>
            </div>
        </div>
    </header>

    <main class="bill-render-area">
        <?php if ($mode === 'thermal'): ?>
            <!-- ========================================================
                 80MM / 58MM THERMAL RECEIPT FORMAT
                 ======================================================== -->
            <div class="thermal-receipt">
                <div class="th-header">
                    <div class="th-store-title"><?= e($storeName) ?></div>
                    <div class="th-meta">
                        <?= e($storeAddress) ?><br>
                        Tel: <?= e($storePhone) ?><br>
                        GSTIN: <?= e($storeGstin) ?> | FSSAI: <?= e($storeFssai) ?>
                    </div>
                </div>

                <div class="th-divider"></div>

                <div class="th-info-row">
                    <span>Invoice: <strong><?= e($sale['invoice_no']) ?></strong></span>
                    <span>Date: <?= date('d/m/y H:i', strtotime($sale['sale_date'])) ?></span>
                </div>
                <div class="th-info-row">
                    <span>Cashier: <?= e($sale['cashier_name'] ?? 'Counter #1') ?></span>
                    <span>Pay: <strong><?= strtoupper(e($sale['payment_method'])) ?></strong></span>
                </div>
                <div class="th-info-row">
                    <span>Customer: <?= e($sale['customer_name']) ?></span>
                    <?php if (!empty($sale['customer_phone'])): ?>
                        <span>Ph: <?= e($sale['customer_phone']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="th-divider"></div>

                <table class="th-table">
                    <thead>
                        <tr>
                            <th style="width: 50%;">Item Description</th>
                            <th style="text-align: center; width: 15%;">Qty</th>
                            <th style="text-align: right; width: 17%;">Rate</th>
                            <th style="text-align: right; width: 18%;">Amt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sl = 1;
                        foreach ($items as $item): 
                        ?>
                            <tr>
                                <td colspan="4" style="font-weight: 700; padding-top: 4px;">
                                    <?= $sl++ ?>. <?= e($item['product_name']) ?>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding-left: 10px; font-size: 10px; color: #333;">
                                    B:<?= e($item['batch_no'] ?? 'N/A') ?> Exp:<?= date('m/y', strtotime($item['expiry_date'])) ?>
                                </td>
                                <td style="text-align: center; font-weight: 600;"><?= $item['quantity'] ?></td>
                                <td style="text-align: right;"><?= number_format($item['unit_price'], 2) ?></td>
                                <td style="text-align: right; font-weight: 700;"><?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="th-divider"></div>

                <table class="th-totals-table">
                    <tr>
                        <td>Total Items / Qty:</td>
                        <td style="text-align: right; font-weight: 700;"><?= count($items) ?> / <?= $totalUnits ?></td>
                    </tr>
                    <tr>
                        <td>Subtotal:</td>
                        <td style="text-align: right;"><?= formatCurrency($subtotal) ?></td>
                    </tr>
                    <?php if ($discount > 0): ?>
                        <tr>
                            <td>Discount Applied:</td>
                            <td style="text-align: right;">-<?= formatCurrency($discount) ?></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <td>CGST (<?= $cgstRate ?>%):</td>
                        <td style="text-align: right;"><?= formatCurrency($cgstAmount) ?></td>
                    </tr>
                    <tr>
                        <td>SGST (<?= $sgstRate ?>%):</td>
                        <td style="text-align: right;"><?= formatCurrency($sgstAmount) ?></td>
                    </tr>
                </table>

                <div class="th-grand-total">
                    <span>GRAND TOTAL</span>
                    <span><?= formatCurrency($grandTotal) ?></span>
                </div>

                <table class="th-totals-table">
                    <tr>
                        <td>Payment Mode:</td>
                        <td style="text-align: right; font-weight: 700; text-transform: uppercase;"><?= e($sale['payment_method']) ?></td>
                    </tr>
                    <?php if ($sale['payment_method'] === 'cash'): ?>
                        <tr>
                            <td>Cash Tendered:</td>
                            <td style="text-align: right;"><?= formatCurrency($amountPaid) ?></td>
                        </tr>
                        <tr>
                            <td>Change Returned:</td>
                            <td style="text-align: right; font-weight: 700;"><?= formatCurrency($changeReturned) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>

                <div class="th-barcode-box">
                    <div style="font-size: 9px; letter-spacing: 2px;">*<?= e($sale['invoice_no']) ?>*</div>
                    <div class="barcode-font">||||| | |||| |||| |||</div>
                </div>

                <div class="th-footer">
                    <p style="font-weight: 700;">Thank You! Please Visit Again</p>
                    <p><?= e($systemSettings['store_tagline'] ?? 'Quality Dairy, Cold Beverages & Chocolates') ?></p>
                    <p style="font-size: 9.5px; margin-top: 4px;">For feedback: <?= e($systemSettings['store_email'] ?? 'contact@bondhuchol.com') ?></p>
                </div>
            </div>

        <?php else: ?>
            <!-- ========================================================
                 A4 / A5 STANDARD TAX INVOICE FORMAT
                 ======================================================== -->
            <div class="standard-invoice">
                <div class="inv-header">
                    <div class="inv-logo-area">
                        <h1><?= e($storeName) ?></h1>
                        <div class="inv-tagline"><?= e($systemSettings['store_tagline'] ?? 'Fresh Dairy & Confectionery') ?></div>
                        <p style="font-size: 0.85rem; color: #475569; line-height: 1.4;">
                            <?= e($storeAddress) ?><br>
                            Phone: <strong><?= e($storePhone) ?></strong> | Email: <?= e($systemSettings['store_email'] ?? '') ?><br>
                            <strong>GSTIN:</strong> <?= e($storeGstin) ?> | <strong>FSSAI:</strong> <?= e($storeFssai) ?>
                        </p>
                    </div>

                    <div class="inv-badge-title">
                        <span class="inv-badge">Retail Tax Invoice</span>
                        <div style="font-size: 0.82rem; color: #64748b; margin-top: 0.25rem;">(Original for Recipient)</div>
                        <div style="font-size: 1.1rem; font-weight: 800; color: #0f172a; margin-top: 0.5rem;">
                            # <?= e($sale['invoice_no']) ?>
                        </div>
                    </div>
                </div>

                <div class="inv-meta-box">
                    <div>
                        <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b;">Billed To:</span>
                        <div style="font-size: 1rem; font-weight: 700; color: #0f172a; margin-top: 0.2rem;"><?= e($sale['customer_name']) ?></div>
                        <?php if (!empty($sale['customer_phone'])): ?>
                            <div style="color: #475569; font-size: 0.85rem;">Phone: <?= e($sale['customer_phone']) ?></div>
                        <?php endif; ?>
                        <div style="color: #64748b; font-size: 0.82rem;">Place of Supply: State Code (24)</div>
                    </div>

                    <div style="text-align: right;">
                        <span style="font-size: 0.75rem; text-transform: uppercase; font-weight: 700; color: #64748b;">Invoice Metadata:</span>
                        <div style="font-size: 0.88rem; color: #0f172a; margin-top: 0.2rem;">
                            <strong>Date & Time:</strong> <?= formatDateTime($sale['sale_date']) ?>
                        </div>
                        <div style="font-size: 0.88rem; color: #0f172a;">
                            <strong>Cashier / Terminal:</strong> <?= e($sale['cashier_name'] ?? 'Main Terminal') ?>
                        </div>
                        <div style="font-size: 0.88rem; color: #0f172a;">
                            <strong>Payment Mode:</strong> <span style="text-transform: uppercase; font-weight: 700; color: var(--primary);"><?= e($sale['payment_method']) ?></span>
                        </div>
                    </div>
                </div>

                <table class="inv-table">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 40%;">Item Description</th>
                            <th style="width: 18%;">Batch & Expiry</th>
                            <th style="text-align: center; width: 10%;">Qty</th>
                            <th style="text-align: right; width: 12%;">Unit Rate</th>
                            <th style="text-align: right; width: 15%;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sl = 1;
                        foreach ($items as $item): 
                        ?>
                            <tr>
                                <td><?= $sl++ ?></td>
                                <td>
                                    <strong><?= e($item['product_name']) ?></strong><br>
                                    <small style="color: #64748b;">SKU: <?= e($item['sku']) ?><?= !empty($item['barcode']) ? ' | Barcode: ' . e($item['barcode']) : '' ?></small>
                                </td>
                                <td>
                                    <span style="font-weight: 600;"><?= e($item['batch_no'] ?? 'N/A') ?></span><br>
                                    <small style="color: #64748b;">Exp: <?= formatDate($item['expiry_date'], 'd M Y') ?></small>
                                </td>
                                <td style="text-align: center; font-weight: 700;"><?= $item['quantity'] ?> <?= e($item['unit']) ?></td>
                                <td style="text-align: right;"><?= formatCurrency($item['unit_price']) ?></td>
                                <td style="text-align: right; font-weight: 700;"><?= formatCurrency($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="inv-totals-grid">
                    <div>
                        <div class="inv-amount-words">
                            <strong style="color: #0f172a; display: block; margin-bottom: 0.25rem;">Amount in Words:</strong>
                            <span style="font-style: italic; color: #334155;"><?= e($amountInWords) ?></span>
                        </div>

                        <div style="margin-top: 1rem; font-size: 0.8rem; color: #64748b; background: #fff; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 0.75rem;">
                            <strong>UPI Payment QR:</strong> Scan & Pay to <strong><?= e($upiId) ?></strong> via GPay, PhonePe, Paytm or BHIM.
                        </div>
                    </div>

                    <div>
                        <table class="inv-calc-table">
                            <tr>
                                <td>Subtotal (Taxable Value):</td>
                                <td style="text-align: right; font-weight: 600;"><?= formatCurrency($subtotal) ?></td>
                            </tr>
                            <?php if ($discount > 0): ?>
                                <tr style="color: #059669;">
                                    <td>Trade Discount:</td>
                                    <td style="text-align: right;">-<?= formatCurrency($discount) ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td>CGST (<?= $cgstRate ?>%):</td>
                                <td style="text-align: right;"><?= formatCurrency($cgstAmount) ?></td>
                            </tr>
                            <tr>
                                <td>SGST (<?= $sgstRate ?>%):</td>
                                <td style="text-align: right;"><?= formatCurrency($sgstAmount) ?></td>
                            </tr>
                            <tr class="inv-grand-row">
                                <td>Total Amount Payable:</td>
                                <td style="text-align: right;"><?= formatCurrency($grandTotal) ?></td>
                            </tr>
                            <?php if ($sale['payment_method'] === 'cash'): ?>
                                <tr style="font-size: 0.85rem; color: #475569;">
                                    <td>Cash Received:</td>
                                    <td style="text-align: right;"><?= formatCurrency($amountPaid) ?></td>
                                </tr>
                                <tr style="font-size: 0.85rem; color: #059669; font-weight: 700;">
                                    <td>Change Returned:</td>
                                    <td style="text-align: right;"><?= formatCurrency($changeReturned) ?></td>
                                </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>

                <div class="inv-footer">
                    <div style="max-width: 60%;">
                        <strong style="color: #0f172a; display: block; margin-bottom: 0.2rem;">Terms & Conditions:</strong>
                        <ol style="padding-left: 1.2rem; line-height: 1.4;">
                            <li>Goods once sold can be exchanged within 48 hours with original bill.</li>
                            <li>Perishable dairy items (Milk/Curd/Paneer) must be refrigerated under 4°C immediately.</li>
                        </ol>
                    </div>

                    <div style="text-align: center; min-width: 180px;">
                        <div style="height: 45px;"></div>
                        <div style="border-top: 1px solid #0f172a; padding-top: 0.25rem; font-weight: 700; color: #0f172a;">
                            Authorized Signatory
                        </div>
                        <small style="color: #64748b;"><?= e($storeName) ?></small>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php if ($autoPrint): ?>
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                setTimeout(() => {
                    window.print();
                }, 350);
            });
        </script>
    <?php endif; ?>

</body>
</html>
