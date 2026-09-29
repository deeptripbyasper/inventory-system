-- ==========================================================
-- Inventory Management System Sample Data
-- Shop: Amul Dairy, Ice Cream, Cold Drinks & Cadbury Express
-- Includes: Users, Categories, Suppliers, Products, Batches,
-- Sales Records, and System Settings.
-- ==========================================================

USE `inventory_db`;

-- 1. Default User Accounts (Password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$wN3tV4m.w3zR9E8K6oH60upvP1L6E/K1J.6lA0uP1XQGv4Kwq7p2a', 'Administrator', 'admin@inventorypro.local', 'admin', 'active'),
(2, 'cashier', '$2y$10$wN3tV4m.w3zR9E8K6oH60upvP1L6E/K1J.6lA0uP1XQGv4Kwq7p2a', 'Rahul Sharma (Cashier)', 'cashier@inventorypro.local', 'cashier', 'active');

-- 2. Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Amul Milk & Fresh Dairy', 'Fresh pasteurized milk pouches, curd, butter, paneer, cheese and dairy essentials'),
(2, 'Amul Ice Cream & Frozen Desserts', 'Ice cream tubs, family packs, cones, kulfi, cassata, bars and sticks'),
(3, 'Cold Drinks & Soft Beverages', 'Carbonated sodas, cola cans, PET bottles, fruit juices, energy drinks and mineral water'),
(4, 'Cadbury Chocolates & Confectionery', 'Dairy Milk, Silk variants, 5 Star, Perk, Fuse, Gems, dark chocolate and gift boxes');

-- 3. Suppliers
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'GCMMF Ltd. (Amul Milk & Dairy Depot)', 'Rajesh Patel', '+91 98250 12345', 'dairy.orders@amul.coop', 'Amul Dairy Complex, Anand / Sector 12, Main Hub'),
(2, 'Amul Ice Cream Distributing Agency', 'Kiran Desai', '+91 98250 67890', 'icecream.supply@amul.coop', 'Cold Storage Logistics Park, Bay 4'),
(3, 'Hindustan Coca-Cola Beverages Agency', 'Vikram Malhotra', '+91 98110 54321', 'supply@coca-cola-dist.in', 'Plot 45, Industrial Beverage Zone'),
(4, 'Mondelēz India Foods Ltd. (Cadbury Hub)', 'Anita Sharma', '+91 98765 43210', 'contact@mondelezin.com', 'Cadbury House, Confectionery Logistics Center'),
(5, 'PepsiCo India & Allied Beverages Agency', 'Sanjay Mehra', '+91 98300 67890', 'sales@pepsidist.in', 'Transport Nagar, Ring Road Depot');

