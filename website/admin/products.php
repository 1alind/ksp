<?php
$pageTitle = 'Product Catalog & Inventory | AURA Luxury Admin';
$adminActive = 'products';
$ordersDb = json_decode(file_get_contents(__DIR__ . '/../database/orders.json'), true);
$ordersList = $ordersDb['orders'] ?? [];
$productsDb = json_decode(file_get_contents(__DIR__ . '/../database/products.json'), true);
$productsList = $productsDb['products'] ?? [];
$usersDb = json_decode(file_get_contents(__DIR__ . '/../database/users.json'), true);
$usersList = $usersDb['users'] ?? [];
$inquiriesDb = json_decode(file_get_contents(__DIR__ . '/../database/inquiries.json'), true);
$inquiriesList = $inquiriesDb['inquiries'] ?? [];

$totalStock = 0;
$featuredCount = 0;
foreach ($productsList as $p) {
    $totalStock += ($p['stock'] ?? 0);
    if (!empty($p['featured'])) $featuredCount++;
}
?>

<div class="page-banner">
    <div class="container">
        <div class="page-banner-content">
            <span class="section-kicker">✦ Executive Command Suite</span>
            <h1 class="page-banner-title">Product Catalog & Inventory</h1>
            <p class="page-banner-subtitle">
                Trilingual luxury catalog management, real-time stock steppers, IQD pricing, and promotional badges.
            </p>
        </div>
    </div>
</div>

