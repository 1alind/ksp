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

// 3. User Authentication Helpers
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
