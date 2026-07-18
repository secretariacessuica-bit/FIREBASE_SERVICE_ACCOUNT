# LIMA Solutions ERP – Current System State

This document snapshots the operational state of LIMA Solutions ERP as of June 2026.

## 1. Module Readiness Registry

| Module | Readiness | Test Coverage | Target Audience |
| :--- | :--- | :--- | :--- |
| **ERP Core** | Production Ready | Unified UAT | Backend Admins |
| **CRM & Leads** | Production Ready | Lead scoring & UTM | Sales / Admin |
| **Quotes & Invoices** | Production Ready | VAT & conversions | Admin / Portal |
| **Payments Integration** | Production Ready | Sandbox verified | Clients |
| **Marketplace Catalog** | Production Ready | Filter / Search | Public Users |
| **Marketplace Demands** | Production Ready | Keyword matches | Portal Clients |
| **Mobile PWA** | Production Ready | Signatures / Offline | Field Operators |

## 2. Infrastructure Metrics
* **Production Host**: Infomaniak PHP 8.4
* **Local Host**: PHP 8.1+ / MySQL 8.0+
* **Session Integrity**: 30-minute inactivities force logouts.
* **Database Size**: Highly optimized via custom SQL indexes on search-heavy fields.
