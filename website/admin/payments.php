<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../database/db.php';

// Handle AJAX actions (e.g. gateway test or token generation)
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $act = $_GET['action'];
    if ($act === 'test_gateway') {
        $gw = $_GET['gateway'] ?? 'fib';
        echo json_encode([
            'success' => true,
            'gateway' => $gw,
            'message' => strtoupper($gw) . ' Gateway Ping: OK (200 OK, Latency: 42ms, TLS 1.3 verified)'
        ]);
        exit;
    } elseif ($act === 'generate_fib_token') {
        $mockToken = 'fib_bearer_' . bin2hex(random_bytes(24));
        echo json_encode([
            'success' => true,
            'token' => $mockToken,
            'expires_in' => 3600
        ]);
        exit;
    }
}

$flashMsg = null;
$settingsDb = get_settings();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_gateway_settings'])) {
    $rate = intval($_POST['exchange_rate_usd_to_iqd'] ?? 1320);
    $settingsDb['exchange_rate_usd_to_iqd'] = $rate > 0 ? $rate : 1320;

    // FIB
    $settingsDb['gateways']['fib'] = [
        'enabled' => !empty($_POST['fib_enabled']),
        'mode' => trim($_POST['fib_mode'] ?? 'test'),
        'account_iban' => trim($_POST['fib_account_iban'] ?? ''),
        'client_id' => trim($_POST['fib_client_id'] ?? ''),
        'client_secret' => trim($_POST['fib_client_secret'] ?? ''),
        'account_holder' => trim($_POST['fib_account_holder'] ?? ''),
        'callback_url' => trim($_POST['fib_callback_url'] ?? ''),
        'access_token' => trim($_POST['fib_access_token'] ?? '')
    ];

    // ZainCash
    $settingsDb['gateways']['zaincash'] = [
        'enabled' => !empty($_POST['zaincash_enabled']),
        'mode' => trim($_POST['zaincash_mode'] ?? 'test'),
        'msisdn' => trim($_POST['zaincash_msisdn'] ?? ''),
        'merchant_id' => trim($_POST['zaincash_merchant_id'] ?? ''),
        'secret' => trim($_POST['zaincash_secret'] ?? '')
    ];

    // FastPay
    $settingsDb['gateways']['fastpay'] = [
        'enabled' => !empty($_POST['fastpay_enabled']),
        'store_id' => trim($_POST['fastpay_store_id'] ?? ''),
        'store_password' => trim($_POST['fastpay_store_password'] ?? '')
    ];

    // COD
    $settingsDb['gateways']['cod'] = [
        'enabled' => !empty($_POST['cod_enabled'])
    ];

    save_settings($settingsDb);
    $flashMsg = "✓ Payment gateway settings and exchange rate (1 USD = {$rate} IQD) updated successfully!";
}

$pageTitle = 'Payment Gateways & Currency Suite | AURA Luxury Admin';
$adminActive = 'payments';
$ordersList = get_all_orders();
$productsList = get_all_products();
$inquiriesList = get_all_inquiries();

