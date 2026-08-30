<?php
/**
 * Aura Store - Database Helper Module (PHP, MySQL PDO & JSON Hybrid Engine)
 * Handles storage of users, products, orders, reviews, and credentials.
 * Supports MySQL Database (via PDO) with automated fallback to JSON files.
 */

define('DB_DIR', __DIR__);

// --- MySQL Configuration ---
// Set these parameters or define environment variables to connect to your MySQL database:
define('MYSQL_ENABLED', getenv('MYSQL_ENABLED') === 'true' || false);
define('MYSQL_HOST', getenv('MYSQL_HOST') ?: '127.0.0.1');
define('MYSQL_PORT', getenv('MYSQL_PORT') ?: '3306');
define('MYSQL_DBNAME', getenv('MYSQL_DBNAME') ?: 'aura_store');
define('MYSQL_USER', getenv('MYSQL_USER') ?: 'root');
define('MYSQL_PASSWORD', getenv('MYSQL_PASSWORD') ?: '');

/**
 * Get MySQL PDO Connection
 */
function get_mysql_pdo() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    
    if (!MYSQL_ENABLED && !getenv('MYSQL_HOST')) {
        return null;
    }
    
    try {
        $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DBNAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASSWORD, $options);
        return $pdo;
    } catch (Exception $e) {
        // Fallback to JSON mode if MySQL is unreachable
        return null;
    }
}

// --- JSON File Helpers ---
function get_db_file($filename) {
    $path = DB_DIR . '/' . $filename;
    if (!file_exists($path)) {
        file_put_contents($path, json_encode([], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return $path;
}

function read_json_db($filename) {
    $path = get_db_file($filename);
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function write_json_db($filename, $data) {
    $path = get_db_file($filename);
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// 1. Products Helpers (MySQL with JSON Fallback)
function get_all_products() {
    $pdo = get_mysql_pdo();
    if ($pdo) {
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
            if (!empty($products)) {
                return $products;
            }
        } catch (Exception $e) {
            // Fallback to JSON
        }
    }

    $data = read_json_db('products.json');
    return $data['products'] ?? [];
}

function get_product_by_id($id) {
    $products = get_all_products();
    foreach ($products as $p) {
        if ($p['id'] == $id) {
            return $p;
        }
    }
    return null;
}

function save_product($new_product) {
    $pdo = get_mysql_pdo();
    if ($pdo) {
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
                    ':images' => json_encode($new_product['images'] ?? []),
                    ':colors' => json_encode($new_product['colors'] ?? []),
                    ':sizes' => json_encode($new_product['sizes'] ?? []),
                    ':size_measurements' => json_encode($new_product['size_measurements'] ?? []),
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
                    ':images' => json_encode($new_product['images'] ?? []),
                    ':colors' => json_encode($new_product['colors'] ?? []),
                    ':sizes' => json_encode($new_product['sizes'] ?? []),
                    ':size_measurements' => json_encode($new_product['size_measurements'] ?? []),
                    ':desc_en' => $new_product['description']['en'] ?? ($new_product['description_en'] ?? ''),
                    ':desc_ar' => $new_product['description']['ar'] ?? ($new_product['description_ar'] ?? ''),
                    ':desc_ku' => $new_product['description']['ku'] ?? ($new_product['description_ku'] ?? ''),
                    ':featured' => !empty($new_product['featured']) ? 1 : 0
                ]);
                $new_product['id'] = (int)$pdo->lastInsertId();
            }
        } catch (Exception $e) {
            // Log error
        }
    }

    // Always keep JSON mirror in sync
    $data = read_json_db('products.json');
    $products = $data['products'] ?? [];
    if (isset($new_product['id']) && $new_product['id'] > 0) {
        foreach ($products as &$p) {
            if ($p['id'] == $new_product['id']) {
                $p = array_merge($p, $new_product);
                $data['products'] = $products;
                write_json_db('products.json', $data);
                return $p;
            }
        }
    } else {
        $max_id = 0;
        foreach ($products as $p) {
            if ($p['id'] > $max_id) $max_id = $p['id'];
        }
        $new_product['id'] = $max_id + 1;
        $products[] = $new_product;
        $data['products'] = $products;
        write_json_db('products.json', $data);
        return $new_product;
    }
    return $new_product;
}

function delete_product($id) {
    $pdo = get_mysql_pdo();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = :id");
            $stmt->execute([':id' => $id]);
        } catch (Exception $e) {}
    }

    $data = read_json_db('products.json');
    $products = $data['products'] ?? [];
    $filtered = array_filter($products, function($p) use ($id) {
        return $p['id'] != $id;
    });
    $data['products'] = array_values($filtered);
    return write_json_db('products.json', $data);
}

// 2. Orders Helpers
function get_all_orders() {
    $data = read_json_db('orders.json');
    return $data['orders'] ?? [];
}

function get_order_by_id($order_id) {
    $orders = get_all_orders();
    foreach ($orders as $order) {
        if (strcasecmp($order['order_id'], trim($order_id)) === 0) {
            return $order;
        }
    }
    return null;
}