-- 4. Products Master Data
INSERT INTO `products` (`id`, `category_id`, `sku`, `barcode`, `name`, `description`, `unit`, `min_stock_alert`, `default_selling_price`, `status`) VALUES
-- Amul Milk & Dairy Products
(1, 1, 'AMUL-TZ-500', '890126201001', 'Amul Taaza Toned Milk (500ml Pouch)', 'Homogenised pasteurised toned milk with 3.0% Fat & 8.5% SNF', 'pouch', 30, 27.00, 'active'),
(2, 1, 'AMUL-TZ-1L', '890126201002', 'Amul Taaza Toned Milk (1 Litre Pouch)', 'Homogenised pasteurised toned milk 1L family pouch', 'pouch', 20, 54.00, 'active'),
(3, 1, 'AMUL-GD-500', '890126201003', 'Amul Gold Full Cream Milk (500ml Pouch)', 'Pasteurised rich full cream milk with 6.0% Fat & 9.0% SNF', 'pouch', 30, 33.00, 'active'),
(4, 1, 'AMUL-GD-1L', '890126201004', 'Amul Gold Full Cream Milk (1 Litre Pouch)', 'Rich pasteurised full cream milk 1 Litre pouch', 'pouch', 20, 66.00, 'active'),
(5, 1, 'AMUL-COW-500', '890126201005', 'Amul Cow Milk (500ml Pouch)', 'Pure wholesome cow milk with 3.5% Fat & 8.5% SNF', 'pouch', 20, 28.00, 'active'),
(6, 1, 'AMUL-BUF-500', '890126201006', 'Amul Buffalo Milk (500ml Pouch)', 'Creamy rich high-fat fresh buffalo milk with 6.5% Fat', 'pouch', 15, 35.00, 'active'),
(7, 1, 'AMUL-DAHI-400', '890126201007', 'Amul Masti Dahi / Fresh Curd (400g Pouch)', 'Thick, creamy, pasteurised curd made from wholesome milk', 'pouch', 20, 35.00, 'active'),
(8, 1, 'AMUL-BUT-100', '890126201008', 'Amul Pasteurised Butter (100g Pack)', 'Iconic taste of India salted pure dairy butter 100g', 'pack', 25, 58.00, 'active'),
(9, 1, 'AMUL-BUT-500', '890126201009', 'Amul Pasteurised Butter (500g Pack)', 'Utterly butterly delicious pure creamery butter 500g block', 'pack', 10, 275.00, 'active'),
(10, 1, 'AMUL-PAN-200', '890126201010', 'Amul Malai Fresh Paneer (200g Pack)', 'Soft and rich vacuum-packed high-protein cottage cheese', 'pack', 15, 90.00, 'active'),
(11, 1, 'AMUL-CHS-200', '890126201011', 'Amul Processed Cheese Slices (200g / 10 Slices)', 'Individually wrapped creamy cheddar processed cheese slices', 'pack', 15, 145.00, 'active'),
(12, 1, 'AMUL-CRM-250', '890126201012', 'Amul Fresh Cream 25% Low Fat (250ml Tetra Pak)', 'Sterilised smooth low-fat fresh cooking cream', 'pack', 12, 70.00, 'active'),

-- Amul Ice Cream Variants
(13, 2, 'AMUL-IC-VAN-1L', '890126202001', 'Amul Vanilla Magic Ice Cream Tub (1 Litre)', 'Creamy rich classic vanilla real milk ice cream tub', 'tub', 10, 160.00, 'active'),
(14, 2, 'AMUL-IC-CHO-1L', '890126202002', 'Amul Real Chocolate Choco Chips Ice Cream (1 Litre)', 'Rich cocoa ice cream loaded with crunchy chocolate chips', 'tub', 8, 240.00, 'active'),
(15, 2, 'AMUL-IC-BSC-1L', '890126202003', 'Amul Butterscotch Bliss Ice Cream Tub (1 Litre)', 'Caramel butterscotch ripple with crispy praline crunch', 'tub', 8, 210.00, 'active'),
(16, 2, 'AMUL-IC-MNG-1L', '890126202004', 'Amul Alphonso King Mango Ice Cream (1 Litre)', 'Real Alphonso mango pulp infused rich creamy ice cream', 'tub', 6, 230.00, 'active'),
(17, 2, 'AMUL-IC-NUT-1L', '890126202005', 'Amul American Nuts Ice Cream Tub (1 Litre)', 'Loaded with roasted almonds, cashews, and fruity swirls', 'tub', 6, 240.00, 'active'),
(18, 2, 'AMUL-TRI-CHO-120', '890126202006', 'Amul Tricone Choco Crunch Ice Cream Cone (120ml)', 'Crispy waffle cone filled with chocolate ice cream & choco topping', 'cone', 25, 45.00, 'active'),
(19, 2, 'AMUL-TRI-BSC-120', '890126202007', 'Amul Tricone Butterscotch Ice Cream Cone (120ml)', 'Waffle cone with butterscotch ice cream & roasted cashew praline', 'cone', 25, 40.00, 'active'),
(20, 2, 'AMUL-IC-KUL-120', '890126202008', 'Amul Shahi Kulfi Cone / Rajbhog (120ml)', 'Traditional royal saffron & cardamom ice cream cone', 'cone', 20, 40.00, 'active'),
(21, 2, 'AMUL-IC-FRO-70', '890126202009', 'Amul Frostik Chocobar Ice Cream (70ml)', 'Vanilla cream bar coated in crisp milk chocolate shell', 'pcs', 30, 30.00, 'active'),
(22, 2, 'AMUL-IC-EPC-80', '890126202010', 'Amul Epic Choco Almond Premium Bar (80ml)', 'Indulgent Belgian-style chocolate bar with roasted almonds', 'pcs', 20, 50.00, 'active'),
(23, 2, 'AMUL-IC-MAT-150', '890126202011', 'Amul Traditional Matka Kulfi (150ml)', 'Authentic clay matka filled with creamy pistachio rabdi kulfi', 'matka', 15, 60.00, 'active'),
(24, 2, 'AMUL-IC-CAS-150', '890126202012', 'Amul Cassata Cut Slice Multi-layer (150ml)', 'Multi-flavor layered ice cream with cake base and nut toppings', 'slice', 15, 50.00, 'active'),

