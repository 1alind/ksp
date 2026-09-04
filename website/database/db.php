<?php
/**
 * Aura Store - Pure MySQL Database Engine (PDO)
 * Exclusively uses MySQL database for products, orders, users, inquiries, reviews, and settings.
 */

// --- MySQL Conjfiguration ---
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
// ==============================================================================
// 1. PRODUCTS (Dual MySQL Database & JSON File Engine)
// ==============================================================================
function get_all_products() {
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM products ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                $products = [];
                foreach ($rows as $row) {
                    $products[] = [
                        'id' => (int)$row['id'],
                        'title' => [
                            'en' => $row['title_en'] ?? '',
                            'ar' => $row['title_ar'] ?? '',
                            'ku' => $row['title_ku'] ?? '',
                        ],
                        'category' => $row['category'] ?? 'clothes',
                        'price' => (float)($row['price'] ?? 0),
                        'old_price' => !empty($row['old_price']) ? (float)$row['old_price'] : null,
                        'rating' => (float)($row['rating'] ?? 5.0),
                        'reviews_count' => (int)($row['reviews_count'] ?? 0),
                        'badge' => $row['badge_en'] ?? '',
                        'badge_ar' => $row['badge_ar'] ?? '',
                        'badge_ku' => $row['badge_ku'] ?? '',
                        'stock' => (int)($row['stock'] ?? 0),
                        'image' => $row['image'] ?? '',
                        'images' => is_string($row['images'] ?? null) ? json_decode($row['images'], true) : ($row['images'] ?? []),
                        'colors' => is_string($row['colors'] ?? null) ? json_decode($row['colors'], true) : ($row['colors'] ?? []),
                        'sizes' => is_string($row['sizes'] ?? null) ? json_decode($row['sizes'], true) : ($row['sizes'] ?? []),
                        'size_measurements' => is_string($row['size_measurements'] ?? null) ? json_decode($row['size_measurements'], true) : ($row['size_measurements'] ?? []),
                        'description' => [
                            'en' => $row['description_en'] ?? '',
                            'ar' => $row['description_ar'] ?? '',
                            'ku' => $row['description_ku'] ?? '',
                        ],
                        'featured' => !empty($row['featured']),
                        'model_group' => $row['model_group'] ?? '',
                        'color_name' => $row['color_name'] ?? '',
                        'color_hex' => $row['color_hex'] ?? '',
                        'linked_products' => is_string($row['linked_products'] ?? null) ? json_decode($row['linked_products'], true) : ($row['linked_products'] ?? [])
                    ];
                }
                return $products;
            }
        } catch (Exception $e) {
            // fallback to JSON
        }
    }

    // JSON file fallback
    $jsonFile = __DIR__ . '/products.json';
    if (file_exists($jsonFile)) {
        $data = json_decode(file_get_contents($jsonFile), true);
        return $data['products'] ?? [];
    }
    return [];
}

function get_product_by_id($id) {
    $all = get_all_products();
    foreach ($all as $p) {
        if ((int)$p['id'] === (int)$id) {
            return $p;
        }
    }
    return null;
}

