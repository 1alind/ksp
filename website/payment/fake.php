<?php
/**
 * AURA Luxury E-Commerce — Universal Payment Simulator Endpoint & Banking Test Portal
 * 
 * Simulates real banking acceptance, QR codes, OTP authorization, and webhook callbacks for:
 * 1. First Iraqi Bank (FIB)
 * 2. ZainCash (زين كاش)
 * 3. FastPay Mobile Wallet (فاست باي)
 * 
 * URL Endpoints:
 * - API Call: fake.php?gateway=fib&action=create_payment
 * - API Call: fake.php?gateway=zaincash&action=init
 * - API Call: fake.php?gateway=fastpay&action=init
 * - Interactive UI: fake.php?gateway=fib&paymentId=FIB-XXXXX&amount=750000&orderId=ORD-12345
 */

// Enable session & CORS
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../database/db.php';

// Detect request format (JSON API vs Interactive Web UI)
$isJsonRequest = (
    (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) ||
    (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
    isset($_GET['format']) && $_GET['format'] === 'json' ||
    isset($_POST['format']) && $_POST['format'] === 'json' ||
    isset($_GET['action']) || isset($_POST['action'])
);

$gateway = strtolower(trim($_GET['gateway'] ?? $_POST['gateway'] ?? 'fib'));
$action = strtolower(trim($_GET['action'] ?? $_POST['action'] ?? 'ui'));

// Read raw JSON body if sent
$rawInput = file_get_contents('php://input');
$jsonData = json_decode($rawInput, true) ?: [];

$amount = floatval($_GET['amount'] ?? $_POST['amount'] ?? $jsonData['amount'] ?? 750000);
$currency = strtoupper(trim($_GET['currency'] ?? $_POST['currency'] ?? $jsonData['currency'] ?? 'IQD'));
$orderId = trim($_GET['order_id'] ?? $_POST['order_id'] ?? $jsonData['order_id'] ?? ('ORD-' . rand(10000, 99999)));
$paymentId = trim($_GET['payment_id'] ?? $_POST['payment_id'] ?? $jsonData['payment_id'] ?? ($gateway . '-' . strtoupper(substr(md5(uniqid()), 0, 8))));
$callbackUrl = trim($_GET['callback_url'] ?? $_POST['callback_url'] ?? $jsonData['callback_url'] ?? '');

// In-memory / session storage for simulated transaction states
if (!isset($_SESSION['simulated_payments'])) {
    $_SESSION['simulated_payments'] = [];
}

// -------------------------------------------------------------------------------------
// 1. API HANDLERS (Simulating Bank Server Responses for SDKs & cURL)
// -------------------------------------------------------------------------------------
if ($isJsonRequest && $action !== 'ui') {
    header('Content-Type: application/json; charset=UTF-8');

    switch ($action) {
        // FIB: OAuth2 Token Generation
        case 'fib_token':
        case 'token':
            echo json_encode([
                'access_token' => 'fib_sim_token_' . bin2hex(random_bytes(20)),
                'token_type' => 'Bearer',
                'expires_in' => 86400,
                'scope' => 'payments:write payments:read accounts:read',
                'status' => 'success',
                'gateway' => 'First Iraqi Bank (Simulated Server)'
            ]);
            exit;

        // FIB: Create Payment & QR
        case 'fib_create':
        case 'create_payment':
            $pid = 'FIB-' . strtoupper(bin2hex(random_bytes(4)));
            $qrData = 'fib://pay?pid=' . $pid . '&amt=' . intval($amount) . '&cur=' . $currency . '&ref=' . urlencode($orderId);
            
            $_SESSION['simulated_payments'][$pid] = [
                'payment_id' => $pid,
                'gateway' => 'fib',
                'amount' => $amount,
                'currency' => $currency,
                'order_id' => $orderId,
                'status' => 'UNPAID',
                'created_at' => time()
            ];

            echo json_encode([
                'success' => true,
                'payment_id' => $pid,
                'qr_code' => $qrData,
                'readable_code' => implode('-', str_split($pid, 4)),
                'amount' => $amount,
                'currency' => $currency,
                'status' => 'UNPAID',
                'simulator_url' => "fake.php?gateway=fib&payment_id=$pid&amount=$amount&order_id=$orderId",
                'valid_until' => date('Y-m-d\TH:i:s\Z', time() + 900)
            ]);
            exit;

        // ZainCash: Initialize Transaction & JWT
        case 'zaincash_init':
        case 'init':
            $txId = 'ZC-' . rand(10000000, 99999999);
            $token = 'zc_jwt_sim_' . bin2hex(random_bytes(24));
            
            $_SESSION['simulated_payments'][$txId] = [
                'payment_id' => $txId,
                'gateway' => 'zaincash',
                'amount' => $amount,
                'currency' => 'IQD',
                'order_id' => $orderId,
                'status' => 'PENDING',
                'created_at' => time()
            ];

            echo json_encode([
                'success' => true,
                'id' => $txId,
                'token' => $token,
                'redirect_url' => "fake.php?gateway=zaincash&payment_id=$txId&amount=$amount&order_id=$orderId",
                'status' => 'pending',
                'gateway' => 'ZainCash (Simulated Server)'
            ]);
            exit;

        // FastPay: Initialize QR & Wallet
        case 'fastpay_init':
            $txId = 'FP-' . rand(100000, 999999);
            $qrData = "fastpay://merchant_pay?store=AURA_LUXURY&amount=$amount&order=$orderId&tx=$txId";
            
            $_SESSION['simulated_payments'][$txId] = [
                'payment_id' => $txId,
                'gateway' => 'fastpay',
                'amount' => $amount,
                'currency' => 'IQD',
                'order_id' => $orderId,
                'status' => 'PENDING',
                'created_at' => time()
            ];

            echo json_encode([
                'success' => true,
                'transaction_id' => $txId,
                'qr_token' => $qrData,
                'simulator_url' => "fake.php?gateway=fastpay&payment_id=$txId&amount=$amount&order_id=$orderId",
                'amount' => $amount,
                'currency' => 'IQD',
                'status' => 'pending'
            ]);
            exit;

        // Check Payment Status (Polling endpoint)
        case 'check_status':
        case 'status':
            $checkId = $paymentId;
            $currentStatus = $_SESSION['simulated_payments'][$checkId]['status'] ?? 'PAID';
            echo json_encode([
                'payment_id' => $checkId,
                'status' => $currentStatus, // 'PAID', 'UNPAID', 'DECLINED'
                'paid_at' => ($currentStatus === 'PAID') ? date('Y-m-d\TH:i:s\Z') : null,
                'amount' => $amount,
                'currency' => $currency
            ]);
            exit;

        // Confirm & Accept Payment (Triggered by client app)
        case 'confirm_payment':
        case 'accept':
            $checkId = $paymentId;
            if (isset($_SESSION['simulated_payments'][$checkId])) {
                $_SESSION['simulated_payments'][$checkId]['status'] = 'PAID';
            }
            echo json_encode([
                'success' => true,
                'message' => 'Simulated payment approved and confirmed.',
                'payment_id' => $checkId,
                'status' => 'PAID',
                'receipt_number' => 'SIM-REC-' . strtoupper(substr(md5(uniqid()), 0, 10)),
                'verified_at' => date('Y-m-d H:i:s')
            ]);
            exit;

        // Fallback default
        default:
            echo json_encode([
                'simulator' => 'AURA Universal Payment Simulator',
                'gateway' => $gateway,
                'action' => $action,
                'status' => 'active',
                'timestamp' => time()
            ]);
            exit;
    }
}

// -------------------------------------------------------------------------------------
// 2. INTERACTIVE BANKING SIMULATOR WEB PORTAL
// -------------------------------------------------------------------------------------
$gatewayTitles = [
    'fib' => 'First Iraqi Bank (FIB) — Bank Mobile Authorization',
    'zaincash' => 'ZainCash (زين كاش) — Electronic Wallet Gateway',
    'fastpay' => 'FastPay (فاست باي) — Instant Mobile Wallet Pay'
];

$gatewayColors = [
    'fib' => ['primary' => '#d4af37', 'bg' => '#0a192f', 'badge' => '#d4af37'],
    'zaincash' => ['primary' => '#ec4899', 'bg' => '#1f132b', 'badge' => '#f472b6'],
    'fastpay' => ['primary' => '#ffc800', 'bg' => '#111827', 'badge' => '#eab308']
];

$currentTheme = $gatewayColors[$gateway] ?? $gatewayColors['fib'];
$portalTitle = $gatewayTitles[$gateway] ?? 'AURA Multi-Bank Payment Simulator';

$submittedAction = $_POST['sim_decision'] ?? '';
$simulationResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($submittedAction)) {
    if ($submittedAction === 'accept') {
        $_SESSION['simulated_payments'][$paymentId]['status'] = 'PAID';
        $simulationResult = [
            'type' => 'success',
            'title' => 'Payment Approved & Verified! (200 OK)',
            'desc' => "Transaction $paymentId for " . number_format($amount) . " IQD was accepted by $portalTitle. Authorization code: AUTH-" . rand(100000, 999999)
        ];
    } elseif ($submittedAction === 'decline') {
        $_SESSION['simulated_payments'][$paymentId]['status'] = 'DECLINED';
        $simulationResult = [
            'type' => 'error',
            'title' => 'Payment Declined by Bank (402)',
            'desc' => 'Insufficient wallet balance or transaction rejected by client pin security.'
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($portalTitle); ?> | Universal Simulator</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        :root {
            --bank-bg: <?php echo $currentTheme['bg']; ?>;
            --bank-accent: <?php echo $currentTheme['primary']; ?>;
            --card-bg: #141824;
            --border-color: rgba(255, 255, 255, 0.12);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        body {
            background-color: #0b0d14;
            background-image: radial-gradient(circle at 50% 20%, rgba(212, 175, 55, 0.08) 0%, transparent 60%);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .sim-container {
            width: 100%;
            max-width: 520px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.7);
            overflow: hidden;
        }

        .sim-header {
            background: var(--bank-bg);
            padding: 24px;
            border-bottom: 1px solid var(--border-color);
            text-align: center;
            position: relative;
        }

        .sim-mode-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.12);
            color: #38bdf8;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }

        .sim-title {
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .sim-subtitle {
            font-size: 12.5px;
            color: var(--text-dim);
        }

        .sim-body {
            padding: 24px;
        }

        .amount-highlight {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            margin-bottom: 20px;
        }

        .amount-num {
            font-size: 28px;
            font-weight: 800;
            color: var(--bank-accent);
            letter-spacing: -0.5px;
        }

        .amount-meta {
            font-size: 12px;
            color: var(--text-dim);
            margin-top: 4px;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .info-table td {
            padding: 8px 0;
            border-bottom: 1px dashed rgba(255, 255, 255, 0.08);
        }

        .info-table td:last-child {
            text-align: right;
            font-weight: 600;
            color: #f1f5f9;
        }

        .btn-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-sim {
            display: block;
            width: 100%;
            padding: 14px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: all 0.2s ease;
            border: none;
        }

        .btn-accept {
            background: #10b981;
            color: #ffffff;
        }

        .btn-accept:hover {
            background: #059669;
            transform: translateY(-1px);
        }

        .btn-decline {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .btn-decline:hover {
            background: rgba(239, 68, 68, 0.25);
        }

        .btn-back {
            background: transparent;
            color: var(--text-dim);
            font-size: 12.5px;
        }

        .btn-back:hover {
            color: #ffffff;
        }

        .result-box {
            padding: 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 13px;
        }

        .result-box.success {
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.4);
            color: #34d399;
        }

        .result-box.error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #f87171;
        }

        .gateway-selector {
            display: flex;
            gap: 6px;
            margin-bottom: 16px;
            background: rgba(255, 255, 255, 0.04);
            padding: 4px;
            border-radius: 8px;
        }

        .gateway-tab {
            flex: 1;
            text-align: center;
            padding: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--text-dim);
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .gateway-tab.active {
            background: var(--bank-bg);
            color: #ffffff;
            border: 1px solid var(--border-color);
        }

        .sim-footer {
            padding: 16px 24px;
            background: rgba(0, 0, 0, 0.2);
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 11.5px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>

    <div class="sim-container">
        
        <!-- Header -->
        <div class="sim-header">
            <span class="sim-mode-badge">⚡ Universal Bank Simulator</span>
            <h1 class="sim-title"><?php echo htmlspecialchars($portalTitle); ?></h1>
            <p class="sim-subtitle">Simulated banking verification server for local and decoupled testing</p>
        </div>

        <!-- Body -->
        <div class="sim-body">

            <!-- Switch Gateway Tabs -->
            <div class="gateway-selector">
                <a href="fake.php?gateway=fib&payment_id=<?php echo urlencode($paymentId); ?>&amount=<?php echo urlencode($amount); ?>&order_id=<?php echo urlencode($orderId); ?>" class="gateway-tab <?php echo $gateway === 'fib' ? 'active' : ''; ?>">FIB Bank</a>
                <a href="fake.php?gateway=zaincash&payment_id=<?php echo urlencode($paymentId); ?>&amount=<?php echo urlencode($amount); ?>&order_id=<?php echo urlencode($orderId); ?>" class="gateway-tab <?php echo $gateway === 'zaincash' ? 'active' : ''; ?>">ZainCash</a>
                <a href="fake.php?gateway=fastpay&payment_id=<?php echo urlencode($paymentId); ?>&amount=<?php echo urlencode($amount); ?>&order_id=<?php echo urlencode($orderId); ?>" class="gateway-tab <?php echo $gateway === 'fastpay' ? 'active' : ''; ?>">FastPay</a>
            </div>

            <?php if ($simulationResult): ?>
                <div class="result-box <?php echo $simulationResult['type']; ?>">
                    <strong><?php echo htmlspecialchars($simulationResult['title']); ?></strong>
                    <p style="margin-top:4px;"><?php echo htmlspecialchars($simulationResult['desc']); ?></p>
                </div>
            <?php endif; ?>

            <!-- Amount Showcase -->
            <div class="amount-highlight">
                <span class="amount-meta">Amount Payable</span>
                <div class="amount-num"><?php echo number_format($amount); ?> <?php echo htmlspecialchars($currency); ?></div>
                <span class="amount-meta">Recipient: AURA Luxury Store VIP Gateway</span>
            </div>

            <!-- Transaction Information -->
            <table class="info-table">
                <tr>
                    <td style="color:var(--text-dim);">Order Reference</td>
                    <td><code><?php echo htmlspecialchars($orderId); ?></code></td>
                </tr>
                <tr>
                    <td style="color:var(--text-dim);">Simulated Payment ID</td>
                    <td><code><?php echo htmlspecialchars($paymentId); ?></code></td>
                </tr>
                <tr>
                    <td style="color:var(--text-dim);">Gateway Method</td>
                    <td><?php echo strtoupper(htmlspecialchars($gateway)); ?> Mobile Gateway</td>
                </tr>
                <tr>
                    <td style="color:var(--text-dim);">Simulated Protocol</td>
                    <td><?php echo $gateway === 'zaincash' ? 'HS256 JWT Token' : ($gateway === 'fib' ? 'OAuth2 Bearer' : 'FastPay QR IPN'); ?></td>
                </tr>
                <tr>
                    <td style="color:var(--text-dim);">Simulated Time</td>
                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                </tr>
            </table>

            <!-- Decision Form -->
            <form action="fake.php?gateway=<?php echo urlencode($gateway); ?>&payment_id=<?php echo urlencode($paymentId); ?>&amount=<?php echo urlencode($amount); ?>&order_id=<?php echo urlencode($orderId); ?>" method="POST">
                <div class="btn-group">
                    <button type="submit" name="sim_decision" value="accept" class="btn-sim btn-accept">
                        ✓ Accept & Approve Payment (Simulate Success)
                    </button>
                    <button type="submit" name="sim_decision" value="decline" class="btn-sim btn-decline">
                        ✕ Decline / Cancel Payment (Simulate Failure)
                    </button>
                    <a href="../checkout.php" class="btn-sim btn-back">
                        ← Return to Checkout
                    </a>
                </div>
            </form>

        </div>

        <!-- Footer -->
        <div class="sim-footer">
            Aura Luxury Store &bull; Pure PHP Payment Simulator &bull; Endpoint: <code>payment/fake.php</code>
        </div>

    </div>

</body>
</html>
