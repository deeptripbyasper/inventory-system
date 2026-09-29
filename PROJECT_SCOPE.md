# 📋 Project Scope & System Specification Document
## Project Name: Bandhu Chol - Real-time Inventory, Expiry & Sales Intelligence System

---

## 1. Executive Summary
**Bandhu Chol** is a web-based, full-stack inventory management and Point of Sale (POS) software application engineered using **PHP**, **MySQLi**, **HTML5**, **CSS3**, and **JavaScript**. 

The primary objective of the system is to solve critical stock visibility problems for retail and wholesale businesses (such as pharmacies, grocery marts, packaged food distributors, and FMCG retailers) by providing **product-wise leftover stock tracking**, **FEFO (First-Expire, First-Out) shelf-life monitoring**, **everyday daily sales intelligence**, and **monthly financial auditing**.

---

## 2. Core Functional Modules & Scope

### 📦 Module 1: Product-Wise Stock Visibility & Catalog Management
- **Unified Inventory Aggregation**: Real-time aggregation of total leftover stock across all lots/batches belonging to a product.
- **Stock Health Indicators**: Automated status badges (*In Stock*, *Low Stock Warning*, *Out of Stock*) based on configurable reorder thresholds.
- **360° Product Inspector**: Complete drill-down view of any product displaying:
  - SKU, barcode/UPC, category, and measurement units (pcs, box, strip, kg, bottle, etc.).
  - Total initial stock received vs. total units sold vs. remaining leftover stock.
  - Inventory valuation calculated at purchase cost and retail selling price.
  - Comprehensive lot/batch table listing individual expiration dates.
  - Product-specific historical sales logs.
- **Dynamic Search & Filtering**: Multi-condition search by product name, SKU, or barcode with instant category filtering.

---

### 🏢 Module 2: Leftover Stock & Inventory Valuation
- **Lot-by-Lot Leftover Tracking**: Detailed audit showing *Initial Ingested Quantity*, *Sold Quantity*, and *Remaining Leftover Stock*.
- **Depletion Analytics**: Visual percentage progress bars illustrating depletion rate per batch.
- **Capital Tied-Up Audit**: Real-time calculation of **Total Cost Valuation** (actual capital invested) vs. **Total Retail Valuation** (expected gross return).
- **Unrealized Gross Margin**: Automatic calculation of projected future profit held in warehouse stock.

---

### 📅 Module 3: Everyday Sales Report (Daily Intelligence)
- **Arbitrary Date Selector**: Deep inspection of sales performance for today or any historical calendar date.
- **Financial Metrics**:
  - Total Daily Sales Orders & Invoices processed.
  - Total Individual Units Sold.
  - Gross Revenue, Total Discounts applied, and Sales Tax collected.
  - Cost of Goods Sold (COGS) & Daily Gross Profit.
  - Daily Net Profit Margin %.
  - Average Order Value (AOV).
- **Product-Wise Daily Sales Breakdown**: Granular table detailing units sold per product, lot origins, revenue generated, COGS, and profit margin per product.
- **Hourly Sales Distribution Chart**: Visual hourly distribution of checkout activity.
- **Transaction Audit Log**: Comprehensive invoice listing with one-click printable receipts and CSV export.

---

### 📈 Module 4: Monthly Sales Report & Retrospective Trends
- **Month & Year Selectors**: Multi-year and multi-month comparative reporting.
- **Monthly Aggregate Metrics**: Monthly Gross Revenue, Gross Profit, Total Orders, Units Sold, and Average Order Value.
- **Day-by-Day Performance Graph**: High-definition bar/line charts comparing daily revenue against gross profit across all days of the selected month.
- **Daily Performance Table**: Calendar day breakdown detailing invoices, units sold, revenue, profit, and margins.
- **Top 10 Performing Products**: Ranked by units sold and revenue contribution percentage to total monthly sales.

---

### ⏳ Module 5: Expiry Date Tracking & Shelf-Life Watch
- **Automated Color-Coded Expiry Tiers**:
  - 🔴 **Expired Stock**: Items whose expiration date has passed (`expiry_date < CURDATE()`).
  - 🟠 **Critical Expiry**: Items expiring within **≤ 30 days** (Amber Alert).
  - 🟡 **Warning Expiry**: Items expiring within **31 to 60 days**.
  - 🟢 **Safe & Fresh Stock**: Items with shelf life **> 60 days**.
- **Capital-at-Risk Engine**: Quantifies the exact monetary value of products expiring soon to avoid financial write-offs.
- **One-Click Disposal / Write-Off Action**: Direct integration with stock adjustment handlers to record waste disposal for accounting compliance.