<section class="admin-section" style="padding: 40px 0 80px;">
    <div class="container">

        <!-- Unified Admin Navigation Bar -->
        <?php require_once __DIR__ . '/nav.php'; ?>

        <!-- Product Metrics Sub-Cards -->
        <div class="admin-metrics-grid" style="margin-bottom:24px;">
            <div class="admin-metric-card">
                <span class="m-icon">💎</span>
                <div class="m-info">
                    <span class="m-label">Total Catalog Pieces</span>
                    <strong class="m-value"><?php echo count($productsList); ?> Products</strong>
                    <span class="iqd-price-pill">Curated Luxury Collection</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">📊</span>
                <div class="m-info">
                    <span class="m-label">Total Vault Inventory</span>
                    <strong class="m-value" style="color:#22c55e;"><?php echo $totalStock; ?> Units in Stock</strong>
                    <span class="iqd-price-pill">Live Warehouse Count</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">⭐</span>
                <div class="m-info">
                    <span class="m-label">Featured Showcase</span>
                    <strong class="m-value" style="color:var(--accent-gold);"><?php echo $featuredCount; ?> Showcased</strong>
                    <span class="iqd-price-pill">Homepage Luxury Grid</span>
                </div>
            </div>
            <div class="admin-metric-card">
                <span class="m-icon">🏷️</span>
                <div class="m-info">
                    <span class="m-label">Currencies & Pricing</span>
                    <strong class="m-value">100% IQD</strong>
                    <span class="iqd-price-pill">Official Iraqi Dinar</span>
                </div>
            </div>
        </div>

        <!-- Add Product Collapsible Panel -->
        <div class="admin-form-card mb-24" id="addProductCard" style="display:none; border:2px solid var(--accent-gold);">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">+ Add New Luxury Piece to Catalog</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Include trilingual titles in English, Arabic, and Kurdish Badini, pricing in IQD, and high-res imagery.</p>
                </div>
                <button type="button" class="btn btn-ghost btn-sm" onclick="toggleAddProductForm()">✕ Close</button>
            </div>

            <form action="/admin/products.php" method="POST" id="newProductForm">
                <input type="hidden" name="add_new_product" value="1">
                
                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Title (English) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_en" required class="form-control" placeholder="e.g. Royal Midnight Velvet Blazer">
                    </div>
                    <div class="form-group">
                        <label>Title (Arabic - العربية) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ar" required class="form-control" placeholder="مثال: بليزر ملكي كحلي مخملي">
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish - کوردی بادینی) <span class="text-danger">*</span></label>
                        <input type="text" name="prod_title_ku" required class="form-control" placeholder="وەکی: قاتی مخملی یێ شاهانە">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="prod_category" class="form-control">
                            <option value="clothes">Clothes & Apparel</option>
                            <option value="watches">Luxury Timepieces & Watches</option>
                            <option value="perfumes">Arabian Oud & Haute Perfumes</option>
                            <option value="accessories">Handcrafted Leather & Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (IQD) <span class="text-danger">*</span></label>
                        <input type="number" name="prod_price" required class="form-control" placeholder="e.g. 240000" min="1000">
                    </div>
                    <div class="form-group">
                        <label>Original / Strikethrough Price (IQD)</label>
                        <input type="number" name="prod_old_price" class="form-control" placeholder="e.g. 300000 (Optional discount)">
                    </div>
                </div>

                <div class="form-row-3 mb-16">
                    <div class="form-group">
                        <label>Initial Vault Stock</label>
                        <input type="number" name="prod_stock" class="form-control" value="15" min="0">
                    </div>
                    <div class="form-group">
                        <label>Promotional Badge (English)</label>
                        <input type="text" name="prod_badge" class="form-control" placeholder="e.g. Best Seller, Limited Edition">
                    </div>
                    <div class="form-group">
                        <label>Badge (Arabic)</label>
                        <input type="text" name="prod_badge_ar" class="form-control" placeholder="الأكثر مبيعاً">
                    </div>
                </div>

                <div class="form-group mb-16">
                    <label>Cover Image URL <span class="text-danger">*</span></label>
                    <input type="url" name="prod_image" required class="form-control" placeholder="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80">
                </div>

                <div class="form-group mb-16">
                    <label>Additional Gallery Images (Comma-separated URLs)</label>
                    <textarea name="prod_gallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                </div>

                <div class="form-row-3 mb-20">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="prod_desc_en" rows="3" class="form-control" placeholder="Handcrafted with Italian cashmere velvet..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="prod_desc_ar" rows="3" class="form-control" placeholder="مصنوع يدوياً من أفخر أنواع المخمل الإيطالي..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="prod_desc_ku" rows="3" class="form-control" placeholder="ب دەستان هاتیە چێکرن ژ قوماشێ مخملی یێ ئیتالی..."></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:12px;">
                    <button type="button" class="btn btn-outline" onclick="toggleAddProductForm()">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-luxury">Publish Piece to Boutique</button>
                </div>
            </form>
        </div>

        <!-- Products Table Card -->
        <div class="admin-table-card">
            <div class="admin-header-row" style="display:flex; justify-content:space-between; align-items:center; padding:20px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
                <div>
                    <h3 class="admin-card-title" style="margin:0; font-size:18px;">💎 Luxury Catalog Pieces & Inventory</h3>
                    <p class="text-muted" style="margin:4px 0 0; font-size:12.5px;">Click (+) or (-) on any product stock for instant inventory adjustment, or Edit for trilingual details.</p>
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    <button type="button" class="btn btn-primary btn-luxury btn-sm" onclick="toggleAddProductForm()">
                        + Add New Product
                    </button>
                    <input type="text" id="prodSearchInput" onkeyup="filterProductsTable()" placeholder="Search pieces..." class="form-control" style="max-width:200px; padding:8px 12px; font-size:13px;">
                    <select id="prodCategoryFilter" onchange="filterProductsTable()" class="form-control" style="max-width:160px; padding:8px 12px; font-size:13px;">
                        <option value="">All Categories</option>
                        <option value="clothes">Clothes</option>
                        <option value="watches">Watches</option>
                        <option value="perfumes">Perfumes</option>
                        <option value="accessories">Accessories</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="admin-table" id="productsTableMain">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Details</th>
                            <th>Category</th>
                            <th>Price (IQD)</th>
                            <th>Stock Count</th>
                            <th>Rating</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productsList as $p): 
                            $pTitle = is_array($p['title']) ? ($p['title']['en'] ?? reset($p['title'])) : $p['title'];
                            $pPriceIqd = $p['price'] ?? 0;
                            $pOldPriceIqd = $p['old_price'] ?? null;
                            $pStock = $p['stock'] ?? 0;
                            $safeJson = json_encode($p);
                        ?>
                            <tr data-category="<?php echo htmlspecialchars(strtolower($p['category'] ?? '')); ?>" data-search="<?php echo htmlspecialchars(strtolower($pTitle . ' ' . ($p['category'] ?? '') . ' #' . $p['id'])); ?>">
                                <td>#<?php echo $p['id']; ?></td>
                                <td>
                                    <div class="admin-prod-preview">
                                        <img src="<?php echo htmlspecialchars($p['image']); ?>" alt="" class="admin-prod-thumb" id="adminThumb_<?php echo $p['id']; ?>">
                                        <div>
                                            <strong><a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" style="color:var(--text-primary);"><?php echo htmlspecialchars($pTitle); ?></a></strong><br>
                                            <?php if (!empty($p['badge'])): ?>
                                                <small class="badge-tag" style="background:var(--accent-gold-bg); color:var(--accent-gold); border-color:var(--accent-gold); font-weight:700;"><?php echo htmlspecialchars($p['badge']); ?></small>
                                            <?php endif; ?>
                                            <?php if (!empty($p['featured'])): ?>
                                                <small class="badge-tag" style="background:rgba(59,130,246,0.15); color:#60a5fa; border-color:#3b82f6;">⭐ Featured</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge-tag text-uppercase"><?php echo htmlspecialchars($p['category'] ?? 'luxury'); ?></span></td>
                                <td>
                                    <div style="display:flex; flex-direction:column;">
                                        <strong class="font-bold" style="color:var(--accent-gold); font-size:14px;"><?php echo number_format($pPriceIqd); ?> IQD</strong>
                                        <?php if ($pOldPriceIqd && $pOldPriceIqd > $pPriceIqd): ?>
                                            <small style="text-decoration:line-through; color:var(--text-muted); font-size:11.5px;"><?php echo number_format($pOldPriceIqd); ?> IQD</small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="stock-adjuster" id="stockAdjuster_<?php echo $p['id']; ?>" style="display:inline-flex; align-items:center; gap:6px; background:var(--bg-subtle); padding:4px 8px; border-radius:6px; border:1px solid var(--border-color);">
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, -1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="Decrease Stock">-</button>
                                        <span class="stock-value-badge font-bold" id="stockBadge_<?php echo $p['id']; ?>" style="min-width:28px; text-align:center; font-size:13px;"><?php echo $pStock; ?></span>
                                        <button type="button" class="btn-stock-stepper" onclick="window.AuraStore.adjustStock(<?php echo $p['id']; ?>, 1)" style="width:24px; height:24px; display:inline-flex; align-items:center; justify-content:center; border:none; background:var(--bg-surface); color:var(--text-primary); border-radius:4px; font-weight:800; cursor:pointer;" title="Increase Stock">+</button>
                                    </div>
                                </td>
                                <td>
                                    <span style="color:#d97706; font-size:13px;">★ <?php echo $p['rating'] ?? '4.9'; ?></span>
                                    <small class="text-muted">(<?php echo $p['reviews_count'] ?? '24'; ?>)</small>
                                </td>
                                <td>
                                    <div style="display:flex; gap:6px;">
                                        <button type="button" class="btn btn-outline btn-xs" onclick='openEditProductModal(<?php echo htmlspecialchars($safeJson, ENT_QUOTES, 'UTF-8'); ?>)' title="Edit Product Details & Badges">
                                            ✏️ Edit
                                        </button>
                                        <a href="/product.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-ghost btn-xs" title="View in Boutique">👁️</a>
                                        <form action="/admin/products.php" method="POST" onsubmit="return confirm('Delete this product permanently?')" style="display:inline;">
                                            <input type="hidden" name="delete_product_id" value="<?php echo $p['id']; ?>">
                                            <button type="submit" class="btn btn-ghost text-danger btn-xs" title="Delete Product">✕</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- EDIT PRODUCT MODAL (COMPLETE TRILINGUAL ENGINE) -->
