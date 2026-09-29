/**
 * Master Point of Sale (POS) & Billing Terminal Engine
 * Supports: 
 * - Barcode scanning & live search
 * - Direct Print options for Cash, UPI, Card, and Khata
 * - Live Dynamic UPI QR Code with pre-filled amount
 * - Cash Tendered & Change Due Calculator
 * - Zero-reload Silent/Instant Thermal (80mm) & A4 In-Terminal Printing
 * - Parked / Held Bills Management
 * - High-speed Keyboard Shortcuts
 */

let cart = [];
let discountType = 'amt'; // 'amt' or 'pct'
let activePaymentMethod = 'cash';
let lastCompletedSale = null;

let rawCurrency = (typeof window.APP_CURRENCY === 'string' && window.APP_CURRENCY.trim()) ? window.APP_CURRENCY.trim() : '₹';
if (/[0-9]/.test(rawCurrency) || rawCurrency === '$' || rawCurrency.length > 4) {
    rawCurrency = '₹';
}
const currencySymbol = rawCurrency;
const taxRate = parseFloat(window.APP_TAX_RATE || 5.0);
const storeUpiId = window.STORE_UPI_ID || 'bondhuchol@upi';
const storeName = window.STORE_NAME || 'Bondhu Chol';

document.addEventListener('DOMContentLoaded', () => {
    initPOS();
    initClock();
    updateHeldBillsCountBadge();
    handlePaymentMethodChange('cash');
});

/**
 * Initialize POS Event Listeners
 */
function initPOS() {
    const posSearchInput = document.getElementById('posSearchInput');
    const posSearchClear = document.getElementById('posSearchClear');

    // 1. Search filter for POS products
    if (posSearchInput) {
        posSearchInput.addEventListener('input', (e) => {
            const val = e.target.value;
            if (posSearchClear) {
                posSearchClear.style.display = val.length > 0 ? 'inline-flex' : 'none';
            }
            filterPosProducts(val.toLowerCase().trim(), getActiveCategory());
        });

        // Barcode reader / Fast Enter submission
        posSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const visibleCards = Array.from(document.querySelectorAll('.pos-product-card')).filter(c => c.style.display !== 'none');
                if (visibleCards.length > 0) {
                    const productData = visibleCards[0].getAttribute('data-product');
                    if (productData) {
                        addToCart(productData);
                    } else {
                        visibleCards[0].click();
                    }
                    posSearchInput.value = '';
                    if (posSearchClear) posSearchClear.style.display = 'none';
                    filterPosProducts('', getActiveCategory());
                }
            }
        });
    }

    // 2. Click delegation on product cards in catalog grid
    const productGrid = document.getElementById('posProductGrid');
    if (productGrid) {
        productGrid.addEventListener('click', (e) => {
            const card = e.target.closest('.pos-product-card');
            if (card) {
                const productData = card.getAttribute('data-product');
                if (productData) {
                    addToCart(productData);
                }
            }
        });
    }

    // 3. Category Pills Filter
    const pills = document.querySelectorAll('.category-pill');
    pills.forEach(pill => {
        pill.addEventListener('click', () => {
            pills.forEach(p => p.classList.remove('active'));
            pill.classList.add('active');
            const categoryId = pill.getAttribute('data-category');
            const query = posSearchInput ? posSearchInput.value.toLowerCase().trim() : '';
            filterPosProducts(query, categoryId);
        });
    });

    // 4. Discount Input Listener
    const discountInput = document.getElementById('cartDiscountInput');
    if (discountInput) {
        discountInput.addEventListener('input', renderCart);
    }

    // 5. Cash Tendered Input Listener
    const cashInput = document.getElementById('cashTenderedInput');
    if (cashInput) {
        cashInput.addEventListener('input', calculateCashChange);
    }

    // 6. Global Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
        const activeTag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
        const isInputActive = activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select';

        // When Success Modal is open, Enter/Space starts new bill
        const successModal = document.getElementById('saleSuccessModal');
        if (successModal && successModal.style.display === 'flex') {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                startNewBillAfterSuccess();
                return;
            }
            if (e.key === 'p' || e.key === 'P') {
                e.preventDefault();
                reprintFromModal('thermal');
                return;
            }
        }

        // F2: Focus Search / Barcode
        if (e.key === 'F2') {
            e.preventDefault();
            if (posSearchInput) posSearchInput.focus();
        }
        // F4: Focus Cash Tendered Input
        if (e.key === 'F4') {
            e.preventDefault();
            handlePaymentMethodChange('cash');
            if (cashInput) {
                cashInput.focus();
                cashInput.select();
            }
        }
        // F7: Inspect Full Bill Details Modal
        if (e.key === 'F7') {
            e.preventDefault();
            if (cart.length > 0) openInvoicePreviewModal();
        }
        // F8: Fast Bill & Print Thermal
        if (e.key === 'F8') {
            e.preventDefault();
            if (cart.length > 0) submitPOSSale('thermal');
        }
        // F9: Fast Bill & Print A4
        if (e.key === 'F9') {
            e.preventDefault();
            if (cart.length > 0) submitPOSSale('standard');
        }
        // F10: Fast Save Only
        if (e.key === 'F10') {
            e.preventDefault();
            if (cart.length > 0) submitPOSSale('save');
        }
        // 1-4 Number Keys (when not typing in an input) -> Switch payment mode
        if (!isInputActive) {
            if (e.key === '1') handlePaymentMethodChange('cash');
            if (e.key === '2') handlePaymentMethodChange('upi');
            if (e.key === '3') handlePaymentMethodChange('card');
            if (e.key === '4') handlePaymentMethodChange('credit');
        }
        // Escape: Clear Search or Close Open Modals
        if (e.key === 'Escape') {
            const billPreviewModal = document.getElementById('billPreviewModal');
            const heldBillsModal = document.getElementById('heldBillsModal');
            if (billPreviewModal && billPreviewModal.style.display === 'flex') {
                closeInvoicePreviewModal();
            } else if (heldBillsModal && heldBillsModal.style.display === 'flex') {
                closeHeldBillsModal();
            } else if (posSearchInput && document.activeElement === posSearchInput && posSearchInput.value.length > 0) {
                clearPosSearch();
            } else if (successModal && successModal.style.display === 'flex') {
                startNewBillAfterSuccess();
            }
        }
    });
}

