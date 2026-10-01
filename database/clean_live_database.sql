-- ==============================================================================
-- 🚀 CLEAN LIVE DATABASE SCRIPT: ZERO STOCKS & WIPE INVOICES/CUSTOMERS
-- Compatible with MySQL 5.7+, MySQL 8.0+, MariaDB, and SQLite
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Wipe all Sales, Invoices, and Customer Transactions
DELETE FROM `sale_items`;
DELETE FROM `sales`;

-- 2. Wipe Stock Logs and Audit Adjustments
DELETE FROM `stock_adjustments`;
DELETE FROM `stock_in_logs`;

-- 3. Reset all Batch Stocks to 0 (Mark as sold_out, ready for fresh stock inward)
UPDATE `product_batches` 
SET `current_quantity` = 0, 
    `initial_quantity` = 0, 
    `status` = 'sold_out';

-- 4. Reset Auto-Increment Counters so fresh sales start from ID #1
ALTER TABLE `sale_items` AUTO_INCREMENT = 1;
ALTER TABLE `sales` AUTO_INCREMENT = 1;
ALTER TABLE `stock_adjustments` AUTO_INCREMENT = 1;
ALTER TABLE `stock_in_logs` AUTO_INCREMENT = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- Verification Query
-- ==============================================================================
SELECT 
    (SELECT COUNT(*) FROM `sales`) as remaining_sales,
    (SELECT COUNT(*) FROM `sale_items`) as remaining_sale_items,
    (SELECT SUM(`current_quantity`) FROM `product_batches`) as total_stock_units,
    (SELECT COUNT(*) FROM `product_batches`) as total_batches,
    (SELECT COUNT(*) FROM `products`) as total_products;