-- Cold Drinks & Soft Beverages
(25, 3, 'BEV-COKE-750', '890176401001', 'Coca-Cola Original Taste (750ml PET Bottle)', 'Classic effervescent refreshing cola soft drink', 'bottle', 30, 40.00, 'active'),
(26, 3, 'BEV-COKE-CAN', '890176401002', 'Coca-Cola Classic Slim Can (300ml)', 'Chilled sparkling cola in a convenient sleek can', 'can', 24, 40.00, 'active'),
(27, 3, 'BEV-THUM-750', '890176401003', 'Thums Up Charged Carbonated Drink (750ml PET)', 'Strong fizzy thunderous cola taste with spicy punch', 'bottle', 35, 40.00, 'active'),
(28, 3, 'BEV-THUM-CAN', '890176401004', 'Thums Up Strong Cola Can (300ml)', 'Taste the thunder strong carbonated cola can', 'can', 24, 40.00, 'active'),
(29, 3, 'BEV-SPRT-750', '890176401005', 'Sprite Clear Lime Flavored Drink (750ml PET)', 'Crisp clear lemon-lime thirst-quenching soda', 'bottle', 30, 40.00, 'active'),
(30, 3, 'BEV-SPRT-CAN', '890176401006', 'Sprite Refreshing Lime Can (300ml)', 'Clear lemon-lime sparkling beverage in 300ml can', 'can', 24, 40.00, 'active'),
(31, 3, 'BEV-FANT-750', '890176401007', 'Fanta Orange Fruity Soda (750ml PET Bottle)', 'Vibrant sparkling orange soda bursting with citrus flavor', 'bottle', 20, 40.00, 'active'),
(32, 3, 'BEV-LIMC-750', '890176401008', 'Limca Fresh Lemon Drink (750ml PET Bottle)', 'Zesty lime and lemony fizz cloudy soft drink', 'bottle', 20, 40.00, 'active'),
(33, 3, 'BEV-MAAZ-600', '890176401009', 'Maaza Real Mango Pulp Drink (600ml Bottle)', 'Rich thick juice crafted with handpicked Alphonso mangoes', 'bottle', 25, 42.00, 'active'),
(34, 3, 'BEV-PEPS-750', '890176401010', 'Pepsi Carbonated Soft Drink (750ml PET Bottle)', 'Bold, refreshing, and crisp cola soft drink', 'bottle', 25, 40.00, 'active'),
(35, 3, 'BEV-MDEW-750', '890176401011', 'Mountain Dew Citrus Blast (750ml PET)', 'High-energy citrus soda - Darr Ke Aage Jeet Hai', 'bottle', 25, 40.00, 'active'),
(36, 3, 'BEV-RDBL-250', '890176401012', 'Red Bull Energy Drink (250ml Can)', 'Vitalizes body and mind premium energy drink can', 'can', 12, 125.00, 'active'),
(37, 3, 'BEV-STNG-250', '890176401013', 'Sting Energy Berry Blast Drink (250ml PET)', 'Electrifying berry flavored caffeinated energy beverage', 'bottle', 40, 20.00, 'active'),
(38, 3, 'BEV-APPY-600', '890176401014', 'Appy Fizz Sparkling Apple Juice (600ml)', 'Crisp bubbly sparkling apple drink with fruit juice', 'bottle', 20, 38.00, 'active'),
(39, 3, 'BEV-BISL-1L', '890176401015', 'Bisleri Packaged Drinking Water (1 Litre)', 'Purified with minerals added packaged drinking water', 'bottle', 48, 20.00, 'active'),

