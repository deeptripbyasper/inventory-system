# 🏪 Amul Dairy, Cold Drinks & Cadbury Express - Inventory & POS Billing System

A modern, high-performance, full-featured Retail Inventory Management and Smart Billing System developed in **PHP 8.x**, **MySQLi / SQLite (with Prepared Statements & Atomic Transactions)**, **HTML5**, **CSS3**, and **Vanilla JavaScript**.

Custom-tailored for retail shops selling **Amul Milk & Fresh Dairy**, **Amul Ice Cream variants**, **Chilled Cold Drinks & Soft Beverages**, and **Cadbury Chocolates & Confectionery**.

---

## 🌟 Key Features

### 1. 🛒 Point of Sale (POS) & Smart Billing Terminal
- **Fast Barcode Scanner Entry**: Auto-scans product barcodes (`890...`) or SKUs with instant cart add upon scan or `Enter`.
- **Category Filter Pills**: Quick one-click category filtering with real-time product counts:
  - 🥛 **Amul Milk & Fresh Dairy** (Taaza, Gold, Cow Milk, Buffalo Milk, Curd/Dahi, Butter, Paneer, Cheese, Fresh Cream)
  - 🍦 **Amul Ice Cream & Frozen Treats** (Vanilla Tub, Choco Chips, Butterscotch, Mango, American Nuts, Tricone, Kulfi, Cassata, Epic Bar, Frostik)
  - 🥤 **Cold Drinks & Soft Beverages** (Coca-Cola, Thums Up, Sprite, Fanta, Limca, Maaza, Pepsi, Mountain Dew, Red Bull, Sting, Appy Fizz, Bisleri)
  - 🍫 **Cadbury Chocolates** (Dairy Milk, Silk variants, 5 Star, Perk, Fuse, Gems, Bournville Dark, Celebrations, Nutties, Choclairs)
- **First-Expire, First-Out (FEFO) Batch Auto-Allocation**: Prioritizes selling units from the nearest expiring batch (critical for perishable milk and dairy!).
- **Smart Cash Tender & Change Calculator**: Instant change calculation with quick tender pills (`Exact`, `₹50`, `₹100`, `₹200`, `₹500`).
- **Multiple Payment Modes**: Cash, UPI / Dynamic QR Code (GPay, PhonePe, Paytm), Card, and Store Credit / Khata.
- **Park / Hold Bill Feature**: Park a customer's active cart and resume anytime from the "Held Bills" drawer.
- **Shortcuts**: `F2` (Search/Scan), `F4` (Cash Tender), `F8` (Print Thermal), `Esc` (Clear).

### 2. 🖨️ Dual-Mode Bill & Invoice Printing System
- **80mm / 58mm POS Thermal Roll Receipt**:
  - Store Header, Address, Phone, GSTIN & FSSAI License Number.
  - Itemized table with Batch No, Expiry Date, Qty, Rate, and Amount.
  - Subtotal, Trade Discount, CGST (2.5%) & SGST (2.5%) Tax breakdown, Grand Total.
  - Cash Tendered & Change Returned.
  - UPI Payment QR Code & Code128 Barcode representation.
  - Tailored `@media print` CSS for 80mm roll paper without margin clipping.
- **Standard A4 / A5 Tax Invoice**:
  - Full formal Tax Invoice layout with GST breakdown, HSN/SAC codes, Amount in Words (Indian numbering system format), Bank/UPI details, Terms & Conditions, and Authorized Signatory seal.
- **1-Click Mode Toggle**: Seamlessly switch between Thermal Receipt and A4 Tax Invoice with 1 click.
- **Automatic Auto-Print (`?autoprint=1`)**: Automatically triggers `window.print()` upon completing checkout.
- **Invoices & Sales History Terminal (`invoices.php`)**: Complete searchable history with one-click thermal and standard bill reprint buttons.

---

## 📂 Project Architecture