<div class="modal-overlay" id="editProductModalOverlay">
    <div class="modal-card modal-card-wide" style="max-width:880px; max-height:90vh; overflow-y:auto;">
        <div class="modal-header" style="position:sticky; top:0; background:var(--bg-card); z-index:10; border-bottom:1px solid var(--border-color); padding:16px 24px; margin:-24px -24px 20px -24px;">
            <div>
                <div style="display:flex; align-items:center; gap:10px;">
                    <h3 style="margin:0; font-size:20px; font-weight:800;">✏️ Edit Luxury Piece</h3>
                    <span class="badge-tag" id="editProductModalIdBadge" style="background:var(--accent-gold-bg); color:var(--accent-gold); font-weight:800;">#0</span>
                </div>
                <small class="text-muted" id="editProductModalSub">Update trilingual titles, pricing discounts, badges, and gallery</small>
            </div>
            <button type="button" class="btn-close-modal" onclick="closeEditProductModal()" style="font-size:20px; cursor:pointer;">✕</button>
        </div>

        <form action="/admin/products.php" method="POST" id="editProductForm">
            <input type="hidden" name="update_product" value="1">
            <input type="hidden" name="edit_prod_id" id="editProdId">

            <!-- Section 1: Core Pricing & Category -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">💰 Pricing & Category</span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Category <span class="text-danger">*</span></label>
                        <select name="edit_prod_category" id="editProdCategory" class="form-control">
                            <option value="clothes">Clothes & Apparel</option>
                            <option value="watches">Luxury Watches</option>
                            <option value="perfumes">Arabian Oud & Perfumes</option>
                            <option value="accessories">Leather & Accessories</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Selling Price (IQD) <span class="text-danger">*</span></label>
                        <input type="number" name="edit_prod_price" id="editProdPrice" required class="form-control" oninput="calculateDiscountPreview()">
                    </div>
                    <div class="form-group">
                        <label>Original / Old Price (IQD)</label>
                        <input type="number" name="edit_prod_old_price" id="editProdOldPrice" class="form-control" oninput="calculateDiscountPreview()">
                        <span id="editDiscountBadge" class="badge-tag" style="display:none; margin-top:4px; background:rgba(34,197,94,0.15); color:#22c55e; border-color:#22c55e; font-weight:700;"></span>
                    </div>
                </div>

                <div class="form-row-3" style="margin-top:12px;">
                    <div class="form-group">
                        <label>Stock Count</label>
                        <input type="number" name="edit_prod_stock" id="editProdStock" class="form-control" value="10">
                    </div>
                    <div class="form-group" style="display:flex; align-items:center; gap:10px; margin-top:24px;">
                        <label style="display:flex; align-items:center; gap:8px; cursor:pointer; font-weight:600; font-size:13.5px;">
                            <input type="checkbox" name="edit_prod_featured" id="editProdFeatured" value="1" style="width:18px; height:18px; accent-color:var(--accent-gold);">
                            <span>⭐ Featured on Homepage Showcase</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Quick Price Helpers (IQD)</label>
                        <div style="display:flex; gap:6px; flex-wrap:wrap;">
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.9); calculateDiscountPreview();">10% Off</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.8); calculateDiscountPreview();">20% Off</button>
                            <button type="button" class="btn btn-ghost btn-xs" onclick="document.getElementById('editProdPrice').value = Math.round(Number(document.getElementById('editProdPrice').value || 100000) * 0.5); calculateDiscountPreview();">50% Off</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Badges & Promotion Tags -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🏷️ Promotional Badge / Ribbon</span>

                <div style="margin-bottom:12px;">
                    <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Quick Presets (Click to Auto-fill Trilingual Badges):</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('⚡ 50% OFF', '⚡ خصم 50%', '⚡ داشکاندنا %50')">⚡ 50% OFF</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('🔥 Best Seller', '🔥 الأكثر مبيعاً', '🔥 پڕفرۆشترین')">🔥 Best Seller</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('💎 Limited Edition', '💎 إصدار محدود', '💎 وەشانەکا سنوردار')">💎 Limited Edition</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('✨ New Arrival', '✨ وصل حديثاً', '✨ نوی گەهشتی')">✨ New Arrival</button>
                        <button type="button" class="badge-tag" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('👑 Royal Luxury', '👑 فاخر ملكي', '👑 شاهانە و نازک')">👑 Royal Luxury</button>
                        <button type="button" class="badge-tag text-danger" style="cursor:pointer; background:var(--bg-surface); padding:4px 10px; font-weight:600;" onclick="setEditBadgePreset('', '', '')">✕ Clear</button>
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label>Badge (English)</label>
                        <input type="text" name="edit_prod_badge" id="editProdBadge" class="form-control" placeholder="e.g. Best Seller">
                    </div>
                    <div class="form-group">
                        <label>Badge (Arabic - العربية)</label>
                        <input type="text" name="edit_prod_badge_ar" id="editProdBadgeAr" class="form-control" placeholder="مثال: الأكثر مبيعاً">
                    </div>
                    <div class="form-group">
                        <label>Badge (Kurdish - کوردی بادینی)</label>
                        <input type="text" name="edit_prod_badge_ku" id="editProdBadgeKu" class="form-control" placeholder="وەکی: پڕفرۆشترین">
                    </div>
                </div>
            </div>

            <!-- Section 3: Image & Gallery -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🖼️ Product Imagery & Gallery</span>
                
                <div style="display:grid; grid-template-columns:100px 1fr; gap:16px; align-items:start; margin-bottom:14px;">
                    <div style="text-align:center;">
                        <img id="editImageLivePreview" src="https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80" alt="Preview" style="width:100px; height:100px; object-fit:cover; border-radius:8px; border:2px solid var(--accent-gold);">
                        <small class="text-muted" style="display:block; font-size:10.5px; margin-top:4px;">Main Preview</small>
                    </div>

                    <div>
                        <div class="form-group mb-12">
                            <label>Primary Cover Image URL <span class="text-danger">*</span></label>
                            <input type="url" name="edit_prod_image" id="editProdImage" required class="form-control" oninput="updateEditImagePreview()">
                        </div>

                        <div class="form-group">
                            <label>Additional Gallery Images (Comma-Separated URLs)</label>
                            <textarea name="edit_prod_gallery" id="editProdGallery" rows="2" class="form-control" placeholder="https://image1.jpg, https://image2.jpg, https://image3.jpg"></textarea>
                        </div>
                    </div>
                </div>

                <div>
                    <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:6px;">Sample Photography Presets:</label>
                    <div style="display:flex; gap:6px; flex-wrap:wrap;">
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1594938298603-c8148c4dae35?auto=format&fit=crop&w=800&q=80')">👔 Velvet Blazer</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1524805444758-089113d48a6d?auto=format&fit=crop&w=800&q=80')">⌚ Swiss Watch</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1592945403244-b3fbafd7f539?auto=format&fit=crop&w=800&q=80')">✨ Arabian Oud</button>
                        <button type="button" class="btn btn-ghost btn-xs" onclick="setEditImagePreset('https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=800&q=80')">👜 Leather Bag</button>
                    </div>
                </div>
            </div>

            <!-- Section 4: Trilingual Titles -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:20px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">🌐 Trilingual Titles</span>
                <div class="form-row-3">
                    <div class="form-group">
                        <label>Title (English) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_en" id="editProdTitleEn" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Title (Arabic - العربية) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ar" id="editProdTitleAr" required class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Title (Kurdish - کوردی بادینی) <span class="text-danger">*</span></label>
                        <input type="text" name="edit_prod_title_ku" id="editProdTitleKu" required class="form-control">
                    </div>
                </div>
            </div>

            <!-- Section 5: Sizes, Colors & Descriptions -->
            <div style="background:var(--bg-subtle); padding:16px; border-radius:var(--radius-sm); border:1px solid var(--border-color); margin-bottom:24px;">
                <span style="font-weight:700; font-size:13.5px; color:var(--accent-gold); text-transform:uppercase; letter-spacing:1px; display:block; margin-bottom:12px;">📝 Attributes & Trilingual Descriptions</span>
                
                <div class="form-row-2 mb-16">
                    <div class="form-group">
                        <label>Available Sizes (Comma-separated)</label>
                        <input type="text" name="edit_prod_sizes" id="editProdSizes" class="form-control" placeholder="S, M, L, XL or 42mm, 44mm">
                    </div>
                    <div class="form-group">
                        <label>Available Colors / Editions</label>
                        <input type="text" name="edit_prod_colors" id="editProdColors" class="form-control" placeholder="Midnight Black, Royal Gold, Emerald">
                    </div>
                </div>

                <div class="form-row-3">
                    <div class="form-group">
                        <label>Description (English)</label>
                        <textarea name="edit_prod_desc_en" id="editProdDescEn" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Arabic - العربية)</label>
                        <textarea name="edit_prod_desc_ar" id="editProdDescAr" rows="3" class="form-control"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Description (Kurdish - کوردی)</label>
                        <textarea name="edit_prod_desc_ku" id="editProdDescKu" rows="3" class="form-control"></textarea>
                    </div>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:12px; padding-top:10px;">
                <button type="button" class="btn btn-outline" onclick="closeEditProductModal()">Cancel</button>
                <button type="submit" class="btn btn-primary btn-luxury" style="padding:10px 24px;">Save & Apply Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleAddProductForm() {
    const card = document.getElementById('addProductCard');
    if (!card) return;
    const isHidden = card.style.display === 'none';
    card.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function filterProductsTable() {
    const q = (document.getElementById('prodSearchInput').value || '').toLowerCase();
    const cat = document.getElementById('prodCategoryFilter').value.toLowerCase();
    const rows = document.querySelectorAll('#productsTableMain tbody tr');
    rows.forEach(row => {
        const rowSearch = row.getAttribute('data-search') || '';
        const rowCat = row.getAttribute('data-category') || '';
        const matchesQ = !q || rowSearch.includes(q);
        const matchesCat = !cat || rowCat === cat;
        row.style.display = (matchesQ && matchesCat) ? '' : 'none';
    });
}

function openEditProductModal(product) {
    if (!product) return;
    document.getElementById('editProdId').value = product.id;
    document.getElementById('editProductModalIdBadge').innerText = '#' + product.id;
    
    const pTitleEn = typeof product.title === 'object' ? (product.title.en || '') : product.title;
    const pTitleAr = typeof product.title === 'object' ? (product.title.ar || pTitleEn) : pTitleEn;
    const pTitleKu = typeof product.title === 'object' ? (product.title.ku || pTitleEn) : pTitleEn;

    document.getElementById('editProductModalSub').innerText = 'Editing: ' + pTitleEn + ' (' + (product.category || 'luxury') + ')';
    document.getElementById('editProdTitleEn').value = pTitleEn;
    document.getElementById('editProdTitleAr').value = pTitleAr;
    document.getElementById('editProdTitleKu').value = pTitleKu;

    document.getElementById('editProdCategory').value = product.category || 'clothes';
    document.getElementById('editProdPrice').value = product.price || 0;
    document.getElementById('editProdOldPrice').value = product.old_price || '';
    document.getElementById('editProdStock').value = product.stock !== undefined ? product.stock : 10;
    document.getElementById('editProdFeatured').checked = !!product.featured;

    document.getElementById('editProdBadge').value = product.badge || '';
    document.getElementById('editProdBadgeAr').value = product.badge_ar || product.badge || '';
    document.getElementById('editProdBadgeKu').value = product.badge_ku || product.badge || '';

    const mainImg = product.image || '';
    document.getElementById('editProdImage').value = mainImg;
    const gallery = Array.isArray(product.images) ? product.images.join(', ') : (mainImg || '');
    document.getElementById('editProdGallery').value = gallery;
    updateEditImagePreview();

    const sizes = Array.isArray(product.sizes) ? product.sizes.join(', ') : (product.sizes || '');
    document.getElementById('editProdSizes').value = sizes;

    const colors = Array.isArray(product.colors) ? product.colors.join(', ') : (product.colors || '');
    document.getElementById('editProdColors').value = colors;

    const pDescEn = typeof product.description === 'object' ? (product.description.en || '') : (product.description || '');
    const pDescAr = typeof product.description === 'object' ? (product.description.ar || pDescEn) : pDescEn;
    const pDescKu = typeof product.description === 'object' ? (product.description.ku || pDescEn) : pDescEn;

    document.getElementById('editProdDescEn').value = pDescEn;
    document.getElementById('editProdDescAr').value = pDescAr;
    document.getElementById('editProdDescKu').value = pDescKu;

    calculateDiscountPreview();
    document.getElementById('editProductModalOverlay').classList.add('open');
}

function closeEditProductModal() {
    document.getElementById('editProductModalOverlay').classList.remove('open');
}

function updateEditImagePreview() {
    const url = document.getElementById('editProdImage').value;
    const imgEl = document.getElementById('editImageLivePreview');
    if (imgEl && url) {
        imgEl.src = url;
    }
}

function setEditImagePreset(url) {
    document.getElementById('editProdImage').value = url;
    const galleryEl = document.getElementById('editProdGallery');
    if (!galleryEl.value || galleryEl.value.indexOf(url) === -1) {
        galleryEl.value = url;
    }
    updateEditImagePreview();
}

function setEditBadgePreset(en, ar, ku) {
    document.getElementById('editProdBadge').value = en;
    document.getElementById('editProdBadgeAr').value = ar;
    document.getElementById('editProdBadgeKu').value = ku;
}

function calculateDiscountPreview() {
    const price = Number(document.getElementById('editProdPrice').value) || 0;
    const oldPrice = Number(document.getElementById('editProdOldPrice').value) || 0;
    const badgeEl = document.getElementById('editDiscountBadge');
    if (oldPrice > price && price > 0) {
        const pct = Math.round(((oldPrice - price) / oldPrice) * 100);
        const saveIqd = oldPrice - price;
        badgeEl.style.display = 'inline-block';
        badgeEl.innerText = pct + '% OFF (Save ' + saveIqd.toLocaleString() + ' IQD)';
    } else {
        badgeEl.style.display = 'none';
    }
}
</script>
