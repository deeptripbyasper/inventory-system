-- ==============================================================================
-- OmniStock Production Database Export
-- Generated: 2026-10-11 18:33:03
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('admin','manager','cashier') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30002;

INSERT INTO `users` VALUES
('1', 'admin', '$2y$10$/s.sNiwr0jkcjwHBmO4tG.aBP4s8sKBeLGbx09kRab5SwpvFs3x6u', 'Administrator', 'admin@bondhuchol.com', 'admin', 'active', '2026-10-11 09:00:21', '2026-10-02 22:43:46'),
('2', 'cashier', '$2y$10$/s.sNiwr0jkcjwHBmO4tG.aBP4s8sKBeLGbx09kRab5SwpvFs3x6u', 'Rahul Sharma (Cashier)', 'cashier@bondhuchol.com', 'cashier', 'active', NULL, '2026-10-02 22:43:46');

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=30002;

INSERT INTO `categories` VALUES
('1', 'Fresh Milk & Pouches', 'Fresh pasteurized milk pouches (Taaza, Gold, Cow, Buffalo), slim milk, and daily dairy pouches', '2026-10-02 22:43:47'),
('2', 'Amul Products & Ice Creams', 'Pure dairy butter, paneer, cheese slices/cubes/blocks, fresh cream, ghee, and Amul ice cream tubs, cones, sticks and kulfi', '2026-10-02 22:43:47'),
('3', 'Cold Drinks & Soft Beverages', 'Carbonated sodas (Coca-Cola, Thums Up, Sprite, Pepsi, 7UP, Mountain Dew), fruit juices, energy drinks and packaged mineral water', '2026-10-02 22:43:47'),
('4', 'Cadbury Chocolates & Confectionery', 'Cadbury Dairy Milk, Silk variants, 5 Star, Perk, Fuse, Gems, Bournville dark chocolates, Celebrations gift boxes, Nutties & Choclairs', '2026-10-02 22:43:47');

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contact_person` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=60002;

INSERT INTO `suppliers` VALUES
('1', 'GCMMF Ltd. (Amul Fresh Milk & Dairy Depot)', 'Rajesh Patel', '+91 98250 12345', 'dairy.orders@amul.coop', 'Amul Milk Hub, Sector 12 Industrial Area', '2026-10-02 22:43:47'),
('2', 'Amul Ice Cream & Frozen Foods Depot', 'Kiran Desai', '+91 98250 67890', 'icecream.supply@amul.coop', 'Cold Storage Logistics Park, Bay 4', '2026-10-02 22:43:47'),
('3', 'Hindustan Coca-Cola Beverages Agency', 'Vikram Malhotra', '+91 98110 54321', 'supply@coca-cola-dist.in', 'Plot 45, Industrial Beverage Zone', '2026-10-02 22:43:47'),
('4', 'Mondelēz India Foods Ltd. (Cadbury Hub)', 'Anita Sharma', '+91 98765 43210', 'contact@mondelezin.com', 'Cadbury House, Confectionery Logistics Center', '2026-10-02 22:43:47'),
('5', 'PepsiCo India & Allied Beverages Agency', 'Sanjay Mehra', '+91 98300 67890', 'sales@pepsidist.in', 'Transport Nagar, Ring Road Depot', '2026-10-02 22:43:47'),
('30002', 'Abhirup trader', 'Abhirup', '987678900', 'abhi@gmail.com', 'Kolkata', '2026-10-03 05:53:42');

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category_id` int DEFAULT NULL,
  `sku` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `barcode` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unit` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pcs',
  `min_stock_alert` int NOT NULL DEFAULT '10',
  `default_selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `status` enum('active','inactive') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  UNIQUE KEY `sku` (`sku`),
  KEY `fk_products_category` (`category_id`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=120002;

INSERT INTO `products` VALUES
('1', '1', 'MILK-AMUL-TZ-500', '890126201001', 'Amul Taaza Toned Milk (500ml Pouch)', 'Homogenised pasteurised toned milk with 3.0% Fat & 8.5% SNF', 'pouch', '30', '27.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('2', '1', 'MILK-AMUL-TZ-1L', '890126201002', 'Amul Taaza Toned Milk (1 Litre Pouch)', 'Homogenised pasteurised toned milk 1L family pouch', 'pouch', '20', '54.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('3', '1', 'MILK-AMUL-GD-500', '890126201003', 'Amul Gold Full Cream Milk (500ml Pouch)', 'Pasteurised rich full cream milk with 6.0% Fat & 9.0% SNF', 'pouch', '30', '33.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('4', '1', 'MILK-AMUL-GD-1L', '890126201004', 'Amul Gold Full Cream Milk (1 Litre Pouch)', 'Rich pasteurised full cream milk 1 Litre pouch', 'pouch', '20', '66.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('5', '1', 'MILK-AMUL-COW-500', '890126201005', 'Amul Cow Milk (500ml Pouch)', 'Pure wholesome cow milk with 3.5% Fat & 8.5% SNF', 'pouch', '20', '28.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('6', '1', 'MILK-AMUL-BUF-500', '890126201006', 'Amul Buffalo Milk (500ml Pouch)', 'Creamy rich high-fat fresh buffalo milk with 6.5% Fat', 'pouch', '15', '35.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('7', '1', 'MILK-AMUL-TEA-500', '890126201013', 'Amul Tea Special Milk (500ml Pouch)', 'Specially formulated rich milk for thick Indian tea & coffee', 'pouch', '20', '30.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('8', '1', 'MILK-AMUL-SLM-500', '890126201014', 'Amul Slim \'n\' Trim Skimmed Milk (500ml Pouch)', 'Zero cholesterol double toned skimmed diet milk (1.5% Fat)', 'pouch', '15', '25.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('9', '1', 'MILK-AMUL-TZ-TP1L', '890126201015', 'Amul Taaza Homogenised Toned Milk (1L Tetra Pak)', 'UHT treated long shelf life toned milk in 6-layer aseptic pack', 'pack', '10', '75.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('10', '1', 'MILK-AMUL-GD-TP1L', '890126201016', 'Amul Gold Homogenised Full Cream Milk (1L Tetra Pak)', 'UHT treated full cream rich milk 1L tetra pack', 'pack', '10', '85.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('11', '2', 'AMUL-BUT-100', '890126201008', 'Amul Pasteurised Butter (100g Pack)', 'Iconic salted pure dairy butter 100g block', 'pack', '25', '58.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('12', '2', 'AMUL-BUT-500', '890126201009', 'Amul Pasteurised Butter (500g Pack)', 'Utterly butterly delicious pure creamery butter 500g block', 'pack', '10', '275.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('13', '2', 'AMUL-PAN-200', '890126201010', 'Amul Malai Fresh Paneer (200g Pack)', 'Soft and rich vacuum-packed high-protein cottage cheese', 'pack', '15', '90.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('14', '2', 'AMUL-PAN-500', '890126201017', 'Amul Malai Fresh Paneer (500g Pack)', 'Fresh hygienic malai cottage cheese 500g family pack', 'pack', '10', '215.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('15', '2', 'AMUL-CHS-200', '890126201011', 'Amul Processed Cheese Slices (200g / 10 Slices)', 'Individually wrapped creamy cheddar processed cheese slices', 'pack', '15', '145.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('16', '2', 'AMUL-CHB-200', '890126201018', 'Amul Processed Cheese Block (200g)', 'Grateable rich cheddar processed cheese block', 'pack', '15', '130.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('17', '2', 'AMUL-CHC-200', '890126201019', 'Amul Processed Cheese Cubes (200g / 8 Cubes)', 'Individually wrapped delicious bite-sized cheese cubes', 'pack', '15', '135.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('18', '2', 'AMUL-DAHI-400', '890126201007', 'Amul Masti Dahi / Fresh Curd (400g Pouch)', 'Thick, creamy, pasteurised curd made from wholesome milk', 'pouch', '20', '35.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('19', '2', 'AMUL-DAHI-200', '890126201020', 'Amul Masti Dahi Cup (200g Tub)', 'Convenient single-serve thick set curd cup', 'cup', '20', '25.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('20', '2', 'AMUL-CRM-250', '890126201012', 'Amul Fresh Cream 25% Low Fat (250ml Tetra Pak)', 'Sterilised smooth low-fat fresh cooking cream', 'pack', '12', '70.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('21', '2', 'AMUL-GHE-500', '890126201021', 'Amul Pure Ghee (500ml Refill Pouch)', 'Traditional aromatic pure cow & buffalo milk clarified butter', 'pouch', '10', '320.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('22', '2', 'AMUL-GHE-1L', '890126201022', 'Amul Pure Ghee Tin (1 Litre)', 'Golden aromatic granular pure desi ghee 1 Litre tin', 'tin', '8', '630.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('23', '2', 'AMUL-MTH-400', '890126201023', 'Amul Mithai Mate Condensed Milk (400g Tin)', 'Sweetened condensed milk for festive Indian sweets and desserts', 'tin', '10', '140.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('24', '2', 'AMUL-IC-VAN-1L', '890126202001', 'Amul Vanilla Magic Ice Cream Tub (1 Litre)', 'Creamy rich classic vanilla real milk ice cream tub', 'tub', '10', '160.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('25', '2', 'AMUL-IC-CHO-1L', '890126202002', 'Amul Real Chocolate Choco Chips Ice Cream (1 Litre)', 'Rich cocoa ice cream loaded with crunchy chocolate chips', 'tub', '8', '240.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('26', '2', 'AMUL-IC-BSC-1L', '890126202003', 'Amul Butterscotch Bliss Ice Cream Tub (1 Litre)', 'Caramel butterscotch ripple with crispy praline crunch', 'tub', '8', '210.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('27', '2', 'AMUL-IC-MNG-1L', '890126202004', 'Amul Alphonso King Mango Ice Cream (1 Litre)', 'Real Alphonso mango pulp infused rich creamy ice cream', 'tub', '6', '230.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('28', '2', 'AMUL-IC-NUT-1L', '890126202005', 'Amul American Nuts Ice Cream Tub (1 Litre)', 'Loaded with roasted almonds, cashews, and fruity swirls', 'tub', '6', '240.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('29', '2', 'AMUL-TRI-CHO-120', '890126202006', 'Amul Tricone Choco Crunch Ice Cream Cone (120ml)', 'Crispy waffle cone filled with chocolate ice cream & choco topping', 'cone', '25', '45.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('30', '2', 'AMUL-TRI-BSC-120', '890126202007', 'Amul Tricone Butterscotch Ice Cream Cone (120ml)', 'Waffle cone with butterscotch ice cream & roasted cashew praline', 'cone', '25', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('31', '2', 'AMUL-IC-KUL-120', '890126202008', 'Amul Shahi Kulfi Cone / Rajbhog (120ml)', 'Traditional royal saffron & cardamom ice cream cone', 'cone', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('32', '2', 'AMUL-IC-FRO-70', '890126202009', 'Amul Frostik Chocobar Ice Cream (70ml)', 'Vanilla cream bar coated in crisp milk chocolate shell', 'pcs', '30', '30.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('33', '2', 'AMUL-IC-EPC-80', '890126202010', 'Amul Epic Choco Almond Premium Bar (80ml)', 'Indulgent Belgian-style chocolate bar with roasted almonds', 'pcs', '20', '50.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('34', '2', 'AMUL-IC-MAT-150', '890126202011', 'Amul Traditional Matka Kulfi (150ml)', 'Authentic clay matka filled with creamy pistachio rabdi kulfi', 'matka', '15', '60.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('35', '2', 'AMUL-IC-CAS-150', '890126202012', 'Amul Cassata Cut Slice Multi-layer (150ml)', 'Multi-flavor layered ice cream with cake base and nut toppings', 'slice', '15', '50.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('36', '3', 'BEV-COKE-750', '890176401001', 'Coca-Cola Original Taste (750ml PET Bottle)', 'Classic effervescent refreshing cola soft drink', 'bottle', '30', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('37', '3', 'BEV-COKE-CAN', '890176401002', 'Coca-Cola Classic Slim Can (300ml)', 'Chilled sparkling cola in a convenient sleek can', 'can', '24', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('38', '3', 'BEV-COKE-2L', '890176401016', 'Coca-Cola Original Taste (2 Litre Family Bottle)', '2L large family party pack sparkling cola', 'bottle', '15', '95.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('39', '3', 'BEV-THUM-750', '890176401003', 'Thums Up Charged Carbonated Drink (750ml PET)', 'Strong fizzy thunderous cola taste with spicy punch', 'bottle', '35', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('40', '3', 'BEV-THUM-CAN', '890176401004', 'Thums Up Strong Cola Can (300ml)', 'Taste the thunder strong carbonated cola can', 'can', '24', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('41', '3', 'BEV-THUM-2L', '890176401017', 'Thums Up Strong Cola (2 Litre Party Bottle)', '2L large party bottle of thunderous strong cola', 'bottle', '15', '95.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('42', '3', 'BEV-SPRT-750', '890176401005', 'Sprite Clear Lime Flavored Drink (750ml PET)', 'Crisp clear lemon-lime thirst-quenching soda', 'bottle', '30', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('43', '3', 'BEV-SPRT-CAN', '890176401006', 'Sprite Refreshing Lime Can (300ml)', 'Clear lemon-lime sparkling beverage in 300ml can', 'can', '24', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('44', '3', 'BEV-SPRT-2L', '890176401018', 'Sprite Clear Lime Soda (2 Litre Family Bottle)', '2L family party pack refreshing clear lemon-lime soda', 'bottle', '15', '95.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('45', '3', 'BEV-FANT-750', '890176401007', 'Fanta Orange Fruity Soda (750ml PET Bottle)', 'Vibrant sparkling orange soda bursting with citrus flavor', 'bottle', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('46', '3', 'BEV-LIMC-750', '890176401008', 'Limca Fresh Lemon Drink (750ml PET Bottle)', 'Zesty lime and lemony fizz cloudy soft drink', 'bottle', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('47', '3', 'BEV-MAAZ-600', '890176401009', 'Maaza Real Mango Pulp Drink (600ml Bottle)', 'Rich thick juice crafted with handpicked Alphonso mangoes', 'bottle', '25', '42.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('48', '3', 'BEV-MAAZ-12L', '890176401019', 'Maaza Real Mango Drink (1.2 Litre Bottle)', 'Thick rich delicious mango fruit beverage 1.2L bottle', 'bottle', '15', '75.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('49', '3', 'BEV-PEPS-750', '890176401010', 'Pepsi Carbonated Soft Drink (750ml PET Bottle)', 'Bold, refreshing, and crisp cola soft drink', 'bottle', '25', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('50', '3', 'BEV-MDEW-750', '890176401011', 'Mountain Dew Citrus Blast (750ml PET)', 'High-energy citrus soda - Darr Ke Aage Jeet Hai', 'bottle', '25', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('51', '3', 'BEV-7UP-750', '890176401020', '7UP Lemon Lime Refreshing Soda (750ml PET)', 'Clear refreshing lemon-lime sparkling soft drink', 'bottle', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('52', '3', 'BEV-MIR-750', '890176401021', 'Mirinda Fizzy Orange Soda (750ml PET)', 'Fizzy tangy orange soda with bold fruity taste', 'bottle', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('53', '3', 'BEV-RDBL-250', '890176401012', 'Red Bull Energy Drink (250ml Can)', 'Vitalizes body and mind premium energy drink can', 'can', '12', '125.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('54', '3', 'BEV-STNG-250', '890176401013', 'Sting Energy Berry Blast Drink (250ml PET)', 'Electrifying berry flavored caffeinated energy beverage', 'bottle', '40', '20.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('55', '3', 'BEV-APPY-600', '890176401014', 'Appy Fizz Sparkling Apple Juice (600ml)', 'Crisp bubbly sparkling apple drink with fruit juice', 'bottle', '20', '38.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('56', '3', 'BEV-BISL-1L', '890176401015', 'Bisleri Packaged Drinking Water (1 Litre)', 'Purified with minerals added packaged drinking water', 'bottle', '48', '20.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('57', '3', 'BEV-BISL-500', '890176401022', 'Bisleri Packaged Drinking Water (500ml Bottle)', 'Compact on-the-go pure mineral drinking water', 'bottle', '48', '10.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('58', '4', 'CAD-CDM-13', '890123301015', 'Cadbury Dairy Milk Chocolate Bar (13.2g Mini)', 'Pocket-friendly smooth and creamy milk chocolate mini bar', 'bar', '40', '10.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('59', '4', 'CAD-CDM-24', '890123301016', 'Cadbury Dairy Milk Chocolate Bar (24g Small)', 'Classic melt-in-mouth creamy milk chocolate bar', 'bar', '40', '20.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('60', '4', 'CAD-CDM-50', '890123301001', 'Cadbury Dairy Milk Chocolate Bar (50g Medium)', 'Standard delicious Cadbury Dairy Milk chocolate bar', 'bar', '30', '45.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('61', '4', 'CAD-CDM-130', '890123301017', 'Cadbury Dairy Milk Chocolate Bar (130g Family Pack)', 'Large family slab of creamy rich Cadbury Dairy Milk', 'bar', '15', '100.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('62', '4', 'CAD-SILK-60', '890123301002', 'Cadbury Dairy Milk Silk Chocolate (60g Bar)', 'Extra smooth, melt-in-mouth premium silk chocolate', 'bar', '25', '85.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('63', '4', 'CAD-SILK-ALM', '890123301003', 'Cadbury Dairy Milk Silk Roast Almond (58g Bar)', 'Silk chocolate embedded with whole roasted crunchy almonds', 'bar', '20', '90.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('64', '4', 'CAD-SILK-FN', '890123301004', 'Cadbury Dairy Milk Silk Fruit & Nut (55g Bar)', 'Silk chocolate with juicy raisins and crunchy nuts', 'bar', '20', '90.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('65', '4', 'CAD-SILK-ORE', '890123301005', 'Cadbury Dairy Milk Silk Oreo (60g Bar)', 'Creamy silk chocolate filled with crunchy Oreo cookie bits', 'bar', '15', '95.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('66', '4', 'CAD-SILK-BUB', '890123301006', 'Cadbury Dairy Milk Silk Bubbly (50g Bar)', 'Melt-in-mouth bubbly aerated silk chocolate bar', 'bar', '15', '90.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('67', '4', 'CAD-SILK-HAZ', '890123301018', 'Cadbury Dairy Milk Silk Hazelnut (58g Bar)', 'Rich silk chocolate infused with crunchy imported hazelnuts', 'bar', '15', '95.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('68', '4', 'CAD-SILK-MOU', '890123301019', 'Cadbury Dairy Milk Silk Mousse (50g Bar)', 'Silk chocolate bar with a light, fluffy chocolate mousse centre', 'bar', '15', '90.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('69', '4', 'CAD-5STR-20', '890123301020', 'Cadbury 5 Star Chocolate Bar (20g Mini)', 'Chewy caramel and soft nougat chocolate bar', 'bar', '50', '10.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('70', '4', 'CAD-5STR-40', '890123301007', 'Cadbury 5 Star Chocolate Bar (40g Standard)', 'Caramel & nougat filled bar - Eat 5 Star, Do Nothing', 'bar', '40', '20.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('71', '4', 'CAD-5STR-3D', '890123301021', 'Cadbury 5 Star 3D Chocolate Bar (42g)', 'Crunchy biscuit and caramel wrapped in chocolate', 'bar', '25', '35.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('72', '4', 'CAD-PERK-13', '890123301022', 'Cadbury Perk Chocolate Wafer Bar (13g Mini)', 'Crisp wafer finger layered in sweet chocolate', 'bar', '60', '5.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('73', '4', 'CAD-PERK-28', '890123301008', 'Cadbury Perk Chocolate Wafer Bar (28g Standard)', 'Light, crispy wafer layers drenched in creamy chocolate', 'bar', '50', '10.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('74', '4', 'CAD-FUSE-45', '890123301009', 'Cadbury Fuse Peanut & Caramel Chocolate (45g)', 'Fudge feast with roasted peanuts and chewy caramel', 'bar', '25', '35.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('75', '4', 'CAD-GEMS-10', '890123301023', 'Cadbury Gems Chocolate Buttons (10.6g Mini)', 'Pocket pack of colourful chocolate buttons', 'pack', '50', '5.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('76', '4', 'CAD-GEMS-23', '890123301010', 'Cadbury Gems Chocolate Buttons (23g Pack)', 'Crisp candy-coated colourful chocolate buttons pack', 'pack', '40', '10.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('77', '4', 'CAD-GEMS-SUR', '890123301024', 'Cadbury Gems Surprise Ball with Toy (17.4g)', 'Chocolate gems ball with an exciting collectible toy inside', 'ball', '20', '40.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('78', '4', 'CAD-BRN-80', '890123301011', 'Cadbury Bournville 70% Dark Chocolate (80g Bar)', 'Intense rich dark cocoa chocolate for true connoisseurs', 'bar', '15', '110.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('79', '4', 'CAD-BRN-CRAN', '890123301025', 'Cadbury Bournville Cranberry Dark Chocolate (80g Bar)', 'Rich dark chocolate infused with real dried cranberries', 'bar', '15', '115.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('80', '4', 'CAD-CEL-130', '890123301012', 'Cadbury Celebrations Rich Chocolate Gift Pack (130g)', 'Assorted premium chocolate gift box for all occasions', 'box', '12', '150.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('81', '4', 'CAD-CEL-186', '890123301026', 'Cadbury Celebrations Premium Assorted Gift Box (186g)', 'Special assorted Dairy Milk & Silk festive gift hamper', 'box', '10', '225.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('82', '4', 'CAD-NUT-30', '890123301013', 'Cadbury Nutties Milk Chocolate Coated Cashews (30g)', 'Roasted cashew nuts enrobed in smooth milk chocolate', 'box', '15', '45.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('83', '4', 'CAD-CHOC-50', '890123301014', 'Cadbury Choclairs Gold Caramel Candies (Pack of 50)', 'Chewy golden caramel candies with rich chocolate center', 'pack', '10', '100.00', 'active', '2026-10-02 22:43:47', '2026-10-02 22:43:47'),
('30002', '4', 'PRD-CADB-689', '', 'Cadburry Chocolate', '', 'pcs', '10', '110.00', 'active', '2026-10-03 05:58:26', '2026-10-03 05:58:26'),
('60002', '3', 'PRD-THUM-450', '', 'Thumbs Up 200 ml', '', 'pcs', '10', '20.00', 'active', '2026-10-03 07:27:54', '2026-10-03 07:27:54'),
('60003', '3', 'PRD-THUM-692', '', 'Thumbs Up 200 ml', '', 'pcs', '10', '20.00', 'active', '2026-10-03 07:28:07', '2026-10-03 07:28:07'),
('90002', '3', 'PRD-MOUN-443', '', 'Mountain Dew', '', 'can', '10', '50.00', 'active', '2026-10-10 18:57:06', '2026-10-10 18:57:06');

DROP TABLE IF EXISTS `product_batches`;
CREATE TABLE `product_batches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `batch_no` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mfg_date` date DEFAULT NULL,
  `expiry_date` date NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `selling_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `initial_quantity` int NOT NULL DEFAULT '0',
  `current_quantity` int NOT NULL DEFAULT '0',
  `status` enum('available','low_stock','sold_out','expired','discarded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `idx_expiry` (`expiry_date`),
  KEY `idx_batch_product` (`product_id`,`current_quantity`),
  KEY `fk_batches_supplier` (`supplier_id`),
  CONSTRAINT `fk_batches_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_batches_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=120002;

INSERT INTO `product_batches` VALUES
('1', '1', '1', 'BAT-TZ500-01', '2026-09-30', '2026-10-04', '24.50', '27.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('2', '1', '1', 'BAT-TZ500-02', '2026-10-01', '2026-10-06', '24.50', '27.00', '0', '-2', 'sold_out', '2026-10-02 22:43:47'),
('3', '2', '1', 'BAT-TZ1L-01', '2026-10-01', '2026-10-06', '49.00', '54.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('4', '3', '1', 'BAT-GD500-01', '2026-09-30', '2026-10-04', '30.00', '133.00', '0', '10', 'expired', '2026-10-02 22:43:47'),
('5', '3', '1', 'BAT-GD500-02', '2026-10-01', '2026-10-06', '30.00', '33.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('6', '4', '1', 'BAT-GD1L-01', '2026-10-01', '2026-10-06', '60.00', '66.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('7', '5', '1', 'BAT-COW-01', '2026-10-01', '2026-10-05', '25.50', '28.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('8', '6', '1', 'BAT-BUF-01', '2026-10-01', '2026-10-05', '32.00', '35.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('9', '7', '1', 'BAT-TEA-01', '2026-10-01', '2026-10-05', '27.00', '30.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('10', '8', '1', 'BAT-SLM-01', '2026-10-01', '2026-10-05', '22.50', '25.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('11', '9', '1', 'BAT-TZTP-01', '2026-09-02', '2027-03-01', '68.00', '75.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('12', '10', '1', 'BAT-GDTP-01', '2026-09-02', '2027-03-01', '77.00', '85.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('13', '11', '1', 'BAT-BUT100-A', '2026-09-02', '2027-03-01', '52.00', '58.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('14', '12', '1', 'BAT-BUT500-A', '2026-09-12', '2027-03-11', '250.00', '275.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('15', '13', '1', 'BAT-PAN200-01', '2026-09-27', '2026-10-22', '78.00', '90.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('16', '14', '1', 'BAT-PAN500-01', '2026-09-27', '2026-10-22', '190.00', '215.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('17', '15', '1', 'BAT-CHS200-01', '2026-08-23', '2027-04-20', '125.00', '145.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('18', '16', '1', 'BAT-CHB200-01', '2026-08-23', '2027-04-20', '112.00', '130.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('19', '17', '1', 'BAT-CHC200-01', '2026-08-23', '2027-04-20', '116.00', '135.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('20', '18', '1', 'BAT-DAHI-01', '2026-09-29', '2026-10-07', '30.00', '35.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('21', '19', '1', 'BAT-DAHI200-1', '2026-09-30', '2026-10-08', '21.00', '25.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('22', '20', '1', 'BAT-CRM250-01', '2026-09-07', '2026-12-31', '60.00', '70.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('23', '21', '1', 'BAT-GHE500-01', '2026-08-18', '2027-06-29', '285.00', '320.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('24', '22', '1', 'BAT-GHE1L-01', '2026-08-18', '2027-06-29', '560.00', '630.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('25', '23', '1', 'BAT-MTH400-01', '2026-09-02', '2027-05-30', '120.00', '140.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('26', '24', '2', 'BAT-IC-VAN-01', '2026-09-02', '2027-05-30', '130.00', '160.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('27', '25', '2', 'BAT-IC-CHO-01', '2026-09-12', '2027-06-09', '195.00', '240.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('28', '26', '2', 'BAT-IC-BSC-01', '2026-09-07', '2027-06-04', '170.00', '210.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('29', '27', '2', 'BAT-IC-MNG-01', '2026-09-17', '2027-06-19', '185.00', '230.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('30', '28', '2', 'BAT-IC-NUT-01', '2026-09-22', '2027-06-29', '195.00', '240.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('31', '29', '2', 'BAT-TRI-CHO-01', '2026-09-12', '2027-03-31', '34.00', '45.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('32', '30', '2', 'BAT-TRI-BSC-01', '2026-09-12', '2027-03-31', '30.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('33', '31', '2', 'BAT-KUL-01', '2026-09-17', '2027-04-10', '30.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('34', '32', '2', 'BAT-FRO-01', '2026-09-07', '2027-03-21', '22.00', '30.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('35', '33', '2', 'BAT-EPC-01', '2026-09-17', '2027-04-20', '38.00', '50.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('36', '34', '2', 'BAT-MAT-01', '2026-09-22', '2027-03-31', '46.00', '60.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('37', '35', '2', 'BAT-CAS-01', '2026-09-20', '2027-03-31', '38.00', '50.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('38', '36', '3', 'BAT-COKE750-1', '2026-09-02', '2027-03-01', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('39', '37', '3', 'BAT-COKECAN-1', '2026-08-23', '2027-02-19', '31.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('40', '38', '3', 'BAT-COKE2L-01', '2026-09-12', '2027-03-11', '78.00', '95.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('41', '39', '3', 'BAT-THUM750-1', '2026-09-07', '2027-03-06', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('42', '40', '3', 'BAT-THUMCAN-1', '2026-08-28', '2027-02-24', '31.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('43', '41', '3', 'BAT-THUM2L-01', '2026-09-12', '2027-03-11', '78.00', '95.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('44', '42', '3', 'BAT-SPRT750-1', '2026-09-12', '2027-03-11', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('45', '43', '3', 'BAT-SPRTCAN-1', '2026-09-02', '2027-03-01', '31.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('46', '44', '3', 'BAT-SPRT2L-01', '2026-09-12', '2027-03-11', '78.00', '95.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('47', '45', '3', 'BAT-FANT750-1', '2026-08-18', '2027-02-14', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('48', '46', '3', 'BAT-LIMC750-1', '2026-08-23', '2027-02-19', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('49', '47', '3', 'BAT-MAAZ600-1', '2026-09-12', '2027-03-11', '33.00', '42.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('50', '48', '3', 'BAT-MAAZ12L-1', '2026-09-12', '2027-03-11', '60.00', '75.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('51', '49', '5', 'BAT-PEPS750-1', '2026-09-02', '2027-03-01', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('52', '50', '5', 'BAT-MDEW750-1', '2026-09-07', '2027-03-06', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('53', '51', '5', 'BAT-7UP750-01', '2026-09-07', '2027-03-06', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('54', '52', '5', 'BAT-MIR750-01', '2026-09-07', '2027-03-06', '32.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('55', '53', '3', 'BAT-RDBL250-1', '2026-08-13', '2027-07-29', '102.00', '125.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('56', '54', '5', 'BAT-STNG250-1', '2026-09-12', '2027-03-11', '15.50', '20.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('57', '55', '3', 'BAT-APPY600-1', '2026-09-02', '2027-03-01', '30.00', '38.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('58', '56', '3', 'BAT-BISL1L-01', '2026-09-22', '2027-03-21', '13.00', '20.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('59', '57', '3', 'BAT-BISL500-1', '2026-09-22', '2027-03-21', '6.50', '10.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('60', '58', '4', 'BAT-CDM13-01', '2026-08-03', '2027-07-29', '7.80', '10.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('61', '59', '4', 'BAT-CDM24-01', '2026-08-03', '2027-07-29', '15.50', '20.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('62', '60', '4', 'BAT-CDM50-01', '2026-08-03', '2027-07-29', '36.00', '45.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('63', '61', '4', 'BAT-CDM130-1', '2026-08-18', '2027-08-13', '80.00', '100.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('64', '62', '4', 'BAT-SILK60-01', '2026-08-18', '2027-08-18', '68.00', '85.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('65', '63', '4', 'BAT-SILKALM-1', '2026-08-23', '2027-08-18', '72.00', '90.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('66', '64', '4', 'BAT-SILKFN-1', '2026-08-28', '2027-08-28', '72.00', '90.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('67', '65', '4', 'BAT-SILKORE-1', '2026-09-02', '2027-08-28', '76.00', '95.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('68', '66', '4', 'BAT-SILKBUB-1', '2026-08-23', '2027-08-18', '72.00', '90.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('69', '67', '4', 'BAT-SILKHAZ-1', '2026-09-02', '2027-08-28', '76.00', '95.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('70', '68', '4', 'BAT-SILKMOU-1', '2026-09-02', '2027-08-28', '72.00', '90.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('71', '69', '4', 'BAT-5STR20-01', '2026-08-13', '2027-08-08', '7.80', '10.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('72', '70', '4', 'BAT-5STR40-01', '2026-08-13', '2027-08-08', '15.50', '20.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('73', '71', '4', 'BAT-5STR3D-01', '2026-08-18', '2027-08-13', '27.50', '35.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('74', '72', '4', 'BAT-PERK13-01', '2026-08-03', '2027-07-29', '3.90', '5.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('75', '73', '4', 'BAT-PERK28-01', '2026-08-03', '2027-07-29', '7.80', '10.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('76', '74', '4', 'BAT-FUSE45-01', '2026-08-18', '2027-08-13', '27.50', '35.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('77', '75', '4', 'BAT-GEMS10-01', '2026-08-03', '2027-07-29', '3.90', '5.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('78', '76', '4', 'BAT-GEMS23-01', '2026-08-03', '2027-07-29', '7.80', '10.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('79', '77', '4', 'BAT-GEMSSUR-1', '2026-08-18', '2027-08-13', '31.00', '40.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('80', '78', '4', 'BAT-BRN80-01', '2026-08-13', '2027-08-08', '88.00', '110.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('81', '79', '4', 'BAT-BRNCRAN-1', '2026-08-13', '2027-08-08', '92.00', '115.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('82', '80', '4', 'BAT-CEL130-01', '2026-09-02', '2027-08-28', '120.00', '150.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('83', '81', '4', 'BAT-CEL186-01', '2026-09-02', '2027-08-28', '180.00', '225.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('84', '82', '4', 'BAT-NUT30-01', '2026-08-23', '2027-08-18', '35.00', '45.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('85', '83', '4', 'BAT-CHOC50-01', '2026-08-03', '2027-07-29', '80.00', '100.00', '0', '0', 'sold_out', '2026-10-02 22:43:47'),
('30002', '30002', NULL, 'BAT-20261003-01', '2026-10-03', '2027-10-03', '100.00', '110.00', '50', '44', 'available', '2026-10-03 05:58:26'),
('30003', '51', '30002', 'BAT-20261003-0559', '2026-10-03', '2027-10-03', '30.00', '50.00', '100', '100', 'available', '2026-10-03 05:59:50'),
('60002', '60002', '30002', 'BAT-20261003-01', '2026-10-03', '2027-10-03', '16.00', '20.00', '50', '51', 'available', '2026-10-03 07:27:54'),
('60003', '60003', '30002', 'BAT-20261003-01', '2026-10-03', '2027-10-03', '16.00', '20.00', '50', '50', 'available', '2026-10-03 07:28:08'),
('90002', '90002', '30002', 'BAT-20261010-01', '2026-10-10', '2027-10-10', '45.00', '50.00', '100', '100', 'available', '2026-10-10 18:57:06'),
('90003', '90002', NULL, 'BAT-20261010-1904', '2026-10-10', '2027-10-10', '46.00', '51.00', '100', '98', 'available', '2026-10-10 19:04:56'),
('90004', '90002', '30002', 'BAT-20261010-1922', '2026-10-10', '2027-10-10', '40.00', '45.00', '25', '25', 'available', '2026-10-10 19:23:46');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value_text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`) /*T![clustered_index] CLUSTERED */
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` VALUES
('currency_code', 'INR', '2026-10-02 22:43:46'),
('currency_symbol', '₹', '2026-10-02 22:43:46'),
('default_low_stock_threshold', '15', '2026-10-02 22:43:46'),
('expiry_alert_days_critical', '3', '2026-10-02 22:43:46'),
('expiry_alert_days_warning', '7', '2026-10-02 22:43:46'),
('store_address', '39, Satyen Roy Road, Behala, Kolkata - 700034.', '2026-10-02 22:43:46'),
('store_email', 'contact@bondhuchol.com', '2026-10-02 22:43:46'),
('store_fssai', '10019021004321', '2026-10-02 22:43:46'),
('store_gstin', '19AAAAA0000A1Z5', '2026-10-02 22:43:46'),
('store_name', 'Bondhu Chol', '2026-10-02 22:43:46'),
('store_phone', '+91 98765 00000 / +91 98300 00000', '2026-10-02 22:43:46'),
('store_tagline', 'Fresh Milk, Amul Dairy & Ice Creams, Cold Drinks & Cadbury Chocolates', '2026-10-02 22:43:46'),
('store_upi_id', 'bondhuchol@upi', '2026-10-02 22:43:46'),
('tax_rate_percent', '5.00', '2026-10-02 22:43:46');

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `invoice_no` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int DEFAULT NULL,
  `customer_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Walk-in Customer',
  `customer_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sale_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `discount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `tax` decimal(10,2) NOT NULL DEFAULT '0.00',
  `grand_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `amount_paid` decimal(10,2) NOT NULL DEFAULT '0.00',
  `change_returned` decimal(10,2) NOT NULL DEFAULT '0.00',
  `payment_method` enum('cash','card','upi','credit','other') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cash',
  `status` enum('completed','refunded','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'completed',
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `idx_sale_date` (`sale_date`),
  UNIQUE KEY `invoice_no` (`invoice_no`),
  KEY `fk_sales_user` (`user_id`),
  CONSTRAINT `fk_sales_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=210001;

INSERT INTO `sales` VALUES
('90001', 'INV-20261003-141B', '1', 'Walk-in Customer', '', '2026-10-03 05:59:10', '220.00', '0.00', '11.00', '231.00', '231.00', '0.00', 'cash', 'completed', NULL, '2026-10-03 05:59:10'),
('120001', 'INV-20261003-E507', '1', 'Walk-in Customer', '', '2026-10-03 10:00:44', '440.00', '0.00', '22.00', '462.00', '462.00', '0.00', 'cash', 'completed', NULL, '2026-10-03 10:00:44');

DROP TABLE IF EXISTS `sale_items`;
CREATE TABLE `sale_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sale_id` int NOT NULL,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `unit_cost_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `unit_price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_items_sale` (`sale_id`),
  KEY `fk_items_product` (`product_id`),
  KEY `fk_items_batch` (`batch_id`),
  CONSTRAINT `fk_items_sale` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_items_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=210001;

INSERT INTO `sale_items` VALUES
('90001', '90001', '30002', '30002', '2', '100.00', '110.00', '220.00', '2026-10-03 05:59:10'),
('120001', '120001', '30002', '30002', '4', '100.00', '110.00', '440.00', '2026-10-03 10:00:44');

DROP TABLE IF EXISTS `stock_adjustments`;
CREATE TABLE `stock_adjustments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `batch_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `adjustment_type` enum('addition','subtraction') COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` int NOT NULL,
  `reason` enum('expired','damaged','lost','found','inventory_audit','other') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_adjust_product` (`product_id`),
  KEY `fk_adjust_batch` (`batch_id`),
  KEY `fk_adjust_user` (`user_id`),
  CONSTRAINT `fk_adjust_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_adjust_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_adjust_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=90001;

INSERT INTO `stock_adjustments` VALUES
('1', '60002', '60002', '1', 'addition', '1', 'expired', '', '2026-10-10 18:24:13'),
('30001', '3', '4', '1', 'addition', '10', 'other', '', '2026-10-10 18:40:21'),
('60001', '90002', '90003', '1', 'subtraction', '1', 'damaged', '', '2026-10-10 19:11:28'),
('60002', '90002', '90003', '1', 'subtraction', '1', 'damaged', '', '2026-10-10 19:13:55');

DROP TABLE IF EXISTS `stock_in_logs`;
CREATE TABLE `stock_in_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `batch_id` int NOT NULL,
  `supplier_id` int DEFAULT NULL,
  `user_id` int DEFAULT NULL,
  `quantity` int NOT NULL,
  `purchase_price` decimal(10,2) NOT NULL,
  `selling_price` decimal(10,2) NOT NULL,
  `invoice_reference` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`) /*T![clustered_index] CLUSTERED */,
  KEY `fk_stockin_product` (`product_id`),
  KEY `fk_stockin_batch` (`batch_id`),
  KEY `fk_stockin_supplier` (`supplier_id`),
  KEY `fk_stockin_user` (`user_id`),
  CONSTRAINT `fk_stockin_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stockin_batch` FOREIGN KEY (`batch_id`) REFERENCES `product_batches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stockin_supplier` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stockin_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci AUTO_INCREMENT=90001;

INSERT INTO `stock_in_logs` VALUES
('1', '30002', '30002', NULL, '1', '50', '100.00', '110.00', 'INITIAL-STOCK', 'Initial batch upon product creation', '2026-10-03 05:58:26'),
('2', '51', '30003', '30002', '1', '100', '30.00', '40.00', '', '', '2026-10-03 05:59:50'),
('30001', '60002', '60002', '30002', '1', '50', '0.00', '0.00', 'INITIAL-STOCK', 'Initial batch upon product creation', '2026-10-03 07:27:54'),
('30002', '60003', '60003', '30002', '1', '50', '0.00', '0.00', 'INITIAL-STOCK', 'Initial batch upon product creation', '2026-10-03 07:28:08'),
('60001', '90002', '90002', '30002', '1', '100', '45.00', '50.00', 'INITIAL-STOCK', 'Initial batch upon product creation', '2026-10-10 18:57:06'),
('60002', '90002', '90003', NULL, '1', '100', '46.00', '51.00', '', '', '2026-10-10 19:04:56'),
('60003', '90002', '90004', '30002', '1', '25', '40.00', '45.00', '', '', '2026-10-10 19:23:46');

SET FOREIGN_KEY_CHECKS = 1;
