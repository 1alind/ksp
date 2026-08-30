<?php
/**
 * Aura Store - Pure MySQL Database Engine (PDO)
 * Exclusively uses MySQL database for products, orders, users, inquiries, reviews, and settings.
 */

// --- MySQL Configuration ---
define('MYSQL_ENABLED', true);
define('MYSQL_HOST', getenv('MYSQL_HOST') ?: 'sql104.infinityfree.com');
define('MYSQL_PORT', getenv('MYSQL_PORT') ?: '3306');
define('MYSQL_DBNAME', getenv('MYSQL_DBNAME') ?: 'if0_41557722_shop');
define('MYSQL_USER', getenv('MYSQL_USER') ?: 'if0_41557722');
define('MYSQL_PASSWORD', getenv('MYSQL_PASSWORD') ?: 'sjBEcko70k');

/**
 * Get MySQL PDO Connection
 */
function get_mysql_pdo() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    try {
        $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DBNAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASSWORD, $options);
        return $pdo;
    } catch (Exception $e) {
        return null;
    }
}

// Auto-run schema & table initialization
if (file_exists(__DIR__ . '/init_db.php')) {
    require_once __DIR__ . '/init_db.php';
}

// ==============================================================================
// 1. PRODUCTS (MySQL Database Operations)
// ==============================================================================
function get_all_products() {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
        $rows = $stmt->fetchAll();
        $products = [];
        foreach ($rows as $row) {
            $products[] = [
                'id' => (int)$row['id'],
                'title' => [
                    'en' => $row['title_en'],
                    'ar' => $row['title_ar'],
                    'ku' => $row['title_ku'],
                ],
                'category' => $row['category'],
                'price' => (float)$row['price'],
                'old_price' => $row['old_price'] ? (float)$row['old_price'] : null,
                'rating' => (float)$row['rating'],
                'reviews_count' => (int)$row['reviews_count'],
                'badge' => $row['badge_en'],
                'badge_ar' => $row['badge_ar'],
                'badge_ku' => $row['badge_ku'],
                'stock' => (int)$row['stock'],
                'image' => $row['image'],
                'images' => is_string($row['images']) ? json_decode($row['images'], true) : ($row['images'] ?? []),
                'colors' => is_string($row['colors']) ? json_decode($row['colors'], true) : ($row['colors'] ?? []),
                'sizes' => is_string($row['sizes']) ? json_decode($row['sizes'], true) : ($row['sizes'] ?? []),
                'size_measurements' => is_string($row['size_measurements']) ? json_decode($row['size_measurements'], true) : ($row['size_measurements'] ?? []),
                'description' => [
                    'en' => $row['description_en'],
                    'ar' => $row['description_ar'],
                    'ku' => $row['description_ku'],
                ],
                'featured' => (bool)$row['featured']
            ];
        }
        return $products;
    } catch (Exception $e) {
        return [];
    }
}

function get_product_by_id($id) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return null;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        
        return [
            'id' => (int)$row['id'],
            'title' => [
                'en' => $row['title_en'],
                'ar' => $row['title_ar'],
                'ku' => $row['title_ku'],
            ],
            'category' => $row['category'],
            'price' => (float)$row['price'],
            'old_price' => $row['old_price'] ? (float)$row['old_price'] : null,
            'rating' => (float)$row['rating'],
            'reviews_count' => (int)$row['reviews_count'],
            'badge' => $row['badge_en'],
            'badge_ar' => $row['badge_ar'],
            'badge_ku' => $row['badge_ku'],
            'stock' => (int)$row['stock'],
            'image' => $row['image'],
            'images' => is_string($row['images']) ? json_decode($row['images'], true) : ($row['images'] ?? []),
            'colors' => is_string($row['colors']) ? json_decode($row['colors'], true) : ($row['colors'] ?? []),
            'sizes' => is_string($row['sizes']) ? json_decode($row['sizes'], true) : ($row['sizes'] ?? []),
            'size_measurements' => is_string($row['size_measurements']) ? json_decode($row['size_measurements'], true) : ($row['size_measurements'] ?? []),
            'description' => [
                'en' => $row['description_en'],
                'ar' => $row['description_ar'],
                'ku' => $row['description_ku'],
            ],
            'featured' => (bool)$row['featured']
        ];
    } catch (Exception $e) {
        return null;
    }
}

