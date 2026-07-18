# LIMA Solutions ERP – Project Bible

## 1. Architecture Summary
LIMA Solutions ERP is a multi-tenant business management platform designed for moving (déménagement), cleaning, and storage operations in Switzerland. 
* **Backend**: PHP 8.1+ (MariaDB 10.6+ / MySQL 8.0+), structured API-First design, multi-company tenant isolation using `company_id`.
* **Frontend**: Vanilla CSS and Javascript. Single Page Application (SPA) dashboard for admins, Espace Client portal, and Mobile PWA for field operators.
* **Security**: Strong Content Security Policy (CSP), HTTP security headers (`nosniff`, `X-Frame-Options`), session timeout control (1800s), and custom CSRF token validations.

## 2. Core Modules
* **CRM & Leads**: Multi-tenant customer lifecycle, tracking UTM params, lead scoring engine (Priority Alerts).
* **Quotes & Invoicing**: Structured quote conversion to invoices with Swiss tax calculations (VAT 8.1%), automatic PDF compiler.
* **Payments & Stripe/TWINT**: Hosted Stripe Checkout integration supporting credit cards, Apple Pay, Google Pay, and TWINT (via Stripe). Webhook validations and idempotency checking.
* **Operational Projects & Smart Assignments**: Kanban boards, route distance team recommendation algorithms, field timesheet approvals.
* **Mobile Operator (PWA)**: Offline-first tracking app, signature captures, Canvas-compressed photo uploads, Geofencing check-in reminders.
* **Marketplace (Preciso de / Je cherche)**: Public used furniture catalog, used item creation, buyer demand registry, auto-matching alert system.

## 3. Integrations
* **Stripe Hosted Checkout**: Payment processing and webhooks.
* **Infomaniak SMTP**: Outbound system emails (simulated in sandbox logs).
* **OpenSSL / SHA256**: Payload signing and verification for webhook signatures.

## 4. Deployment Process
Deployments are managed via Paramiko SFTP synchronization scripts (`deploy_sftp.py`) mapping the `/public_site` directory to the remote server root at Infomaniak. Database migrations are incremental scripts in `db/` executed via command line or browser triggers.

## 5. Known Limitations
* Geofencing depends on browser permission accuracy.
* Open banking ISO 20022 parsing for bank invoices is manual.
* PDF conversion for receipts is not physical (rendered in HTML).
