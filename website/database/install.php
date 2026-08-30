<?php
/**
 * Aura Store - Interactive Database Setup & Diagnostic Wizard
 * Run this by opening /database/install.php in your browser on InfinityFree
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/init_db.php';

$message = '';
$messageType = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'seed') {
        $res = auto_init_database(true);
        if (($res['status'] ?? '') === 'success') {
            $message = $res['message'] ?? 'Database successfully initialized and seeded!';
            $messageType = 'success';
        } else {
            $message = $res['message'] ?? 'Error initializing database.';
            $messageType = 'danger';
        }
    }
}

// Test PDO connection
$pdoError = null;
$pdo = null;
try {
    $dsn = "mysql:host=" . MYSQL_HOST . ";port=" . MYSQL_PORT . ";dbname=" . MYSQL_DBNAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ];
    $pdo = new PDO($dsn, MYSQL_USER, MYSQL_PASSWORD, $options);
} catch (Exception $e) {
    $pdoError = $e->getMessage();
}

$tables = [];
$productCount = 0;
if ($pdo) {
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array('products', $tables)) {
            $cStmt = $pdo->query("SELECT COUNT(*) as total FROM `products`");
            $productCount = (int)$cStmt->fetch()['total'];
        }
    } catch (Exception $e) {
        $pdoError = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA Database Setup & Diagnostic Wizard</title>
    <link href="https://fonts.googleapis.com/css2?family=Alexandria:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #09090b;
            --surface: #18181b;
            --border: #27272a;
            --text: #f4f4f5;
            --muted: #a1a1aa;
            --accent: #d97706;
            --accent-hover: #b45309;
            --success: #10b981;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .installer-card {
            background-color: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            width: 100%;
            max-width: 680px;
            padding: 36px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .header {
            text-align: center;
            margin-bottom: 28px;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            border-radius: 999px;
            background: rgba(217, 119, 6, 0.15);
            color: var(--accent);
            margin-bottom: 12px;
        }
        h1 {
            font-family: 'Alexandria', sans-serif;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
        }
        p.subtitle {
            color: var(--muted);
            font-size: 14px;
        }
        .status-box {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.5;
        }
        .status-box.success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: var(--success);
        }
        .status-box.danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: var(--danger);
        }
        .status-box.info {
            background: rgba(217, 119, 6, 0.1);
            border: 1px solid rgba(217, 119, 6, 0.3);
            color: var(--accent);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            margin-bottom: 28px;
        }
        .info-item {
            background: #121214;
            padding: 14px 16px;
            border-radius: 10px;
            border: 1px solid var(--border);
        }
        .info-label {
            font-size: 12px;
            color: var(--muted);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .info-val {
            font-size: 15px;
            font-weight: 600;
            word-break: break-all;
        }
        .val-online { color: var(--success); }
        .val-offline { color: var(--danger); }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px;
            background: var(--accent);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s ease;
            text-align: center;
            text-decoration: none;
        }
        .btn-primary:hover {
            background: var(--accent-hover);
        }
        .btn-secondary {
            display: block;
            width: 100%;
            padding: 12px;
            background: transparent;
            color: var(--muted);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 12px;
            transition: 0.2s ease;
        }
        .btn-secondary:hover {
            background: #27272a;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="header">
            <span class="badge">MySQL Provisioner</span>
            <h1>AURA Database Diagnostics</h1>
            <p class="subtitle">Real-time status check for your InfinityFree database</p>
        </div>

        <?php if ($message): ?>
            <div class="status-box <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($pdoError): ?>
            <div class="status-box danger">
                <strong>MySQL Connection Failed:</strong><br>
                <?= htmlspecialchars($pdoError) ?>
                <br><br>
                <em>Check that your MySQL database credentials in <code>website/database/db.php</code> match your InfinityFree cPanel exactly.</em>
            </div>
        <?php endif; ?>

        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">MySQL Host</div>
                <div class="info-val"><?= htmlspecialchars(MYSQL_HOST) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Database Name</div>
                <div class="info-val"><?= htmlspecialchars(MYSQL_DBNAME) ?></div>
            </div>
            <div class="info-item">
                <div class="info-label">Connection Status</div>
                <div class="info-val <?= $pdo ? 'val-online' : 'val-offline' ?>">
                    <?= $pdo ? '● Connected & Ready' : '○ Offline / Error' ?>
                </div>
            </div>
            <div class="info-item">
                <div class="info-label">Products in Database</div>
                <div class="info-val <?= $productCount > 0 ? 'val-online' : '' ?>">
                    <?= $productCount ?> Products
                </div>
            </div>
        </div>

        <?php if ($pdo): ?>
            <form method="POST">
                <input type="hidden" name="action" value="seed">
                <button type="submit" class="btn-primary">
                    ⚡ <?= $productCount === 0 ? 'Create Tables & Seed 12 Products Now' : 'Re-Seed & Refresh Example Products' ?>
                </button>
            </form>
        <?php endif; ?>

        <a href="../index.php" class="btn-secondary">← Back to Storefront</a>
    </div>
</body>
</html>