function create_order($order_data) {
    $data = read_json_db('orders.json');
    $orders = $data['orders'] ?? [];
    
    if (empty($order_data['order_id'])) {
        $order_data['order_id'] = 'ORD-' . rand(10000, 99999);
    }
    $order_data['created_at'] = date('Y-m-d\TH:i:s\Z');
    $order_data['order_status'] = $order_data['order_status'] ?? 'Pending';
    
    array_unshift($orders, $order_data);
    $data['orders'] = $orders;
    write_json_db('orders.json', $data);
    return $order_data;
}

function update_order_status($order_id, $status) {
    $data = read_json_db('orders.json');
    $orders = $data['orders'] ?? [];
    foreach ($orders as &$o) {
        if ($o['order_id'] === $order_id) {
            $o['order_status'] = $status;
            $data['orders'] = $orders;
            write_json_db('orders.json', $data);
            return true;
        }
    }
    return false;
}

function update_order_full($order_id, $fields) {
    $data = read_json_db('orders.json');
    $orders = $data['orders'] ?? [];
    foreach ($orders as &$o) {
        if ($o['order_id'] === $order_id) {
            $o = array_merge($o, $fields);
            $data['orders'] = $orders;
            write_json_db('orders.json', $data);
            return $o;
        }
    }
    return null;
}

function delete_order($order_id) {
    $data = read_json_db('orders.json');
    $orders = $data['orders'] ?? [];
    $filtered = array_filter($orders, function($o) use ($order_id) {
        return $o['order_id'] !== $order_id;
    });
    $data['orders'] = array_values($filtered);
    return write_json_db('orders.json', $data);
}

// 3. Store Settings
function get_store_settings() {
    $data = read_json_db('settings.json');
    if (empty($data)) {
        $default = [
            'store_name' => 'AURA Luxury Store',
            'exchange_rate_usd_to_iqd' => 1320,
            'default_currency' => 'USD',
            'delivery_duhok_fee' => 4000,
            'delivery_other_fee' => 5000,
            'contact_phone' => '+964 750 123 4567',
            'contact_email' => 'concierge@aurastore.com',
            'contact_whatsapp' => '9647501234567',
            'gateways' => [
                'fib' => ['enabled' => true, 'mode' => 'test'],
                'zaincash' => ['enabled' => true, 'mode' => 'test'],
                'fastpay' => ['enabled' => true, 'mode' => 'test'],
                'cod' => ['enabled' => true]
            ]
        ];
        write_json_db('settings.json', $default);
        return $default;
    }
    return $data;
}

function save_store_settings($settings) {
    return write_json_db('settings.json', $settings);
}

// 4. Inquiries
function get_all_inquiries() {
    $data = read_json_db('inquiries.json');
    return $data['inquiries'] ?? [];
}

function create_inquiry($inquiry_data) {
    $data = read_json_db('inquiries.json');
    $inquiries = $data['inquiries'] ?? [];
    if (empty($inquiry_data['id'])) {
        $inquiry_data['id'] = 'INQ-' . rand(100, 999);
    }
    $inquiry_data['created_at'] = date('Y-m-d\TH:i:s\Z');
    $inquiry_data['status'] = $inquiry_data['status'] ?? 'New';
    array_unshift($inquiries, $inquiry_data);
    $data['inquiries'] = $inquiries;
    write_json_db('inquiries.json', $data);
    return $inquiry_data;
}

function update_inquiry_status($id, $status) {
    $data = read_json_db('inquiries.json');
    $inquiries = $data['inquiries'] ?? [];
    foreach ($inquiries as &$inq) {
        if ($inq['id'] === $id) {
            $inq['status'] = $status;
            $data['inquiries'] = $inquiries;
            write_json_db('inquiries.json', $data);
            return true;
        }
    }
    return false;
}

function delete_inquiry($id) {
    $data = read_json_db('inquiries.json');
    $inquiries = $data['inquiries'] ?? [];
    $filtered = array_filter($inquiries, function($i) use ($id) {
        return $i['id'] !== $id;
    });
    $data['inquiries'] = array_values($filtered);
    return write_json_db('inquiries.json', $data);
}

// 5. User Authentication Helpers
function get_all_users() {
    $data = read_json_db('users.json');
    return $data['users'] ?? [];
}

function find_user_by_email($email) {
    $users = get_all_users();
    foreach ($users as $u) {
        if (strtolower($u['email']) === strtolower(trim($email))) {
            return $u;
        }
    }
    return null;
}

function register_user($name, $email, $password, $phone = '', $city = '', $address = '') {
    $data = read_json_db('users.json');
    $users = $data['users'] ?? [];
    
    if (find_user_by_email($email)) {
        return ['success' => false, 'error' => 'Email already registered'];
    }
    
    $new_user = [
        'id' => 'USR-' . rand(1000, 9999),
        'name' => trim($name),
        'email' => strtolower(trim($email)),
        'password_hash' => $password,
        'role' => 'customer',
        'phone' => trim($phone),
        'city' => trim($city),
        'address' => trim($address),
        'created_at' => date('Y-m-d\TH:i:s\Z')
    ];
    
    $users[] = $new_user;
    $data['users'] = $users;
    write_json_db('users.json', $data);
    return ['success' => true, 'user' => $new_user];
}

function authenticate_user($email, $password) {
    $user = find_user_by_email($email);
    if ($user && $user['password_hash'] === $password) {
        return $user;
    }
    return null;
}
?>
