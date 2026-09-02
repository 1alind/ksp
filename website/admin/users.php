<?php
$pageTitle = 'Customer Accounts & Directory | AURA Luxury Admin';
$adminActive = 'users';
$ordersDb = json_decode(file_get_contents(__DIR__ . '/../database/orders.json'), true);
$ordersList = $ordersDb['orders'] ?? [];
$productsDb = json_decode(file_get_contents(__DIR__ . '/../database/products.json'), true);
$productsList = $productsDb['products'] ?? [];
$usersDb = json_decode(file_get_contents(__DIR__ . '/../database/users.json'), true);
$usersList = $usersDb['users'] ?? [];
$inquiriesDb = json_decode(file_get_contents(__DIR__ . '/../database/inquiries.json'), true);
$inquiriesList = $inquiriesDb['inquiries'] ?? [];

$totalCustomerSpend = 0;
foreach ($ordersList as $o) {
    $totalCustomerSpend += ($o['total'] ?? 0);
}

$activePage = 'admin';
require_once __DIR__ . '/../header.php';
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Customer Directory & Accounts</h1>
            <p class="page-banner-subtitle">
                Comprehensive customer profiles, contact numbers, order history counts, and lifetime boutique spend.
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <!-- Metrics Grid -->
        <div class="admin-metrics-grid" style="margin-bottom:24px;">
            <div class="admin-metric-card">
                <span class="m-icon">👥</span>
                <div class="m-info">
                    <span class="m-label">Registered Customers</span>
                    <strong class="m-value"><?php echo count($usersList); ?> Clients</strong>
                    <span class="iqd-price-pill">Curated Luxury Client Base</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">💰</span>
                <div class="m-info">
                    <span class="m-label">Total Client Volume</span>
                    <strong class="m-value text-primary"><?php echo number_format($totalCustomerSpend); ?> IQD</strong>
                    <span class="iqd-price-pill">Combined Lifetime Purchases</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">📍</span>
                <div class="m-info">
                    <span class="m-label">Regional Reach</span>
                    <strong class="m-value">All 18 Provinces</strong>
                    <span class="iqd-price-pill">Kurdistan & Federal Iraq</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">💬</span>
                <div class="m-info">
                    <span class="m-label">Concierge Inquiries</span>
                    <strong class="m-value"><?php echo count($inquiriesList); ?> Active</strong>
                    <span class="iqd-price-pill"><a href="/admin/inquiries.php" style="color:inherit;">View Messages →</a></span>
                </div>
            </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 340px; gap:24px; align-items:start;">
            
            <!-- Customers Table Card -->
            <div class="admin-table-card">
                <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                    <div>
                        <h3 class="admin-card-title" style="margin:0; font-size:18px;">👥 Registered Customers</h3>
                        <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">All clients registered through checkout, authentication, or manual admin entry.</p>
                    </div>
                    <input type="text" id="userSearchInput" onkeyup="filterUsersTable()" placeholder="Search clients..." class="form-control" style="max-width:200px; padding:8px 12px; font-size:13px;">
                </div>

                <div class="table-responsive">
                    <table class="admin-table" id="usersTableMain">
                        <thead>
                            <tr>
                                <th>Client Profile</th>
                                <th>Contact Details</th>
                                <th>City</th>
                                <th>Orders</th>
                                <th>Total Spend</th>
                                <th>Member Since</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usersList as $u): 
                                $initials = strtoupper(substr($u['name'] ?? 'U', 0, 2));
                                $uPhone = $u['phone'] ?? '';
                                $cleanPhone = preg_replace('/[^0-9]/', '', $uPhone);
                                if (strpos($cleanPhone, '07') === 0) $cleanPhone = '964' . substr($cleanPhone, 1);
                            ?>
                                <tr data-search="<?php echo htmlspecialchars(strtolower(($u['name'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['phone'] ?? '') . ' ' . ($u['city'] ?? ''))); ?>">
                                    <td>
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:var(--accent-gold); color:#0c0e14; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">
                                                <?php echo $initials; ?>
                                            </div>
                                            <div>
                                                <strong style="color:var(--text-primary); font-size:14px;"><?php echo htmlspecialchars($u['name']); ?></strong><br>
                                                <code style="font-size:11px; color:var(--text-muted);"><?php echo htmlspecialchars($u['user_code'] ?? ('USR-' . str_pad($u['id'], 3, '0', STR_PAD_LEFT))); ?></code>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span style="font-size:13px;"><?php echo htmlspecialchars($u['email']); ?></span><br>
                                        <small class="text-muted">📞 <?php echo htmlspecialchars($uPhone); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-tag" style="font-weight:600;">📍 <?php echo htmlspecialchars($u['city'] ?? 'Duhok'); ?></span>
                                    </td>
                                    <td>
                                        <strong style="font-size:14px;"><?php echo $u['orders_count'] ?? 1; ?></strong> <span class="text-muted">orders</span>
                                    </td>
                                    <td>
                                        <strong style="color:var(--accent-gold); font-size:14px;"><?php echo number_format($u['total_spent'] ?? 240000); ?> IQD</strong>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?php echo !empty($u['created_at']) ? date('M d, Y', strtotime($u['created_at'])) : 'Active Member'; ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-tag" style="background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e; font-weight:700;">Active</span>
                                    </td>
                                    <td>
                                        <div style="display:flex; gap:6px;">
                                            <?php if (!empty($cleanPhone)): ?>
                                                <a href="https://wa.me/<?php echo $cleanPhone; ?>" target="_blank" class="btn btn-outline btn-xs" style="color:#22c55e;">💬 WA</a>
                                            <?php endif; ?>
                                            <form action="/admin/users.php" method="POST" onsubmit="return confirm('Delete customer profile?')" style="display:inline;">
                                                <input type="hidden" name="delete_user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete">✕</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add New Customer Card -->
            <div class="admin-form-card">
                <h3 class="admin-card-title" style="font-size:16px; margin-bottom:6px;">+ Register Customer</h3>
                <p class="text-muted" style="font-size:12.5px; margin-bottom:16px;">Manually add client details from phone inquiries or direct boutique appointments.</p>
                
                <form action="/admin/users.php" method="POST">
                    <input type="hidden" name="add_new_user" value="1">
                    
                    <div class="form-group mb-14">
                        <label>Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="user_name" required class="form-control" placeholder="e.g. Dana Barzani">
                    </div>

                    <div class="form-group mb-14">
                        <label>Email Address <span class="text-danger">*</span></label>
                        <input type="email" name="user_email" required class="form-control" placeholder="dana@example.com">
                    </div>

                    <div class="form-group mb-14">
                        <label>Phone Number (Iraq)</label>
                        <input type="text" name="user_phone" class="form-control" placeholder="0750 000 0000">
                    </div>

                    <div class="form-group mb-14">
                        <label>City / Region</label>
                        <select name="user_city" class="form-control">
                            <option value="Duhok">Duhok</option>
                            <option value="Erbil">Erbil</option>
                            <option value="Sulaymaniyah">Sulaymaniyah</option>
                            <option value="Baghdad">Baghdad</option>
                            <option value="Basra">Basra</option>
                            <option value="Kirkuk">Kirkuk</option>
                            <option value="Najaf">Najaf</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary btn-luxury w-100" style="padding:10px;">
                        Add Customer to Directory
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
function filterUsersTable() {
    const q = (document.getElementById('userSearchInput').value || '').toLowerCase();
    const rows = document.querySelectorAll('#usersTableMain tbody tr');
    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search') || '';
        row.style.display = (!q || rowSearch.includes(q)) ? '' : 'none';
    });
}
</script>

<?php require_once __DIR__ . '/../footer.php'; ?>