function save_product($new_product) {
    $jsonFile = __DIR__ . '/products.json';
    $data = ['products' => []];
    if (file_exists($jsonFile)) {
        $decoded = json_decode(file_get_contents($jsonFile), true);
        if (isset($decoded['products']) && is_array($decoded['products'])) {
            $data = $decoded;
        }
    }

    $existingIndex = -1;
    $maxId = 0;
    foreach ($data['products'] as $idx => $p) {
        $pId = (int)($p['id'] ?? 0);
        if ($pId > $maxId) $maxId = $pId;
        if (isset($new_product['id']) && $pId === (int)$new_product['id'] && (int)$new_product['id'] > 0) {
            $existingIndex = $idx;
        }
    }

    if (empty($new_product['id']) || (int)$new_product['id'] <= 0) {
        $new_product['id'] = $maxId + 1;
    } else {
        $new_product['id'] = (int)$new_product['id'];
    }

    // Ensure proper structure
    $title = is_array($new_product['title'] ?? null) ? $new_product['title'] : [
        'en' => $new_product['title_en'] ?? ($new_product['title'] ?? ''),
        'ar' => $new_product['title_ar'] ?? ($new_product['title'] ?? ''),
        'ku' => $new_product['title_ku'] ?? ($new_product['title'] ?? '')
    ];
    if (empty($title['en'])) $title['en'] = !empty($title['ar']) ? $title['ar'] : (!empty($title['ku']) ? $title['ku'] : 'New Luxury Item');
    if (empty($title['ar'])) $title['ar'] = $title['en'];
    if (empty($title['ku'])) $title['ku'] = $title['en'];

    $description = is_array($new_product['description'] ?? null) ? $new_product['description'] : [
        'en' => $new_product['desc_en'] ?? ($new_product['description_en'] ?? ($new_product['description'] ?? '')),
        'ar' => $new_product['desc_ar'] ?? ($new_product['description_ar'] ?? ($new_product['description'] ?? '')),
        'ku' => $new_product['desc_ku'] ?? ($new_product['description_ku'] ?? ($new_product['description'] ?? ''))
    ];
    if (empty($description['ar'])) $description['ar'] = $description['en'] ?? '';
    if (empty($description['ku'])) $description['ku'] = $description['en'] ?? '';

    $mainImg = trim($new_product['image'] ?? '');
    $imagesList = is_array($new_product['images'] ?? null) ? $new_product['images'] : (is_string($new_product['images'] ?? null) ? array_filter(array_map('trim', explode(',', $new_product['images']))) : []);
    if (empty($mainImg) && !empty($imagesList[0])) {
        $mainImg = $imagesList[0];
    }
    if (empty($mainImg)) {
        $mainImg = 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80';
    }
    if (empty($imagesList)) {
        $imagesList = [$mainImg];
    }

    $productFormatted = [
        'id' => (int)$new_product['id'],
        'title' => $title,
        'category' => $new_product['category'] ?? 'clothes',
        'price' => (float)($new_product['price'] ?? 0),
        'old_price' => !empty($new_product['old_price']) ? (float)$new_product['old_price'] : null,
        'rating' => isset($new_product['rating']) ? (float)$new_product['rating'] : 5.0,
        'reviews_count' => isset($new_product['reviews_count']) ? (int)$new_product['reviews_count'] : 24,
        'badge' => $new_product['badge'] ?? ($new_product['badge_en'] ?? ''),
        'badge_ar' => $new_product['badge_ar'] ?? '',
        'badge_ku' => $new_product['badge_ku'] ?? '',
        'stock' => isset($new_product['stock']) ? (int)$new_product['stock'] : 10,
        'image' => $mainImg,
        'images' => array_values($imagesList),
        'colors' => is_array($new_product['colors'] ?? null) ? $new_product['colors'] : (is_string($new_product['colors'] ?? null) ? array_filter(array_map('trim', explode(',', $new_product['colors']))) : []),
        'sizes' => is_array($new_product['sizes'] ?? null) ? $new_product['sizes'] : (is_string($new_product['sizes'] ?? null) ? array_filter(array_map('trim', explode(',', $new_product['sizes']))) : []),
        'size_measurements' => is_array($new_product['size_measurements'] ?? null) ? $new_product['size_measurements'] : ($new_product['size_measurements'] ?? new stdClass()),
        'description' => $description,
        'featured' => !empty($new_product['featured']),
        'model_group' => trim($new_product['model_group'] ?? ''),
        'color_name' => trim($new_product['color_name'] ?? ($new_product['color'] ?? '')),
        'color_hex' => trim($new_product['color_hex'] ?? ''),
        'linked_products' => is_array($new_product['linked_products'] ?? null) 
            ? array_values(array_map('intval', array_filter($new_product['linked_products']))) 
            : (is_string($new_product['linked_products'] ?? null) ? array_values(array_filter(array_map('intval', explode(',', $new_product['linked_products'])))) : [])
    ];

    if (empty($productFormatted['images']) && !empty($productFormatted['image'])) {
        $productFormatted['images'] = [$productFormatted['image']];
    }

    // Save to JSON
    if ($existingIndex >= 0) {
        $data['products'][$existingIndex] = $productFormatted;
    } else {
        $data['products'][] = $productFormatted;
    }
    file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);

    // Save to MySQL if available
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            if ($existingIndex >= 0) {
                $sql = "UPDATE products SET 
                        title_en = :title_en, title_ar = :title_ar, title_ku = :title_ku,
                        category = :category, price = :price, old_price = :old_price,
                        badge_en = :badge_en, badge_ar = :badge_ar, badge_ku = :badge_ku,
                        stock = :stock, image = :image, images = :images,
                        colors = :colors, sizes = :sizes, size_measurements = :size_measurements,
                        description_en = :desc_en, description_ar = :desc_ar, description_ku = :desc_ku,
                        featured = :featured
                        WHERE id = :id";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $productFormatted['id'],
                    ':title_en' => $productFormatted['title']['en'] ?? '',
                    ':title_ar' => $productFormatted['title']['ar'] ?? '',
                    ':title_ku' => $productFormatted['title']['ku'] ?? '',
                    ':category' => $productFormatted['category'],
                    ':price' => $productFormatted['price'],
                    ':old_price' => $productFormatted['old_price'],
                    ':badge_en' => $productFormatted['badge'],
                    ':badge_ar' => $productFormatted['badge_ar'],
                    ':badge_ku' => $productFormatted['badge_ku'],
                    ':stock' => $productFormatted['stock'],
                    ':image' => $productFormatted['image'],
                    ':images' => json_encode($productFormatted['images'], JSON_UNESCAPED_UNICODE),
                    ':colors' => json_encode($productFormatted['colors'], JSON_UNESCAPED_UNICODE),
                    ':sizes' => json_encode($productFormatted['sizes'], JSON_UNESCAPED_UNICODE),
                    ':size_measurements' => json_encode($productFormatted['size_measurements'], JSON_UNESCAPED_UNICODE),
                    ':desc_en' => $productFormatted['description']['en'] ?? '',
                    ':desc_ar' => $productFormatted['description']['ar'] ?? '',
                    ':desc_ku' => $productFormatted['description']['ku'] ?? '',
                    ':featured' => $productFormatted['featured'] ? 1 : 0
                ]);
            } else {
                $sql = "INSERT INTO products 
                        (id, title_en, title_ar, title_ku, category, price, old_price, rating, reviews_count, badge_en, badge_ar, badge_ku, stock, image, images, colors, sizes, size_measurements, description_en, description_ar, description_ku, featured)
                        VALUES (:id, :title_en, :title_ar, :title_ku, :category, :price, :old_price, :rating, :reviews_count, :badge_en, :badge_ar, :badge_ku, :stock, :image, :images, :colors, :sizes, :size_measurements, :desc_en, :desc_ar, :desc_ku, :featured)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':id' => $productFormatted['id'],
                    ':title_en' => $productFormatted['title']['en'] ?? '',
                    ':title_ar' => $productFormatted['title']['ar'] ?? '',
                    ':title_ku' => $productFormatted['title']['ku'] ?? '',
                    ':category' => $productFormatted['category'],
                    ':price' => $productFormatted['price'],
                    ':old_price' => $productFormatted['old_price'],
                    ':rating' => $productFormatted['rating'],
                    ':reviews_count' => $productFormatted['reviews_count'],
                    ':badge_en' => $productFormatted['badge'],
                    ':badge_ar' => $productFormatted['badge_ar'],
                    ':badge_ku' => $productFormatted['badge_ku'],
                    ':stock' => $productFormatted['stock'],
                    ':image' => $productFormatted['image'],
                    ':images' => json_encode($productFormatted['images'], JSON_UNESCAPED_UNICODE),
                    ':colors' => json_encode($productFormatted['colors'], JSON_UNESCAPED_UNICODE),
                    ':sizes' => json_encode($productFormatted['sizes'], JSON_UNESCAPED_UNICODE),
                    ':size_measurements' => json_encode($productFormatted['size_measurements'], JSON_UNESCAPED_UNICODE),
                    ':desc_en' => $productFormatted['description']['en'] ?? '',
                    ':desc_ar' => $productFormatted['description']['ar'] ?? '',
                    ':desc_ku' => $productFormatted['description']['ku'] ?? '',
                    ':featured' => $productFormatted['featured'] ? 1 : 0
                ]);
            }
        } catch (Exception $e) {
            // MySQL error logged or handled
        }
    }

    return $productFormatted;
}