```
inventory_system/
├── index.php                      # Application entry & router
├── login.php                      # User authentication
├── logout.php                     # Session termination
├── install.php                    # 1-Click Graphical Web Installer
├── README.md                      # Documentation & setup guide
├── config/
│   ├── config.php                 # Global settings, constants, and session management
│   ├── database.php               # MySQLi Singleton Wrapper with Prepared Statements
│   └── db_custom.php              # Auto-generated database credentials
├── includes/
│   ├── header.php                 # HTML5 head, meta tags, fonts, icons, Chart.js
│   ├── navbar.php                 # Top navigation, theme toggle, quick shortcuts
│   ├── sidebar.php                # Navigation with live dynamic alert badges
│   ├── footer.php                 # Modals, scripts, and HTML closers
│   └── functions.php              # Expiry logic, stock badges, currency & CSRF helpers
├── modules/
│   ├── dashboard/index.php        # Executive dashboard & live charts
│   ├── products/                  # Product catalog, add, edit, 360-degree view
│   ├── batches/                   # Lot management, restock shipments, stock adjustments
│   ├── pos/                       # Point of Sale terminal, checkout, printable receipt
│   ├── reports/
│   │   ├── daily_sales.php        # Everyday Sales Report
│   │   ├── monthly_sales.php      # Monthly Sales Report
│   │   ├── leftover_stock.php     # Leftover Stock & Valuation Report
│   │   └── expiry_report.php      # Expiry Date Shelf-Life Tracking
│   ├── categories/index.php       # Product category management
│   ├── suppliers/index.php        # Supplier & vendor directory
│   └── settings/index.php         # System settings (Tax, Currency, Expiry rules)
├── api/
│   ├── search_products.php        # AJAX live search endpoint
│   └── chart_data.php             # Analytics chart JSON endpoint
├── database/
│   ├── schema.sql                 # Complete MySQL schema DDL
│   └── sample_data.sql            # Rich realistic demo dataset
└── assets/
    ├── css/style.css              # Custom modern CSS with Dark/Light theme system
    └── js/
        ├── app.js                 # Theme switcher, modals, table search, CSV exporter
        └── pos.js                 # Interactive shopping cart & billing logic
```

---

## 🚀 Quick Start Guide

### Option 1: Running with XAMPP / WAMP / Laragon
1. Copy or move the `inventory_system` folder into your web root directory (e.g. `C:\xampp\htdocs\inventory_system`).
2. Start **Apache** and **MySQL** in your XAMPP Control Panel.
3. Open your browser and navigate to:
   ```
   http://localhost/inventory_system/
   ```
4. If it's your first time, you will be automatically directed to `install.php`.
5. Enter your MySQL database credentials (default: Host `localhost`, User `root`, Password blank) and click **Run Installation & Initialize**.
6. Sign in with the default credentials below!

### Option 2: Running with PHP Built-in Server
1. Open PowerShell or Terminal in the `inventory_system` folder:
   ```bash
   php -S localhost:8000
   ```
2. Open `http://localhost:8000` in your web browser.
3. Follow the installation wizard.

---

## 🔑 Default Login Credentials

| Role | Username | Password |
|---|---|---|
| **Administrator** | `admin` | `admin123` |
| **Cashier / Staff** | `cashier` | `admin123` |

---

## 🛡️ Security & Quality Standards
- **SQL Injection Defense**: 100% Prepared Statements (`$mysqli->prepare()` & `$stmt->bind_param()`).
- **Cross-Site Scripting (XSS)**: Escaped outputs using `htmlspecialchars()` via `e()` helper.
- **Cross-Site Request Forgery (CSRF)**: Cryptographically secure token verification on all forms.
- **Strict Password Hashing**: Industry-standard `password_hash()` (bcrypt) with runtime re-hashing upgrade.
- **Installation Security Lock**: Automated `config/install.lock` to prevent unauthorized database resets on live servers.
- **Session Security**: Enforced `HttpOnly`, `SameSite=Lax`, and automated HTTPS `Secure` cookie flags.
- **Stock Concurrency Protection**: Atomic transactions (`$mysqli->begin_transaction()`, `$mysqli->commit()`, and rollback).
- **Responsive Layout**: Fluid CSS Grid and Flexbox supporting mobile, tablet, and widescreen desktop displays.

---

## 🌐 Live Hosting & Production Deployment

OmniStock is 100% ready for live hosting across all environments:
- **cPanel / Shared Hosting**: Upload files, run `/install.php` wizard or import SQL, and you're live.
- **Linux VPS (Ubuntu/Nginx/Apache)**: Nginx configuration (`nginx-site.conf`), Apache (`.htaccess`), and Let's Encrypt SSL ready.
- **Cloud Containers (Docker / Docker Compose)**: Run `docker-compose up -d` for instant deployment with MySQL 8.0 & phpMyAdmin.
- **Cloud PaaS (Railway, Render, Fly.io)**: Native `.env` support with `DATABASE_URL` automatic parsing and `/api/health.php` monitoring.

👉 For complete step-by-step production hosting instructions, see **[DEPLOYMENT.md](DEPLOYMENT.md)**.

