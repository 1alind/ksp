<?php
/**
 * AURA Luxury Store — Payment Gateways & SDK Directory
 */

require_once __DIR__ . '/../database/db.php';
$settings = get_store_settings();
$gateways = $settings['gateways'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA Payment Gateways & SDK Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-page: #0b0d14;
            --bg-card: #131722;
            --accent: #d4af37;
            --border: rgba(255, 255, 255, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: var(--bg-page); color: var(--text-main); padding: 40px 20px; line-height: 1.6; }
        .container { max-width: 900px; margin: 0 auto; }
        .header { margin-bottom: 32px; text-align: center; }
        .header h1 { font-size: 28px; font-weight: 800; color: #ffffff; margin-bottom: 8px; }
        .header p { color: var(--text-muted); font-size: 14px; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px; margin-bottom: 32px; }
        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; }
        .card h3 { font-size: 18px; font-weight: 700; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; }
        .badge { font-size: 11px; padding: 3px 8px; border-radius: 6px; font-weight: 700; }
        .badge-sim { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }
        .card p { font-size: 13px; color: var(--text-muted); margin-bottom: 16px; }
        .btn { display: inline-block; background: var(--accent); color: #000000; font-weight: 700; font-size: 12.5px; padding: 8px 16px; border-radius: 6px; text-decoration: none; }
        .btn-outline { background: transparent; color: #ffffff; border: 1px solid var(--border); }
        .sim-banner { background: linear-gradient(135deg, rgba(212, 175, 55, 0.15), rgba(15, 23, 42, 0.9)); border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 12px; padding: 24px; margin-bottom: 32px; text-align: center; }
        .sim-banner h2 { font-size: 20px; color: var(--accent); margin-bottom: 8px; }
        .sim-banner p { font-size: 13.5px; color: #e2e8f0; margin-bottom: 16px; }
        code { background: rgba(255,255,255,0.06); padding: 2px 6px; border-radius: 4px; font-size: 12px; color: #f472b6; }
    </style>
</head>
<body>
    <div class="container">
        
        <div class="header">
            <span style="color:var(--accent); font-size:12px; font-weight:800; text-transform:uppercase; letter-spacing:1px;">AURA Luxury E-Commerce</span>
            <h1>Payment Gateways & Native PHP SDKs</h1>
            <p>Modular, pure-PHP payment architecture with universal banking acceptance simulation.</p>
        </div>

        <div class="sim-banner">
            <h2>⚡ Universal Bank Simulator (fake.php)</h2>
            <p>
                All bank API calls route to <code>payment/fake.php</code> when in test/simulation mode. No external bank connections required.
            </p>
            <a href="fake.php?gateway=fib&amount=750000&order_id=ORD-99999" class="btn" target="_blank">Launch Simulator Web Portal →</a>
        </div>

        <div class="grid">
            
            <!-- FIB Bank -->
            <div class="card">
                <h3>First Iraqi Bank (FIB) <span class="badge badge-sim">SDK Active</span></h3>
                <p>OAuth2 authentication, dynamic QR generation, mobile banking scan acceptance.</p>
                <div style="display:flex; gap:8px;">
                    <a href="fake.php?gateway=fib&amount=520000" class="btn" target="_blank">Test FIB Flow</a>
                    <a href="/admin/payments.php" class="btn btn-outline">Configure</a>
                </div>
            </div>

            <!-- ZainCash -->
            <div class="card">
                <h3>ZainCash (زين كاش) <span class="badge badge-sim">SDK Active</span></h3>
                <p>HS256 HMAC JWT signature encoding, transaction redirect, and OTP authorization.</p>
                <div style="display:flex; gap:8px;">
                    <a href="fake.php?gateway=zaincash&amount=310000" class="btn" target="_blank">Test ZainCash</a>
                    <a href="/admin/payments.php" class="btn btn-outline">Configure</a>
                </div>
            </div>

            <!-- FastPay -->
            <div class="card">
                <h3>FastPay Wallet <span class="badge badge-sim">SDK Active</span></h3>
                <p>Merchant store payments, FastPay QR codes, and instant mobile wallet settlement.</p>
                <div style="display:flex; gap:8px;">
                    <a href="fake.php?gateway=fastpay&amount=195000" class="btn" target="_blank">Test FastPay</a>
                    <a href="/admin/payments.php" class="btn btn-outline">Configure</a>
                </div>
            </div>

            <!-- Cash on Delivery -->
            <div class="card">
                <h3>Cash on Delivery (COD) <span class="badge badge-sim">Active</span></h3>
                <p>Doorstep cash collection across Erbil, Sulaymaniyah, Duhok, Baghdad, and all Iraq.</p>
                <a href="../checkout.php" class="btn btn-outline">View Checkout</a>
            </div>

        </div>

        <div style="text-align:center; margin-top:32px;">
            <a href="../index.php" style="color:var(--text-muted); font-size:13px; text-decoration:none;">← Return to AURA Boutique</a>
        </div>

    </div>
</body>
</html>
