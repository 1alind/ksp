# 💾 AURA Database & Storage Layer

This directory holds all data stores, JSON databases, and relational SQL schemas.

---

## 📁 File Structure

| File | Purpose |
| :--- | :--- |
| **`products.json`** | Product catalog items, multi-lingual titles, descriptions, categories, prices, badges, images, and sizes. |
| **`orders.json`** | Customer order records, payment statuses, client addresses, and courier dispatch logistics. |
| **`settings.json`** | FIB, ZainCash, and FastPay gateway credentials, exchange rates, boutique branding, and contact details. |
| **`users.json`** | Registered client records, emails, phone numbers, and customer accounts. |
| **`inquiries.json`** | Messages received from the contact form. |
| **`translations.json`** | Multi-language translation strings (English, Arabic, Kurdish Badini). |
| **`schema.sql`** | Relational SQL database schema for production MySQL/PostgreSQL deployment. |
| **`seed_examples.sql`** | Sample seed records for SQL tables. |
| **`db.php`** | PHP Database helper utilities and JSON store accessors. |
