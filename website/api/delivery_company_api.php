<?php
/**
 * AURA LUXURY ATELIER — Delivery Company Public & Internal API Endpoint
 * Endpoint: /api/delivery_company_api.php
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
require_once __DIR__ . '/../database/delivery_sync.php';

$action = trim($_GET['action'] ?? ($_POST['action'] ?? ''));

// 1. Hourly Sync Check Status
if ($action === 'check_sync') {
    $force = !empty($_GET['force']) || !empty($_POST['force']);
    $result = check_and_sync_delivery_company_hourly($force);
    echo json_encode([
        'success' => true,
        'sync_check' => $result
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// 2. Query status for a single package
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['package_id']) || $action === 'get_status')) {
    $packageId = trim($_GET['package_id'] ?? '');
    if (empty($packageId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing package_id parameter']);
        exit;
    }

    $pkg = query_delivery_company_api($packageId);
    if ($pkg) {
        echo json_encode([
            'success' => true,
            'source' => 'Delivery Company Central Logistics API',
            'data' => $pkg
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Package ID not found at courier logistics']);
    }
    exit;
}

// 3. Update status of a package in delivery company system (e.g. Courier dispatch scan)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $packageId = trim($data['package_id'] ?? '');
    $newStatus = trim($data['status'] ?? '');
    $checkpoint = trim($data['checkpoint'] ?? ($data['notes'] ?? ''));
    $driverName = trim($data['driver_name'] ?? '');
    $driverPhone = trim($data['driver_phone'] ?? '');

    if (empty($packageId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing package_id']);
        exit;
    }

    if (empty($newStatus)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Missing status']);
        exit;
    }

    $updateData = ['status' => $newStatus];
    if (!empty($checkpoint)) $updateData['checkpoint'] = $checkpoint;
    if (!empty($driverName)) $updateData['driver_name'] = $driverName;
    if (!empty($driverPhone)) $updateData['driver_phone'] = $driverPhone;

    $updatedRecord = set_company_package_record($packageId, $updateData);

    // If immediate sync requested
    if (!empty($data['sync_database_now'])) {
        sync_all_orders_from_company_api('Direct Courier API Push');
    }

    echo json_encode([
        'success' => true,
        'message' => "Package {$packageId} updated at delivery company system",
        'package' => $updatedRecord
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// Default response: API status & documentation
$state = get_delivery_sync_state();
echo json_encode([
    'service' => 'Aura Delivery Company Logistics API',
    'status' => 'operational',
    'sync_interval_seconds' => DELIVERY_SYNC_INTERVAL,
    'last_sync_timestamp' => $state['last_sync_timestamp'],
    'last_sync_datetime' => $state['last_sync_datetime'],
    'endpoints' => [
        'GET /api/delivery_company_api.php?package_id={PACKAGE_ID}' => 'Get current courier status for package',
        'POST /api/delivery_company_api.php' => 'Update courier status for package',
        'GET /api/delivery_company_api.php?action=check_sync' => 'Check and trigger 60-min lazy sync'
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