/**
 * Get all linked color/model variants for a given product
 */
function get_linked_color_variants($product) {
    if (empty($product)) return [];
    $currentId = (int)($product['id'] ?? 0);
    $modelGroup = trim($product['model_group'] ?? '');
    $linkedIds = is_array($product['linked_products'] ?? null) ? $product['linked_products'] : [];
    
    // If there is no model group and no linked products, return empty
    if (empty($modelGroup) && empty($linkedIds)) {
        // Also check if any other product links to this one
        $all = get_all_products();
        $hasInboundLinks = false;
        foreach ($all as $p) {
            $pLinkedIds = is_array($p['linked_products'] ?? null) ? $p['linked_products'] : [];
            if (in_array($currentId, $pLinkedIds, true)) {
                $hasInboundLinks = true;
                break;
            }
        }
        if (!$hasInboundLinks) {
            return [];
        }
    } else {
        $all = get_all_products();
    }
    
    $variants = [];
    $seenIds = [];

    foreach ($all as $p) {
        $pId = (int)($p['id'] ?? 0);
        $pModelGroup = trim($p['model_group'] ?? '');
        $pLinkedIds = is_array($p['linked_products'] ?? null) ? $p['linked_products'] : [];
        
        $isLinked = false;
        if (!empty($modelGroup) && !empty($pModelGroup) && strcasecmp($modelGroup, $pModelGroup) === 0) {
            $isLinked = true;
        } elseif (in_array($pId, $linkedIds, true)) {
            $isLinked = true;
        } elseif (in_array($currentId, $pLinkedIds, true)) {
            $isLinked = true;
        }
        
        if ($isLinked && !isset($seenIds[$pId])) {
            $seenIds[$pId] = true;
            $colorName = !empty($p['color_name']) ? $p['color_name'] : (!empty($p['colors']) ? $p['colors'][0] : 'Color Variation');
            $variants[] = [
                'id' => $pId,
                'title' => $p['title'],
                'image' => $p['image'],
                'price' => (float)($p['price'] ?? 0),
                'old_price' => !empty($p['old_price']) ? (float)$p['old_price'] : null,
                'stock' => (int)($p['stock'] ?? 0),
                'color_name' => $colorName,
                'color_hex' => !empty($p['color_hex']) ? $p['color_hex'] : '#d4af37',
                'model_group' => $pModelGroup,
                'is_current' => ($pId === $currentId)
            ];
        }
    }
    
    // Only return if there is at least 1 other sibling besides the current item
    if (count($variants) < 2) {
        return [];
    }
    
    // Ensure current product is included and sort so current or active is nicely ordered
    usort($variants, function($a, $b) {
        return $a['id'] <=> $b['id'];
    });
    
    return $variants;
}