function save_product($new_product) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return $new_product;

    try {
        if (isset($new_product['id']) && $new_product['id'] > 0) {
            $sql = "UPDATE products SET 
                    title_en = :title_en, title_ar = :title_ar, title_ku = :title_ku,
                    category = :category, price = :price, old_price = :old_price,
                    stock = :stock, image = :image, images = :images,
                    colors = :colors, sizes = :sizes, size_measurements = :size_measurements,
                    description_en = :desc_en, description_ar = :desc_ar, description_ku = :desc_ku,
                    featured = :featured
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':id' => $new_product['id'],
                ':title_en' => $new_product['title']['en'] ?? ($new_product['title_en'] ?? ''),
                ':title_ar' => $new_product['title']['ar'] ?? ($new_product['title_ar'] ?? ''),
                ':title_ku' => $new_product['title']['ku'] ?? ($new_product['title_ku'] ?? ''),
                ':category' => $new_product['category'] ?? 'clothes',
                ':price' => $new_product['price'] ?? 0,
                ':old_price' => $new_product['old_price'] ?? null,
                ':stock' => $new_product['stock'] ?? 10,
                ':image' => $new_product['image'] ?? '',
                ':images' => is_string($new_product['images'] ?? null) ? $new_product['images'] : json_encode($new_product['images'] ?? []),
                ':colors' => is_string($new_product['colors'] ?? null) ? $new_product['colors'] : json_encode($new_product['colors'] ?? []),
                ':sizes' => is_string($new_product['sizes'] ?? null) ? $new_product['sizes'] : json_encode($new_product['sizes'] ?? []),
                ':size_measurements' => is_string($new_product['size_measurements'] ?? null) ? $new_product['size_measurements'] : json_encode($new_product['size_measurements'] ?? []),
                ':desc_en' => $new_product['description']['en'] ?? ($new_product['description_en'] ?? ''),
                ':desc_ar' => $new_product['description']['ar'] ?? ($new_product['description_ar'] ?? ''),
                ':desc_ku' => $new_product['description']['ku'] ?? ($new_product['description_ku'] ?? ''),
                ':featured' => !empty($new_product['featured']) ? 1 : 0
            ]);
        } else {
            $sql = "INSERT INTO products 
                    (title_en, title_ar, title_ku, category, price, old_price, stock, image, images, colors, sizes, size_measurements, description_en, description_ar, description_ku, featured)
                    VALUES (:title_en, :title_ar, :title_ku, :category, :price, :old_price, :stock, :image, :images, :colors, :sizes, :size_measurements, :desc_en, :desc_ar, :desc_ku, :featured)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':title_en' => $new_product['title']['en'] ?? ($new_product['title_en'] ?? ''),
                ':title_ar' => $new_product['title']['ar'] ?? ($new_product['title_ar'] ?? ''),
                ':title_ku' => $new_product['title']['ku'] ?? ($new_product['title_ku'] ?? ''),
                ':category' => $new_product['category'] ?? 'clothes',
                ':price' => $new_product['price'] ?? 0,
                ':old_price' => $new_product['old_price'] ?? null,
                ':stock' => $new_product['stock'] ?? 10,
                ':image' => $new_product['image'] ?? '',
                ':images' => is_string($new_product['images'] ?? null) ? $new_product['images'] : json_encode($new_product['images'] ?? []),
                ':colors' => is_string($new_product['colors'] ?? null) ? $new_product['colors'] : json_encode($new_product['colors'] ?? []),
                ':sizes' => is_string($new_product['sizes'] ?? null) ? $new_product['sizes'] : json_encode($new_product['sizes'] ?? []),
                ':size_measurements' => is_string($new_product['size_measurements'] ?? null) ? $new_product['size_measurements'] : json_encode($new_product['size_measurements'] ?? []),
                ':desc_en' => $new_product['description']['en'] ?? ($new_product['description_en'] ?? ''),
                ':desc_ar' => $new_product['description']['ar'] ?? ($new_product['description_ar'] ?? ''),
                ':desc_ku' => $new_product['description']['ku'] ?? ($new_product['description_ku'] ?? ''),
                ':featured' => !empty($new_product['featured']) ? 1 : 0
            ]);
            $new_product['id'] = (int)$pdo->lastInsertId();
        }
        return $new_product;
    } catch (Exception $e) {
        return $new_product;
    }
}