---

### 🛒 Module 6: Point of Sale (POS) & Checkout Terminal
- **FEFO (First-Expire, First-Out) Auto-Allocation**: Prioritizes selling items from the nearest expiring batch first to reduce perishable waste.
- **Interactive UI**: Split-screen design featuring a searchable product catalog on the left and a live invoice register on the right.
- **Barcode Scanner Fast Entry**: Auto-scans products and places them into the cart instantly.
- **Financial Calculation Engine**: Live calculation of subtotals, item quantity modifications, customer discounts, sales taxes, and grand totals.
- **Payment Method Support**: Cash, Debit/Credit Card, UPI / Mobile Wallet, Store Credit, and Others.
- **Thermal & Standard Printable Receipts**: Dual format printable invoice with store branding, tax breakdown, and batch trace info.

---

### 🚚 Module 7: Restock Shipments & Lot Ingestion
- **Shipment Intake**: Receive inventory shipments, assign Lot/Batch numbers, and record manufacturing and expiry dates.
- **Cost & Price Tracking**: Capture unit purchase costs and batch-specific selling prices.
- **Stock-In Audit Logs**: Maintains complete supplier invoice references and receiving notes.

---

### 🛠️ Module 8: Stock Adjustments & Waste Write-Offs
- **Inventory Discrepancy Reconciliation**: Subtract or add stock with documented reason codes:
  - *Expired Stock Disposal*
  - *Damaged / Broken Goods*
  - *Lost / Inventory Shrinkage*
  - *Routine Audit Reconciliation*
  - *Found Stock Recovery*
- **Audit Trail**: Logs timestamps, adjusting user, quantity adjusted, and authorization notes.

---

### 🏷️ Module 9: Category & Supplier Directory
- **Category Management**: Organize inventory by departments (e.g. Pharmaceuticals, Beverages, Dairy, Grocery, Personal Care).
- **Supplier Directory**: Maintain vendor profiles, contact personnel, phone numbers, emails, addresses, and batches supplied.

---

### ⚙️ Module 10: System Settings & Web Installer
- **Branding & Localisation**: Configure Store Name, Tagline, Address, Phone, Email, Currency Symbol (`$`, `₹`, `€`, `£`, etc.), and Currency Code.
- **Taxation & Shelf-Life Thresholds**: Configure default Sales Tax %, Critical Expiry Days (default: 30), and Warning Expiry Days (default: 60).
- **1-Click Web Setup Wizard (`install.php`)**: Connects to MySQL, generates database schema, sets up configuration files, and seeds realistic test data.

---

## 3. Technical & Non-Functional Architecture

| Layer | Technology / Implementation |
|---|---|
| **Backend Engine** | PHP 7.4+ / PHP 8.x |
| **Database Access** | MySQLi with 100% Prepared Statements (`$stmt->bind_param()`) |
| **Concurrency Control** | ACID Transactions with row-level locks (`$mysqli->begin_transaction()`, `$mysqli->commit()`, rollback) |
| **Frontend UI** | HTML5 Semantic Markup, Vanilla CSS3 (Custom Design System), JavaScript (ES6) |
| **Data Visualizations** | Chart.js 4.4+ (Daily Hourly Trends, 7-Day Revenue, Monthly Revenue & Profit Trends) |
| **Icons & Typography** | Google Font (*Inter*), FontAwesome 6 Free Vector Icons |
| **Security Standards** | CSRF Token Validation on all POST requests, XSS HTML Escaping (`e()` helper), Bcrypt Password Hashing |
| **Portability** | Plug-and-play compatibility with XAMPP, WAMP, Laragon, Apache, Nginx, and PHP Built-in Server |

---

## 4. User Roles & Access Matrix

| Feature / Operation | Administrator | Cashier / Staff |
|---|:---:|:---:|
| Executive Dashboard & Charts | ✅ | ✅ |
| Point of Sale (POS) Billing & Invoicing | ✅ | ✅ |
| Product Catalog Viewing & Details | ✅ | ✅ |
| Add / Edit Products | ✅ | ❌ |
| Restock Inventory (New Batches) | ✅ | ❌ |
| Stock Adjustments & Waste Write-off | ✅ | ❌ |
| Everyday & Monthly Sales Reports | ✅ | Read-Only |
| Leftover Stock & Expiry Reports | ✅ | Read-Only |
| Category & Supplier Management | ✅ | ❌ |
| System Configuration & Settings | ✅ | ❌ |