function delete_product($id) {
    $id = (int)$id;
    $jsonFile = __DIR__ . '/products.json';
    if (file_exists($jsonFile)) {
        $decoded = json_decode(file_get_contents($jsonFile), true);
        if (isset($decoded['products']) && is_array($decoded['products'])) {
            $decoded['products'] = array_values(array_filter($decoded['products'], function($p) use ($id) {
                return (int)($p['id'] ?? 0) !== $id;
            }));
            @file_put_contents($jsonFile, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } catch (Exception $e) {
            // silently handle
        }
    }
    return true;
}

function adjust_product_stock($id, $delta) {
    $id = (int)$id;
    $delta = (int)$delta;
    $newStock = 0;

    $jsonFile = __DIR__ . '/products.json';
    if (file_exists($jsonFile)) {
        $decoded = json_decode(file_get_contents($jsonFile), true);
        if (isset($decoded['products']) && is_array($decoded['products'])) {
            foreach ($decoded['products'] as &$p) {
                if ((int)($p['id'] ?? 0) === $id) {
                    $p['stock'] = max(0, (int)($p['stock'] ?? 0) + $delta);
                    $newStock = $p['stock'];
                    break;
                }
            }
            unset($p);
            @file_put_contents($jsonFile, json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("UPDATE products SET stock = GREATEST(0, stock + :delta) WHERE id = :id");
            $stmt->execute([':delta' => $delta, ':id' => $id]);
            $stmt2 = $pdo->prepare("SELECT stock FROM products WHERE id = :id LIMIT 1");
            $stmt2->execute([':id' => $id]);
            $r = $stmt2->fetch();
            if ($r) {
                $newStock = (int)$r['stock'];
            }
        } catch (Exception $e) {
            // Handled
        }
    }

    return $newStock;
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

// ==============================================================================
// 7. SETTINGS OPERATIONS
// ==============================================================================
function get_settings() {
    $file = __DIR__ . '/settings.json';
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function get_setting($key, $default = null) {
    $settings = get_settings();
    return $settings[$key] ?? $default;
}
?>