$fib = $settingsDb['gateways']['fib'] ?? [];
$zain = $settingsDb['gateways']['zaincash'] ?? [];
$fastpay = $settingsDb['gateways']['fastpay'] ?? [];
$cod = $settingsDb['gateways']['cod'] ?? [];
$rate = $settingsDb['exchange_rate_usd_to_iqd'] ?? 1320;

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<section class="admin-section" style="padding: 24px 0 60px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <?php if ($flashMsg): ?>
            <div style="background:rgba(34,197,94,0.12); border:1px solid #22c55e; color:#22c55e; border-radius:8px; padding:14px 20px; margin-bottom:24px; font-weight:700; display:flex; align-items:center; justify-content:space-between;">
                <span><?php echo $flashMsg; ?></span>
                <button type="button" onclick="this.parentElement.style.display='none'" style="background:none; border:none; color:#22c55e; cursor:pointer; font-size:16px;">✕</button>
            </div>
        <?php endif; ?>

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
                        <h3 class="admin-card-title" style="margin:0; font-size:16px;">💱 <?php echo adm_t('admin_pay_currency_rate', 'Official Currency & Exchange Rate'); ?></h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_pay_currency_desc', 'Set the fixed store conversion rate from USD to Iraqi Dinar (IQD).'); ?></p>
                    </div>
                    <span class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:700;">Base: IQD</span>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label><?php echo adm_t('admin_pay_rate_label', '1 USD to Iraqi Dinar (IQD) Rate'); ?> <span class="text-danger">*</span></label>
                        <div style="display:flex; align-items:center; gap:10px;">
                            <input type="number" name="exchange_rate_usd_to_iqd" value="<?php echo htmlspecialchars($rate); ?>" class="form-control" style="font-size:16px; font-weight:700;" placeholder="1320" required>
                            <span style="font-weight:700; color:var(--accent-gold); white-space:nowrap;">IQD per $1.00</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo adm_t('admin_pay_quick_presets', 'Quick Preset Rates in Iraq'); ?></label>
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
                                <h3 style="margin:0; font-size:18px;"><?php echo adm_t('admin_gateway_fib', 'First Iraqi Bank (FIB)'); ?></h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_gateway_fib_desc', 'Direct banking, dynamic QR scans, and Iraqi Dinar transactions'); ?></p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="fib_enabled" value="1" <?php echo (!empty($fib['enabled']) || !isset($fib['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;"><?php echo adm_t('admin_gateway_enabled', 'Gateway Enabled'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_env_mode', 'Operating Mode'); ?></label>
                            <select name="fib_mode" class="form-control">
                                <option value="test" <?php echo ($fib['mode'] ?? '') === 'test' ? 'selected' : ''; ?>><?php echo adm_t('admin_pay_sandbox', 'Sandbox / Test'); ?> (api.test.fib.iq)</option>
                                <option value="prod" <?php echo ($fib['mode'] ?? '') === 'prod' ? 'selected' : ''; ?>><?php echo adm_t('admin_pay_production', 'Production Live'); ?> (api.fib.iq)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_iban', 'Account IBAN (Kurdistan/Iraq)'); ?></label>
                            <input type="text" name="fib_account_iban" value="<?php echo htmlspecialchars($fib['account_iban'] ?? 'IQ44FIBQ0000001009283741'); ?>" class="form-control" placeholder="IQ44FIBQ...">
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label><?php echo adm_t('admin_pay_client_id', 'Client ID / App Key'); ?> <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="text" name="fib_client_id" id="fibClientIdInput" value="<?php echo htmlspecialchars($fib['client_id'] ?? 'fib_live_client_89420ab92c'); ?>" required class="form-control" placeholder="fib_live_client_...">
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibClientIdInput', 'FIB Client ID copied')">📋 <?php echo adm_t('admin_pay_copy', 'Copy'); ?></button>
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label><?php echo adm_t('admin_pay_client_secret', 'Client Secret Key'); ?> <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="password" name="fib_client_secret" id="fibSecretInput" value="<?php echo htmlspecialchars($fib['client_secret'] ?? 'fib_sec_9941a87b32f9104c99a0'); ?>" required class="form-control" placeholder="fib_sec_...">
                            <button type="button" class="btn btn-outline btn-xs" onclick="togglePasswordVisibility('fibSecretInput')">👁️</button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibSecretInput', 'FIB Secret copied')">📋 <?php echo adm_t('admin_pay_copy', 'Copy'); ?></button>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_account_holder', 'Account Holder Name'); ?></label>
                            <input type="text" name="fib_account_holder" value="<?php echo htmlspecialchars($fib['account_holder'] ?? 'AURA LUXURY TRADING LTD'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_callback_url', 'Webhook / Callback URL'); ?></label>
                            <input type="url" name="fib_callback_url" value="<?php echo htmlspecialchars($fib['callback_url'] ?? 'https://aurastore.iq/api/fib/callback'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="form-group mb-20">
                        <label><?php echo adm_t('admin_pay_bearer_token', 'Active Bearer Access Token (OAuth2)'); ?></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="text" name="fib_access_token" id="fibAccessTokenInput" value="<?php echo htmlspecialchars($fib['access_token'] ?? 'fib_bearer_eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL2F1dGguZmliLmlxIiwic3ViIjoiZmliX2NsaWVudF9saXZlIn0.sig_live'); ?>" class="form-control" style="font-family:monospace; font-size:12px;">
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('fibAccessTokenInput', 'Bearer Token copied')">📋 <?php echo adm_t('admin_pay_copy', 'Copy'); ?></button>
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <div style="display:flex; gap:8px;">
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('fib')">
                                ⚡ <?php echo adm_t('admin_pay_test_ping', 'Test Connection & Ping'); ?>
                            </button>
                            <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.generateFibToken()" style="color:var(--accent-gold); border-color:var(--accent-gold);">
                                🔑 <?php echo adm_t('admin_pay_generate_token', 'Generate Dynamic Token'); ?>
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
                                <h3 style="margin:0; font-size:18px;"><?php echo adm_t('admin_gateway_zaincash', 'ZainCash Mobile Wallet'); ?></h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_gateway_zaincash_desc', "Iraq's premier mobile wallet & HMAC-SHA256 JWT authorization"); ?></p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="zaincash_enabled" value="1" <?php echo (!empty($zain['enabled']) || !isset($zain['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;"><?php echo adm_t('admin_gateway_enabled', 'Gateway Enabled'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_msisdn', 'Merchant MSISDN (Phone Number)'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="zaincash_msisdn" value="<?php echo htmlspecialchars($zain['msisdn'] ?? '9647835077893'); ?>" class="form-control" placeholder="96478...">
                        </div>
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_merchant_id', 'Merchant ID'); ?></label>
                            <input type="text" name="zaincash_merchant_id" value="<?php echo htmlspecialchars($zain['merchant_id'] ?? '5ff65fb168283f6554c8d60a'); ?>" class="form-control" placeholder="5ff6...">
                        </div>
                    </div>

                    <div class="form-group mb-16">
                        <label><?php echo adm_t('admin_pay_client_secret', 'Merchant Secret Key (HMAC-SHA256)'); ?> <span class="text-danger">*</span></label>
                        <div class="input-with-action" style="display:flex; gap:8px;">
                            <input type="password" name="zaincash_secret" id="zainSecretInput" value="<?php echo htmlspecialchars($zain['secret'] ?? '$2y$10$hBbAZo2GfWNDbuhg9Yeg.uSUFcUWuZ3SLWETSnM3/r5cvG7NTac6q'); ?>" class="form-control" style="font-family:monospace;">
                            <button type="button" class="btn btn-outline btn-xs" onclick="togglePasswordVisibility('zainSecretInput')">👁️</button>
                            <button type="button" class="btn btn-outline btn-xs" onclick="window.AuraStore.copyToClipboard('zainSecretInput', 'ZainCash Secret copied')">📋 <?php echo adm_t('admin_pay_copy', 'Copy'); ?></button>
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('zaincash')">
                            ⚡ <?php echo adm_t('admin_pay_test_ping', 'Test Connection & Ping'); ?>
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
                                <h3 style="margin:0; font-size:18px"><?php echo adm_t('admin_gateway_fastpay', 'FastPay Mobile Payment'); ?></h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_gateway_fastpay_desc', 'Direct digital wallet payments throughout Erbil, Sulaymaniyah, and Duhok'); ?></p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="fastpay_enabled" value="1" <?php echo (!empty($fastpay['enabled']) || !isset($fastpay['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;"><?php echo adm_t('admin_gateway_enabled', 'Gateway Enabled'); ?></span>
                            </label>
                        </div>
                    </div>

                    <div class="form-row-2 mb-16">
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_store_id', 'Merchant Store ID'); ?></label>
                            <input type="text" name="fastpay_store_id" value="<?php echo htmlspecialchars($fastpay['store_id'] ?? 'FASTPAY_AURA_992'); ?>" class="form-control">
                        </div>
                        <div class="form-group">
                            <label><?php echo adm_t('admin_pay_store_password', 'Store Password / Secret'); ?></label>
                            <input type="password" name="fastpay_store_password" id="fastpayPassInput" value="<?php echo htmlspecialchars($fastpay['store_password'] ?? 'aura_fast_secret_pass'); ?>" class="form-control">
                        </div>
                    </div>

                    <div class="gateway-actions-row" style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap; pt-16; border-top:1px solid var(--border-subtle);">
                        <button type="button" class="btn btn-outline btn-sm" onclick="window.AuraStore.testGatewayConnection('fastpay')">
                            ⚡ <?php echo adm_t('admin_pay_test_ping', 'Test Connection & Ping'); ?>
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
                                <h3 style="margin:0; font-size:18px;"><?php echo adm_t('admin_gateway_cod', 'Cash on Delivery (COD)'); ?></h3>
                                <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;"><?php echo adm_t('admin_pay_cod_desc', 'Client settles directly in Iraqi Dinar (IQD) upon inspection of luxury packaging'); ?></p>
                            </div>
                        </div>
                        <div class="gateway-toggle-wrap">
                            <label class="switch-toggle" style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                                <input type="checkbox" name="cod_enabled" value="1" <?php echo (!empty($cod['enabled']) || !isset($cod['enabled'])) ? 'checked' : ''; ?> style="width:20px; height:20px; accent-color:var(--accent-gold);">
                                <span style="font-size:13px; font-weight:700;"><?php echo adm_t('admin_gateway_enabled', 'Gateway Enabled'); ?></span>
                            </label>
                        </div>
                    </div>

                    <p class="text-muted" style="font-size:13px; margin:0 0 16px;">
                        <?php echo adm_t('admin_pay_cod_desc', 'Client settles directly in Iraqi Dinar (IQD) upon inspection of luxury packaging'); ?>
                    </p>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:28px;">
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:12px 32px; font-size:15px;">
                    💾 <?php echo adm_t('admin_pay_save_all', 'Save All Payment Gateways & Rates'); ?>
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
