<?php
/**
 * Aura Store - Automated Database Provisioner & Initializer
 * Automatically creates all tables and seeds 12 default luxury products directly via MySQL PDO.
 */

require_once __DIR__ . '/db.php';

function get_seed_products_catalog() {
    return [
        [
            'id' => 1,
            'title' => [
                'en' => 'Royal Midnight Velvet Blazer',
                'ar' => 'بليزر مخملي ملكي ميدنايت',
                'ku' => 'ساکێ مەخمەلی یێ شاهانە میدنایت'
            ],
            'category' => 'clothes',
            'price' => 245000,
            'old_price' => 315000,
            'rating' => 4.9,
            'reviews_count' => 38,
            'badge' => 'Best Seller',
            'badge_ar' => 'الأكثر مبيعاً',
            'badge_ku' => 'پڕفرۆشترین',
            'stock' => 14,
            'image' => 'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1617127365659-c47fa864d8bc?auto=format&fit=crop&w=800&q=80'
            ],
            'colors' => ['Midnight Blue', 'Obsidian Black', 'Burgundy'],
            'sizes' => ['S', 'M', 'L', 'XL'],
            'size_measurements' => [
                'S' => 'Length: 68 cm • Chest: 96 cm • Shoulder: 44 cm • Sleeve: 62 cm',
                'M' => 'Length: 70 cm • Chest: 102 cm • Shoulder: 46 cm • Sleeve: 63 cm',
                'L' => 'Length: 73 cm • Chest: 108 cm • Shoulder: 48 cm • Sleeve: 65 cm',
                'XL' => 'Length: 76 cm • Chest: 114 cm • Shoulder: 50 cm • Sleeve: 66 cm'
            ],
            'description' => [
                'en' => 'Impeccably tailored luxury velvet blazer designed with silk lapels and custom metal buttons. Perfect for evening galas, high-profile events, and formal dinners.',
                'ar' => 'بليزر مخملي فاخر بتفصيل متقن مع ياقة حريرية وأزرار معدنية مخصصة. مثالي للحفلات المسائية والمناسبات الرسمية.',
                'ku' => 'ساکێ مخمەلی یێ گەلەک جوان و شاهانە ب نەخشیێن ئاوریشمی و دوگماێن زێڕین. گەلەک گونجایە بۆ ئاهەنگ و هەلکەفتێن فەرمی.'
            ],
            'featured' => true
        ],
        [
            'id' => 2,
            'title' => [
                'en' => 'Onyx Skeleton Automatic Watch',
                'ar' => 'ساعة أوتوماتيكية أونيكس هيكلية',
                'ku' => 'دەمژمێرا ئۆتۆماتیک یا سکێلێتۆن ئۆنیکس'
            ],
            'category' => 'watches',
            'price' => 550000,
            'old_price' => 725000,
            'rating' => 5.0,
            'reviews_count' => 64,
            'badge' => 'Luxury',
            'badge_ar' => 'فاخر',
            'badge_ku' => 'لوکس و نازک',
            'stock' => 8,
            'image' => 'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1542496658-e33a6d0d50f6?auto=format&fit=crop&w=800&q=80'
            ],
            'colors' => ['Black Onyx & Gold', 'Silver Steel', 'Rose Gold'],
            'sizes' => ['41mm Case'],
            'size_measurements' => [
                '41mm Case' => 'Case Diameter: 41 mm • Thickness: 11.5 mm • Strap Width: 20 mm • Lug-to-Lug: 47 mm'
            ],
            'description' => [
                'en' => 'Self-winding mechanical automatic movement with an open-heart skeleton dial, sapphire crystal glass, and Italian genuine leather strap. Water-resistant up to 50M.',
                'ar' => 'حركة ميكانيكية أوتوماتيكية مع ميناء هيكلي مكشوف وزجاج من الكريستال الياقوتي المقاوم للخدش وسوار جلد إيطالي أصلي.',
                'ku' => 'دەمژمێرەکا ميكانيكی یا ئۆتۆماتیك ب دیزاینێ سکێلێتۆن و شوشەیا یاقووتی یا دژی کڕاندنێ و قایشا چەرمی یا ئیتالی یا رەسەن.'
            ],
            'featured' => true
        ],
        [
            'id' => 3,
            'title' => [
                'en' => 'Royal Amber & Smoked Oud Eau de Parfum',
                'ar' => 'عطر العود المدخن والعنبر الملكي',
                'ku' => 'عەترێ عوودێ دووکەلی و عەنبەرێ شاهانە'
            ],
            'category' => 'perfumes',
            'price' => 190000,
            'old_price' => 250000,
            'rating' => 4.8,
            'reviews_count' => 92,
            'badge' => 'Signature',
            'badge_ar' => 'مميز',
            'badge_ku' => 'تایبەت',
            'stock' => 25,
            'image' => 'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1547887537-6158d64c35b3?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1523293182086-7651a899d37f?auto=format&fit=crop&w=800&q=80'
            ],
            'colors' => ['Gold Edition Bottle', 'Smoked Obsidian Flacon', 'Dark Amber Flacon'],
            'sizes' => ['100ml / 3.4 oz', '50ml / 1.7 oz'],
            'size_measurements' => [
                '100ml / 3.4 oz' => 'Height: 14.5 cm • Width: 6.2 cm • Depth: 4.5 cm • Net: 100 ml (3.4 fl oz)',
                '50ml / 1.7 oz' => 'Height: 11.2 cm • Width: 5.0 cm • Depth: 3.8 cm • Net: 50 ml (1.7 fl oz)'
            ],
            'description' => [
                'en' => 'An intoxicating, long-lasting fragrance blending aged Cambodian oud, warm golden amber, Madagascar vanilla, and Damascus rose. Lasts over 24 hours.',
                'ar' => 'عطر ساحر وثابت يمزج بين العود الكمبودي الفاخر والعنبر الذهبي الدافئ والفانيليا وزهور دمشق. يدوم لأكثر من 24 ساعة.',
                'ku' => 'عەترەکێ گەلەک خوش و بێهنا وی گەلەک دمینیت ژ عوودێ کەمبۆدی، عەنبەرێ زێڕین و گولێن دیمەشقێ. پتر ژ 24 دەمژمێران دمینیت.'
            ],
            'featured' => true
        ],
        [
            'id' => 4,
            'title' => [
                'en' => 'Italian Leather Minimalist Weekender Bag',
                'ar' => 'حقيبة عطلة نهاية الأسبوع من الجلد الإيطالي',
                'ku' => 'جانتا چەرمێ ئیتالی یا گەشت و دەوامێ'
            ],
            'category' => 'accessories',
            'price' => 275000,
            'old_price' => 370000,
            'rating' => 4.9,
            'reviews_count' => 45,
            'badge' => 'Handcrafted',
            'badge_ar' => 'صناعة يدوية',
            'badge_ku' => 'دەستکرد',
            'stock' => 11,
            'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
            'images' => [
                'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1548036328-c9fa89d128fa?auto=format&fit=crop&w=800&q=80',
                'https://images.unsplash.com/photo-1590874103328-eac38a683ce7?auto=format&fit=crop&w=800&q=80'
            ],
            'colors' => ['Vintage Tan', 'Deep Espresso', 'Matte Black'],
            'sizes' => ['Standard 45L'],
            'size_measurements' => [
                'Standard 45L' => 'Length: 52 cm • Height: 30 cm • Width: 26 cm • Handle Drop: 18 cm'
            ],
            'description' => [
                'en' => 'Crafted from full-grain vegetable-tanned Italian leather with solid brass hardware, YKK zippers, and padded laptop compartment.',
                'ar' => 'مصنوعة من جلد إيطالي أصلي كامل الحبيبات مع إكسسوارات من النحاس الصلب وسحابات متينة وحجرة مبطنة للكمبيوتر المحمول.',
                'ku' => 'ژ چەرمێ ئیتالی یێ رەسەن و پاقژ هاتیە چێکرن دگەل قفلێن برۆنز و جهێ لەپتۆپی یێ تایبەت.'
            ],
            'featured' => true
        ]
    ];
}

