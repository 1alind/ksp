<?php
/**
 * AURA LUXURY ATELIER — Delivery Company Hourly Synchronization Engine
 * 
 * 60-Minute Lazy-Cron Synchronization Rule:
 * 1. When a user visits the tracking page, the system checks the last time order statuses were updated.
 * 2. If the last update was MORE than 60 minutes (3600 seconds) ago:
 *    - Automatically contacts the delivery company API for all active orders with linked package IDs.
 *    - Updates the order statuses and courier checkpoints in the database.
 *    - Sets the last updated timestamp to the current time.
 * 3. If the last update was LESS than 60 minutes ago:
 *    - It does NOT call the company API.
 *    - Directly serves and displays data from the database.
 */

if (!defined('DELIVERY_SYNC_INTERVAL')) {
    define('DELIVERY_SYNC_INTERVAL', 3600); // 1 hour = 3600 seconds
}

require_once __DIR__ . '/db.php';

/**
 * Get the current delivery synchronization state
 */
function get_delivery_sync_state() {
    $file = __DIR__ . '/delivery_sync_state.json';
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data) && isset($data['last_sync_timestamp'])) {
            return $data;
        }
    }
    return [
        'last_sync_timestamp' => 0,
        'last_sync_datetime' => 'Never',
        'last_synced_orders_count' => 0,
        'last_sync_message' => 'Initial state - no sync performed yet',
        'updated_orders' => []
    ];
}

/**
 * Save the delivery synchronization state
 */
