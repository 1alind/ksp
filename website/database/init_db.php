<?php
/**
 * Aura Store - Automated Database Provisioner & Initializer
 * Checks database connectivity, creates required tables if not present,
 * and seeds rich default products, settings, and sample data.
 */

require_once __DIR__ . '/db.php';

function auto_init_database() {
    static $alreadyRun = false;
    if ($alreadyRun) return;
    $alreadyRun = true;

    $pdo = get_mysql_pdo();
    if (!$pdo) {
        // MySQL connection not configured or unreachable; fallback to JSON mode is active.
        return [
            'status' => 'json_mode',
            'message' => 'Running on JSON storage engine. MySQL server not reachable.'
        ];
    }

    try {
        // 1. Check if tables exist
        $stmt = $pdo->query("SHOW TABLES LIKE 'products'");
        $productsTableExists = $stmt->rowCount() > 0;

        // 2. Create products table if missing
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
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
              KEY `idx_featured` (`featured`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 3. Create orders table if missing
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
              `payment_status` ENUM('Pending', 'Paid', 'Failed', 'Refunded') NOT NULL DEFAULT 'Pending',
              `order_status` ENUM('Received', 'Packaging', 'Shipped', 'OutForDelivery', 'Delivered', 'Cancelled') NOT NULL DEFAULT 'Received',
              `items_json` JSON NOT NULL,
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_order_id` (`order_id`),
              KEY `idx_customer_phone` (`customer_phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 4. Create inquiries & issues table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `inquiries` (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `order_id` VARCHAR(64) DEFAULT NULL,
              `customer_name` VARCHAR(255) NOT NULL,
              `customer_phone` VARCHAR(64) NOT NULL,
              `issue_category` VARCHAR(100) NOT NULL,
              `message` TEXT NOT NULL,
              `status` ENUM('Open', 'In Review', 'Resolved') NOT NULL DEFAULT 'Open',
              `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // 5. Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
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
        ");

        // 6. Check if products are empty, then seed default catalog
        $countStmt = $pdo->query("SELECT COUNT(*) as total FROM `products`");
        $totalProducts = (int)$countStmt->fetch()['total'];

        if ($totalProducts === 0) {
            // Load examples from products.json or insert default luxury suite
            $jsonFile = __DIR__ . '/products.json';
            if (file_exists($jsonFile)) {
                $raw = file_get_contents($jsonFile);
                $decoded = json_decode($raw, true);
                $productsList = $decoded['products'] ?? [];
                
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
                        ':images' => json_encode($p['images'] ?? []),
                        ':colors' => json_encode($p['colors'] ?? []),
                        ':sizes' => json_encode($p['sizes'] ?? []),
                        ':size_measurements' => json_encode($p['size_measurements'] ?? []),
                        ':desc_en' => $p['description']['en'] ?? '',
                        ':desc_ar' => $p['description']['ar'] ?? '',
                        ':desc_ku' => $p['description']['ku'] ?? '',
                        ':featured' => !empty($p['featured']) ? 1 : 0
                    ]);
                }
            }
        }

        return [
            'status' => 'success',
            'message' => 'MySQL database verified and ready.'
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