-- Cadbury Chocolates
(40, 4, 'CAD-CDM-50', '890123301001', 'Cadbury Dairy Milk Chocolate Bar (50g)', 'Classic smooth and creamy milk chocolate bar', 'bar', 30, 45.00, 'active'),
(41, 4, 'CAD-SILK-60', '890123301002', 'Cadbury Dairy Milk Silk Chocolate (60g Bar)', 'Extra smooth, melt-in-mouth premium silk chocolate', 'bar', 25, 85.00, 'active'),
(42, 4, 'CAD-SILK-ALM', '890123301003', 'Cadbury Dairy Milk Silk Roast Almond (58g Bar)', 'Silk chocolate embedded with whole roasted crunchy almonds', 'bar', 20, 90.00, 'active'),
(43, 4, 'CAD-SILK-FN', '890123301004', 'Cadbury Dairy Milk Silk Fruit & Nut (55g Bar)', 'Silk chocolate with juicy raisins and crunchy nuts', 'bar', 20, 90.00, 'active'),
(44, 4, 'CAD-SILK-ORE', '890123301005', 'Cadbury Dairy Milk Silk Oreo (60g Bar)', 'Creamy silk chocolate filled with crunchy Oreo cookie bits', 'bar', 15, 95.00, 'active'),
(45, 4, 'CAD-SILK-BUB', '890123301006', 'Cadbury Dairy Milk Silk Bubbly (50g Bar)', 'Melt-in-mouth bubbly aerated silk chocolate bar', 'bar', 15, 90.00, 'active'),
(46, 4, 'CAD-5STR-40', '890123301007', 'Cadbury 5 Star Chocolate Bar (40g)', 'Chewy caramel and soft nougat covered in milk chocolate', 'bar', 40, 20.00, 'active'),
(47, 4, 'CAD-PERK-28', '890123301008', 'Cadbury Perk Chocolate Wafer Bar (28g)', 'Light, crispy wafer layers drenched in creamy chocolate', 'bar', 50, 10.00, 'active'),
(48, 4, 'CAD-FUSE-45', '890123301009', 'Cadbury Fuse Peanut & Caramel Chocolate (45g)', 'Fudge feast with roasted peanuts and chewy caramel', 'bar', 25, 35.00, 'active'),
(49, 4, 'CAD-GEMS-23', '890123301010', 'Cadbury Gems Chocolate Buttons (23g Pack)', 'Crisp candy-coated colourful chocolate buttons pack', 'pack', 40, 10.00, 'active'),
(50, 4, 'CAD-BRN-80', '890123301011', 'Cadbury Bournville 70% Dark Chocolate (80g Bar)', 'Intense rich dark cocoa chocolate for true connoisseurs', 'bar', 15, 110.00, 'active'),
(51, 4, 'CAD-CEL-130', '890123301012', 'Cadbury Celebrations Rich Chocolate Gift Pack (130g)', 'Assorted premium chocolate gift box for all occasions', 'box', 12, 150.00, 'active'),
(52, 4, 'CAD-NUT-30', '890123301013', 'Cadbury Nutties Milk Chocolate Coated Cashews (30g)', 'Roasted cashew nuts enrobed in smooth milk chocolate', 'box', 15, 45.00, 'active'),
(53, 4, 'CAD-CHOC-50', '890123301014', 'Cadbury Choclairs Gold Caramel Candies (Pack of 50)', 'Chewy golden caramel candies with rich chocolate center', 'pack', 10, 100.00, 'active');