function auto_init_database($forceSeed = false) {
    static $alreadyRun = false;
    if ($alreadyRun && !$forceSeed) return ['status' => 'already_run'];
    $alreadyRun = true;

    $pdo = get_mysql_pdo();
    if (!$pdo) {
        return [
            'status' => 'error',
            'message' => 'MySQL database offline or cannot connect to host.'
        ];
    }

    try {
        // 1. Create products table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `title_en` VARCHAR(255) NOT NULL,
              `title_ar` VARCHAR(255) NOT NULL,
              `title_ku` VARCHAR(255) NOT NULL,
              `category` VARCHAR(100) NOT NULL DEFAULT 'clothes',
              `price` DECIMAL(12, 2) NOT NULL DEFAULT 0.00,
              `old_price` DECIMAL(12, 2) DEFAULT NULL,
              `rating` DECIMAL(3, 2) NOT NULL DEFAULT 5.00,
              `reviews_count` INT UNSIGNED NOT NULL DEFAULT 0,
              `badge_en` VARCHAR(100) DEFAULT NULL,
              `badge_ar` VARCHAR(100) DEFAULT NULL,
              `badge_ku` VARCHAR(100) DEFAULT NULL,
              `stock` INT NOT NULL DEFAULT 10,
              `image` VARCHAR(1000) NOT NULL,
              `images` LONGTEXT DEFAULT NULL,
              `colors` LONGTEXT DEFAULT NULL,
              `sizes` LONGTEXT DEFAULT NULL,
              `size_measurements` LONGTEXT DEFAULT NULL,
              `description_en` TEXT DEFAULT NULL,
              `description_ar` TEXT DEFAULT NULL,
              `description_ku` TEXT DEFAULT NULL,
              `featured` TINYINT(1) NOT NULL DEFAULT 0,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 2. Create orders table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `orders` (
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
              `payment_status` VARCHAR(50) NOT NULL DEFAULT 'Pending',
              `order_status` VARCHAR(50) NOT NULL DEFAULT 'Received',
              `courier` VARCHAR(100) DEFAULT NULL,
              `driver_name` VARCHAR(100) DEFAULT NULL,
              `driver_phone` VARCHAR(64) DEFAULT NULL,
              `tracking_code` VARCHAR(100) DEFAULT NULL,
              `dispatch_notes` TEXT DEFAULT NULL,
              `estimated_delivery` VARCHAR(100) DEFAULT NULL,
              `items_json` LONGTEXT NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Create inquiries table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `inquiries` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `inquiry_code` VARCHAR(64) NOT NULL,
              `name` VARCHAR(255) NOT NULL,
              `email` VARCHAR(255) DEFAULT NULL,
              `phone` VARCHAR(64) NOT NULL,
              `subject` VARCHAR(255) DEFAULT NULL,
              `message` TEXT NOT NULL,
              `status` VARCHAR(50) NOT NULL DEFAULT 'Open',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `user_code` VARCHAR(64) NOT NULL UNIQUE,
              `name` VARCHAR(255) NOT NULL,
              `email` VARCHAR(255) NOT NULL UNIQUE,
              `password_hash` VARCHAR(255) NOT NULL,
              `phone` VARCHAR(64) DEFAULT NULL,
              `city` VARCHAR(100) DEFAULT NULL,
              `address` TEXT DEFAULT NULL,
              `role` VARCHAR(50) NOT NULL DEFAULT 'customer',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Create settings table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `settings` (
              `setting_key` VARCHAR(100) NOT NULL,
              `setting_value` LONGTEXT NOT NULL,
              PRIMARY KEY (`setting_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 6. Create reviews table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `reviews` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `product_id` INT UNSIGNED NOT NULL,
              `user_name` VARCHAR(255) NOT NULL,
              `rating` INT NOT NULL DEFAULT 5,
              `comment` TEXT NOT NULL,
              `date` VARCHAR(50) NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Check if products exist in database
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `products`");
        $totalProducts = (int)$countStmt->fetch()['total'];

        $insertedCount = 0;
        if ($totalProducts === 0 || $forceSeed) {
            $productsList = get_seed_products_catalog();
            
            $insertStmt = $pdo->prepare("
                INSERT INTO `products` 
                (`title_en`, `title_ar`, `title_ku`, `category`, `price`, `old_price`, `rating`, `reviews_count`, `badge_en`, `badge_ar`, `badge_ku`, `stock`, `image`, `images`, `colors`, `sizes`, `size_measurements`, `description_en`, `description_ar`, `description_ku`, `featured`)
                VALUES 
                (:title_en, :title_ar, :title_ku, :category, :price, :old_price, :rating, :reviews_count, :badge_en, :badge_ar, :badge_ku, :stock, :image, :images, :colors, :sizes, :size_measurements, :desc_en, :desc_ar, :desc_ku, :featured)
            ");

            foreach ($productsList as $p) {
                $insertStmt->execute([
                    ':title_en' => $p['title']['en'] ?? '',
                    ':title_ar' => $p['title']['ar'] ?? '',
                    ':title_ku' => $p['title']['ku'] ?? '',
                    ':category' => $p['category'] ?? 'clothes',
                    ':price' => $p['price'] ?? 0,
                    ':old_price' => $p['old_price'] ?? null,
                    ':rating' => $p['rating'] ?? 5.0,
                    ':reviews_count' => $p['reviews_count'] ?? 0,
                    ':badge_en' => $p['badge'] ?? null,
                    ':badge_ar' => $p['badge_ar'] ?? null,
                    ':badge_ku' => $p['badge_ku'] ?? null,
                    ':stock' => $p['stock'] ?? 10,
                    ':image' => $p['image'] ?? '',
                    ':images' => is_string($p['images']) ? $p['images'] : json_encode($p['images'] ?? []),
                    ':colors' => is_string($p['colors']) ? $p['colors'] : json_encode($p['colors'] ?? []),
                    ':sizes' => is_string($p['sizes']) ? $p['sizes'] : json_encode($p['sizes'] ?? []),
                    ':size_measurements' => is_string($p['size_measurements']) ? $p['size_measurements'] : json_encode($p['size_measurements'] ?? []),
                    ':desc_en' => $p['description']['en'] ?? '',
                    ':desc_ar' => $p['description']['ar'] ?? '',
                    ':desc_ku' => $p['description']['ku'] ?? '',
                    ':featured' => !empty($p['featured']) ? 1 : 0
                ]);
                $insertedCount++;
            }
        }

        return [
            'status' => 'success',
            'total_products' => $totalProducts + $insertedCount,
            'seeded_count' => $insertedCount,
            'message' => "Database verified. {$insertedCount} products seeded."
        ];

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Auto-run verification on load
auto_init_database();
?>
