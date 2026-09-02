<?php
$pageTitle = 'Payment Gateways & Currency Suite | AURA Luxury Admin';
$adminActive = 'payments';
$settingsDb = json_decode(file_get_contents(__DIR__ . '/../database/settings.json'), true);
$ordersDb = json_decode(file_get_contents(__DIR__ . '/../database/orders.json'), true);
$ordersList = $ordersDb['orders'] ?? [];
$productsDb = json_decode(file_get_contents(__DIR__ . '/../database/products.json'), true);
$productsList = $productsDb['products'] ?? [];
$usersDb = json_decode(file_get_contents(__DIR__ . '/../database/users.json'), true);
$usersList = $usersDb['users'] ?? [];
$inquiriesDb = json_decode(file_get_contents(__DIR__ . '/../database/inquiries.json'), true);
$inquiriesList = $inquiriesDb['inquiries'] ?? [];

$fib = $settingsDb['gateways']['fib'] ?? [];
$zain = $settingsDb['gateways']['zaincash'] ?? [];
$fastpay = $settingsDb['gateways']['fastpay'] ?? [];
$cod = $settingsDb['gateways']['cod'] ?? [];
$rate = $settingsDb['exchange_rate_usd_to_iqd'] ?? 1320;

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Payment Gateways & Currency</h1>
            <p class="page-banner-subtitle">
                Configure Iraqi digital banking APIs (FIB & ZainCash), FastPay, COD, and official USD/IQD conversion rates.
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <!-- Architecture & Simulator Banner -->
        <div style="background:linear-gradient(135deg, rgba(212,175,55,0.12), rgba(15,23,42,0.9)); border:1px solid rgba(212,175,55,0.35); border-radius:12px; padding:18px 22px; margin-bottom:28px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:14px;">
            <div>
                <span style="background:rgba(56,189,248,0.15); color:#38bdf8; border:1px solid rgba(56,189,248,0.3); font-size:11px; font-weight:800; padding:2px 8px; border-radius:4px; text-transform:uppercase;">⚡ Native PHP SDK Architecture</span>
                <h3 style="color:#ffffff; font-size:17px; font-weight:800; margin:6px 0 4px;">Modular Payment Folder: <code>/payment/</code> & Universal Bank Simulator</h3>
                <p class="text-muted" style="margin:0; font-size:12.5px;">All banking SDKs are organized in <code>/payment/fib/</code>, <code>/payment/zaincash/</code>, <code>/payment/fastpay/</code>, <code>/payment/cod/</code>, and simulated via <code>/payment/fake.php</code>.</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                <a href="/payment/fake.php?gateway=fib&amount=750000" target="_blank" class="btn btn-primary btn-luxury btn-sm">
                    ⚡ Launch fake.php Simulator
                </a>
                <a href="/payment/index.php" target="_blank" class="btn btn-outline btn-sm">
                    📁 View Payment Directory
                </a>
            </div>
        </div>

        <form action="/admin/payments.php" method="POST" id="gatewaySettingsForm">
            <input type="hidden" name="save_gateway_settings" value="1">

            <!-- Currency & Exchange Rate Setting -->
            <div class="admin-form-card mb-24">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:16px;">💱 Official Currency & Exchange Rate</h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Set the fixed store conversion rate from USD to Iraqi Dinar (IQD).</p>
                    </div>
                    <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:700;">Base: IQD</span>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label>1 USD to Iraqi Dinar (IQD) Rate <span class="text-danger">*</span></label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="number" name="exchange_rate_usd_to_iqd" value="<?php echo htmlspecialchars($rate); ?>" class="form-control" style="font-size:16px; font-weight:700;" placeholder="1320" required>
                            <span style="font-weight:700; color:var(--accent-gold); white-space:nowrap;">IQD per $1.00</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Quick Preset Rates in Iraq</label>
                        <div style="display:flex; gap:8px; margin-top:6px;">
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.querySelector('[name=exchange_rate_usd_to_iqd]').value=1320">Official (1,320)</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.querySelector('[name=exchange_rate_usd_to_iqd]').value=1450">Commercial (1,450)</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.querySelector('[name=exchange_rate_usd_to_iqd]').value=1500">Market (1,500)</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gateway-cards-grid" style="display:grid; grid-template-columns:1fr; gap:24px;">
                
                <!-- 1. FIB Gateway Card -->
                <div class="gateway-card active-gateway" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:24px; box-shadow:var(--shadow-sm);">
                    <div class="gateway-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div class="gateway-brand" style="display:flex; align-items:center; gap:12px;">
                            <span class="gateway-icon-badge" style="font-size:28px;">🏦</span>
                            <div>
                                <h3 style="margin:0; font-size:18px;">First Iraqi Bank (FIB API Suite)</h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Direct banking, dynamic QR scans, and Iraqi Dinar transactions</p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="fib_enabled" value="1" <?php echo (!empty($fib['enabled']) || !isset($fib['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;">Enable FIB</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label>Environment Mode</label>
                            <select name="fib_mode" class="form-control">
                                <option value="test" <?php echo ($fib['mode'] ?? '') === 'test' ? 'selected' : ''; ?>>Sandbox / Test (api.test.fib.iq)</option>
                                <option value="prod" <?php echo ($fib['mode'] ?? '') === 'prod' ? 'selected' : ''; ?>>Production Live (api.fib.iq)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account IBAN (Kurdistan/Iraq)</label>
                            <input type="text" name="fib_account_iban" value="<?php echo htmlspecialchars($fib['account_iban'] ?? 'IQ44FIBQ0000001009283741'); ?>" class="form-control" placeholder="IQ44FIBQ...">
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label>FIB Client ID / App Key <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="text" name="fib_client_id" id="fibClientIdInput" value="<?php echo htmlspecialchars($fib['client_id'] ?? 'fib_live_client_89420ab92c'); ?>" required class="form-control" placeholder="fib_live_client_...">
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibClientIdInput', 'FIB Client ID copied')">📋 Copy</button>
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label>FIB Client Secret Key <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="password" name="fib_client_secret" id="fibSecretInput" value="<?php echo htmlspecialchars($fib['client_secret'] ?? 'fib_sec_9941a87b32f9104c99a0'); ?>" required class="form-control" placeholder="fib_sec_...">
                            <button type="button" class="btn btn-outline btn-xs" onclick="togglePasswordVisibility('fibSecretInput')">👁️</button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibSecretInput', 'FIB Secret copied')">📋</button>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label>Account Holder Name</label>
                            <input type="text" name="fib_account_holder" value="<?php echo htmlspecialchars($fib['account_holder'] ?? 'AURA LUXURY TRADING LTD'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Webhook / Callback URL</label>
                            <input type="url" name="fib_callback_url" value="<?php echo htmlspecialchars($fib['callback_url'] ?? 'https://aurastore.iq/api/fib/callback'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group mb-20">
                        <label>Active Bearer Access Token (OAuth2)</label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="text" name="fib_access_token" id="fibAccessTokenInput" value="<?php echo htmlspecialchars($fib['access_token'] ?? 'fib_bearer_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2F1dGguZmliLmlxIiwic3ViIjoiZmliX2NsaWVudF9saXZlIn0.sig_live'); ?>" class="form-control" style="font-family:monospace; font-size:12px;">
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibAccessTokenInput', 'Bearer Token copied')">📋 Copy</button>
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('fib')">
                                ⚡ Test FIB Connection & Ping
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.generateFibToken()" style="color:var(--accent-gold); border-color:var(--accent-gold);">
                                🔑 Generate Dynamic Token
                            </button>
                        </div>
                        <span class="text-muted" style="font-size:11.5px;">API v1 OAuth2 Bearer</span>
                    </div>
                </div>

                <!-- 2. ZainCash Gateway Card -->
                <div class="gateway-card active-gateway" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:24px; box-shadow:var(--shadow-sm);">
                    <div class="gateway-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div class="gateway-brand" style="display:flex; align-items:center; gap:12px;">
                            <span class="gateway-icon-badge" style="font-size:28px;">📱</span>
                            <div>
                                <h3 style="margin:0; font-size:18px;">ZainCash (زين كاش API Suite)</h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Iraq's premier mobile wallet & HMAC-SHA256 JWT authorization</p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="zaincash_enabled" value="1" <?php echo (!empty($zain['enabled']) || !isset($zain['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;">Enable ZainCash</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label>Merchant MSISDN (Phone Number) <span class="text-danger">*</span></label>
                            <input type="text" name="zaincash_msisdn" value="<?php echo htmlspecialchars($zain['msisdn'] ?? '9647835077893'); ?>" class="form-control" placeholder="96478...">
                        </div>
                        <div class="form-group">
                            <label>Merchant ID</label>
                            <input type="text" name="zaincash_merchant_id" value="<?php echo htmlspecialchars($zain['merchant_id'] ?? '5ff65fb168283f6554c8d60a'); ?>" class="form-control" placeholder="5ff6...">
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label>Merchant Secret Key (HMAC-SHA256) <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="password" name="zaincash_secret" id="zainSecretInput" value="<?php echo htmlspecialchars($zain['secret'] ?? '$2y$10$hBbAZo2GfWNDbuhg9Yeg.uSUFcUWuZ3SLWETSnM3/r5cvG7NTac6q'); ?>" class="form-control" style="font-family:monospace;">
                            <button type="button" class="btn btn-outline btn-xs" onclick="togglePasswordVisibility('zainSecretInput')">👁️</button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('zainSecretInput', 'ZainCash Secret copied')">📋</button>
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('zaincash')">
                            ⚡ Test ZainCash Connection & Ping
                        </button>
                        <span class="text-muted" style="font-size:11.5px;">JWT HS256 Tokenization</span>
                    </div>
                </div>

                <!-- 3. FastPay Gateway Card -->
                <div class="gateway-card active-gateway" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:24px; box-shadow:var(--shadow-sm);">
                    <div class="gateway-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div class="gateway-brand" style="display:flex; align-items:center; gap:12px;">
                            <span class="gateway-icon-badge" style="font-size:28px;">💳</span>
                            <div>
                                <h3 style="margin:0; font-size:18px;">FastPay Kurdistan (فاست باى)</h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Direct digital wallet payments throughout Erbil, Sulaymaniyah, and Duhok</p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="fastpay_enabled" value="1" <?php echo (!empty($fastpay['enabled']) || !isset($fastpay['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;">Enable FastPay</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label>Merchant Store ID</label>
                            <input type="text" name="fastpay_store_id" value="<?php echo htmlspecialchars($fastpay['store_id'] ?? 'FASTPAY_AURA_992'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Store Password / Secret</label>
                            <input type="password" name="fastpay_store_password" id="fastpayPassInput" value="<?php echo htmlspecialchars($fastpay['store_password'] ?? 'aura_fast_secret_pass'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('fastpay')">
                            ⚡ Test FastPay Connection & Ping
                        </button>
                        <span class="text-muted" style="font-size:11.5px;">Kurdistan Regional API</span>
                    </div>
                </div>

                <!-- 4. Cash On Delivery (COD) -->
                <div class="gateway-card" style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:24px; box-shadow:var(--shadow-sm);">
                    <div class="gateway-header" style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
                        <div class="gateway-brand" style="display:flex; align-items:center; gap:12px;">
                            <span class="gateway-icon-badge" style="font-size:28px;">💵</span>
                            <div>
                                <h3 style="margin:0; font-size:18px;">Cash On Delivery (الدفع عند الاستلام)</h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Client settles directly in Iraqi Dinar (IQD) upon inspection of luxury packaging</p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="cod_enabled" value="1" <?php echo (!empty($cod['enabled']) || !isset($cod['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;">Enable COD</span>
                            </label>
                        </div>
                    </div>

                    <p class="text-muted" style="font-size:13px; margin:0 0 16px;">
                        Available across all 18 Iraqi Governorates with white-glove driver delivery. Driver provides printed tax invoice.
                    </p>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:12px 32px; font-size:15px;">
                    💾 Save All Payment Gateways & Rates
                </button>
            </div>
        </form>
    </div>
</section>

<script>
function togglePasswordVisibility(inputId) {
    const el = document.getElementById(inputId);
    if (el) el.type = el.type === 'password' ? 'text' : 'password';
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
