# 🛠️ AURA Admin & Management Portal

Welcome to the **Admin Module** for the AURA Luxury Store.
This directory contains all files related to the store management dashboard, products, orders, payments, branding, and customer accounts.

---

## 📁 File Structure & Purpose

| File | Purpose |
| :--- | :--- |
| **`index.php`** | **Main Management Dashboard**. Contains the complete tabbed administration panel with live metrics, quick actions, and data tables. |
| **`products.php`** | **Product & Stock Manager**. Add new products, adjust inventory, update pricing, and manage multi-lingual titles & images. |
| **`orders.php`** | **Orders & Logistics Radar**. View orders, change statuses (Pending, Confirmed, Shipped, Delivered), and assign couriers. |
| **`payments.php`** | **Payment Gateway Configuration**. Manage FIB (First Iraqi Bank), ZainCash, FastPay, Cash on Delivery, and USD/IQD exchange rate. |
| **`branding.php`** | **Store Customizer**. Change store name, slogan, logo emblem/image, favicon, announcement bar, and boutique address. |
| **`users.php`** | **Customer Accounts**. Manage client profiles and staff permissions. |
| **`inquiries.php`** | **Customer Support Messages**. View customer inquiries submitted through the contact form. |

---

## 💡 How to Change Settings & Features

1. **Change Store Name or Logo**:
   - Navigate to **Admin Panel > Store Customizer** in your browser, or edit `/website/database/settings.json`.

2. **Change Payment Gateway Credentials (FIB, ZainCash, FastPay)**:
   - Go to **Admin Panel > Payment Gateways**, or edit `/website/database/settings.json` under the `"gateways"` key.

3. **Add or Edit Products**:
   - Use the **Products Manager** tab in the admin dashboard, or modify `/website/database/products.json`.
