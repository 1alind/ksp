<?php
/**
 * AURA LUXURY ATELIER — Delivery Company Automated Webhook API
 * Endpoint: POST /api/delivery_webhook.php
 * 
 * Automatically receives delivery company status changes:
 * - "Shipped" / "In Transit"
 * - "Out for Delivery"
 * - "Delivered"
 * - "Cancelled"
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database/db.php';

// If GET request, return documentation and endpoint status
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode([
        'status' => 'active',
        'service' => 'Aura Delivery Company Webhook API',
        'description' => 'Automated status listener for third-party courier / delivery logistics services',
        'accepted_methods' => ['POST'],
        'example_payload' => [
            'package_id' => 'EXP-9921',
            'status' => 'Shipped', // Options: 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled'
            'courier' => 'Kurdistan Express',
            'driver_name' => 'Karwan Ali',
            'driver_phone' => '0750 123 4567',
            'notes' => 'Package scanned at central distribution sorting hub'
        ],
        'documentation' => 'Send POST with application/json or form-data containing package_id and status'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Read incoming payload (JSON or POST)
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    $data = $_POST;
}

$packageId = trim($data['package_id'] ?? ($data['tracking_code'] ?? ($data['order_id'] ?? '')));
$rawStatus = trim($data['status'] ?? ($data['order_status'] ?? ''));
$courier = trim($data['courier'] ?? '');
$driverName = trim($data['driver_name'] ?? '');
$driverPhone = trim($data['driver_phone'] ?? '');
$notes = trim($data['notes'] ?? ($data['dispatch_notes'] ?? ($data['checkpoint'] ?? '')));

if (empty($packageId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing package_id or tracking_code parameter'
    ]);
    exit;
}

if (empty($rawStatus)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing status parameter (e.g. Shipped, Out for Delivery, Delivered)'
    ]);
    exit;
}

// Normalize status names
$statusMap = [
    'waiting' => 'Waiting',
    'pending' => 'Waiting',
    'ready' => 'Ready to Ship',
    'ready_to_ship' => 'Ready to Ship',
    'ready to ship' => 'Ready to Ship',
    'rdy to ship' => 'Ready to Ship',
    'rdy_to_ship' => 'Ready to Ship',
    'prepared' => 'Ready to Ship',
    'shipped' => 'Shipped',
    'in_transit' => 'Shipped',
    'in transit' => 'Shipped',
    'dispatched' => 'Shipped',
    'out_for_delivery' => 'Out for Delivery',
    'out for delivery' => 'Out for Delivery',
    'out_to_delivery' => 'Out for Delivery',
    'with_courier' => 'Out for Delivery',
    'delivered' => 'Delivered',
    'completed' => 'Delivered',
    'cancelled' => 'Cancelled',
    'canceled' => 'Cancelled'
];

$normalizedKey = strtolower(str_replace(['-', ' '], '_', $rawStatus));
$standardStatus = $statusMap[$normalizedKey] ?? ucwords(str_replace('_', ' ', $rawStatus));

// Find order by tracking_code (Package ID) or order_id
$allOrders = get_all_orders();
$targetOrder = null;

foreach ($allOrders as $o) {
    if (!empty($o['tracking_code']) && strcasecmp(trim($o['tracking_code']), $packageId) === 0) {
        $targetOrder = $o;
        break;
    }
    if (!empty($o['order_id']) && strcasecmp(trim($o['order_id']), $packageId) === 0) {
        $targetOrder = $o;
        break;
    }
}

if (!$targetOrder) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => "No order found matching Package ID / Order ID: {$packageId}"
    ]);
    exit;
}

$orderId = $targetOrder['order_id'];
$prevStatus = $targetOrder['order_status'] ?? 'Waiting';

// Prepare updated fields
$updateFields = [
    'order_status' => $standardStatus
];

if (!empty($courier)) {
    $updateFields['courier'] = $courier;
}
if (!empty($driverName)) {
    $updateFields['driver_name'] = $driverName;
}
if (!empty($driverPhone)) {
    $updateFields['driver_phone'] = $driverPhone;
}

// Update dispatch notes if notes provided
if (!empty($notes)) {
    $timestamp = date('Y-m-d H:i');
    $existing = trim($targetOrder['dispatch_notes'] ?? '');
    $updateFields['dispatch_notes'] = $existing 
        ? "{$existing} | [{$timestamp} Courier API: {$notes}]"
        : "[{$timestamp} Courier API: {$notes}]";
}

// Update via database full update
$updated = update_order_full($orderId, $updateFields);

if ($updated) {
    echo json_encode([
        'success' => true,
        'message' => 'Order status successfully updated by delivery company API',
        'order_id' => $orderId,
        'package_id' => $targetOrder['tracking_code'] ?: $packageId,
        'previous_status' => $prevStatus,
        'current_status' => $standardStatus,
        'courier' => $updateFields['courier'] ?? ($targetOrder['courier'] ?? 'Courier'),
        'timestamp' => date('c')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database update failed'
    ]);
}