function delete_product($id) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    } catch (Exception $e) {
        return false;
    }
}

// ==============================================================================
// 2. ORDERS (MySQL Database Operations)
// ==============================================================================
function get_all_orders() {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM orders ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        $orders = [];
        foreach ($rows as $r) {
            $orders[] = [
                'id' => (int)$r['id'],
                'order_id' => $r['order_id'],
                'customer_name' => $r['customer_name'],
                'phone' => $r['customer_phone'],
                'email' => $r['customer_email'],
                'city' => $r['governorate'],
                'district' => $r['district'],
                'address' => $r['customer_address'],
                'subtotal' => (float)$r['subtotal'],
                'shipping' => (float)$r['shipping_fee'],
                'discount' => (float)$r['discount_amount'],
                'total' => (float)$r['total_amount'],
                'total_iqd' => (float)$r['total_amount'],
                'payment_method' => $r['payment_method'],
                'payment_status' => $r['payment_status'],
                'order_status' => $r['order_status'],
                'courier' => $r['courier'],
                'driver_name' => $r['driver_name'],
                'driver_phone' => $r['driver_phone'],
                'tracking_code' => $r['tracking_code'],
                'dispatch_notes' => $r['dispatch_notes'],
                'estimated_delivery' => $r['estimated_delivery'],
                'items' => is_string($r['items_json']) ? json_decode($r['items_json'], true) : ($r['items_json'] ?? []),
                'created_at' => $r['created_at'],
                'date' => $r['created_at']
            ];
        }
        return $orders;
    } catch (Exception $e) {
        return [];
    }
}

// Alias for get_all_orders
function get_orders() {
    return get_all_orders();
}

function get_order_by_id($order_id) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = :oid OR tracking_code = :oid LIMIT 1");
        $stmt->execute([':oid' => trim($order_id)]);
        $r = $stmt->fetch();
        if (!$r) return null;

        return [
            'id' => (int)$r['id'],
            'order_id' => $r['order_id'],
            'customer_name' => $r['customer_name'],
            'phone' => $r['customer_phone'],
            'email' => $r['customer_email'],
            'city' => $r['governorate'],
            'district' => $r['district'],
            'address' => $r['customer_address'],
            'subtotal' => (float)$r['subtotal'],
            'shipping' => (float)$r['shipping_fee'],
            'discount' => (float)$r['discount_amount'],
            'total' => (float)$r['total_amount'],
            'total_iqd' => (float)$r['total_amount'],
            'payment_method' => $r['payment_method'],
            'payment_status' => $r['payment_status'],
            'order_status' => $r['order_status'],
            'courier' => $r['courier'],
            'driver_name' => $r['driver_name'],
            'driver_phone' => $r['driver_phone'],
            'tracking_code' => $r['tracking_code'],
            'dispatch_notes' => $r['dispatch_notes'],
            'estimated_delivery' => $r['estimated_delivery'],
            'items' => is_string($r['items_json']) ? json_decode($r['items_json'], true) : ($r['items_json'] ?? []),
            'created_at' => $r['created_at'],
            'date' => $r['created_at']
        ];
    } catch (Exception $e) {
        return null;
    }
}

