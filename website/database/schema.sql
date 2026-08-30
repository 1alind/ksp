-- =========================================================
-- AURA Luxury Store - MySQL Database Schema & Seed Script
-- Compatible with MySQL 5.7+, MySQL 8.0+, and MariaDB
-- Default Character Set: utf8mb4 (Full Unicode for Kurdish & Arabic)
-- =========================================================

CREATE DATABASE IF NOT EXISTS `aura_store` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `aura_store`;

-- ---------------------------------------------------------
-- 1. Table: `products`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title_en` VARCHAR(255) NOT NULL,
  `title_ar` VARCHAR(255) NOT NULL,
  `title_ku` VARCHAR(255) NOT NULL,
  `category` ENUM('clothes', 'watches', 'perfumes', 'accessories') NOT NULL DEFAULT 'clothes',
  `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `old_price` DECIMAL(12, 2) DEFAULT NULL,
  `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
  `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `badge_en` VARCHAR(100) DEFAULT NULL,
  `badge_ar` VARCHAR(100) DEFAULT NULL,
  `badge_ku` VARCHAR(100) DEFAULT NULL,
  `stock` INT NOT NULL DEFAULT 10,
  `image` VARCHAR(1000) NOT NULL,
  `images` JSON DEFAULT NULL,
  `colors` JSON DEFAULT NULL,
  `sizes` JSON DEFAULT NULL,
  `size_measurements` JSON DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `description_ar` TEXT DEFAULT NULL,
  `description_ku` TEXT DEFAULT NULL,
  `featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_featured` (`featured`),
  KEY `idx_price` (`price`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 2. Table: `orders`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` VARCHAR(64) NOT NULL UNIQUE,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(64) NOT NULL,
  `customer_email` VARCHAR(255) DEFAULT NULL,
  `governorate` VARCHAR(100) NOT NULL,
  `district` VARCHAR(100) DEFAULT NULL,
  `customer_address` TEXT NOT NULL,
  `subtotal` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `shipping_fee` DECIMAL(12, 2) NOT NULL DEFAULT 5000.00,
  `discount_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
  `payment_method` VARCHAR(100) NOT NULL DEFAULT 'COD',
  `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
  `order_status` ENUM('Received', 'Packaging', 'Shipped', 'OutForDelivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Received',
  `items_json` JSON NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_customer_phone` (`customer_phone`),
  KEY `idx_order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 3. Table: `users`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(64) DEFAULT NULL,
  `role` ENUM('admin', 'customer', 'concierge') NOT NULL DEFAULT 'customer',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 4. Table: `reviews`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `user_name` VARCHAR(255) NOT NULL,
  `user_avatar` VARCHAR(500) DEFAULT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `comment` TEXT NOT NULL,
  `verified_purchase` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 5. Table: `inquiries` (Customer & Package Issues)
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `inquiries`;
CREATE TABLE `inquiries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` VARCHAR(64) DEFAULT NULL,
  `customer_name` VARCHAR(255) NOT NULL,
  `customer_phone` VARCHAR(64) NOT NULL,
  `issue_category` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('Open', 'In Review', 'Resolved') NOT NULL DEFAULT 'Open',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- 6. Table: `settings`
-- ---------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `key_name` VARCHAR(128) NOT NULL,
  `key_value` TEXT NOT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------
-- SEED DATA: Insert Initial Catalog Products
-- ---------------------------------------------------------
INSERT INTO `products` 
(`id`, `title_en`, `title_ar`, `title_ku`, `category`, `price`, `old_price`, `rating`, `reviews_count`, `badge_en`, `badge_ar`, `badge_ku`, `stock`, `image`, `images`, `colors`, `sizes`, `size_measurements`, `description_en`, `description_ar`, `description_ku`, `featured`)
VALUES
(
  1,
  'Royal Midnight Velvet Blazer',
  'بليزر مخملي ملكي ميدنايت',
  'ساکێ مەخمەلی یێ شاهانە میدنایت',
  'clothes',
  245000.00,
  315000.00,
  4.90,
  38,
  'Best Seller',
  'الأكثر مبيعاً',
  'پڕفرۆشترین',
  14,
  'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80"]',
  '["Midnight Blue", "Obsidian Black", "Burgundy"]',
  '["S", "M", "L", "XL"]',
  '{"S": "Length: 68 cm • Chest: 96 cm • Shoulder: 44 cm • Sleeve: 62 cm", "M": "Length: 70 cm • Chest: 102 cm • Shoulder: 46 cm • Sleeve: 63 cm", "L": "Length: 73 cm • Chest: 108 cm • Shoulder: 48 cm • Sleeve: 65 cm", "XL": "Length: 76 cm • Chest: 114 cm • Shoulder: 50 cm • Sleeve: 66 cm"}',
  'Impeccably tailored luxury velvet blazer designed with silk lapels and custom metal buttons. Perfect for evening galas, high-profile events, and formal dinners.',
  'بليزر مخملي فاخر بتفصيل متقن مع ياقة حريرية وأزرار معدنية مخصصة. مثالي للحفلات المسائية والمناسبات الرسمية.',
  'ساکێ مخمەلی یێ گەلەک جوان و شاهانە ب نەخشیێن ئاوریشمی و دوگماێن زێڕین. گەلەک گونجایە بۆ ئاهەنگ و هەلکەفتێن فەرمی.',
  1
),
(
  2,
  'Onyx Skeleton Automatic Watch',
  'ساعة أوتوماتيكية أونيكس هيكلية',
  'دەمژمێرا ئۆتۆماتیک یا سکێلێتۆن ئۆنیکس',
  'watches',
  550000.00,
  725000.00,
  5.00,
  64,
  'Luxury',
  'فاخر',
  'لوکس و نازک',
  8,
  'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80"]',
  '["Black Onyx & Gold", "Silver Steel", "Rose Gold"]',
  '["41mm Case"]',
  '{"41mm Case": "Case Diameter: 41 mm • Thickness: 11.5 mm • Strap Width: 20 mm • Lug-to-Lug: 47 mm"}',
  'Self-winding mechanical automatic movement with an open-heart skeleton dial, sapphire crystal glass, and Italian genuine leather strap. Water-resistant up to 50M.',
  'حركة ميكانيكية أوتوماتيكية مع ميناء هيكلي مكشوف وزجاج من الكريستال الياقوتي المقاوم للخدش وسوار جلد إيطالي أصلي.',
  'دەمژمێرەکا ميكانيكی یا ئۆتۆماتیك ب دیزاینێ سکێلێتۆن و شوشەیا یاقووتی یا دژی کڕاندنێ و قایشا چەرمی یا ئیتالی یا رەسەن.',
  1
),
(
  3,
  'Royal Amber & Smoked Oud Eau de Parfum',
  'عطر العود المدخن والعنبر الملكي',
  'عەترێ عوودێ دووکەلی و عەنبەرێ شاهانە',
  'perfumes',
  190000.00,
  250000.00,
  4.80,
  92,
  'Signature',
  'مميز',
  'تایبەت',
  25,
  'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=800&q=80"]',
  '["Gold Edition Bottle", "Smoked Obsidian Flacon", "Dark Amber Flacon"]',
  '["100ml / 3.4 oz", "50ml / 1.7 oz"]',
  '{"100ml / 3.4 oz": "Height: 14.5 cm • Width: 6.2 cm • Depth: 4.5 cm • Net: 100 ml (3.4 fl oz)", "50ml / 1.7 oz": "Height: 11.2 cm • Width: 5.0 cm • Depth: 3.8 cm • Net: 50 ml (1.7 fl oz)"}',
  'An intoxicating, long-lasting fragrance blending aged Cambodian oud, warm golden amber, Madagascar vanilla, and Damascus rose. Lasts over 24 hours.',
  'عطر ساحر وثابت يمزج بين العود الكمبودي الفاخر والعنبر الذهبي الدافئ والفانيليا وزهور دمشق. يدوم لأكثر من 24 ساعة.',
  'عەترەکێ گەلەک خوش و بێهنا وی گەلەک دمینیت ژ عوودێ کەمبۆدی، عەنبەرێ زێڕین و گولێن دیمەشقێ. پتر ژ 24 دەمژمێران دمینیت.',
  1
),
(
  4,
  'Italian Leather Minimalist Weekender Bag',
  'حقيبة عطلة نهاية الأسبوع من الجلد الإيطالي',
  'جانتا چەرمێ ئیتالی یا گەشت و دەوامێ',
  'accessories',
  275000.00,
  370000.00,
  4.90,
  45,
  'Handcrafted',
  'صناعة يدوية',
  'دەستکرد',
  11,
  'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
  '["https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80", "https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80"]',
  '["Vintage Tan", "Deep Espresso", "Matte Black"]',
  '["Standard 45L"]',
  '{"Standard 45L": "Length: 52 cm • Height: 30 cm • Width: 26 cm • Handle Drop: 18 cm"}',
  'Crafted from full-grain vegetable-tanned Italian leather with solid brass hardware, YKK zippers, and padded laptop compartment.',
  'مصنوعة من جلد إيطالي أصلي كامل الحبيبات مع إكسسوارات من النحاس الصلب وسحابات متينة وحجرة مبطنة للكمبيوتر المحمول.',
  'ژ چەرمێ ئیتالی یێ رەسەن و پاقژ هاتیە چێکرن دگەل قفلێن برۆنز و جهێ لەپتۆپی یێ تایبەت.',
  1
);