-- 5. Product Batches (Configured with dynamic dates relative to CURDATE() for realistic expiry & stock alerts)
INSERT INTO `product_batches` (`id`, `product_id`, `supplier_id`, `batch_no`, `mfg_date`, `expiry_date`, `purchase_price`, `selling_price`, `initial_quantity`, `current_quantity`, `status`) VALUES
-- Amul Milk Pouches (Short shelf life: 2-7 days)
(1, 1, 1, 'BAT-TZ500-01', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 24.50, 27.00, 60, 18, 'available'),
(2, 1, 1, 'BAT-TZ500-02', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 24.50, 27.00, 100, 85, 'available'),
(3, 2, 1, 'BAT-TZ1L-01',  DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 49.00, 54.00, 50, 38, 'available'),
(4, 3, 1, 'BAT-GD500-01', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 30.00, 33.00, 60, 22, 'available'),
(5, 3, 1, 'BAT-GD500-02', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 30.00, 33.00, 100, 90, 'available'),
(6, 4, 1, 'BAT-GD1L-01',  DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 60.00, 66.00, 40, 28, 'available'),
(7, 5, 1, 'BAT-COW-01',   DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 25.50, 28.00, 50, 35, 'available'),
(8, 6, 1, 'BAT-BUF-01',   DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 32.00, 35.00, 40, 24, 'available'),
(9, 7, 1, 'BAT-DAHI-01',  DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 30.00, 35.00, 40, 26, 'available'),
(10, 8, 1, 'BAT-BUT100-A', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 52.00, 58.00, 80, 55, 'available'),
(11, 9, 1, 'BAT-BUT500-A', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 250.00, 275.00, 30, 20, 'available'),
(12, 10, 1, 'BAT-PAN-01',  DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 78.00, 90.00, 35, 18, 'available'),
(13, 11, 1, 'BAT-CHS-01',  DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 125.00, 145.00, 40, 30, 'available'),
(14, 12, 1, 'BAT-CRM-01',  DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 60.00, 70.00, 30, 22, 'available'),

-- Amul Ice Cream Batches (6-12 months shelf life)
(15, 13, 2, 'BAT-IC-VAN-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 240 DAY), 130.00, 160.00, 25, 16, 'available'),
(16, 14, 2, 'BAT-IC-CHO-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 250 DAY), 195.00, 240.00, 20, 14, 'available'),
(17, 15, 2, 'BAT-IC-BSC-01', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 245 DAY), 170.00, 210.00, 20, 15, 'available'),
(18, 16, 2, 'BAT-IC-MNG-01', DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 260 DAY), 185.00, 230.00, 18, 12, 'available'),
(19, 17, 2, 'BAT-IC-NUT-01', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 270 DAY), 195.00, 240.00, 18, 14, 'available'),
(20, 18, 2, 'BAT-TRI-CHO-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 34.00, 45.00, 60, 45, 'available'),
(21, 19, 2, 'BAT-TRI-BSC-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 30.00, 40.00, 60, 48, 'available'),
(22, 20, 2, 'BAT-KUL-01',     DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 190 DAY), 30.00, 40.00, 50, 38, 'available'),
(23, 21, 2, 'BAT-FRO-01',     DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 170 DAY), 22.00, 30.00, 70, 52, 'available'),
(24, 22, 2, 'BAT-EPC-01',     DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 38.00, 50.00, 50, 36, 'available'),
(25, 23, 2, 'BAT-MAT-01',     DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 46.00, 60.00, 30, 22, 'available'),
(26, 24, 2, 'BAT-CAS-01',     DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 38.00, 50.00, 35, 25, 'available'),

