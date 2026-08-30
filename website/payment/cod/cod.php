<?php
/**
 * Cash on Delivery (COD) Payment Handler & Delivery Verification
 */

class CodPayment {
    
    public static function processOrder($orderData) {
        return [
            'success' => true,
            'payment_method' => 'Cash on Delivery',
            'payment_status' => 'Pending (Pay on Delivery)',
            'amount_due' => $orderData['total'] ?? 0,
            'currency' => 'IQD',
            'delivery_instructions' => 'Payment will be collected by AURA VIP Courier in cash at time of doorstep handover.'
        ];
    }
}
