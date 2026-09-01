# 💳 AURA Payment Gateways & Banking Simulators

This directory holds all payment integrations, API connectors, webhooks, and sandbox simulators for the AURA Luxury Store.

---

## 📁 Directory Structure

```
website/payments/
├── index.php                 # Payment hub & API documentation overview
├── fake.php                  # Interactive Sandbox Payment Simulator for FIB, ZainCash & FastPay
├── fib/                      # First Iraqi Bank integration module
│   ├── fib.php               # OAuth2 Bearer authorization & Payment creation SDK
│   ├── config.php            # FIB Client credentials, IBAN, and callback URLs
│   └── callback.php          # Real-time Webhook listener & status updater
├── zaincash/                 # ZainCash wallet integration module
│   ├── zaincash.php          # JWT payload generator & Transaction initializer
│   ├── config.php            # Merchant ID, MSISDN, and secret encryption key
│   └── redirect.php          # Payment completion redirect handler
├── fastpay/                  # FastPay mobile wallet integration module
│   ├── fastpay.php           # QR token generator & Instant Payment Notification (IPN)
│   ├── config.php            # Store ID & merchant credentials
│   └── callback.php          # Status verifier
└── cod/                      # Cash on Delivery module
    └── cod.php               # Delivery settlement logic
```

---

## 🚀 How to Test & Configure

### 1. Interactive Payment Simulator (`fake.php`)
Open in your browser:
- `http://localhost:3000/payments/fake.php?gateway=fib`
- `http://localhost:3000/payments/fake.php?gateway=zaincash`
- `http://localhost:3000/payments/fake.php?gateway=fastpay`

You can test approving (200 OK) or declining (402 Failed) simulated bank transactions.

### 2. Updating Production Credentials
You can edit the individual `config.php` files in each gateway subfolder, or configure them dynamically from the **Admin Panel > Payment Gateways** tab. All changes are securely stored in `/website/database/settings.json`.
