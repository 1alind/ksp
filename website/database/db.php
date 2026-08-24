<?php
/**
 * Aura Store - Database Helper Module (PHP & JSON Storage Engine)
 * Handles storage of users, products, orders, reviews, and credentials inside the database/ directory.
 */

define('DB_DIR', __DIR__);

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

// 1. Products Helpers
function get_all_products() {
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
    $data = read_json_db('products.json');
    $products = $data['products'] ?? [];
    if (isset($new_product['id']) && $new_product['id'] > 0) {
        // Update existing
        foreach ($products as &$p) {
            if ($p['id'] == $new_product['id']) {
                $p = array_merge($p, $new_product);
                $data['products'] = $products;
                write_json_db('products.json', $data);
                return $p;
            }
        }
    } else {
        // Create new
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
    return null;
}

function delete_product($id) {
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

// 3. Store Settings & Payment Gateway Tokens
function get_store_settings() {
    $data = read_json_db('settings.json');
    if (empty($data)) {
        $default = [
            'store_name' => 'AURA Luxury Store',
            'exchange_rate_usd_to_iqd' => 1320,
            'default_currency' => 'USD',
            'delivery_kurdistan_fee' => 0,
            'delivery_iraq_fee' => 0,
            'free_delivery_threshold' => 0,
            'contact_phone' => '+964 750 123 4567',
            'contact_email' => 'concierge@aurastore.com',
            'contact_whatsapp' => '9647501234567',
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
                    'merchant_id' => '5ff6561082c3f8109c11f2a3',
                    'secret_key' => '$2y$10$hBbAZo2GfWge2j0xEv3q8.8Vo5AeaJk6m3mG0a.a2K9p8N.O0s1qG',
                    'msisdn' => '9647835077893',
                    'base_url_test' => 'https://test.zaincash.iq/transaction',
                    'base_url_prod' => 'https://api.zaincash.iq/transaction',
                    'redirect_url' => 'https://aurastore.iq/api/zaincash/redirect'
                ],
                'fastpay' => [
                    'enabled' => true,
                    'mode' => 'test',
                    'merchant_mobile' => '07501234567',
                    'store_id' => 'FP_STORE_94821',
                    'store_password' => 'FastPaySecretPass2026'
                ],
                'cod' => [
                    'enabled' => true,
                    'max_limit' => 5000
                ],
                'card' => [
                    'enabled' => true,
                    'mode' => 'test'
                ]
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

// 4. Inquiries & VIP Concierge Messages
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
        'password_hash' => $password, // Can use password_hash() in prod
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