function clearPosSearch() {
    const posSearchInput = document.getElementById('posSearchInput');
    const posSearchClear = document.getElementById('posSearchClear');
    if (posSearchInput) {
        posSearchInput.value = '';
        posSearchInput.focus();
    }
    if (posSearchClear) posSearchClear.style.display = 'none';
    filterPosProducts('', getActiveCategory());
}

function getActiveCategory() {
    const activePill = document.querySelector('.category-pill.active');
    return activePill ? activePill.getAttribute('data-category') : 'all';
}

function filterPosProducts(query, categoryId) {
    const cards = document.querySelectorAll('.pos-product-card');
    cards.forEach(card => {
        const name = (card.getAttribute('data-name') || '').toLowerCase();
        const sku = (card.getAttribute('data-sku') || '').toLowerCase();
        const barcode = (card.getAttribute('data-barcode') || '').toLowerCase();
        const cat = card.getAttribute('data-category-id');

        const matchesQuery = name.includes(query) || sku.includes(query) || barcode.includes(query);
        const matchesCategory = (categoryId === 'all' || cat === categoryId);

        if (matchesQuery && matchesCategory) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

/**
 * Add Item to Cart
 */
function addToCart(productJson) {
    let product;
    if (typeof productJson === 'string') {
        try {
            product = JSON.parse(productJson);
        } catch (e) {
            console.error('Failed to parse product data:', e);
            return;
        }
    } else {
        product = productJson;
    }

    if (!product || !product.id) return;

    if (product.stock <= 0) {
        showPosToast(`Out of Stock: ${product.name} has 0 units!`, 'error');
        return;
    }

    const existingIndex = cart.findIndex(item => item.product_id === product.id && item.batch_id === product.batch_id);

    if (existingIndex > -1) {
        if (cart[existingIndex].qty + 1 > product.stock) {
            showPosToast(`Stock Limit: Only ${product.stock} ${product.unit || 'units'} available`, 'warning');
            return;
        }
        cart[existingIndex].qty += 1;
        showPosToast(`+1 ${product.name} added (Qty: ${cart[existingIndex].qty})`, 'success');
    } else {
        cart.push({
            product_id: product.id,
            batch_id: product.batch_id,
            batch_no: product.batch_no,
            expiry_date: product.expiry_date,
            name: product.name,
            sku: product.sku || '',
            barcode: product.barcode || '',
            unit: product.unit || 'unit',
            category_id: parseInt(product.category_id) || 1,
            category_name: product.category_name || 'General',
            unit_price: parseFloat(product.price),
            cost_price: parseFloat(product.cost_price || 0),
            max_stock: parseInt(product.stock),
            qty: 1
        });
        showPosToast(`${product.name} added to bill`, 'success');
    }

    renderCart();

    // Ensure newly added/updated items are immediately in clear view in the top items viewport
    const itemsListContainer = document.getElementById('posCartItems');
    if (itemsListContainer) {
        if (cart.length === 1) {
            itemsListContainer.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
            const itemEl = document.getElementById(`cartItemRow_${existingIndex > -1 ? existingIndex : cart.length - 1}`);
            if (itemEl) {
                itemEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }
        }
    }
}

/**
 * Update Cart Quantity
 */
function updateCartQty(index, newQty) {
    newQty = parseInt(newQty);
    if (isNaN(newQty) || newQty <= 0) {
        removeFromCart(index);
        return;
    }

    if (newQty > cart[index].max_stock) {
        showPosToast(`Stock limit: Maximum available is ${cart[index].max_stock} ${cart[index].unit}`, 'warning');
        cart[index].qty = cart[index].max_stock;
    } else {
        cart[index].qty = newQty;
    }

    renderCart();
}

/**
 * Remove Item from Cart
 */
function removeFromCart(index) {
    if (cart[index]) {
        const removedName = cart[index].name;
        cart.splice(index, 1);
        renderCart();
        showPosToast(`${removedName} removed from bill`, 'info');
    }
}

/**
 * Clear Entire Cart
 */
function clearCart() {
    if (cart.length === 0) return;
    if (confirm('Are you sure you want to clear the entire cart?')) {
        cart = [];
        renderCart();
        showPosToast('Cart cleared', 'info');
    }
}

/**
 * Set Discount Mode (Amount vs Percentage)
 */
function setDiscountType(type) {
    discountType = type;
    const btnAmt = document.getElementById('discTypeAmt');
    const btnPct = document.getElementById('discTypePct');
    if (btnAmt && btnPct) {
        btnAmt.classList.toggle('active', type === 'amt');
        btnPct.classList.toggle('active', type === 'pct');
    }
    renderCart();
}

/**
 * Scroll Navigation & Pre-Invoice Product Inspection Functions
 */
function scrollCatalog(direction) {
    const grid = document.getElementById('posProductGrid');
    if (!grid) return;
    const scrollStep = 320;
    if (direction === 'down') {
        grid.scrollBy({ top: scrollStep, behavior: 'smooth' });
    } else if (direction === 'up') {
        grid.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function scrollCartToTop() {
    const itemsList = document.getElementById('posCartItems');
    if (itemsList) {
        itemsList.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function scrollCartToBilling() {
    const billingViewport = document.getElementById('posBillingViewportWrapper');
    if (billingViewport) {
        billingViewport.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

function scrollCartToBottom() {
    const billingViewport = document.getElementById('posBillingViewportWrapper');
    if (billingViewport) {
        billingViewport.scrollTo({ top: billingViewport.scrollHeight, behavior: 'smooth' });
    }
}

function handleScrollBody(el) {
    // Retained for backward compatibility
}

function toggleBillReviewDrawer() {
    const content = document.getElementById('billReviewContent');
    const chevron = document.getElementById('billReviewChevron');
    const hint = document.getElementById('billReviewToggleHint');
    if (!content) return;
    const isHidden = content.style.display === 'none' || content.style.display === '';
    if (isHidden) {
        content.style.display = 'block';
        if (chevron) chevron.style.transform = 'rotate(180deg)';
        if (hint) hint.innerText = 'Click to collapse list';
    } else {
        content.style.display = 'none';
        if (chevron) chevron.style.transform = 'rotate(0deg)';
        if (hint) hint.innerText = 'Click to inspect list';
    }
}

function openInvoicePreviewModal() {
    const modal = document.getElementById('billPreviewModal');
    if (!modal) return;
    if (cart.length === 0) {
        showPosToast('Add at least one product before inspecting invoice', 'warning');
        return;
    }

    const custName = (document.getElementById('customerNameInput') && document.getElementById('customerNameInput').value) || 'Walk-in Customer';
    const custPhone = (document.getElementById('customerPhoneInput') && document.getElementById('customerPhoneInput').value) || '';
    const custDisplay = custPhone ? `${custName} (${custPhone})` : custName;

    const custEl = document.getElementById('previewModalCustomer');
    const payEl = document.getElementById('previewModalPayMode');
    const countEl = document.getElementById('previewModalItemsCount');

    if (custEl) custEl.innerText = custDisplay;
    if (payEl) payEl.innerText = activePaymentMethod.toUpperCase();
    if (countEl) countEl.innerText = `${cart.length} Line Items`;

    let subtotal = 0;
    let tBodyHtml = '';
    cart.forEach((it, idx) => {
        const lineTot = it.qty * it.unit_price;
        subtotal += lineTot;
        tBodyHtml += `
            <tr>
                <td style="padding: 0.45rem 0.6rem; color: var(--text-muted); font-weight: 600;">${idx + 1}</td>
                <td style="padding: 0.45rem 0.6rem;">
                    <div style="font-weight: 700; color: var(--text-primary); font-size: 0.95rem;">${escapeHtml(it.name)}</div>
                    <small style="color: var(--text-muted); font-size: 0.75rem;">${escapeHtml(it.category_name || '')}</small>
                </td>
                <td style="padding: 0.45rem 0.6rem; text-align: center; font-weight: 700;">
                    <span class="badge badge-info" style="font-size: 0.75rem;">${it.qty} ${escapeHtml(it.unit)}</span>
                </td>
                <td style="padding: 0.5rem 0.65rem; text-align: right; font-size: 0.9rem; font-weight: 700; color: var(--text-primary);">
                    ${currencySymbol}${it.unit_price.toFixed(2)}
                </td>
                <td style="padding: 0.5rem 0.65rem; text-align: right; font-weight: 800; color: var(--primary); font-size: 0.95rem;">
                    ${currencySymbol}${lineTot.toFixed(2)}
                </td>
            </tr>
        `;
    });

    const discountFinal = parseFloat((document.getElementById('cartDiscountFinal') && document.getElementById('cartDiscountFinal').value) || 0);
    const taxable = Math.max(0, subtotal - discountFinal);
    const tax = (taxable * taxRate) / 100;
    const grand = taxable + tax;

    const tableBody = document.getElementById('previewModalTableBody');
    if (tableBody) tableBody.innerHTML = tBodyHtml;

    const subEl = document.getElementById('previewModalSubtotal');
    const discEl = document.getElementById('previewModalDiscount');
    const taxMEl = document.getElementById('previewModalTax');
    const grandMEl = document.getElementById('previewModalGrandTotal');

    if (subEl) subEl.innerText = `${currencySymbol}${subtotal.toFixed(2)}`;
    if (discEl) discEl.innerText = `- ${currencySymbol}${discountFinal.toFixed(2)}`;
    if (taxMEl) taxMEl.innerText = `${currencySymbol}${tax.toFixed(2)}`;
    if (grandMEl) grandMEl.innerText = `${currencySymbol}${grand.toFixed(2)}`;

    modal.style.display = 'flex';
}

function closeInvoicePreviewModal() {
    const modal = document.getElementById('billPreviewModal');
    if (modal) modal.style.display = 'none';
}

/**
 * Render Cart & Financial Totals
 */
function renderCart() {
    const cartContainer = document.getElementById('posCartItems');
    const subtotalEl = document.getElementById('cartSubtotal');
    const taxEl = document.getElementById('cartTax');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const cartCountEl = document.getElementById('cartItemCount');
    const itemsCountBadge = document.getElementById('itemsCountBadge');
    const miniPayableTotal = document.getElementById('miniPayableTotal');
    const subribbonTextEl = document.getElementById('cartSubribbonText');
    const billReviewCountEl = document.getElementById('billReviewCount');
    const billReviewTableBody = document.getElementById('billReviewTableBody');
    const cartDataInput = document.getElementById('cartDataInput');
    const discountFinalInput = document.getElementById('cartDiscountFinal');
    const inWordsEl = document.getElementById('grandTotalInWords');

    const btnThermal = document.getElementById('btnThermalPrint');
    const btnA4 = document.getElementById('btnA4Print');
    const btnSave = document.getElementById('btnSaveOnly');
    const stripPayBtn = document.getElementById('stripPayBtn');
    const directPayBtns = document.querySelectorAll('.pos-pay-btn');

    if (!cartContainer) return;

    if (cart.length === 0) {
        cartContainer.innerHTML = `
            <div class="pos-empty-cart">
                <i class="fa-solid fa-cart-shopping"></i>
                <p style="font-weight: 600; font-size: 1rem; color: var(--text-secondary); margin-bottom: 0.25rem;">Cart is empty</p>
                <small style="color: var(--text-muted);">Click items from the catalog or scan barcodes to begin billing</small>
            </div>
        `;
        if (subtotalEl) subtotalEl.innerText = `${currencySymbol}0.00`;
        if (taxEl) taxEl.innerText = `${currencySymbol}0.00`;
        if (grandTotalEl) grandTotalEl.innerText = `${currencySymbol}0.00`;
        if (cartCountEl) cartCountEl.innerText = '0 Items (0 Units)';
        if (itemsCountBadge) itemsCountBadge.innerText = '0';
        if (miniPayableTotal) miniPayableTotal.innerText = `${currencySymbol}0.00`;
        if (subribbonTextEl) subribbonTextEl.innerText = '0 Items to Bill';
        if (billReviewCountEl) billReviewCountEl.innerText = '0';
        if (billReviewTableBody) {
            billReviewTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 0.65rem; font-size: 0.78rem;">No products added yet</td></tr>`;
        }
        if (discountFinalInput) discountFinalInput.value = '0.00';
        if (inWordsEl) inWordsEl.innerText = 'Zero Rupees Only';

        // Update Sticky Bottom Strip
        const stripGrandTotalEl = document.getElementById('stripGrandTotal');
        if (stripGrandTotalEl) stripGrandTotalEl.innerText = `${currencySymbol}0.00`;

        // Hide Scroll Divider when cart is empty
        const scrollDividerEl = document.getElementById('posScrollDivider');
        if (scrollDividerEl) scrollDividerEl.style.display = 'none';

        if (btnThermal) btnThermal.disabled = true;
        if (btnA4) btnA4.disabled = true;
        if (btnSave) btnSave.disabled = true;
        if (stripPayBtn) stripPayBtn.disabled = true;
        directPayBtns.forEach(btn => btn.disabled = true);

        if (cartDataInput) cartDataInput.value = '[]';
        calculateCashChange();
        updateUpiQr(0);
        updateMainButtonText(0);
        return;
    }

    let subtotal = 0;
    let totalUnits = 0;
    let html = '';
    let reviewRowsHtml = '';

    cart.forEach((item, index) => {
        const itemTotal = item.qty * item.unit_price;
        subtotal += itemTotal;
        totalUnits += item.qty;

        let catBadgeClass = 'cat-badge-dairy';
        if (item.category_id === 2) catBadgeClass = 'cat-badge-icecream';
        else if (item.category_id === 3) catBadgeClass = 'cat-badge-drinks';
        else if (item.category_id === 4) catBadgeClass = 'cat-badge-chocolate';

        // 1. Detailed Cart Item Card (with stepper & batch info)
        html += `
            <div class="cart-item ${catBadgeClass}-border" id="cartItemRow_${index}">
                <div class="cart-item-header">
                    <div style="flex: 1; min-width: 0;">
                        <div class="cart-item-top-row">
                            <span class="cart-item-cat-tag ${catBadgeClass}">${escapeHtml(item.category_name || 'Item')}</span>
                            <span class="cart-item-stock-limit"><i class="fa-solid fa-boxes-stacked"></i> Avail: ${item.max_stock} ${escapeHtml(item.unit)}</span>
                        </div>
                        <div class="cart-item-title" title="${escapeHtml(item.name)}">${escapeHtml(item.name)}</div>
                        <div class="cart-item-meta">
                            <span class="meta-tag"><i class="fa-solid fa-indian-rupee-sign"></i> <strong>${currencySymbol}${item.unit_price.toFixed(2)} / ${escapeHtml(item.unit)}</strong></span>
                        </div>
                    </div>
                    <button type="button" class="cart-item-remove" onclick="removeFromCart(${index})" title="Remove item from bill">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
                <div class="cart-item-controls">
                    <div class="cart-qty-control">
                        <button type="button" class="cart-qty-btn minus-btn" onclick="updateCartQty(${index}, ${item.qty - 1})" title="Decrease quantity">-</button>
                        <input type="number" class="cart-qty-input" value="${item.qty}" min="1" max="${item.max_stock}" 
                               onchange="updateCartQty(${index}, this.value)">
                        <button type="button" class="cart-qty-btn plus-btn" onclick="updateCartQty(${index}, ${item.qty + 1})" title="Increase quantity">+</button>
                        <span class="cart-unit-label">${escapeHtml(item.unit)}</span>
                    </div>
                    <div class="cart-price-col">
                        <div class="cart-unit-rate-badge">
                            <span class="rate-lbl">Price / Unit:</span> <strong>${currencySymbol}${item.unit_price.toFixed(2)}</strong> / ${escapeHtml(item.unit)}
                        </div>
                        <div class="cart-item-total">
                            ${currencySymbol}${itemTotal.toFixed(2)}
                        </div>
                        <div class="cart-calc-line">
                            ${item.qty} ${escapeHtml(item.unit)} × ${currencySymbol}${item.unit_price.toFixed(2)}
                        </div>
                    </div>
                </div>
            </div>
        `;

        // 2. Pre-Invoice Review Compact Row
        reviewRowsHtml += `
            <tr>
                <td style="color: var(--text-muted); font-weight: 600;">${index + 1}</td>
                <td>
                    <div style="font-weight: 700; color: var(--text-primary); line-height: 1.2;">${escapeHtml(item.name)}</div>
                </td>
                <td style="font-size: 0.72rem; color: var(--text-secondary); font-family: monospace;">${escapeHtml(item.sku)}</td>
                <td style="text-align: center;">
                    <span class="badge badge-primary" style="font-size: 0.72rem; padding: 0.1rem 0.35rem;">${item.qty} ${escapeHtml(item.unit)}</span>
                </td>
                <td style="text-align: right; font-size: 0.82rem; font-weight: 700; color: var(--text-primary);">
                    ${currencySymbol}${item.unit_price.toFixed(2)}
                </td>
                <td style="text-align: right; font-weight: 700; color: var(--primary); font-size: 0.8rem;">${currencySymbol}${itemTotal.toFixed(2)}</td>
            </tr>
        `;
    });

    cartContainer.innerHTML = html;

    if (billReviewTableBody) {
        billReviewTableBody.innerHTML = reviewRowsHtml;
    }

    // Discount calculation
    const discountInput = document.getElementById('cartDiscountInput');
    const rawDiscount = discountInput ? Math.max(0, parseFloat(discountInput.value) || 0) : 0;
    let finalDiscount = 0;

    if (discountType === 'pct') {
        finalDiscount = (subtotal * rawDiscount) / 100;
    } else {
        finalDiscount = Math.min(subtotal, rawDiscount);
    }

    const taxableAmount = Math.max(0, subtotal - finalDiscount);
    const taxAmount = (taxableAmount * taxRate) / 100;
    const grandTotal = taxableAmount + taxAmount;

    if (subtotalEl) subtotalEl.innerText = `${currencySymbol}${subtotal.toFixed(2)}`;
    if (taxEl) taxEl.innerText = `${currencySymbol}${taxAmount.toFixed(2)}`;
    if (grandTotalEl) grandTotalEl.innerText = `${currencySymbol}${grandTotal.toFixed(2)}`;
    if (cartCountEl) cartCountEl.innerText = `${cart.length} Items (${totalUnits} Units)`;
    if (itemsCountBadge) itemsCountBadge.innerText = `${cart.length}`;
    if (miniPayableTotal) miniPayableTotal.innerText = `${currencySymbol}${grandTotal.toFixed(2)}`;
    if (subribbonTextEl) subribbonTextEl.innerText = `${cart.length} Item${cart.length === 1 ? '' : 's'} (${totalUnits} Units)`;
    if (billReviewCountEl) billReviewCountEl.innerText = `${cart.length}`;
    if (discountFinalInput) discountFinalInput.value = finalDiscount.toFixed(2);
    if (inWordsEl) inWordsEl.innerText = numberToWords(grandTotal);

    // Update Sticky Bottom Strip
    const stripGrandTotalEl = document.getElementById('stripGrandTotal');
    if (stripGrandTotalEl) stripGrandTotalEl.innerText = `${currencySymbol}${grandTotal.toFixed(2)}`;

    // Show Scroll Divider when cart has items
    const scrollDividerEl = document.getElementById('posScrollDivider');
    if (scrollDividerEl) scrollDividerEl.style.display = 'flex';

    if (btnThermal) btnThermal.disabled = false;
    if (btnA4) btnA4.disabled = false;
    if (btnSave) btnSave.disabled = false;
    if (stripPayBtn) stripPayBtn.disabled = false;
    directPayBtns.forEach(btn => btn.disabled = false);

    if (cartDataInput) cartDataInput.value = JSON.stringify(cart);

    calculateCashChange();
    updateUpiQr(grandTotal);
    updateMainButtonText(grandTotal);
}

/**
 * In-Terminal Flash Toast Notification
 */
function showPosToast(message, type = 'info') {
    let toastContainer = document.getElementById('posToastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'posToastContainer';
        toastContainer.className = 'pos-toast-container';
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = `pos-toast pos-toast-${type}`;
    const icon = type === 'success' ? 'fa-circle-check' : (type === 'warning' ? 'fa-triangle-exclamation' : (type === 'error' ? 'fa-circle-xmark' : 'fa-circle-info'));
    toast.innerHTML = `<i class="fa-solid ${icon}"></i> <span>${escapeHtml(message)}</span>`;
    
    toastContainer.appendChild(toast);
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 2200);
}

/**
 * Handle Payment Method Switch (Cash vs UPI vs Card vs Credit)
 */
function handlePaymentMethodChange(method) {
    activePaymentMethod = method;

    // Check radio button
    const radio = document.querySelector(`input[name="payment_method"][value="${method}"]`);
    if (radio) radio.checked = true;

    // Toggle panels
    const panelCash = document.getElementById('payPanelCash');
    const panelUpi = document.getElementById('payPanelUpi');
    const panelCard = document.getElementById('payPanelCard');
    const panelCredit = document.getElementById('payPanelCredit');

    if (panelCash) panelCash.style.display = (method === 'cash') ? 'block' : 'none';
    if (panelUpi) panelUpi.style.display = (method === 'upi') ? 'block' : 'none';
    if (panelCard) panelCard.style.display = (method === 'card') ? 'block' : 'none';
    if (panelCredit) panelCredit.style.display = (method === 'credit') ? 'block' : 'none';

    const grandTotalEl = document.getElementById('cartGrandTotal');
    const currentGrandTotal = grandTotalEl ? parseFloat(grandTotalEl.innerText.replace(/[^0-9.]/g, '')) || 0 : 0;

    if (method === 'cash') {
        calculateCashChange();
    } else if (method === 'upi') {
        updateUpiQr(currentGrandTotal);
    }

    updateMainButtonText(currentGrandTotal);
}

/**
 * Update Main Checkout Button Text dynamically
 */
function updateMainButtonText(grandTotal) {
    const mainBtnText = document.getElementById('mainBtnText');
    if (!mainBtnText) return;

    const modeLabels = {
        'cash': 'Cash',
        'upi': 'UPI',
        'card': 'Card',
        'credit': 'Khata'
    };
    const modeName = modeLabels[activePaymentMethod] || 'Cash';
    
    if (grandTotal > 0) {
        mainBtnText.innerText = `Quick Pay ${currencySymbol}${grandTotal.toFixed(2)} (${modeName}) & Print Thermal [F8]`;
    } else {
        mainBtnText.innerText = `Quick Bill & Print Thermal (80mm) [F8]`;
    }
}

/**
 * Set Quick Cash Tendered Amount
 */
function setCashTendered(val) {
    const cashInput = document.getElementById('cashTenderedInput');
    const grandTotalEl = document.getElementById('cartGrandTotal');
    const currentGrandTotal = grandTotalEl ? parseFloat(grandTotalEl.innerText.replace(/[^0-9.]/g, '')) || 0 : 0;

    if (cashInput) {
        if (val === 'exact') {
            cashInput.value = currentGrandTotal.toFixed(2);
        } else {
            cashInput.value = parseFloat(val).toFixed(2);
        }
        calculateCashChange();
    }
}

/**
 * Calculate Cash Change to Return
 */
function calculateCashChange() {
    const cashInput = document.getElementById('cashTenderedInput');
    const changeDisplay = document.getElementById('cashChangeDisplay');
    const changeHidden = document.getElementById('cashChangeInput');
    const grandTotalEl = document.getElementById('cartGrandTotal');

    const grandTotal = grandTotalEl ? parseFloat(grandTotalEl.innerText.replace(/[^0-9.]/g, '')) || 0 : 0;
    const tendered = cashInput && !isNaN(parseFloat(cashInput.value)) ? parseFloat(cashInput.value) : 0;

    if (tendered >= grandTotal && grandTotal > 0) {
        const change = tendered - grandTotal;
        if (changeDisplay) changeDisplay.innerText = `${currencySymbol}${change.toFixed(2)}`;
        if (changeHidden) changeHidden.value = change.toFixed(2);
    } else {
        if (changeDisplay) changeDisplay.innerText = `${currencySymbol}0.00`;
        if (changeHidden) changeHidden.value = '0.00';
    }
}

/**
 * Update Dynamic UPI QR Code Image with live amount
 */
function updateUpiQr(grandTotal) {
    const upiImg = document.getElementById('dynamicUpiQrImg');
    const upiPill = document.getElementById('upiAmountPill');

    if (upiPill) {
        upiPill.innerText = `${currencySymbol}${grandTotal.toFixed(2)}`;
    }

    if (upiImg) {
        const cleanName = encodeURIComponent(storeName.substring(0, 25));
        const upiUri = `upi://pay?pa=${encodeURIComponent(storeUpiId)}&pn=${cleanName}&am=${grandTotal.toFixed(2)}&cu=INR&tn=Bill%20Payment`;
        upiImg.src = `https://api.qrserver.com/v1/create-qr-code/?size=160x160&data=${encodeURIComponent(upiUri)}`;
    }
}

/**
 * Copy UPI ID to Clipboard
 */
function copyUpiId() {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(storeUpiId).then(() => {
            alert(`UPI ID "${storeUpiId}" copied to clipboard!`);
        });
    } else {
        alert(`Store UPI ID: ${storeUpiId}`);
    }
}

/**
 * Card Type toggle pill
 */
function setCardType(btn, type) {
    document.querySelectorAll('.card-pill-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}

/**
 * Submit Sale with Selected Print Mode & Optional Direct Method
 */
function submitPOSSale(printAction, explicitMethod = null) {
    if (cart.length === 0) {
        alert('Cannot submit an empty cart. Please add products first.');
        return;
    }

    if (explicitMethod) {
        handlePaymentMethodChange(explicitMethod);
    }

    const cartDataInput = document.getElementById('cartDataInput');
    if (cartDataInput) {
        cartDataInput.value = JSON.stringify(cart);
    }

    const printActionInput = document.getElementById('printActionInput');
    if (printActionInput) {
        printActionInput.value = printAction;
    }

    const form = document.getElementById('checkoutForm');
    if (!form) return;

    // Prepare FormData for AJAX seamless in-terminal checkout
    const formData = new FormData(form);
    formData.append('ajax', '1');

    // Disable buttons during submission to prevent double billing
    const allActionBtns = document.querySelectorAll('.pos-btn-main, .pos-btn-sub, .btn-direct-thermal, .btn-direct-a4');
    allActionBtns.forEach(btn => btn.disabled = true);

    const actionUrl = form.getAttribute('action') || (window.BASE_URL + '/modules/pos/process_sale.php');

    fetch(actionUrl, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(res => res.json())
    .then(data => {
        allActionBtns.forEach(btn => btn.disabled = false);

        if (data.success) {
            lastCompletedSale = data;

            // Trigger silent print in hidden iframe if printAction requested
            if (printAction === 'thermal' || printAction === 'standard') {
                const targetUrl = (printAction === 'thermal') ? data.thermal_url : data.standard_url;
                triggerIframePrint(targetUrl);
            }

            // Show In-Terminal Bill Success Modal
            showSaleSuccessModal(data);

            // Deduct stock levels locally in product cards
            cart.forEach(cartItem => {
                const card = document.querySelector(`.pos-product-card[data-id="${cartItem.product_id}"]`);
                if (card) {
                    const productData = card.getAttribute('data-product');
                    if (productData) {
                        try {
                            const p = JSON.parse(productData);
                            p.stock = Math.max(0, p.stock - cartItem.qty);
                            card.setAttribute('data-product', JSON.stringify(p));
                            
                            const stockBadge = card.querySelector('.pos-stock-badge');
                            if (stockBadge) {
                                stockBadge.innerHTML = `<i class="fa-solid ${p.stock <= p.min_stock ? 'fa-triangle-exclamation' : 'fa-check'}"></i> ${p.stock} ${escapeHtml(p.unit)}`;
                                stockBadge.className = `pos-stock-badge ${p.stock <= p.min_stock ? 'low-stock' : 'in-stock'}`;
                            }
                        } catch (e) {}
                    }
                }
            });

            // Clear current cart in memory
            cart = [];
            const custNameInput = document.getElementById('customerNameInput');
            const custPhoneInput = document.getElementById('customerPhoneInput');
            if (custNameInput) custNameInput.value = 'Walk-in Customer';
            if (custPhoneInput) custPhoneInput.value = '';
            renderCart();

        } else {
            alert('Checkout Failed: ' + (data.error || 'Unknown error occurred.'));
        }
    })
    .catch(err => {
        console.error('AJAX Checkout Error, falling back to standard submit:', err);
        form.submit();
    });
}

/**
 * Trigger Instant Printing via Hidden Iframe
 */
function triggerIframePrint(url) {
    const iframe = document.getElementById('posPrintIframe');
    if (!iframe) return;

    iframe.src = url;
    iframe.onload = function() {
        try {
            setTimeout(() => {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            }, 300);
        } catch (e) {
            console.log('Iframe print focus error, opening print window:', e);
            window.open(url, '_blank');
        }
    };
}

/**
 * Show Completed Sale Success Modal
 */
function showSaleSuccessModal(data) {
    const modal = document.getElementById('saleSuccessModal');
    if (!modal) return;

    const invoiceNoEl = document.getElementById('successModalInvoiceNo');
    const customerEl = document.getElementById('successModalCustomer');
    const payMethodEl = document.getElementById('successModalPayMethod');
    const itemsEl = document.getElementById('successModalItems');
    const totalEl = document.getElementById('successModalTotal');

    if (invoiceNoEl) invoiceNoEl.innerText = `Invoice #${data.invoice_no}`;
    if (customerEl) customerEl.innerText = data.customer_name || 'Walk-in Customer';
    if (payMethodEl) payMethodEl.innerText = (data.payment_method || 'CASH').toUpperCase();
    if (itemsEl) itemsEl.innerText = `${data.items_count} Items Billed`;
    if (totalEl) totalEl.innerText = data.grand_total_formatted || `${currencySymbol}${parseFloat(data.grand_total).toFixed(2)}`;

    modal.style.display = 'flex';
}

/**
 * Reprint from Modal
 */
function reprintFromModal(mode) {
    if (!lastCompletedSale) return;
    const targetUrl = (mode === 'thermal') ? lastCompletedSale.thermal_url : lastCompletedSale.standard_url;
    triggerIframePrint(targetUrl);
}

/**
 * Close modal and ready POS for the next customer
 */
function startNewBillAfterSuccess() {
    const modal = document.getElementById('saleSuccessModal');
    if (modal) modal.style.display = 'none';

    const posSearchInput = document.getElementById('posSearchInput');
    if (posSearchInput) {
        posSearchInput.value = '';
        posSearchInput.focus();
    }
}

/**
 * Hold / Park Current Bill to localStorage
 */
function holdCurrentBill() {
    if (cart.length === 0) {
        alert('Cart is empty. Nothing to hold.');
        return;
    }

    const customerNameInput = document.getElementById('customerNameInput');
    const customerPhoneInput = document.getElementById('customerPhoneInput');
    const name = customerNameInput ? customerNameInput.value.trim() : 'Walk-in Customer';
    const phone = customerPhoneInput ? customerPhoneInput.value.trim() : '';

    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    const newHeld = {
        id: 'HOLD-' + Date.now(),
        time: new Date().toLocaleTimeString(),
        date: new Date().toLocaleDateString(),
        customer_name: name || 'Walk-in Customer',
        customer_phone: phone,
        cart: cart
    };

    heldBills.push(newHeld);
    localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));

    alert(`Bill parked successfully under "${newHeld.customer_name}"! You can now serve the next customer.`);
    cart = [];
    if (customerNameInput) customerNameInput.value = 'Walk-in Customer';
    if (customerPhoneInput) customerPhoneInput.value = '';
    renderCart();
    updateHeldBillsCountBadge();
}

/**
 * Update Held Bills Badge Count
 */
function updateHeldBillsCountBadge() {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    const countBadge = document.getElementById('heldBillsCount');
    if (countBadge) {
        if (heldBills.length > 0) {
            countBadge.style.display = 'inline-block';
            countBadge.innerText = heldBills.length;
        } else {
            countBadge.style.display = 'none';
        }
    }
}

/**
 * Open Held Bills Modal
 */
function openHeldBillsModal() {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    const listContainer = document.getElementById('heldBillsList');
    const modal = document.getElementById('heldBillsModal');

    if (!listContainer || !modal) return;

    if (heldBills.length === 0) {
        listContainer.innerHTML = `
            <div style="text-align: center; padding: 2rem 1rem; color: var(--text-muted);">
                <i class="fa-solid fa-folder-open" style="font-size: 2.5rem; opacity: 0.4; margin-bottom: 0.5rem;"></i>
                <p>No parked bills found.</p>
            </div>
        `;
    } else {
        let html = '';
        heldBills.forEach((hb, idx) => {
            let total = 0;
            hb.cart.forEach(i => total += (i.qty * i.unit_price));

            html += `
                <div class="held-bill-card">
                    <div>
                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">
                            ${escapeHtml(hb.customer_name)}
                        </div>
                        <small style="color: var(--text-muted); font-size: 0.78rem;">
                            Held at ${hb.time} • ${hb.cart.length} items • <strong>${currencySymbol}${total.toFixed(2)}</strong>
                        </small>
                    </div>
                    <div style="display: flex; gap: 0.4rem;">
                        <button type="button" class="btn btn-primary btn-sm" onclick="resumeHeldBill(${idx})">
                            <i class="fa-solid fa-arrow-rotate-right"></i> Resume
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteHeldBill(${idx})">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        listContainer.innerHTML = html;
    }

    modal.style.display = 'flex';
}

function closeHeldBillsModal() {
    const modal = document.getElementById('heldBillsModal');
    if (modal) modal.style.display = 'none';
}

function resumeHeldBill(index) {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    if (heldBills[index]) {
        if (cart.length > 0 && !confirm('Resuming this bill will replace current items in cart. Continue?')) {
            return;
        }

        const bill = heldBills[index];
        cart = bill.cart;

        const customerNameInput = document.getElementById('customerNameInput');
        const customerPhoneInput = document.getElementById('customerPhoneInput');
        if (customerNameInput) customerNameInput.value = bill.customer_name;
        if (customerPhoneInput) customerPhoneInput.value = bill.customer_phone || '';

        heldBills.splice(index, 1);
        localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));

        renderCart();
        updateHeldBillsCountBadge();
        closeHeldBillsModal();
    }
}

function deleteHeldBill(index) {
    const heldBills = JSON.parse(localStorage.getItem('pos_held_bills') || '[]');
    heldBills.splice(index, 1);
    localStorage.setItem('pos_held_bills', JSON.stringify(heldBills));
    updateHeldBillsCountBadge();
    openHeldBillsModal();
}

/**
 * Clock updater
 */
function initClock() {
    const timeEl = document.getElementById('liveTimeStr');
    if (!timeEl) return;
    function update() {
        const now = new Date();
        timeEl.innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }
    update();
    setInterval(update, 1000);
}

function formatExpDate(dateStr) {
    if (!dateStr) return 'N/A';
    const d = new Date(dateStr);
    return isNaN(d) ? dateStr : d.toLocaleDateString([], { day: '2-digit', month: 'short' });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

/**
 * Convert number to words in JS for real-time display
 */
function numberToWords(num) {
    num = Math.round(num * 100) / 100;
    const rupees = Math.floor(num);
    const paise = Math.round((num - rupees) * 100);

    const a = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const b = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    function inWords(n) {
        if (n < 20) return a[n];
        if (n < 100) return b[Math.floor(n / 10)] + (n % 10 ? ' ' + a[n % 10] : '');
        if (n < 1000) return a[Math.floor(n / 100)] + ' Hundred' + (n % 100 ? ' and ' + inWords(n % 100) : '');
        if (n < 100000) return inWords(Math.floor(n / 1000)) + ' Thousand' + (n % 1000 ? ' ' + inWords(n % 1000) : '');
        if (n < 10000000) return inWords(Math.floor(n / 100000)) + ' Lakh' + (n % 100000 ? ' ' + inWords(n % 100000) : '');
        return inWords(Math.floor(n / 10000000)) + ' Crore' + (n % 10000000 ? ' ' + inWords(n % 10000000) : '');
    }

    if (rupees === 0) return 'Zero Rupees Only';
    let res = inWords(rupees) + ' Rupees';
    if (paise > 0) res += ' and ' + inWords(paise) + ' Paise';
    return res + ' Only';
}