function create_order($order_data) {
    $pdo = get_mysql_pdo();
    if (empty($order_data['order_id'])) {
        $order_data['order_id'] = 'ORD-' . rand(10000, 99999);
    }
    $order_data['created_at'] = date('Y-m-d H:i:s');
    $order_data['order_status'] = $order_data['order_status'] ?? 'Pending';
    
    if (!$pdo) return $order_data;

    try {
        $sql = "INSERT INTO orders 
                (order_id, customer_name, customer_phone, customer_email, governorate, district, customer_address, subtotal, shipping_fee, discount_amount, total_amount, payment_method, payment_status, order_status, courier, driver_name, driver_phone, tracking_code, dispatch_notes, estimated_delivery, items_json)
                VALUES 
                (:order_id, :customer_name, :customer_phone, :customer_email, :governorate, :district, :customer_address, :subtotal, :shipping_fee, :discount_amount, :total_amount, :payment_method, :payment_status, :order_status, :courier, :driver_name, :driver_phone, :tracking_code, :dispatch_notes, :estimated_delivery, :items_json)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':order_id' => $order_data['order_id'],
            ':customer_name' => $order_data['customer_name'] ?? ($order_data['name'] ?? ''),
            ':customer_phone' => $order_data['customer_phone'] ?? ($order_data['phone'] ?? ''),
            ':customer_email' => $order_data['customer_email'] ?? ($order_data['email'] ?? ''),
            ':governorate' => $order_data['governorate'] ?? ($order_data['city'] ?? 'Duhok'),
            ':district' => $order_data['district'] ?? '',
            ':customer_address' => $order_data['customer_address'] ?? ($order_data['address'] ?? ''),
            ':subtotal' => $order_data['subtotal'] ?? 0,
            ':shipping_fee' => $order_data['shipping_fee'] ?? ($order_data['shipping'] ?? 0),
            ':discount_amount' => $order_data['discount_amount'] ?? ($order_data['discount'] ?? 0),
            ':total_amount' => $order_data['total_amount'] ?? ($order_data['total'] ?? 0),
            ':payment_method' => $order_data['payment_method'] ?? 'COD',
            ':payment_status' => $order_data['payment_status'] ?? 'Pending',
            ':order_status' => $order_data['order_status'] ?? 'Received',
            ':courier' => $order_data['courier'] ?? '',
            ':driver_name' => $order_data['driver_name'] ?? '',
            ':driver_phone' => $order_data['driver_phone'] ?? '',
            ':tracking_code' => $order_data['tracking_code'] ?? '',
            ':dispatch_notes' => $order_data['dispatch_notes'] ?? '',
            ':estimated_delivery' => $order_data['estimated_delivery'] ?? '',
            ':items_json' => is_string($order_data['items'] ?? null) ? $order_data['items'] : json_encode($order_data['items'] ?? [])
        ]);
        return $order_data;
    } catch (Exception $e) {
        return $order_data;
    }
}

function update_order_status($order_id, $status) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("UPDATE orders SET order_status = :status WHERE order_id = :oid");
        return $stmt->execute([':status' => $status, ':oid' => $order_id]);
    } catch (Exception $e) {
        return false;
    }
}

function update_order_full($order_id, $fields) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $setClauses = [];
        $params = [':oid' => $order_id];

        $columnMap = [
            'order_status' => 'order_status',
            'payment_status' => 'payment_status',
            'courier' => 'courier',
            'driver_name' => 'driver_name',
            'driver_phone' => 'driver_phone',
            'tracking_code' => 'tracking_code',
            'dispatch_notes' => 'dispatch_notes',
            'estimated_delivery' => 'estimated_delivery',
        ];

        foreach ($fields as $key => $val) {
            if (isset($columnMap[$key])) {
                $col = $columnMap[$key];
                $paramName = ':' . $col;
                $setClauses[] = "`{$col}` = {$paramName}";
                $params[$paramName] = $val;
            }
        }

        if (empty($setClauses)) return false;

        $sql = "UPDATE orders SET " . implode(", ", $setClauses) . " WHERE order_id = :oid";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (Exception $e) {
        return false;
    }
}

