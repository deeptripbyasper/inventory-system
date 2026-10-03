-- ==========================================================
-- Inventory Management System Master Sample Data
-- Shop: Bondhu Chol
-- Specialization: Fresh Milk, Amul Products & Ice Creams,
-- Cold Drinks & Beverages, Cadbury Chocolates & Confectionery
-- ==========================================================

-- 1. Default User Accounts (Password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `status`) VALUES
(1, 'admin', '$2y$10$wN3tV4m.w3zR9E8K6oH60upvP1L6E/K1J.6lA0uP1XQGv4Kwq7p2a', 'Administrator', 'admin@bondhuchol.com', 'admin', 'active'),
(2, 'cashier', '$2y$10$wN3tV4m.w3zR9E8K6oH60upvP1L6E/K1J.6lA0uP1XQGv4Kwq7p2a', 'Rahul Sharma (Cashier)', 'cashier@bondhuchol.com', 'cashier', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 2. Master Categories
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Fresh Milk & Pouches', 'Fresh pasteurized milk pouches (Taaza, Gold, Cow, Buffalo), slim milk, and daily dairy pouches'),
(2, 'Amul Products & Ice Creams', 'Pure dairy butter, paneer, cheese slices/cubes/blocks, fresh cream, ghee, and Amul ice cream tubs, cones, sticks and kulfi'),
(3, 'Cold Drinks & Soft Beverages', 'Carbonated sodas (Coca-Cola, Thums Up, Sprite, Pepsi, 7UP, Mountain Dew), fruit juices, energy drinks and packaged mineral water'),
(4, 'Cadbury Chocolates & Confectionery', 'Cadbury Dairy Milk, Silk variants, 5 Star, Perk, Fuse, Gems, Bournville dark chocolates, Celebrations gift boxes, Nutties & Choclairs')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `description` = VALUES(`description`);

-- 3. Authorized Suppliers & Depots
INSERT INTO `suppliers` (`id`, `name`, `contact_person`, `phone`, `email`, `address`) VALUES
(1, 'GCMMF Ltd. (Amul Fresh Milk & Dairy Depot)', 'Rajesh Patel', '+91 98250 12345', 'dairy.orders@amul.coop', 'Amul Milk Hub, Sector 12 Industrial Area'),
(2, 'Amul Ice Cream & Frozen Foods Depot', 'Kiran Desai', '+91 98250 67890', 'icecream.supply@amul.coop', 'Cold Storage Logistics Park, Bay 4'),
(3, 'Hindustan Coca-Cola Beverages Agency', 'Vikram Malhotra', '+91 98110 54321', 'supply@coca-cola-dist.in', 'Plot 45, Industrial Beverage Zone'),
(4, 'Mondelēz India Foods Ltd. (Cadbury Hub)', 'Anita Sharma', '+91 98765 43210', 'contact@mondelezin.com', 'Cadbury House, Confectionery Logistics Center'),
(5, 'PepsiCo India & Allied Beverages Agency', 'Sanjay Mehra', '+91 98300 67890', 'sales@pepsidist.in', 'Transport Nagar, Ring Road Depot')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `phone` = VALUES(`phone`), `email` = VALUES(`email`);

-- 4. Products Catalog (Milk, Amul Products, Cold Drinks, Cadbury Chocolates)
INSERT INTO `products` (`id`, `category_id`, `sku`, `barcode`, `name`, `description`, `unit`, `min_stock_alert`, `default_selling_price`, `status`) VALUES
-- 🥛 1. FRESH MILK & POUCHES
(1, 1, 'MILK-AMUL-TZ-500', '890126201001', 'Amul Taaza Toned Milk (500ml Pouch)', 'Homogenised pasteurised toned milk with 3.0% Fat & 8.5% SNF', 'pouch', 30, 27.00, 'active'),
(2, 1, 'MILK-AMUL-TZ-1L',  '890126201002', 'Amul Taaza Toned Milk (1 Litre Pouch)', 'Homogenised pasteurised toned milk 1L family pouch', 'pouch', 20, 54.00, 'active'),
(3, 1, 'MILK-AMUL-GD-500', '890126201003', 'Amul Gold Full Cream Milk (500ml Pouch)', 'Pasteurised rich full cream milk with 6.0% Fat & 9.0% SNF', 'pouch', 30, 33.00, 'active'),
(4, 1, 'MILK-AMUL-GD-1L',  '890126201004', 'Amul Gold Full Cream Milk (1 Litre Pouch)', 'Rich pasteurised full cream milk 1 Litre pouch', 'pouch', 20, 66.00, 'active'),
(5, 1, 'MILK-AMUL-COW-500','890126201005', 'Amul Cow Milk (500ml Pouch)', 'Pure wholesome cow milk with 3.5% Fat & 8.5% SNF', 'pouch', 20, 28.00, 'active'),
(6, 1, 'MILK-AMUL-BUF-500','890126201006', 'Amul Buffalo Milk (500ml Pouch)', 'Creamy rich high-fat fresh buffalo milk with 6.5% Fat', 'pouch', 15, 35.00, 'active'),
(7, 1, 'MILK-AMUL-TEA-500','890126201013', 'Amul Tea Special Milk (500ml Pouch)', 'Specially formulated rich milk for thick Indian tea & coffee', 'pouch', 20, 30.00, 'active'),
(8, 1, 'MILK-AMUL-SLM-500','890126201014', 'Amul Slim ''n'' Trim Skimmed Milk (500ml Pouch)', 'Zero cholesterol double toned skimmed diet milk (1.5% Fat)', 'pouch', 15, 25.00, 'active'),
(9, 1, 'MILK-AMUL-TZ-TP1L','890126201015', 'Amul Taaza Homogenised Toned Milk (1L Tetra Pak)', 'UHT treated long shelf life toned milk in 6-layer aseptic pack', 'pack', 10, 75.00, 'active'),
(10, 1, 'MILK-AMUL-GD-TP1L','890126201016', 'Amul Gold Homogenised Full Cream Milk (1L Tetra Pak)', 'UHT treated full cream rich milk 1L tetra pack', 'pack', 10, 85.00, 'active'),

-- 🍦 2. AMUL PRODUCTS & ICE CREAMS
(11, 2, 'AMUL-BUT-100', '890126201008', 'Amul Pasteurised Butter (100g Pack)', 'Iconic salted pure dairy butter 100g block', 'pack', 25, 58.00, 'active'),
(12, 2, 'AMUL-BUT-500', '890126201009', 'Amul Pasteurised Butter (500g Pack)', 'Utterly butterly delicious pure creamery butter 500g block', 'pack', 10, 275.00, 'active'),
(13, 2, 'AMUL-PAN-200', '890126201010', 'Amul Malai Fresh Paneer (200g Pack)', 'Soft and rich vacuum-packed high-protein cottage cheese', 'pack', 15, 90.00, 'active'),
(14, 2, 'AMUL-PAN-500', '890126201017', 'Amul Malai Fresh Paneer (500g Pack)', 'Fresh hygienic malai cottage cheese 500g family pack', 'pack', 10, 215.00, 'active'),
(15, 2, 'AMUL-CHS-200', '890126201011', 'Amul Processed Cheese Slices (200g / 10 Slices)', 'Individually wrapped creamy cheddar processed cheese slices', 'pack', 15, 145.00, 'active'),
(16, 2, 'AMUL-CHB-200', '890126201018', 'Amul Processed Cheese Block (200g)', 'Grateable rich cheddar processed cheese block', 'pack', 15, 130.00, 'active'),
(17, 2, 'AMUL-CHC-200', '890126201019', 'Amul Processed Cheese Cubes (200g / 8 Cubes)', 'Individually wrapped delicious bite-sized cheese cubes', 'pack', 15, 135.00, 'active'),
(18, 2, 'AMUL-DAHI-400', '890126201007', 'Amul Masti Dahi / Fresh Curd (400g Pouch)', 'Thick, creamy, pasteurised curd made from wholesome milk', 'pouch', 20, 35.00, 'active'),
(19, 2, 'AMUL-DAHI-200', '890126201020', 'Amul Masti Dahi Cup (200g Tub)', 'Convenient single-serve thick set curd cup', 'cup', 20, 25.00, 'active'),
(20, 2, 'AMUL-CRM-250', '890126201012', 'Amul Fresh Cream 25% Low Fat (250ml Tetra Pak)', 'Sterilised smooth low-fat fresh cooking cream', 'pack', 12, 70.00, 'active'),
(21, 2, 'AMUL-GHE-500', '890126201021', 'Amul Pure Ghee (500ml Refill Pouch)', 'Traditional aromatic pure cow & buffalo milk clarified butter', 'pouch', 10, 320.00, 'active'),
(22, 2, 'AMUL-GHE-1L',  '890126201022', 'Amul Pure Ghee Tin (1 Litre)', 'Golden aromatic granular pure desi ghee 1 Litre tin', 'tin', 8, 630.00, 'active'),
(23, 2, 'AMUL-MTH-400', '890126201023', 'Amul Mithai Mate Condensed Milk (400g Tin)', 'Sweetened condensed milk for festive Indian sweets and desserts', 'tin', 10, 140.00, 'active'),
(24, 2, 'AMUL-IC-VAN-1L', '890126202001', 'Amul Vanilla Magic Ice Cream Tub (1 Litre)', 'Creamy rich classic vanilla real milk ice cream tub', 'tub', 10, 160.00, 'active'),
(25, 2, 'AMUL-IC-CHO-1L', '890126202002', 'Amul Real Chocolate Choco Chips Ice Cream (1 Litre)', 'Rich cocoa ice cream loaded with crunchy chocolate chips', 'tub', 8, 240.00, 'active'),
(26, 2, 'AMUL-IC-BSC-1L', '890126202003', 'Amul Butterscotch Bliss Ice Cream Tub (1 Litre)', 'Caramel butterscotch ripple with crispy praline crunch', 'tub', 8, 210.00, 'active'),
(27, 2, 'AMUL-IC-MNG-1L', '890126202004', 'Amul Alphonso King Mango Ice Cream (1 Litre)', 'Real Alphonso mango pulp infused rich creamy ice cream', 'tub', 6, 230.00, 'active'),
(28, 2, 'AMUL-IC-NUT-1L', '890126202005', 'Amul American Nuts Ice Cream Tub (1 Litre)', 'Loaded with roasted almonds, cashews, and fruity swirls', 'tub', 6, 240.00, 'active'),
(29, 2, 'AMUL-TRI-CHO-120', '890126202006', 'Amul Tricone Choco Crunch Ice Cream Cone (120ml)', 'Crispy waffle cone filled with chocolate ice cream & choco topping', 'cone', 25, 45.00, 'active'),
(30, 2, 'AMUL-TRI-BSC-120', '890126202007', 'Amul Tricone Butterscotch Ice Cream Cone (120ml)', 'Waffle cone with butterscotch ice cream & roasted cashew praline', 'cone', 25, 40.00, 'active'),
(31, 2, 'AMUL-IC-KUL-120', '890126202008', 'Amul Shahi Kulfi Cone / Rajbhog (120ml)', 'Traditional royal saffron & cardamom ice cream cone', 'cone', 20, 40.00, 'active'),
(32, 2, 'AMUL-IC-FRO-70',  '890126202009', 'Amul Frostik Chocobar Ice Cream (70ml)', 'Vanilla cream bar coated in crisp milk chocolate shell', 'pcs', 30, 30.00, 'active'),
(33, 2, 'AMUL-IC-EPC-80',  '890126202010', 'Amul Epic Choco Almond Premium Bar (80ml)', 'Indulgent Belgian-style chocolate bar with roasted almonds', 'pcs', 20, 50.00, 'active'),
(34, 2, 'AMUL-IC-MAT-150', '890126202011', 'Amul Traditional Matka Kulfi (150ml)', 'Authentic clay matka filled with creamy pistachio rabdi kulfi', 'matka', 15, 60.00, 'active'),
(35, 2, 'AMUL-IC-CAS-150', '890126202012', 'Amul Cassata Cut Slice Multi-layer (150ml)', 'Multi-flavor layered ice cream with cake base and nut toppings', 'slice', 15, 50.00, 'active'),

-- 🥤 3. COLD DRINKS & SOFT BEVERAGES
(36, 3, 'BEV-COKE-750', '890176401001', 'Coca-Cola Original Taste (750ml PET Bottle)', 'Classic effervescent refreshing cola soft drink', 'bottle', 30, 40.00, 'active'),
(37, 3, 'BEV-COKE-CAN', '890176401002', 'Coca-Cola Classic Slim Can (300ml)', 'Chilled sparkling cola in a convenient sleek can', 'can', 24, 40.00, 'active'),
(38, 3, 'BEV-COKE-2L',  '890176401016', 'Coca-Cola Original Taste (2 Litre Family Bottle)', '2L large family party pack sparkling cola', 'bottle', 15, 95.00, 'active'),
(39, 3, 'BEV-THUM-750', '890176401003', 'Thums Up Charged Carbonated Drink (750ml PET)', 'Strong fizzy thunderous cola taste with spicy punch', 'bottle', 35, 40.00, 'active'),
(40, 3, 'BEV-THUM-CAN', '890176401004', 'Thums Up Strong Cola Can (300ml)', 'Taste the thunder strong carbonated cola can', 'can', 24, 40.00, 'active'),
(41, 3, 'BEV-THUM-2L',  '890176401017', 'Thums Up Strong Cola (2 Litre Party Bottle)', '2L large party bottle of thunderous strong cola', 'bottle', 15, 95.00, 'active'),
(42, 3, 'BEV-SPRT-750', '890176401005', 'Sprite Clear Lime Flavored Drink (750ml PET)', 'Crisp clear lemon-lime thirst-quenching soda', 'bottle', 30, 40.00, 'active'),
(43, 3, 'BEV-SPRT-CAN', '890176401006', 'Sprite Refreshing Lime Can (300ml)', 'Clear lemon-lime sparkling beverage in 300ml can', 'can', 24, 40.00, 'active'),
(44, 3, 'BEV-SPRT-2L',  '890176401018', 'Sprite Clear Lime Soda (2 Litre Family Bottle)', '2L family party pack refreshing clear lemon-lime soda', 'bottle', 15, 95.00, 'active'),
(45, 3, 'BEV-FANT-750', '890176401007', 'Fanta Orange Fruity Soda (750ml PET Bottle)', 'Vibrant sparkling orange soda bursting with citrus flavor', 'bottle', 20, 40.00, 'active'),
(46, 3, 'BEV-LIMC-750', '890176401008', 'Limca Fresh Lemon Drink (750ml PET Bottle)', 'Zesty lime and lemony fizz cloudy soft drink', 'bottle', 20, 40.00, 'active'),
(47, 3, 'BEV-MAAZ-600', '890176401009', 'Maaza Real Mango Pulp Drink (600ml Bottle)', 'Rich thick juice crafted with handpicked Alphonso mangoes', 'bottle', 25, 42.00, 'active'),
(48, 3, 'BEV-MAAZ-12L', '890176401019', 'Maaza Real Mango Drink (1.2 Litre Bottle)', 'Thick rich delicious mango fruit beverage 1.2L bottle', 'bottle', 15, 75.00, 'active'),
(49, 3, 'BEV-PEPS-750', '890176401010', 'Pepsi Carbonated Soft Drink (750ml PET Bottle)', 'Bold, refreshing, and crisp cola soft drink', 'bottle', 25, 40.00, 'active'),
(50, 3, 'BEV-MDEW-750', '890176401011', 'Mountain Dew Citrus Blast (750ml PET)', 'High-energy citrus soda - Darr Ke Aage Jeet Hai', 'bottle', 25, 40.00, 'active'),
(51, 3, 'BEV-7UP-750',  '890176401020', '7UP Lemon Lime Refreshing Soda (750ml PET)', 'Clear refreshing lemon-lime sparkling soft drink', 'bottle', 20, 40.00, 'active'),
(52, 3, 'BEV-MIR-750',  '890176401021', 'Mirinda Fizzy Orange Soda (750ml PET)', 'Fizzy tangy orange soda with bold fruity taste', 'bottle', 20, 40.00, 'active'),
(53, 3, 'BEV-RDBL-250', '890176401012', 'Red Bull Energy Drink (250ml Can)', 'Vitalizes body and mind premium energy drink can', 'can', 12, 125.00, 'active'),
(54, 3, 'BEV-STNG-250', '890176401013', 'Sting Energy Berry Blast Drink (250ml PET)', 'Electrifying berry flavored caffeinated energy beverage', 'bottle', 40, 20.00, 'active'),
(55, 3, 'BEV-APPY-600', '890176401014', 'Appy Fizz Sparkling Apple Juice (600ml)', 'Crisp bubbly sparkling apple drink with fruit juice', 'bottle', 20, 38.00, 'active'),
(56, 3, 'BEV-BISL-1L',  '890176401015', 'Bisleri Packaged Drinking Water (1 Litre)', 'Purified with minerals added packaged drinking water', 'bottle', 48, 20.00, 'active'),
(57, 3, 'BEV-BISL-500', '890176401022', 'Bisleri Packaged Drinking Water (500ml Bottle)', 'Compact on-the-go pure mineral drinking water', 'bottle', 48, 10.00, 'active'),

-- 🍫 4. CADBURY CHOCOLATES & CONFECTIONERY
(58, 4, 'CAD-CDM-13',   '890123301015', 'Cadbury Dairy Milk Chocolate Bar (13.2g Mini)', 'Pocket-friendly smooth and creamy milk chocolate mini bar', 'bar', 40, 10.00, 'active'),
(59, 4, 'CAD-CDM-24',   '890123301016', 'Cadbury Dairy Milk Chocolate Bar (24g Small)', 'Classic melt-in-mouth creamy milk chocolate bar', 'bar', 40, 20.00, 'active'),
(60, 4, 'CAD-CDM-50',   '890123301001', 'Cadbury Dairy Milk Chocolate Bar (50g Medium)', 'Standard delicious Cadbury Dairy Milk chocolate bar', 'bar', 30, 45.00, 'active'),
(61, 4, 'CAD-CDM-130',  '890123301017', 'Cadbury Dairy Milk Chocolate Bar (130g Family Pack)', 'Large family slab of creamy rich Cadbury Dairy Milk', 'bar', 15, 100.00, 'active'),
(62, 4, 'CAD-SILK-60',  '890123301002', 'Cadbury Dairy Milk Silk Chocolate (60g Bar)', 'Extra smooth, melt-in-mouth premium silk chocolate', 'bar', 25, 85.00, 'active'),
(63, 4, 'CAD-SILK-ALM', '890123301003', 'Cadbury Dairy Milk Silk Roast Almond (58g Bar)', 'Silk chocolate embedded with whole roasted crunchy almonds', 'bar', 20, 90.00, 'active'),
(64, 4, 'CAD-SILK-FN',  '890123301004', 'Cadbury Dairy Milk Silk Fruit & Nut (55g Bar)', 'Silk chocolate with juicy raisins and crunchy nuts', 'bar', 20, 90.00, 'active'),
(65, 4, 'CAD-SILK-ORE', '890123301005', 'Cadbury Dairy Milk Silk Oreo (60g Bar)', 'Creamy silk chocolate filled with crunchy Oreo cookie bits', 'bar', 15, 95.00, 'active'),
(66, 4, 'CAD-SILK-BUB', '890123301006', 'Cadbury Dairy Milk Silk Bubbly (50g Bar)', 'Melt-in-mouth bubbly aerated silk chocolate bar', 'bar', 15, 90.00, 'active'),
(67, 4, 'CAD-SILK-HAZ', '890123301018', 'Cadbury Dairy Milk Silk Hazelnut (58g Bar)', 'Rich silk chocolate infused with crunchy imported hazelnuts', 'bar', 15, 95.00, 'active'),
(68, 4, 'CAD-SILK-MOU', '890123301019', 'Cadbury Dairy Milk Silk Mousse (50g Bar)', 'Silk chocolate bar with a light, fluffy chocolate mousse centre', 'bar', 15, 90.00, 'active'),
(69, 4, 'CAD-5STR-20',  '890123301020', 'Cadbury 5 Star Chocolate Bar (20g Mini)', 'Chewy caramel and soft nougat chocolate bar', 'bar', 50, 10.00, 'active'),
(70, 4, 'CAD-5STR-40',  '890123301007', 'Cadbury 5 Star Chocolate Bar (40g Standard)', 'Caramel & nougat filled bar - Eat 5 Star, Do Nothing', 'bar', 40, 20.00, 'active'),
(71, 4, 'CAD-5STR-3D',  '890123301021', 'Cadbury 5 Star 3D Chocolate Bar (42g)', 'Crunchy biscuit and caramel wrapped in chocolate', 'bar', 25, 35.00, 'active'),
(72, 4, 'CAD-PERK-13',  '890123301022', 'Cadbury Perk Chocolate Wafer Bar (13g Mini)', 'Crisp wafer finger layered in sweet chocolate', 'bar', 60, 5.00, 'active'),
(73, 4, 'CAD-PERK-28',  '890123301008', 'Cadbury Perk Chocolate Wafer Bar (28g Standard)', 'Light, crispy wafer layers drenched in creamy chocolate', 'bar', 50, 10.00, 'active'),
(74, 4, 'CAD-FUSE-45',  '890123301009', 'Cadbury Fuse Peanut & Caramel Chocolate (45g)', 'Fudge feast with roasted peanuts and chewy caramel', 'bar', 25, 35.00, 'active'),
(75, 4, 'CAD-GEMS-10',  '890123301023', 'Cadbury Gems Chocolate Buttons (10.6g Mini)', 'Pocket pack of colourful chocolate buttons', 'pack', 50, 5.00, 'active'),
(76, 4, 'CAD-GEMS-23',  '890123301010', 'Cadbury Gems Chocolate Buttons (23g Pack)', 'Crisp candy-coated colourful chocolate buttons pack', 'pack', 40, 10.00, 'active'),
(77, 4, 'CAD-GEMS-SUR', '890123301024', 'Cadbury Gems Surprise Ball with Toy (17.4g)', 'Chocolate gems ball with an exciting collectible toy inside', 'ball', 20, 40.00, 'active'),
(78, 4, 'CAD-BRN-80',   '890123301011', 'Cadbury Bournville 70% Dark Chocolate (80g Bar)', 'Intense rich dark cocoa chocolate for true connoisseurs', 'bar', 15, 110.00, 'active'),
(79, 4, 'CAD-BRN-CRAN', '890123301025', 'Cadbury Bournville Cranberry Dark Chocolate (80g Bar)', 'Rich dark chocolate infused with real dried cranberries', 'bar', 15, 115.00, 'active'),
(80, 4, 'CAD-CEL-130',  '890123301012', 'Cadbury Celebrations Rich Chocolate Gift Pack (130g)', 'Assorted premium chocolate gift box for all occasions', 'box', 12, 150.00, 'active'),
(81, 4, 'CAD-CEL-186',  '890123301026', 'Cadbury Celebrations Premium Assorted Gift Box (186g)', 'Special assorted Dairy Milk & Silk festive gift hamper', 'box', 10, 225.00, 'active'),
(82, 4, 'CAD-NUT-30',   '890123301013', 'Cadbury Nutties Milk Chocolate Coated Cashews (30g)', 'Roasted cashew nuts enrobed in smooth milk chocolate', 'box', 15, 45.00, 'active'),
(83, 4, 'CAD-CHOC-50',  '890123301014', 'Cadbury Choclairs Gold Caramel Candies (Pack of 50)', 'Chewy golden caramel candies with rich chocolate center', 'pack', 10, 100.00, 'active')
ON DUPLICATE KEY UPDATE 
    `category_id` = VALUES(`category_id`),
    `name` = VALUES(`name`),
    `description` = VALUES(`description`),
    `default_selling_price` = VALUES(`default_selling_price`),
    `unit` = VALUES(`unit`),
    `min_stock_alert` = VALUES(`min_stock_alert`);

-- 5. Product Batches with Dynamic Real-World Expiry Dates
INSERT INTO `product_batches` (`id`, `product_id`, `supplier_id`, `batch_no`, `mfg_date`, `expiry_date`, `purchase_price`, `selling_price`, `initial_quantity`, `current_quantity`, `status`) VALUES
-- Fresh Milk Pouches (Short shelf life: 2-4 days)
(1, 1, 1, 'BAT-TZ500-01', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 24.50, 27.00, 0, 0, 'sold_out'),
(2, 1, 1, 'BAT-TZ500-02', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 24.50, 27.00, 0, 0, 'sold_out'),
(3, 2, 1, 'BAT-TZ1L-01',  DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 49.00, 54.00, 0, 0, 'sold_out'),
(4, 3, 1, 'BAT-GD500-01', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), 30.00, 33.00, 0, 0, 'sold_out'),
(5, 3, 1, 'BAT-GD500-02', DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 30.00, 33.00, 0, 0, 'sold_out'),
(6, 4, 1, 'BAT-GD1L-01',  DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), 60.00, 66.00, 0, 0, 'sold_out'),
(7, 5, 1, 'BAT-COW-01',   DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 25.50, 28.00, 0, 0, 'sold_out'),
(8, 6, 1, 'BAT-BUF-01',   DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 32.00, 35.00, 0, 0, 'sold_out'),
(9, 7, 1, 'BAT-TEA-01',   DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 27.00, 30.00, 0, 0, 'sold_out'),
(10, 8, 1, 'BAT-SLM-01',  DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 22.50, 25.00, 0, 0, 'sold_out'),
(11, 9, 1, 'BAT-TZTP-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 68.00, 75.00, 0, 0, 'sold_out'),
(12, 10, 1, 'BAT-GDTP-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 77.00, 85.00, 0, 0, 'sold_out'),

-- Amul Dairy Products
(13, 11, 1, 'BAT-BUT100-A', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 52.00, 58.00, 0, 0, 'sold_out'),
(14, 12, 1, 'BAT-BUT500-A', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 250.00, 275.00, 0, 0, 'sold_out'),
(15, 13, 1, 'BAT-PAN200-01', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 78.00, 90.00, 0, 0, 'sold_out'),
(16, 14, 1, 'BAT-PAN500-01', DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), 190.00, 215.00, 0, 0, 'sold_out'),
(17, 15, 1, 'BAT-CHS200-01', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 125.00, 145.00, 0, 0, 'sold_out'),
(18, 16, 1, 'BAT-CHB200-01', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 112.00, 130.00, 0, 0, 'sold_out'),
(19, 17, 1, 'BAT-CHC200-01', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 116.00, 135.00, 0, 0, 'sold_out'),
(20, 18, 1, 'BAT-DAHI-01',   DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), 30.00, 35.00, 0, 0, 'sold_out'),
(21, 19, 1, 'BAT-DAHI200-1', DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), 21.00, 25.00, 0, 0, 'sold_out'),
(22, 20, 1, 'BAT-CRM250-01', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 90 DAY), 60.00, 70.00, 0, 0, 'sold_out'),
(23, 21, 1, 'BAT-GHE500-01', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 270 DAY), 285.00, 320.00, 0, 0, 'sold_out'),
(24, 22, 1, 'BAT-GHE1L-01',  DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 270 DAY), 560.00, 630.00, 0, 0, 'sold_out'),
(25, 23, 1, 'BAT-MTH400-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 240 DAY), 120.00, 140.00, 0, 0, 'sold_out'),

-- Amul Ice Creams
(26, 24, 2, 'BAT-IC-VAN-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 240 DAY), 130.00, 160.00, 0, 0, 'sold_out'),
(27, 25, 2, 'BAT-IC-CHO-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 250 DAY), 195.00, 240.00, 0, 0, 'sold_out'),
(28, 26, 2, 'BAT-IC-BSC-01', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 245 DAY), 170.00, 210.00, 0, 0, 'sold_out'),
(29, 27, 2, 'BAT-IC-MNG-01', DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 260 DAY), 185.00, 230.00, 0, 0, 'sold_out'),
(30, 28, 2, 'BAT-IC-NUT-01', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 270 DAY), 195.00, 240.00, 0, 0, 'sold_out'),
(31, 29, 2, 'BAT-TRI-CHO-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 34.00, 45.00, 0, 0, 'sold_out'),
(32, 30, 2, 'BAT-TRI-BSC-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 30.00, 40.00, 0, 0, 'sold_out'),
(33, 31, 2, 'BAT-KUL-01',     DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 190 DAY), 30.00, 40.00, 0, 0, 'sold_out'),
(34, 32, 2, 'BAT-FRO-01',     DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 170 DAY), 22.00, 30.00, 0, 0, 'sold_out'),
(35, 33, 2, 'BAT-EPC-01',     DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 200 DAY), 38.00, 50.00, 0, 0, 'sold_out'),
(36, 34, 2, 'BAT-MAT-01',     DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 46.00, 60.00, 0, 0, 'sold_out'),
(37, 35, 2, 'BAT-CAS-01',     DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 180 DAY), 38.00, 50.00, 0, 0, 'sold_out'),

