<?php
/**
 * Point of Sale (POS) & Retail Billing Terminal
 * Professional High-Performance Billing Engine
 * Tailored for Amul Dairy, Ice Creams, Cold Drinks, and Cadbury Confectionery
 */

$pageTitle = 'POS Billing Terminal';
require_once __DIR__ . '/../../includes/header.php';

$db = Database::getInstance();

// Fetch categories with active product counts
$categories = $db->fetchAll("
    SELECT c.*, COUNT(p.id) as product_count
    FROM `categories` c
    LEFT JOIN `products` p ON c.id = p.category_id AND p.status = 'active'
    GROUP BY c.id, c.name, c.description, c.created_at
    ORDER BY c.id ASC
");

// Fetch active products with available FEFO batches (nearest expiry first)
$products = $db->fetchAll("
    SELECT 
        p.id as product_id,
        p.category_id,
        c.name as category_name,
        p.name as product_name,
        p.sku,
        p.barcode,
        p.unit,
        p.min_stock_alert,
        b.id as batch_id,
        b.batch_no,
        b.expiry_date,
        b.purchase_price,
        b.selling_price,
        b.current_quantity
    FROM `product_batches` b
    JOIN `products` p ON b.product_id = p.id
    LEFT JOIN `categories` c ON p.category_id = c.id
    WHERE p.status = 'active' AND b.current_quantity > 0 AND b.expiry_date >= CURDATE()
    ORDER BY c.id ASC, p.name ASC, b.expiry_date ASC
");

// Group available items for frontend (one card per product with earliest FEFO batch)
$availableItems = [];
$seenProducts = [];
foreach ($products as $p) {
    $prodId = (int)$p['product_id'];
    $sellingPrice = (float)($p['selling_price'] > 0 ? $p['selling_price'] : ($p['default_selling_price'] ?? 0));
    
    if (!isset($seenProducts[$prodId])) {
        $seenProducts[$prodId] = true;
        $availableItems[] = [
            'id' => $prodId,
            'batch_id' => (int)$p['batch_id'],
            'batch_no' => $p['batch_no'],
            'expiry_date' => $p['expiry_date'],
            'name' => $p['product_name'],
            'sku' => $p['sku'],
            'barcode' => $p['barcode'],
            'unit' => $p['unit'],
            'min_stock' => (int)$p['min_stock_alert'],
            'price' => $sellingPrice,
            'cost_price' => (float)$p['purchase_price'],
            'stock' => (int)$p['current_quantity'],
            'category_id' => (int)$p['category_id'],
            'category_name' => $p['category_name'] ?? 'General'
        ];
    }
}

$totalProductsCount = count($availableItems);
?>

<div class="pos-container-wrapper">
    <!-- Top Action Ribbon -->
    <div class="pos-top-ribbon">
        <div class="pos-ribbon-left">
            <div class="pos-badge-live" title="POS Register Online and Ready">
                <span class="live-dot"></span>
                <span class="live-text">Register Active</span>
            </div>
            <div class="pos-clock" id="posLiveClock" title="System Time">
                <i class="fa-regular fa-clock"></i>
                <span id="liveTimeStr">--:--:--</span>
            </div>
            <div class="pos-cashier-badge" title="Active Cashier">
                <i class="fa-solid fa-user-check"></i>
                <span><?= e($currentUser['full_name'] ?? 'Cashier') ?> (<?= ucfirst(e($currentUser['role'] ?? 'Staff')) ?>)</span>
            </div>
            <div class="pos-store-name" title="Store Name">
                <i class="fa-solid fa-store"></i>
                <span><?= e(STORE_NAME) ?></span>
            </div>
        </div>

        <div class="pos-ribbon-right">
            <button type="button" class="btn btn-secondary btn-sm pos-ribbon-btn" onclick="holdCurrentBill()" title="Hold current cart to serve another customer">
                <i class="fa-solid fa-pause"></i>
                <span>Hold Bill</span>
            </button>
            <button type="button" class="btn btn-secondary btn-sm pos-ribbon-btn" onclick="openHeldBillsModal()" id="heldBillsBtn" title="View Parked / Held Bills">
                <i class="fa-solid fa-folder-open"></i>
                <span>Held Bills</span>
                <span class="badge badge-primary held-count-pill" id="heldBillsCount" style="display: none;">0</span>
            </button>
            <a href="<?= BASE_URL ?>/modules/pos/invoices.php" class="btn btn-secondary btn-sm pos-ribbon-btn" title="View past bills & reprint">
                <i class="fa-solid fa-clock-rotate-left"></i>
                <span>Sales History</span>
            </a>
            <button type="button" class="btn btn-outline-danger btn-sm pos-ribbon-btn" onclick="clearCart()" title="Clear cart (Esc)">
                <i class="fa-solid fa-trash-can"></i>
                <span>Clear</span>
            </button>
        </div>
    </div>

    <!-- Main POS Layout: 2 Columns -->
    <div class="pos-layout">
        <!-- Left Column: Catalog, Search, and Category Pills -->
        <div class="pos-products-panel">
            <div class="pos-search-header">
                <!-- Search & Barcode Scan Input -->
                <div class="pos-search-box">
                    <div class="search-input-wrapper">
                        <i class="fa-solid fa-barcode barcode-scan-icon" title="Barcode Scanner Ready"></i>
                        <input type="text" id="posSearchInput" class="form-control pos-search-input" placeholder="Scan Barcode (e.g. 890126...) or search Milk, Ice Cream, Cold Drinks, Cadbury... [F2]" autofocus autocomplete="off">
                        <span class="search-shortcut-badge" title="Press F2 to focus">F2</span>
                        <button type="button" class="search-clear-btn" id="posSearchClear" style="display: none;" onclick="clearPosSearch()" title="Clear search">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>

                <!-- Category Filter Pills -->
                <div class="category-pills-bar">
                    <button type="button" class="category-pill active" data-category="all">
                        <i class="fa-solid fa-border-all"></i>
                        <span>All Items</span>
                        <span class="pill-count"><?= $totalProductsCount ?></span>
                    </button>
                    <?php 
                    $catIcons = [
                        1 => 'fa-bottle-droplet',     // Milk & Dairy
                        2 => 'fa-ice-cream',          // Ice Cream
                        3 => 'fa-champagne-glasses',  // Cold Drinks
                        4 => 'fa-cookie-bite'          // Cadbury Chocolates
                    ];
                    foreach ($categories as $cat): 
                        $catIcon = $catIcons[$cat['id']] ?? 'fa-tag';
                    ?>
                        <button type="button" class="category-pill" data-category="<?= $cat['id'] ?>">
                            <i class="fa-solid <?= $catIcon ?>"></i>
                            <span><?= e($cat['name']) ?></span>
                            <span class="pill-count"><?= $cat['product_count'] ?? 0 ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Products Grid Viewport -->
            <div class="pos-product-grid" id="posProductGrid">
                <?php if (!empty($availableItems)): ?>
                    <?php foreach ($availableItems as $item): 
                        $expInfo = getExpiryStatus($item['expiry_date']);
                        $isLowStock = $item['stock'] <= $item['min_stock'];
                        
                        // Category theme styling
                        $catClass = 'cat-dairy';
                        $catIcon = 'fa-bottle-droplet';
                        if ($item['category_id'] == 2) {
                            $catClass = 'cat-icecream';
                            $catIcon = 'fa-ice-cream';
                        } elseif ($item['category_id'] == 3) {
                            $catClass = 'cat-drinks';
                            $catIcon = 'fa-champagne-glasses';
                        } elseif ($item['category_id'] == 4) {
                            $catClass = 'cat-chocolate';
                            $catIcon = 'fa-cookie-bite';
                        }
                    ?>
                        <div class="pos-product-card <?= $catClass ?>" 
                             data-id="<?= $item['id'] ?>"
                             data-name="<?= e($item['name']) ?>"
                             data-sku="<?= e($item['sku']) ?>"
                             data-barcode="<?= e($item['barcode']) ?>"
                             data-category-id="<?= $item['category_id'] ?>"
                             data-product="<?= htmlspecialchars(json_encode($item), ENT_QUOTES, 'UTF-8') ?>"
                             onclick="addToCart(this.getAttribute('data-product'))"
                             title="Click to add <?= e($item['name']) ?> to bill">
                            
                            <div class="pos-card-top">
                                <span class="pos-cat-tag">
                                    <i class="fa-solid <?= $catIcon ?>"></i> <?= e($item['category_name']) ?>
                                </span>
                                <span class="pos-stock-badge <?= $isLowStock ? 'low-stock' : 'in-stock' ?>">
                                    <i class="fa-solid <?= $isLowStock ? 'fa-triangle-exclamation' : 'fa-check' ?>"></i>
                                    <?= $item['stock'] ?> <?= e($item['unit']) ?>
                                </span>
                            </div>

                            <div class="pos-product-name" title="<?= e($item['name']) ?>">
                                <?= e($item['name']) ?>
                            </div>

                            <div class="pos-card-bottom">
                                <div class="pos-product-price-wrapper">
                                    <span class="pos-unit-price-label">Price per Unit</span>
                                    <div class="pos-price-num-wrap">
                                        <span class="pos-product-price"><?= formatCurrency($item['price']) ?></span>
                                        <span class="pos-unit-tag">/ <?= e($item['unit']) ?></span>
                                    </div>
                                </div>
                            </div>

                            <div class="pos-card-hover-overlay">
                                <span><i class="fa-solid fa-cart-plus"></i> Add Item</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="pos-no-products">
                        <i class="fa-solid fa-boxes-packing"></i>
                        <h4>No available in-stock items found</h4>
                        <p>Add product batches with available stock in the inventory module.</p>
                        <a href="<?= BASE_URL ?>/modules/batches/restock.php" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-plus"></i> Restock Batches Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Quick Scroll Navigation Toolbar for Catalog Menu -->
            <div class="catalog-scroll-bar">
                <div class="catalog-scroll-hint">
                    <i class="fa-solid fa-arrow-down-short-wide"></i>
                    <span>Scroll to browse full menu</span>
                </div>
                <div class="catalog-scroll-buttons">
                    <button type="button" class="btn btn-outline-secondary btn-sm catalog-nav-btn" onclick="scrollCatalog('up')" title="Scroll to Top of Menu">
                        <i class="fa-solid fa-arrow-up"></i> <span>Top</span>
                    </button>
                    <button type="button" class="btn btn-primary btn-sm catalog-nav-btn" onclick="scrollCatalog('down')" title="Scroll Down Menu">
                        <i class="fa-solid fa-arrow-down"></i> <span>Scroll Down</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: Cart & Fixed Bottom Billing Terminal -->
        <div class="pos-cart-panel" id="posCartPanel">
            <!-- 1. Cart Header -->
            <div class="pos-cart-header">
                <div class="pos-header-left">
                    <div class="pos-register-icon">
                        <i class="fa-solid fa-cart-shopping"></i>
                    </div>
                    <div>
                        <h3 class="pos-panel-title">Current Bill</h3>
                        <span class="pos-panel-subtitle" id="cartItemCount">0 Items (0 Units)</span>
                    </div>
                </div>
                <div class="pos-header-right">
                    <button type="button" class="btn btn-outline-primary btn-sm pos-header-action-btn" onclick="openInvoicePreviewModal()" title="Inspect Full Bill Details [F7]">
                        <i class="fa-solid fa-magnifying-glass-dollar"></i>
                        <span>Inspect [F7]</span>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm pos-header-action-btn" onclick="clearCart()" title="Clear Entire Cart [Esc]">
                        <i class="fa-solid fa-trash-can"></i>
                        <span>Clear</span>
                    </button>
                </div>
            </div>

            <!-- 2. Dedicated Cart Items Viewport -->
            <div class="pos-cart-items-container" id="posItemsViewportWrapper">
                <div class="pos-cart-items-list" id="posCartItems">
                    <div class="pos-empty-cart">
                        <div class="empty-cart-icon-wrap">
                            <i class="fa-solid fa-cart-shopping"></i>
                        </div>
                        <h4>Your Cart is Empty</h4>
                        <p>Scan a barcode or click products from the catalog on the left to start billing.</p>
                        <div class="empty-cart-shortcuts">
                            <span><kbd>F2</kbd> Search / Scan</span>
                            <span><kbd>F4</kbd> Cash Tender</span>
                            <span><kbd>F8</kbd> Quick Print</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 3. Fixed Docked Billing & Checkout Section -->
            <div class="pos-compact-billing-section" id="posBillingViewportWrapper">
                <form action="<?= BASE_URL ?>/modules/pos/process_sale.php" method="POST" id="checkoutForm" class="pos-billing-form">
                    <input type="hidden" name="csrf_token" value="<?= e(getCsrfToken()) ?>">
                    <input type="hidden" name="cart_data" id="cartDataInput" value="[]">
                    <input type="hidden" name="print_action" id="printActionInput" value="thermal">
                    <input type="hidden" name="discount" id="cartDiscountFinal" value="0.00">
                    <span id="grandTotalInWords" style="display: none;">Zero Rupees Only</span>
                    <span id="itemsCountBadge" style="display: none;">0</span>
                    <span id="miniPayableTotal" style="display: none;"><?= formatCurrency(0) ?></span>

                    <!-- Row 1: Customer Details Input -->
                    <div class="pos-customer-compact-bar">
                        <div class="pos-input-group-compact cust-name-group">
                            <i class="fa-solid fa-user"></i>
                            <input type="text" name="customer_name" id="customerNameInput" class="pos-compact-field" placeholder="Customer Name" value="Walk-in Customer" autocomplete="off">
                        </div>
                        <div class="pos-input-group-compact cust-phone-group">
                            <i class="fa-solid fa-phone"></i>
                            <input type="tel" name="customer_phone" id="customerPhoneInput" class="pos-compact-field" placeholder="Phone (Optional)" autocomplete="off">
                        </div>
                    </div>

                    <!-- Row 2: Financial Breakdown Grid -->
                    <div class="pos-financial-compact-grid">
                        <div class="fin-col">
                            <span class="fin-label">Subtotal</span>
                            <span class="fin-val" id="cartSubtotal"><?= formatCurrency(0) ?></span>
                        </div>
                        <div class="fin-col">
                            <div class="fin-label-row">
                                <span class="fin-label">Discount</span>
                                <div class="disc-mini-toggle">
                                    <button type="button" class="disc-btn active" id="discTypeAmt" onclick="setDiscountType('amt')"><?= e(CURRENCY_SYMBOL) ?></button>
                                    <button type="button" class="disc-btn" id="discTypePct" onclick="setDiscountType('pct')">%</button>
                                </div>
                            </div>
                            <input type="number" step="0.01" min="0" name="discount_value" id="cartDiscountInput" class="fin-disc-input" value="0.00">
                        </div>
                        <div class="fin-col">
                            <span class="fin-label">GST 5%</span>
                            <span class="fin-val" id="cartTax"><?= formatCurrency(0) ?></span>
                        </div>
                        <div class="fin-col fin-total-col">
                            <span class="fin-total-label">Payable Amount</span>
                            <span class="fin-total-amount" id="cartGrandTotal"><?= formatCurrency(0) ?></span>
                        </div>
                    </div>

                    <!-- Row 3: Payment Method Segmented Tabs -->
                    <div class="pos-payment-mode-tabs">
                        <label class="pay-tab-pill">
                            <input type="radio" name="payment_method" value="cash" checked onchange="handlePaymentMethodChange('cash')">
                            <span class="pay-tab-content">
                                <i class="fa-solid fa-money-bill-wave"></i>
                                <span>Cash [1]</span>
                            </span>
                        </label>
                        <label class="pay-tab-pill">
                            <input type="radio" name="payment_method" value="upi" onchange="handlePaymentMethodChange('upi')">
                            <span class="pay-tab-content">
                                <i class="fa-solid fa-qrcode"></i>
                                <span>UPI / QR [2]</span>
                            </span>
                        </label>
                        <label class="pay-tab-pill">
                            <input type="radio" name="payment_method" value="card" onchange="handlePaymentMethodChange('card')">
                            <span class="pay-tab-content">
                                <i class="fa-solid fa-credit-card"></i>
                                <span>Card [3]</span>
                            </span>
                        </label>
                        <label class="pay-tab-pill">
                            <input type="radio" name="payment_method" value="credit" onchange="handlePaymentMethodChange('credit')">
                            <span class="pay-tab-content">
                                <i class="fa-solid fa-book-bookmark"></i>
                                <span>Khata [4]</span>
                            </span>
                        </label>
                    </div>

                    <!-- Row 4: Payment Details Area -->
                    <div class="pos-payment-compact-panels">
                        <!-- Cash Payment Panel -->
                        <div id="payPanelCash" class="pay-subpanel">
                            <div class="cash-compact-row">
                                <div class="cash-input-wrap">
                                    <span class="cash-currency-prefix"><?= e(CURRENCY_SYMBOL) ?></span>
                                    <input type="number" step="0.01" min="0" name="amount_paid" id="cashTenderedInput" class="cash-field" placeholder="Cash Tendered [F4]">
                                </div>
                                <div class="cash-quick-notes">
                                    <button type="button" class="quick-note-pill" onclick="setCashTendered('exact')">Exact</button>
                                    <button type="button" class="quick-note-pill" onclick="setCashTendered(100)">₹100</button>
                                    <button type="button" class="quick-note-pill" onclick="setCashTendered(200)">₹200</button>
                                    <button type="button" class="quick-note-pill" onclick="setCashTendered(500)">₹500</button>
                                    <button type="button" class="quick-note-pill" onclick="setCashTendered(2000)">₹2k</button>
                                </div>
                                <div class="cash-change-pill">
                                    <span class="change-lbl">Change:</span>
                                    <strong class="change-val" id="cashChangeDisplay"><?= formatCurrency(0) ?></strong>
                                    <input type="hidden" name="change_returned" id="cashChangeInput" value="0.00">
                                </div>
                            </div>
                        </div>

                        <!-- UPI QR Code Panel -->
                        <div id="payPanelUpi" class="pay-subpanel" style="display: none;">
                            <div class="upi-compact-row">
                                <div class="upi-qr-mini">
                                    <img id="dynamicUpiQrImg" src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=upi://pay?pa=<?= urlencode($systemSettings['store_upi_id'] ?? 'amuldairyexpress@upi') ?>%26pn=<?= urlencode(STORE_NAME) ?>%26cu=INR" alt="UPI QR">
                                    <span class="upi-qr-badge" id="upiAmountPill"><?= formatCurrency(0) ?></span>
                                </div>
                                <div class="upi-compact-info">
                                    <div class="upi-store-line">
                                        <i class="fa-solid fa-building-columns"></i>
                                        <span id="storeUpiText"><?= e($systemSettings['store_upi_id'] ?? 'amuldairyexpress@upi') ?></span>
                                        <button type="button" onclick="copyUpiId()" class="copy-upi-btn" title="Copy UPI ID"><i class="fa-regular fa-copy"></i> Copy</button>
                                    </div>
                                    <input type="text" name="upi_ref_no" id="upiRefInput" class="pos-compact-field" placeholder="UTR / Txn Ref No. (Optional)" autocomplete="off">
                                </div>
                            </div>
                        </div>

                        <!-- Card Payment Panel -->
                        <div id="payPanelCard" class="pay-subpanel" style="display: none;">
                            <div class="card-compact-row">
                                <input type="text" name="card_last4" class="pos-compact-field" placeholder="Card Last 4 Digits" maxlength="4" style="flex: 1;">
                                <input type="text" name="card_auth_code" class="pos-compact-field" placeholder="Approval / Auth Code" style="flex: 1.5;">
                            </div>
                        </div>

                        <!-- Khata / Credit Panel -->
                        <div id="payPanelCredit" class="pay-subpanel" style="display: none;">
                            <div class="khata-compact-row">
                                <input type="text" name="credit_notes" class="pos-compact-field" placeholder="Customer Khata Ledger Notes / Due Date" style="width: 100%;">
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Action Print & Checkout Buttons -->
                    <div class="pos-action-buttons-bar">
                        <button type="button" id="btnThermalPrint" class="btn-pay-main" onclick="submitPOSSale('thermal')" disabled>
                            <i class="fa-solid fa-bolt"></i>
                            <span id="mainBtnText">Quick Bill & Print Thermal [F8]</span>
                        </button>
                        <div class="pos-subactions-row">
                            <button type="button" id="btnA4Print" class="btn-pay-sub" onclick="submitPOSSale('standard')" disabled>
                                <i class="fa-solid fa-print"></i>
                                <span>A4 Invoice [F9]</span>
                            </button>
                            <button type="button" id="btnSaveOnly" class="btn-pay-sub" onclick="submitPOSSale('save')" disabled>
                                <i class="fa-solid fa-floppy-disk"></i>
                                <span>Save Only [F10]</span>
                            </button>
                        </div>
                    </div>

                    <!-- Keyboard Shortcut Hint Bar -->
                    <div class="pos-shortcuts-bar">
                        <span><kbd>F2</kbd> Search</span>
                        <span><kbd>F4</kbd> Cash</span>
                        <span><kbd>F7</kbd> Inspect</span>
                        <span><kbd>F8</kbd> Thermal</span>
                        <span><kbd>F9</kbd> A4</span>
                        <span><kbd>Esc</kbd> Clear</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: In-Terminal Bill Completed / Instant Print Modal -->
<div class="pos-modal-backdrop" id="saleSuccessModal">
    <div class="sale-success-card">
        <div class="sale-success-header">
            <div class="sale-success-icon">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <h3>Payment Successful!</h3>
            <p id="successModalInvoiceNo">Invoice #INV-20260928-XXXX</p>
        </div>
        <div class="sale-success-body">
            <table class="sale-meta-grid">
                <tr>
                    <td class="meta-lbl">Customer:</td>
                    <td class="meta-val" id="successModalCustomer">Walk-in Customer</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Payment Mode:</td>
                    <td class="meta-val pay-mode-highlight" id="successModalPayMethod">CASH</td>
                </tr>
                <tr>
                    <td class="meta-lbl">Items Billed:</td>
                    <td class="meta-val" id="successModalItems">0 Items</td>
                </tr>
                <tr class="meta-total-row">
                    <td>Total Paid:</td>
                    <td id="successModalTotal">₹0.00</td>
                </tr>
            </table>

            <div class="sale-success-actions">
                <div class="reprint-btn-row">
                    <button type="button" class="btn btn-primary" id="btnModalPrintThermal" onclick="reprintFromModal('thermal')">
                        <i class="fa-solid fa-receipt"></i> Print Thermal (80mm)
                    </button>
                    <button type="button" class="btn btn-secondary" id="btnModalPrintA4" onclick="reprintFromModal('standard')">
                        <i class="fa-solid fa-print"></i> A4 Invoice
                    </button>
                </div>
                <button type="button" class="btn btn-success new-sale-btn" onclick="startNewBillAfterSuccess()">
                    <i class="fa-solid fa-plus"></i> New Sale / Next Customer (Enter)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 2: Pre-Sale Bill Inspection & Product Details Verification Modal (F7) -->
<div class="pos-modal-backdrop" id="billPreviewModal">
    <div class="pos-modal-card modal-card-large">
        <div class="pos-modal-header">
            <div>
                <h3>
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    Pre-Invoice Product & Details Inspection
                </h3>
                <small>Verify item details, quantities, rates and batches before payment</small>
            </div>
            <button type="button" class="pos-modal-close" onclick="closeInvoicePreviewModal()">&times;</button>
        </div>
        <div class="pos-modal-body">
            <div class="preview-header-bar">
                <div>
                    <span>Customer:</span> 
                    <strong id="previewModalCustomer">Walk-in Customer</strong>
                </div>
                <div>
                    <span>Payment Mode:</span>
                    <strong id="previewModalPayMode">CASH</strong>
                </div>
                <div>
                    <span>Total Items:</span>
                    <strong id="previewModalItemsCount">0 Items</strong>
                </div>
            </div>

            <!-- Detailed Items Table -->
            <div class="preview-table-container">
                <table class="table preview-table">
                    <thead>
                        <tr>
                            <th style="width: 35px;">#</th>
                            <th>Product Name</th>
                            <th>SKU Code</th>
                            <th style="text-align: center;">Qty</th>
                            <th style="text-align: right;">Price</th>
                            <th style="text-align: right;">Total Amount</th>
                        </tr>
                    </thead>
                    <tbody id="previewModalTableBody">
                        <!-- Populated dynamically via pos.js -->
                    </tbody>
                </table>
            </div>

            <!-- Financial Summary Box -->
            <div class="preview-financial-box">
                <div class="preview-fin-row">
                    <span>Subtotal:</span>
                    <strong id="previewModalSubtotal">₹0.00</strong>
                </div>
                <div class="preview-fin-row">
                    <span>Discount:</span>
                    <strong style="color: var(--success);" id="previewModalDiscount">₹0.00</strong>
                </div>
                <div class="preview-fin-row">
                    <span>GST Tax (<?= TAX_RATE ?>%):</span>
                    <strong id="previewModalTax">₹0.00</strong>
                </div>
                <div class="preview-fin-total-row">
                    <span>Grand Total:</span>
                    <span id="previewModalGrandTotal">₹0.00</span>
                </div>
            </div>
        </div>
        <div class="pos-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeInvoicePreviewModal()">
                <i class="fa-solid fa-arrow-left"></i> Back to Editing
            </button>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="btn btn-primary btn-sm" onclick="closeInvoicePreviewModal(); submitPOSSale('thermal');">
                    <i class="fa-solid fa-receipt"></i> Pay & Print Thermal
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal 3: Parked / Held Bills Modal -->
<div class="pos-modal-backdrop" id="heldBillsModal">
    <div class="pos-modal-card">
        <div class="pos-modal-header">
            <h3><i class="fa-solid fa-folder-open"></i> Parked / Held Bills</h3>
            <button type="button" class="pos-modal-close" onclick="closeHeldBillsModal()">&times;</button>
        </div>
        <div class="pos-modal-body" id="heldBillsList">
            <!-- Dynamically populated via pos.js -->
        </div>
        <div class="pos-modal-footer">
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeHeldBillsModal()">Close</button>
        </div>
    </div>
</div>

<!-- Hidden iframe for Silent/Direct Printing without reloading the page -->
<iframe id="posPrintIframe" style="display: none; position: absolute; width: 0; height: 0; border: none;"></iframe>

<script>
    window.APP_CURRENCY = '<?= e(CURRENCY_SYMBOL) ?>';
    window.APP_TAX_RATE = <?= (float)TAX_RATE ?>;
    window.STORE_NAME = '<?= e(STORE_NAME) ?>';
    window.STORE_UPI_ID = '<?= e($systemSettings['store_upi_id'] ?? 'bondhuchol@upi') ?>';
    window.BASE_URL = '<?= e(BASE_URL) ?>';
</script>

<?php 
$extraScripts = [BASE_URL . '/assets/js/pos.js'];
include INCLUDES_PATH . '/footer.php'; 
?>