function delete_order($order_id) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = :oid");
        return $stmt->execute([':oid' => $order_id]);
    } catch (Exception $e) {
        return false;
    }
}

// ==============================================================================
// 3. STORE SETTINGS (MySQL Database Operations)
// ==============================================================================
function get_store_settings() {
    $pdo = get_mysql_pdo();
    $defaultSettings = [
        'store_name' => 'AURA Luxury Store',
        'store_name_ar' => 'متجر أورا الفاخر',
        'store_name_ku' => 'فرووشگەها ئۆرا یا لوکس',
        'store_tagline_en' => 'Haute Couture & Swiss Horology',
        'store_tagline_ar' => 'أزياء راقية وساعات سويسرية فاخرة',
        'store_tagline_ku' => 'جلوبەرگێن سەردەم و دەمژمێرێن سویسری یێن شاهانە',
        'store_description_en' => 'Exclusive online luxury boutique offering Swiss timepieces, haute couture, and rare artisan perfumes.',
        'store_description_ar' => 'بوتيك إلكتروني فاخر يقدم الساعات السويسرية، الأزياء الراقية، والعطور النادرة.',
        'store_description_ku' => 'بوتیکا سەرهێل یا لوکس بۆ دەمژمێرێن سویسری، جلوبەرگێن مارکە، و عەترێن دەگمەن.',
        'hero_headline_en' => 'Timeless Elegance & Haute Horology',
        'hero_headline_ar' => 'أناقة خالدة وساعات سويسرية فاخرة',
        'hero_headline_ku' => 'جوانییا بێ داوی و دەمژمێرێن شاهانە',
        'hero_subtitle_en' => 'Curated masterworks of Swiss watchmaking, bespoke couture, and rare fragrances with express delivery across Iraq and Kurdistan.',
        'hero_subtitle_ar' => 'تشكيلة مختارة من الساعات السويسرية والأزياء الراقية مع توصيل سريع في العراق وكوردستان.',
        'hero_subtitle_ku' => 'کۆمەکا تایبەت ژ دەمژمێرێن سویسری و جلوبەرگێن لوکس دگەل گەهاندنا لەزگین.',
        'logo_type' => 'emblem',
        'logo_emblem' => 'A',
        'logo_main' => 'AURA',
        'logo_sub' => 'STUDIO',
        'logo_image_url' => '',
        'favicon_url' => '',
        'brand_accent_color' => '#d4af37',
        'announcement_enabled' => true,
        'exchange_rate_usd_to_iqd' => 1320,
        'default_currency' => 'IQD',
        'delivery_kurdistan_fee' => 0,
        'delivery_iraq_fee' => 0,
        'free_delivery_threshold' => 0,
        'contact_phone' => '+964 750 123 4567',
        'contact_email' => 'concierge@aurastore.com',
        'contact_whatsapp' => '9647501234567',
        'boutique_location_en' => '100% Online Luxury Store • Express Door-to-Door Delivery',
        'boutique_location_ar' => 'متجر إلكتروني فاخر 100% • توصيل سريع ومباشر للباب',
        'boutique_location_ku' => 'فرۆشگەها سەرهێل یا لوکس ١٠٠٪ • گەهاندنا ئێکسەر بۆ بەر دەرگەهی',
        'announcement_text_en' => 'Express Delivery (Iraq & Kurdistan Region Only) • Exclusive Limited Time Collection',
        'announcement_text_ar' => 'توصيل سريع (العراق وإقليم كوردستان فقط) • تخفيضات حصرية لفترة محدودة',
        'announcement_text_ku' => 'گەهاندنا لەزگین (عیراق و هەرێما کوردستانێ ب تنێ) • داشکاندنا تایبەت بۆ دەمەکێ دیارکری',
        'gateways' => [
            'fib' => [
                'enabled' => true,
                'mode' => 'test',
                'client_id' => 'fib_live_client_89420ab92c',
                'client_secret' => 'fib_sec_9941a87b32f9104c99a0',
                'base_url_test' => 'https://api.test.fib.iq/v1',
                'base_url_prod' => 'https://api.fib.iq/v1',
                'account_holder' => 'AURA LUXURY TRADING LTD (FIB Iraq)',
                'account_iban' => 'IQ44FIBQ0000001009283741',
                'callback_url' => 'https://aurastore.iq/api/fib/callback',
                'webhook_secret' => 'whsec_fib_849204810238'
            ],
            'zaincash' => [
                'enabled' => true,
                'mode' => 'test',
                'merchant_id' => '5ff589a1033dd50000000001',
                'secret_key' => '$2y$10$hBbAZo2GfBggR50s/m2k9u.hF7x6y.Z.2023912837492',
                'msisdn' => '9647835077893',
                'init_url_test' => 'https://test.zaincash.iq/transaction/init',
                'init_url_prod' => 'https://api.zaincash.iq/transaction/init',
                'redirect_url' => 'https://aurastore.iq/payment/zaincash/redirect.php'
            ],
            'fastpay' => [
                'enabled' => true,
                'mode' => 'test',
                'merchant_mobile' => '+9647501234567',
                'store_password' => 'fastpay_store_pass_8821',
                'store_id' => 'FP-STORE-99214',
                'api_url_test' => 'https://dev.fast-pay.cash/merchant/generate-payment-token',
                'api_url_prod' => 'https://api.fast-pay.cash/merchant/generate-payment-token',
                'callback_url' => 'https://aurastore.iq/payment/fastpay/callback.php'
            ],
            'cod' => [
                'enabled' => true,
                'instructions_en' => 'Pay in Cash upon receiving and inspecting your luxury package.',
                'instructions_ar' => 'الدفع نقداً عند استلام الشحنة ومعاينتها.',
                'instructions_ku' => 'پارەدانا کاش دەمێ وەرگرتنا پاکێتا خوە.'
            ]
        ]
    ];

    if (!$pdo) return $defaultSettings;

    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'store_config' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && !empty($row['setting_value'])) {
            $decoded = json_decode($row['setting_value'], true);
            if (is_array($decoded)) {
                return array_merge($defaultSettings, $decoded);
            }
        }
        // If not found in db, save default to db
        save_store_settings($defaultSettings);
        return $defaultSettings;
    } catch (Exception $e) {
        return $defaultSettings;
    }
}

