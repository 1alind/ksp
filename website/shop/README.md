# 🛍️ AURA Shop & Product Catalog Module

This folder contains all files for browsing luxury collections, inspecting individual product details, and referencing sizing blueprints.

---

## 📁 File Structure

| File | Description |
| :--- | :--- |
| **`shop.php`** | **Store Catalog & Search**. Displays product cards, category tabs (Clothes, Watches, Perfumes, Accessories), live keyword searching, and price sorting. |
| **`product.php`** | **Product Detail Page**. High-resolution photo gallery, size and color selectors, real-time measurements badge card, add-to-cart controls, and stock indicators. |
| **`size_guide.php`** | **Interactive Size Guide & Measurement Visualizer**. High-contrast vector garment schematics with live height and width measurement badges for T-shirts, tops, and jeans. |

---

## 💡 How to Customize Products & Sizes

- **To add or modify products**: Edit `/website/database/products.json` or use the Admin panel.
- **To update size measurement blueprints**: In `product.php` or `products.json`, specify `size_measurements` (e.g. `{"S": "Height: 65cm • Width: 45cm", "M": "Height: 70cm • Width: 50cm"}`).
