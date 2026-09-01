# 🛒 AURA Checkout, Cart & Order Tracking Module

This folder contains all files that handle the customer purchasing funnel, shipping calculation, and post-purchase delivery tracking.

---

## 📁 File Structure

| File | Description |
| :--- | :--- |
| **`cart.php`** | **Shopping Bag View**. Displays added items, thumbnail previews, quantity steppers, subtotal recalculation, and coupon discount validation. |
| **`checkout.php`** | **Multi-Step Checkout**. Customer name, phone, governorate/city selection, delivery address, and payment method selector (FIB, ZainCash, FastPay, Cash on Delivery). |
| **`track.php`** | **Real-Time Order & Logistics Radar**. Order lookup by Order ID or Phone number, displaying live delivery progress stages (Pending -> Confirmed -> Shipped -> Out for Delivery -> Delivered). |

---

## 💡 How Order Processing Works

1. Customer places order on `checkout.php`.
2. Order is recorded with a unique ID (e.g., `ORD-84920`) in `/website/database/orders.json`.
3. Customer receives instant tracking link: `/track.php?order_id=ORD-84920`.
4. Admin can assign courier drivers and tracking codes in **Admin Panel > Orders**.