function save_store_settings($settings) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value) 
            VALUES ('store_config', :val) 
            ON DUPLICATE KEY UPDATE setting_value = :val2
        ");
        return $stmt->execute([':val' => $json, ':val2' => $json]);
    } catch (Exception $e) {
        return false;
    }
}

// ==============================================================================
// 4. INQUIRIES & CLAIMS (MySQL Database Operations)
// ==============================================================================
function get_all_inquiries() {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM inquiries ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        $inquiries = [];
        foreach ($rows as $r) {
            $inquiries[] = [
                'id' => $r['inquiry_code'],
                'name' => $r['name'],
                'email' => $r['email'],
                'phone' => $r['phone'],
                'subject' => $r['subject'],
                'message' => $r['message'],
                'status' => $r['status'],
                'date' => $r['created_at'],
                'created_at' => $r['created_at']
            ];
        }
        return $inquiries;
    } catch (Exception $e) {
        return [];
    }
}

function create_inquiry($inquiry_data) {
    $pdo = get_mysql_pdo();
    if (empty($inquiry_data['id'])) {
        $inquiry_data['id'] = 'INQ-' . rand(100, 999);
    }
    $inquiry_data['created_at'] = date('Y-m-d H:i:s');
    $inquiry_data['status'] = $inquiry_data['status'] ?? 'New';
    
    if (!$pdo) return $inquiry_data;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO inquiries (inquiry_code, name, email, phone, subject, message, status)
            VALUES (:code, :name, :email, :phone, :subject, :message, :status)
        ");
        $stmt->execute([
            ':code' => $inquiry_data['id'],
            ':name' => $inquiry_data['name'] ?? '',
            ':email' => $inquiry_data['email'] ?? '',
            ':phone' => $inquiry_data['phone'] ?? '',
            ':subject' => $inquiry_data['subject'] ?? '',
            ':message' => $inquiry_data['message'] ?? '',
            ':status' => $inquiry_data['status'] ?? 'New'
        ]);
        return $inquiry_data;
    } catch (Exception $e) {
        return $inquiry_data;
    }
}