-- Cold Drinks Batches
(27, 25, 3, 'BAT-COKE-750-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 32.00, 40.00, 80, 65, 'available'),
(28, 26, 3, 'BAT-COKE-CAN-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 140 DAY), 31.00, 40.00, 48, 36, 'available'),
(29, 27, 3, 'BAT-THUM-750-1', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 90, 72, 'available'),
(30, 28, 3, 'BAT-THUM-CAN-1', DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 145 DAY), 31.00, 40.00, 48, 38, 'available'),
(31, 29, 3, 'BAT-SPRT-750-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 32.00, 40.00, 75, 58, 'available'),
(32, 30, 3, 'BAT-SPRT-CAN-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 31.00, 40.00, 48, 35, 'available'),
(33, 31, 3, 'BAT-FANT-750-1', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 135 DAY), 32.00, 40.00, 50, 38, 'available'),
(34, 32, 3, 'BAT-LIMC-750-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 140 DAY), 32.00, 40.00, 50, 40, 'available'),
(35, 33, 3, 'BAT-MAAZ-600-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 33.00, 42.00, 60, 46, 'available'),
(36, 34, 5, 'BAT-PEPS-750-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 32.00, 40.00, 60, 48, 'available'),
(37, 35, 5, 'BAT-MDEW-750-1', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 60, 45, 'available'),
(38, 36, 3, 'BAT-RDBL-250-1', DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 102.00, 125.00, 36, 26, 'available'),
(39, 37, 5, 'BAT-STNG-250-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 15.50, 20.00, 100, 80, 'available'),
(40, 38, 3, 'BAT-APPY-600-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 30.00, 38.00, 50, 38, 'available'),
(41, 39, 3, 'BAT-BISL-1L-01', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 170 DAY), 13.00, 20.00, 120, 95, 'available'),

-- Cadbury Chocolates Batches
(42, 40, 4, 'BAT-CDM50-01',  DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 36.00, 45.00, 80, 62, 'available'),
(43, 41, 4, 'BAT-SILK60-01', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 68.00, 85.00, 60, 44, 'available'),
(44, 42, 4, 'BAT-SILKALM-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 72.00, 90.00, 40, 28, 'available'),
(45, 43, 4, 'BAT-SILKFN-1',  DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 72.00, 90.00, 40, 30, 'available'),
(46, 44, 4, 'BAT-SILKORE-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 76.00, 95.00, 35, 25, 'available'),
(47, 45, 4, 'BAT-SILKBUB-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 72.00, 90.00, 35, 26, 'available'),
(48, 46, 4, 'BAT-5STR-01',   DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 15.50, 20.00, 100, 75, 'available'),
(49, 47, 4, 'BAT-PERK-01',   DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 7.80, 10.00, 150, 115, 'available'),
(50, 48, 4, 'BAT-FUSE-01',   DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 315 DAY), 27.50, 35.00, 60, 42, 'available'),
(51, 49, 4, 'BAT-GEMS-01',   DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 7.80, 10.00, 120, 88, 'available'),
(52, 50, 4, 'BAT-BRN-01',    DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 88.00, 110.00, 30, 20, 'available'),
(53, 51, 4, 'BAT-CEL-01',    DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 120.00, 150.00, 25, 18, 'available'),
(54, 52, 4, 'BAT-NUT-01',    DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 35.00, 45.00, 35, 24, 'available'),
(55, 53, 4, 'BAT-CHOC-01',   DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 80.00, 100.00, 25, 16, 'available');

-- 6. Sales Master and Detailed Items
INSERT INTO `sales` (`id`, `invoice_no`, `user_id`, `customer_name`, `customer_phone`, `sale_date`, `subtotal`, `discount`, `tax`, `grand_total`, `amount_paid`, `change_returned`, `payment_method`, `status`, `notes`) VALUES
(1, 'INV-20260926-001', 1, 'Priya Sharma', '+91 98765 11223', NOW(), 232.00, 10.00, 11.10, 233.10, 250.00, 16.90, 'cash', 'completed', 'Morning Milk & Dairy essentials'),
(2, 'INV-20260926-002', 2, 'Rohan Verma', '+91 98234 44556', DATE_SUB(NOW(), INTERVAL 1 HOUR), 365.00, 0.00, 18.25, 383.25, 383.25, 0.00, 'upi', 'completed', 'Ice cream and chocolates combo'),
(3, 'INV-20260925-001', 1, 'Amit Sengupta', '+91 98190 77889', DATE_SUB(NOW(), INTERVAL 1 DAY), 410.00, 20.00, 19.50, 409.50, 500.00, 90.50, 'cash', 'completed', 'Party snacks and cold drinks'),
(4, 'INV-20260924-001', 2, 'Neha Gupta', '+91 97654 33221', DATE_SUB(NOW(), INTERVAL 2 DAY), 520.00, 25.00, 24.75, 519.75, 519.75, 0.00, 'card', 'completed', 'Cadbury Celebration & Silk box'),
(5, 'INV-20260922-001', 1, 'Kunal Kapoor', '+91 98311 99001', DATE_SUB(NOW(), INTERVAL 4 DAY), 314.00, 0.00, 15.70, 329.70, 329.70, 0.00, 'upi', 'completed', 'Amul Butter & Dairy Milk');

INSERT INTO `sale_items` (`sale_id`, `product_id`, `batch_id`, `quantity`, `unit_cost_price`, `unit_price`, `subtotal`) VALUES
-- Sale 1
(1, 1, 2, 2, 24.50, 27.00, 54.00),
(1, 3, 5, 2, 30.00, 33.00, 66.00),
(1, 7, 9, 1, 30.00, 35.00, 35.00),
(1, 8, 10, 1, 52.00, 58.00, 58.00),
(1, 47, 49, 1, 7.80, 10.00, 10.00),

-- Sale 2
(2, 14, 16, 1, 195.00, 240.00, 240.00),
(2, 41, 43, 1, 68.00, 85.00, 85.00),
(2, 46, 48, 2, 15.50, 20.00, 40.00),

-- Sale 3
(3, 25, 27, 2, 32.00, 40.00, 80.00),
(3, 27, 29, 2, 32.00, 40.00, 80.00),
(3, 18, 20, 2, 34.00, 45.00, 90.00),
(3, 13, 15, 1, 130.00, 160.00, 160.00),

-- Sale 4
(4, 51, 53, 2, 120.00, 150.00, 300.00),
(4, 42, 44, 1, 72.00, 90.00, 90.00),
(4, 44, 46, 1, 76.00, 95.00, 95.00),
(4, 47, 49, 3, 7.80, 10.00, 30.00),

-- Sale 5
(5, 9, 11, 1, 250.00, 275.00, 275.00),
(5, 40, 42, 1, 36.00, 45.00, 45.00);

-- 7. System Settings
INSERT INTO `settings` (`key_name`, `value_text`) VALUES
('store_name', 'Amul Dairy, Cold Drinks & Cadbury Express'),
('store_tagline', 'Fresh Amul Milk, Ice Creams, Chilled Cold Drinks & Cadbury Chocolates'),
('store_email', 'contact@amuldairyexpress.local'),
('store_phone', '+91 98765 00000 / +91 98250 11111'),
('store_address', 'Shop No. 4 & 5, Crystal Commercial Plaza, Near Main Market, Sector 15'),
('store_gstin', '24AAAAA0000A1Z5'),
('store_fssai', '10019021004321'),
('store_upi_id', 'amuldairyexpress@upi'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('tax_rate_percent', '5.00'),
('expiry_alert_days_critical', '3'),
('expiry_alert_days_warning', '7'),
('default_low_stock_threshold', '15');