-- Cold Drinks & Soft Beverages
(38, 36, 3, 'BAT-COKE750-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(39, 37, 3, 'BAT-COKECAN-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 140 DAY), 31.00, 40.00, 0, 0, 'sold_out'),
(40, 38, 3, 'BAT-COKE2L-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 78.00, 95.00, 0, 0, 'sold_out'),
(41, 39, 3, 'BAT-THUM750-1', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(42, 40, 3, 'BAT-THUMCAN-1', DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 145 DAY), 31.00, 40.00, 0, 0, 'sold_out'),
(43, 41, 3, 'BAT-THUM2L-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 78.00, 95.00, 0, 0, 'sold_out'),
(44, 42, 3, 'BAT-SPRT750-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(45, 43, 3, 'BAT-SPRTCAN-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 31.00, 40.00, 0, 0, 'sold_out'),
(46, 44, 3, 'BAT-SPRT2L-01', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 78.00, 95.00, 0, 0, 'sold_out'),
(47, 45, 3, 'BAT-FANT750-1', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 135 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(48, 46, 3, 'BAT-LIMC750-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 140 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(49, 47, 3, 'BAT-MAAZ600-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 33.00, 42.00, 0, 0, 'sold_out'),
(50, 48, 3, 'BAT-MAAZ12L-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 60.00, 75.00, 0, 0, 'sold_out'),
(51, 49, 5, 'BAT-PEPS750-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(52, 50, 5, 'BAT-MDEW750-1', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(53, 51, 5, 'BAT-7UP750-01', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(54, 52, 5, 'BAT-MIR750-01', DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 155 DAY), 32.00, 40.00, 0, 0, 'sold_out'),
(55, 53, 3, 'BAT-RDBL250-1', DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 102.00, 125.00, 0, 0, 'sold_out'),
(56, 54, 5, 'BAT-STNG250-1', DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 160 DAY), 15.50, 20.00, 0, 0, 'sold_out'),
(57, 55, 3, 'BAT-APPY600-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 150 DAY), 30.00, 38.00, 0, 0, 'sold_out'),
(58, 56, 3, 'BAT-BISL1L-01', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 170 DAY), 13.00, 20.00, 0, 0, 'sold_out'),
(59, 57, 3, 'BAT-BISL500-1', DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 170 DAY), 6.50, 10.00, 0, 0, 'sold_out'),

-- Cadbury Chocolates & Sweets
(60, 58, 4, 'BAT-CDM13-01',  DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 7.80, 10.00, 0, 0, 'sold_out'),
(61, 59, 4, 'BAT-CDM24-01',  DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 15.50, 20.00, 0, 0, 'sold_out'),
(62, 60, 4, 'BAT-CDM50-01',  DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 36.00, 45.00, 0, 0, 'sold_out'),
(63, 61, 4, 'BAT-CDM130-1',  DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 315 DAY), 80.00, 100.00, 0, 0, 'sold_out'),
(64, 62, 4, 'BAT-SILK60-01', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 68.00, 85.00, 0, 0, 'sold_out'),
(65, 63, 4, 'BAT-SILKALM-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 72.00, 90.00, 0, 0, 'sold_out'),
(66, 64, 4, 'BAT-SILKFN-1',  DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 72.00, 90.00, 0, 0, 'sold_out'),
(67, 65, 4, 'BAT-SILKORE-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 76.00, 95.00, 0, 0, 'sold_out'),
(68, 66, 4, 'BAT-SILKBUB-1', DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 72.00, 90.00, 0, 0, 'sold_out'),
(69, 67, 4, 'BAT-SILKHAZ-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 76.00, 95.00, 0, 0, 'sold_out'),
(70, 68, 4, 'BAT-SILKMOU-1', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 72.00, 90.00, 0, 0, 'sold_out'),
(71, 69, 4, 'BAT-5STR20-01', DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 7.80, 10.00, 0, 0, 'sold_out'),
(72, 70, 4, 'BAT-5STR40-01', DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 15.50, 20.00, 0, 0, 'sold_out'),
(73, 71, 4, 'BAT-5STR3D-01', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 315 DAY), 27.50, 35.00, 0, 0, 'sold_out'),
(74, 72, 4, 'BAT-PERK13-01', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 3.90, 5.00, 0, 0, 'sold_out'),
(75, 73, 4, 'BAT-PERK28-01', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 7.80, 10.00, 0, 0, 'sold_out'),
(76, 74, 4, 'BAT-FUSE45-01', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 315 DAY), 27.50, 35.00, 0, 0, 'sold_out'),
(77, 75, 4, 'BAT-GEMS10-01', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 3.90, 5.00, 0, 0, 'sold_out'),
(78, 76, 4, 'BAT-GEMS23-01', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 7.80, 10.00, 0, 0, 'sold_out'),
(79, 77, 4, 'BAT-GEMSSUR-1', DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_ADD(CURDATE(), INTERVAL 315 DAY), 31.00, 40.00, 0, 0, 'sold_out'),
(80, 78, 4, 'BAT-BRN80-01',  DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 88.00, 110.00, 0, 0, 'sold_out'),
(81, 79, 4, 'BAT-BRNCRAN-1', DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_ADD(CURDATE(), INTERVAL 310 DAY), 92.00, 115.00, 0, 0, 'sold_out'),
(82, 80, 4, 'BAT-CEL130-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 120.00, 150.00, 0, 0, 'sold_out'),
(83, 81, 4, 'BAT-CEL186-01', DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 330 DAY), 180.00, 225.00, 0, 0, 'sold_out'),
(84, 82, 4, 'BAT-NUT30-01',   DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_ADD(CURDATE(), INTERVAL 320 DAY), 35.00, 45.00, 0, 0, 'sold_out'),
(85, 83, 4, 'BAT-CHOC50-01', DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_ADD(CURDATE(), INTERVAL 300 DAY), 80.00, 100.00, 0, 0, 'sold_out')
ON DUPLICATE KEY UPDATE 
    `purchase_price` = VALUES(`purchase_price`),
    `selling_price` = VALUES(`selling_price`),
    `expiry_date` = VALUES(`expiry_date`);

-- 6. Master Store Settings
INSERT INTO `settings` (`key_name`, `value_text`) VALUES
('store_name', 'Bondhu Chol'),
('store_tagline', 'Fresh Milk, Amul Dairy & Ice Creams, Cold Drinks & Cadbury Chocolates'),
('store_email', 'contact@bondhuchol.com'),
('store_phone', '+91 98765 00000 / +91 98300 00000'),
('store_address', '39, Satyen Roy Road, Behala, Kolkata - 700034.'),
('store_gstin', '19AAAAA0000A1Z5'),
('store_fssai', '10019021004321'),
('store_upi_id', 'bondhuchol@upi'),
('currency_symbol', '₹'),
('currency_code', 'INR'),
('tax_rate_percent', '5.00'),
('expiry_alert_days_critical', '3'),
('expiry_alert_days_warning', '7'),
('default_low_stock_threshold', '15')
ON DUPLICATE KEY UPDATE `value_text` = VALUES(`value_text`);