function update_inquiry_status($id, $status) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("UPDATE inquiries SET status = :status WHERE inquiry_code = :code");
        return $stmt->execute([':status' => $status, ':code' => $id]);
    } catch (Exception $e) {
        return false;
    }
}

function delete_inquiry($id) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return false;

    try {
        $stmt = $pdo->prepare("DELETE FROM inquiries WHERE inquiry_code = :code");
        return $stmt->execute([':code' => $id]);
    } catch (Exception $e) {
        return false;
    }
}

// ==============================================================================
// 5. USER AUTHENTICATION & ACCOUNTS (MySQL Database Operations)
// ==============================================================================
function get_all_users() {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM users ORDER BY id DESC");
        $rows = $stmt->fetchAll();
        $users = [];
        foreach ($rows as $r) {
            $users[] = [
                'id' => $r['user_code'],
                'name' => $r['name'],
                'email' => $r['email'],
                'password_hash' => $r['password_hash'],
                'role' => $r['role'],
                'phone' => $r['phone'],
                'city' => $r['city'],
                'address' => $r['address'],
                'created_at' => $r['created_at']
            ];
        }
        return $users;
    } catch (Exception $e) {
        return [];
    }
}

function find_user_by_email($email) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return null;

    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $stmt->execute([':email' => trim($email)]);
        $r = $stmt->fetch();
        if (!$r) return null;

        return [
            'id' => $r['user_code'],
            'name' => $r['name'],
            'email' => $r['email'],
            'password_hash' => $r['password_hash'],
            'role' => $r['role'],
            'phone' => $r['phone'],
            'city' => $r['city'],
            'address' => $r['address'],
            'created_at' => $r['created_at']
        ];
    } catch (Exception $e) {
        return null;
    }
}

function register_user($name, $email, $password, $phone = '', $city = '', $address = '') {
    $pdo = get_mysql_pdo();
    if (!$pdo) {
        return ['success' => false, 'error' => 'Database connection offline'];
    }

    if (find_user_by_email($email)) {
        return ['success' => false, 'error' => 'Email already registered'];
    }

    $user_code = 'USR-' . rand(1000, 9999);
    try {
        $stmt = $pdo->prepare("
            INSERT INTO users (user_code, name, email, password_hash, phone, city, address, role)
            VALUES (:code, :name, :email, :pass, :phone, :city, :addr, 'customer')
        ");
        $stmt->execute([
            ':code' => $user_code,
            ':name' => trim($name),
            ':email' => strtolower(trim($email)),
            ':pass' => $password,
            ':phone' => trim($phone),
            ':city' => trim($city),
            ':addr' => trim($address)
        ]);

        return [
            'success' => true,
            'user' => [
                'id' => $user_code,
                'name' => trim($name),
                'email' => strtolower(trim($email)),
                'role' => 'customer',
                'phone' => trim($phone),
                'city' => trim($city),
                'address' => trim($address)
            ]
        ];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function authenticate_user($email, $password) {
    $user = find_user_by_email($email);
    if ($user && $user['password_hash'] === $password) {
        return $user;
    }
    return null;
}

// ==============================================================================
// 6. REVIEWS (MySQL Database Operations)
// ==============================================================================
function get_all_reviews() {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->query("SELECT * FROM reviews ORDER BY id DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function get_product_reviews($productId) {
    $pdo = get_mysql_pdo();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT * FROM reviews WHERE product_id = :pid ORDER BY id DESC");
        $stmt->execute([':pid' => $productId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}
?>