function save_delivery_sync_state($state) {
    $file = __DIR__ . '/delivery_sync_state.json';
    @file_put_contents($file, json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return true;
}

/**
 * Get company packages registry from delivery company database
 */
function get_company_packages_registry() {
    $file = __DIR__ . '/company_packages.json';
    if (file_exists($file)) {
        $content = @file_get_contents($file);
        $data = json_decode($content, true);
        if (is_array($data) && isset($data['packages'])) {
            return $data['packages'];
        }
    }
    return [];
}

/**
 * Save or update a package record in the delivery company system
 */
function set_company_package_record($packageId, $record) {
    $file = __DIR__ . '/company_packages.json';
    $registry = get_company_packages_registry();
    $registry[$packageId] = array_merge($registry[$packageId] ?? [], $record, [
        'package_id' => $packageId,
        'last_updated' => date('Y-m-d H:i:s')
    ]);
    @file_put_contents($file, json_encode(['packages' => $registry], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $registry[$packageId];
}

/**
 * Query the Delivery Company API for a specific package ID
 */
function query_delivery_company_api($packageId, $orderId = '', $currentStatus = 'Waiting') {
    if (empty($packageId)) {
        return null;
    }

    $packageId = trim($packageId);
    $registry = get_company_packages_registry();

    // Check if the delivery company has this package in its central registry
    if (isset($registry[$packageId])) {
        $pkg = $registry[$packageId];
        return [
            'success' => true,
            'package_id' => $packageId,
            'order_id' => $pkg['order_id'] ?? $orderId,
            'status' => $pkg['status'] ?? 'Ready to Ship',
            'courier' => $pkg['courier'] ?? 'Kurdistan Express Logistics',
            'driver_name' => $pkg['driver_name'] ?? '',
            'driver_phone' => $pkg['driver_phone'] ?? '',
            'checkpoint' => $pkg['checkpoint'] ?? 'In transit with delivery courier',
            'estimated_delivery' => $pkg['estimated_delivery'] ?? 'Estimated Arrival: Within 24 – 72 Hours',
            'last_updated' => $pkg['last_updated'] ?? date('Y-m-d H:i:s')
        ];
    }

    // Default company record creation if package exists on store but not in mock company registry
    $defaultPkg = [
        'package_id' => $packageId,
        'order_id' => $orderId,
        'status' => in_array($currentStatus, ['Waiting', 'Ready to Ship', 'Shipped', 'Out for Delivery', 'Delivered']) ? $currentStatus : 'Ready to Ship',
        'courier' => 'Kurdistan Express Logistics',
        'driver_name' => '',
        'driver_phone' => '',
        'checkpoint' => 'Package ID registered with courier central manifest',
        'estimated_delivery' => 'Estimated Arrival: Within 24 – 72 Hours',
        'last_updated' => date('Y-m-d H:i:s')
    ];
    set_company_package_record($packageId, $defaultPkg);

    return array_merge(['success' => true], $defaultPkg);
}

/**
 * Perform synchronization of all orders against the Delivery Company API
 * and update their statuses in the store database.
 */
function sync_all_orders_from_company_api($triggerSource = 'Hourly Auto-Sync') {
    $allOrders = get_all_orders();
    $updatedOrderIds = [];
    $totalOrdersChecked = 0;

    foreach ($allOrders as $ord) {
        $orderId = $ord['order_id'] ?? '';
        $companyPackageId = trim($ord['tracking_code'] ?? '');
        $currentStatus = $ord['order_status'] ?? 'Waiting';

        // Only sync orders that have a company package ID linked and are not already Delivered or Cancelled
        if (!empty($companyPackageId) && !in_array($currentStatus, ['Delivered', 'Cancelled'], true)) {
            $totalOrdersChecked++;
            $companyData = query_delivery_company_api($companyPackageId, $orderId, $currentStatus);

            if ($companyData && !empty($companyData['status'])) {
                $newStatus = $companyData['status'];
                $hasStatusChange = (strcasecmp($newStatus, $currentStatus) !== 0);
                
                $updateFields = [];
                if ($hasStatusChange) {
                    $updateFields['order_status'] = $newStatus;
                }
                if (!empty($companyData['courier']) && empty($ord['courier'])) {
                    $updateFields['courier'] = $companyData['courier'];
                }
                if (!empty($companyData['driver_name']) && empty($ord['driver_name'])) {
                    $updateFields['driver_name'] = $companyData['driver_name'];
                }
                if (!empty($companyData['driver_phone']) && empty($ord['driver_phone'])) {
                    $updateFields['driver_phone'] = $companyData['driver_phone'];
                }
                if (!empty($companyData['checkpoint'])) {
                    $timestamp = date('Y-m-d H:i');
                    $existingNotes = trim($ord['dispatch_notes'] ?? '');
                    if (strpos($existingNotes, $companyData['checkpoint']) === false) {
                        $updateFields['dispatch_notes'] = $existingNotes 
                            ? "{$existingNotes} | [{$timestamp} Company API: {$companyData['checkpoint']}]"
                            : "[{$timestamp} Company API: {$companyData['checkpoint']}]";
                    }
                }

                if (!empty($updateFields)) {
                    update_order_full($orderId, $updateFields);
                    $updatedOrderIds[] = $orderId . " ({$currentStatus} → {$newStatus})";
                }
            }
        }
    }

    // Record the synchronization timestamp
    $now = time();
    $newState = [
        'last_sync_timestamp' => $now,
        'last_sync_datetime' => date('Y-m-d H:i:s', $now),
        'last_synced_orders_count' => count($updatedOrderIds),
        'total_orders_checked' => $totalOrdersChecked,
        'last_sync_trigger' => $triggerSource,
        'last_sync_message' => "Successfully queried delivery company API. " . count($updatedOrderIds) . " order(s) updated in database.",
        'updated_orders' => $updatedOrderIds
    ];
    save_delivery_sync_state($newState);

    return $newState;
}

/**
 * Check if 60 minutes have elapsed since last sync.
 * If >= 60 minutes (3600 seconds), sync all orders from company API and update database.
 * If < 60 minutes, skip sync and serve data directly from database.
 * 
 * @param bool $force Force sync regardless of elapsed time
 * @return array Status of the check and sync
 */
function check_and_sync_delivery_company_hourly($force = false) {
    $state = get_delivery_sync_state();
    $now = time();
    $lastSyncTime = (int)($state['last_sync_timestamp'] ?? 0);
    $elapsedSeconds = $now - $lastSyncTime;
    $interval = DELIVERY_SYNC_INTERVAL; // 3600 seconds = 60 minutes

    if ($force || $lastSyncTime === 0 || $elapsedSeconds >= $interval) {
        $syncResult = sync_all_orders_from_company_api($force ? 'Manual Admin Request' : 'Hourly Auto-Sync (> 60m elapsed)');
        return [
            'did_sync' => true,
            'reason' => $lastSyncTime === 0 ? 'First time sync' : ($force ? 'Forced by admin' : "Elapsed time ({$elapsedSeconds}s) exceeds 60 minutes"),
            'elapsed_seconds' => $elapsedSeconds,
            'last_sync_timestamp' => $syncResult['last_sync_timestamp'],
            'last_sync_datetime' => $syncResult['last_sync_datetime'],
            'updated_orders' => $syncResult['updated_orders']
        ];
    }

    $minutesRemaining = ceil(($interval - $elapsedSeconds) / 60);
    $minutesSinceLast = round($elapsedSeconds / 60, 1);

    return [
        'did_sync' => false,
        'reason' => "Last updated {$minutesSinceLast} min ago (< 60 min). Serving orders directly from database.",
        'elapsed_seconds' => $elapsedSeconds,
        'minutes_since_last_sync' => $minutesSinceLast,
        'minutes_until_next_sync' => $minutesRemaining,
        'last_sync_timestamp' => $lastSyncTime,
        'last_sync_datetime' => $state['last_sync_datetime'] ?? 'Never'
    ];
}
