-- LIMA Solutions ERP - Final Multi-company Database Schema

CREATE DATABASE IF NOT EXISTS `lima_solutions` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `lima_solutions`;

-- 1. Companies Table
CREATE TABLE IF NOT EXISTS `companies` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `legal_name` VARCHAR(150) NOT NULL,
  `vat_number` VARCHAR(50) NOT NULL,
  `iban` VARCHAR(50) NOT NULL,
  `bic` VARCHAR(20) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `main_color` VARCHAR(10) DEFAULT '#007a87',
  `currency` VARCHAR(10) DEFAULT 'CHF',
  `language` VARCHAR(5) DEFAULT 'FR',
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Users Table
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` VARCHAR(30) DEFAULT 'viewer', -- super_admin, admin, staff, finance, viewer
  `active` TINYINT(1) DEFAULT 1,
  `phone` VARCHAR(30) DEFAULT NULL,
  `postal_code` VARCHAR(20) DEFAULT NULL,
  `address` VARCHAR(255) DEFAULT NULL,
  `hourly_cost` DECIMAL(10,2) DEFAULT 0.00,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. User-Companies Join Table (For multiple company associations)
CREATE TABLE IF NOT EXISTS `user_companies` (
  `user_id` INT NOT NULL,
  `company_id` INT NOT NULL,
  PRIMARY KEY (`user_id`, `company_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Clients Table (Scoped per company)
CREATE TABLE IF NOT EXISTS `clients` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `customer_code` VARCHAR(50) NOT NULL,
  `company` VARCHAR(150) DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `contact_person` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `mobile` VARCHAR(30) DEFAULT NULL,
  `whatsapp` VARCHAR(30) DEFAULT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `website` VARCHAR(150) DEFAULT NULL,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `canton` VARCHAR(100) DEFAULT NULL,
  `postal_code` VARCHAR(20) NOT NULL,
  `country` VARCHAR(100) NOT NULL DEFAULT 'Suisse',
  `vat_number` VARCHAR(50) DEFAULT NULL,
  `preferred_language` VARCHAR(5) DEFAULT 'FR',
  `preferred_currency` VARCHAR(10) DEFAULT 'CHF',
  `notes` TEXT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1, -- For Soft Delete
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_comp_client_code` (`company_id`, `customer_code`),
  INDEX `idx_comp_active` (`company_id`, `active`),
  INDEX `idx_name` (`name`),
  INDEX `idx_email` (`email`),
  INDEX `idx_customer_code` (`customer_code`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4B. CRM Leads Table (Scoped per company)
CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `origin_address` VARCHAR(255) DEFAULT NULL,
  `destination_address` VARCHAR(255) DEFAULT NULL,
  `service_date` DATE DEFAULT NULL,
  `volume_m3` DECIMAL(10,2) DEFAULT NULL,
  `status` VARCHAR(50) DEFAULT 'New', -- New, Contacted, Visit Scheduled, Proposal Sent, Negotiation, Won, Lost
  `notes` TEXT DEFAULT NULL,
  `tags` VARCHAR(255) DEFAULT NULL,
  `utm_source` VARCHAR(100) DEFAULT NULL,
  `utm_medium` VARCHAR(100) DEFAULT NULL,
  `utm_campaign` VARCHAR(100) DEFAULT NULL,
  `referer_url` TEXT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `converted_client_id` INT DEFAULT NULL,
  `source_entity_type` VARCHAR(50) DEFAULT NULL,
  `source_entity_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_leads_company` (`company_id`),
  INDEX `idx_leads_status` (`company_id`, `status`),
  INDEX `idx_leads_email` (`company_id`, `email`),
  INDEX `idx_leads_phone` (`company_id`, `phone`),
  INDEX `idx_leads_dashboard` (`company_id`, `status`, `created_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`converted_client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4C. Simulated Emails Table (For UAT mail logs)
CREATE TABLE IF NOT EXISTS `simulated_emails` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `recipient` VARCHAR(150) NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `headers` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_emails_company` (`company_id`),
  INDEX `idx_emails_recipient` (`recipient`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Invoices Table (Scoped per company)
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `quote_id` INT DEFAULT NULL,
  `invoice_number` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'Draft', -- Draft, Issued, Sent, Paid, Partially Paid, Overdue, Cancelled
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'CHF',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `internal_notes` TEXT DEFAULT NULL,
  `cancellation_reason` TEXT DEFAULT NULL,
  `document_hash` VARCHAR(64) DEFAULT NULL,
  `fiscal_snapshot` LONGTEXT DEFAULT NULL,
  `pdf_path` VARCHAR(255) DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  -- Phase 10.1: billing batch identifier for idempotency and auditability
  `billing_batch_id` VARCHAR(64) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL, -- Soft Delete
  UNIQUE KEY `idx_comp_inv_num` (`company_id`, `invoice_number`),
  INDEX `idx_invoices_company_status` (`company_id`, `status`),
  INDEX `idx_invoices_company_client` (`company_id`, `client_id`),
  INDEX `idx_invoices_quote_id` (`company_id`, `quote_id`),
  INDEX `idx_invoices_issue_date` (`company_id`, `issue_date`),
  INDEX `idx_invoices_due_date` (`company_id`, `due_date`),
  INDEX `idx_invoices_deleted` (`company_id`, `deleted_at`),
  INDEX `idx_inv_billing_batch` (`billing_batch_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Invoice Items Table
CREATE TABLE IF NOT EXISTS `invoice_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `position` INT NOT NULL DEFAULT 1,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
  `unit_id` INT DEFAULT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_rate_id` INT DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  INDEX `idx_invoice_items_invoice` (`invoice_id`),
  INDEX `idx_invoice_items_company_invoice` (`company_id`, `invoice_id`),
  INDEX `idx_invoice_items_position` (`invoice_id`, `position`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `invoice_id` INT NOT NULL,
  `payment_number` VARCHAR(50) NOT NULL,
  `payment_date` DATE NOT NULL,
  `amount` DECIMAL(12,2) NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'CHF',
  `payment_method` VARCHAR(50) NOT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `transaction_reference` VARCHAR(100) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `received_by` INT DEFAULT NULL,
  `receipt_path` VARCHAR(255) DEFAULT NULL,
  `reversed_at` DATETIME DEFAULT NULL,
  `reversed_by` INT DEFAULT NULL,
  `reversal_reason` TEXT DEFAULT NULL,
  `reversal_payment_id` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  UNIQUE KEY `idx_comp_pay_num` (`company_id`, `payment_number`),
  INDEX `idx_payments_company_id` (`company_id`),
  INDEX `idx_payments_invoice_id` (`invoice_id`),
  INDEX `idx_payments_payment_date` (`payment_date`),
  INDEX `idx_payments_payment_number` (`payment_number`),
  INDEX `idx_payments_reversal_payment_id` (`reversal_payment_id`),
  INDEX `idx_payments_reversed_at` (`reversed_at`),
  INDEX `idx_payments_deleted_at` (`deleted_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`reversed_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`reversal_payment_id`) REFERENCES `payments` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Settings Table (Scoped per company)
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL UNIQUE,
  `logo` VARCHAR(255) DEFAULT NULL,
  `main_color` VARCHAR(10) DEFAULT '#007a87',
  `default_vat` DECIMAL(5,2) DEFAULT 8.10, -- Standard Swiss VAT rate
  `default_language` VARCHAR(5) DEFAULT 'FR',
  `default_currency` VARCHAR(10) DEFAULT 'CHF',
  `date_format` VARCHAR(20) DEFAULT 'dd.mm.yyyy',
  `invoice_prefix` VARCHAR(20) DEFAULT '',
  `bank_details` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Activity Logs Table (Audit Trail)
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `company_id` INT DEFAULT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `entity` VARCHAR(50) DEFAULT NULL,
  `entity_id` INT DEFAULT NULL,
  `action` VARCHAR(255) NOT NULL, -- Login, Logout, Create Client, Edit Invoice, etc.
  `before_data` TEXT DEFAULT NULL, -- Snapshot before mutation (JSON)
  `after_data` TEXT DEFAULT NULL, -- Snapshot after mutation (JSON)
  `request_id` VARCHAR(50) DEFAULT NULL, -- Unique mutation request ID
  `reversal_payment_id` INT DEFAULT NULL, -- Linked reversed payment ID
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  INDEX `idx_act_comp_user` (`company_id`, `user_id`),
  INDEX `idx_act_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Company Modules Table (Activation of ERP modules per company)
CREATE TABLE IF NOT EXISTS `company_modules` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `module_name` VARCHAR(50) NOT NULL,
  `enabled` TINYINT(1) DEFAULT 1,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_comp_module` (`company_id`, `module_name`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Module Permissions Table (Role-based access to modules)
CREATE TABLE IF NOT EXISTS `module_permissions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `role` VARCHAR(30) NOT NULL,
  `module_name` VARCHAR(50) NOT NULL,
  `can_view` TINYINT(1) DEFAULT 0,
  `can_edit` TINYINT(1) DEFAULT 0,
  UNIQUE KEY `idx_role_module` (`role`, `module_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. Company Sequences Table (Thread-safe sequential generation)
CREATE TABLE IF NOT EXISTS `company_sequences` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `sequence_key` VARCHAR(50) NOT NULL,
  `current_value` INT NOT NULL DEFAULT 0,
  `prefix` VARCHAR(20) DEFAULT '',
  `suffix` VARCHAR(20) DEFAULT '',
  `padding` INT NOT NULL DEFAULT 6,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `idx_comp_seq_key` (`company_id`, `sequence_key`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. Company Settings Table (Advanced parameters per company)
CREATE TABLE IF NOT EXISTS `company_settings` (
  `company_id` INT PRIMARY KEY,
  `default_currency` VARCHAR(10) DEFAULT 'CHF',
  `default_tax_rate` DECIMAL(5,2) DEFAULT 8.10,
  `invoice_prefix` VARCHAR(20) DEFAULT 'INV-',
  `quote_prefix` VARCHAR(20) DEFAULT 'Q-',
  `payment_prefix` VARCHAR(20) DEFAULT 'PAY-',
  `language` VARCHAR(5) DEFAULT 'FR',
  `timezone` VARCHAR(50) DEFAULT 'Europe/Zurich',
  `date_format` VARCHAR(20) DEFAULT 'd.m.Y',
  `number_format` VARCHAR(20) DEFAULT 'dot_comma', -- dot_comma, comma_dot, apostrophe
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. Tax Rates Table
CREATE TABLE IF NOT EXISTS `tax_rates` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `rate` DECIMAL(5,2) NOT NULL,
  `active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_tax_comp_active` (`company_id`, `active`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. Currencies Table
CREATE TABLE IF NOT EXISTS `currencies` (
  `code` VARCHAR(10) PRIMARY KEY,
  `symbol` VARCHAR(10) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `decimal_places` INT NOT NULL DEFAULT 2
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. Units Table
CREATE TABLE IF NOT EXISTS `units` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `code` VARCHAR(20) NOT NULL,
  `description` VARCHAR(100) DEFAULT NULL,
  `active` TINYINT(1) DEFAULT 1,
  UNIQUE KEY `idx_comp_unit_code` (`company_id`, `code`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. Entity Timeline Table (Audit logs and event logs for entities)
CREATE TABLE IF NOT EXISTS `entity_timeline` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `entity` VARCHAR(50) NOT NULL,
  `entity_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `user_id` INT NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_timeline_entity` (`company_id`, `entity`, `entity_id`),
  INDEX `idx_timeline_created` (`created_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. Attachments Table
CREATE TABLE IF NOT EXISTS `attachments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `module` VARCHAR(50) NOT NULL,
  `entity_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `size` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attach_entity` (`company_id`, `module`, `entity_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. Notifications Table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `read_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`, `read_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. Quotes Table (Orçamentos)
CREATE TABLE IF NOT EXISTS `quotes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `quote_number` VARCHAR(50) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'Draft', -- Draft, Sent, Accepted, Rejected, Expired
  `issue_date` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `currency` VARCHAR(10) DEFAULT 'CHF',
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `notes` TEXT DEFAULT NULL,
  `internal_notes` TEXT DEFAULT NULL,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL, -- Soft Delete
  UNIQUE KEY `idx_comp_quote_num` (`company_id`, `quote_number`),
  INDEX `idx_quotes_company_status` (`company_id`, `status`),
  INDEX `idx_quotes_company_client` (`company_id`, `client_id`),
  INDEX `idx_quotes_issue_date` (`company_id`, `issue_date`),
  INDEX `idx_quotes_deleted` (`company_id`, `deleted_at`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 21. Quote Items Table
CREATE TABLE IF NOT EXISTS `quote_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `quote_id` INT NOT NULL,
  `position` INT NOT NULL DEFAULT 1,
  `description` VARCHAR(255) NOT NULL,
  `quantity` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
  `unit_id` INT DEFAULT NULL,
  `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `tax_rate_id` INT DEFAULT NULL,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  INDEX `idx_quote_items_quote` (`quote_id`),
  INDEX `idx_quote_items_company_quote` (`company_id`, `quote_id`),
  INDEX `idx_quote_items_position` (`quote_id`, `position`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`unit_id`) REFERENCES `units` (`id`) ON DELETE SET NULL,
  FOREIGN KEY (`tax_rate_id`) REFERENCES `tax_rates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 22. Projects Table
CREATE TABLE IF NOT EXISTS `projects` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `client_id` INT NOT NULL,
    `project_code` VARCHAR(50) NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `status` VARCHAR(30) DEFAULT 'Planning',
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `estimated_hours` DECIMAL(8,2) DEFAULT 0.00,
    `budget` DECIMAL(12,2) DEFAULT 0.00,
    `currency` VARCHAR(10) DEFAULT 'CHF',
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `idx_comp_prj_code` (`company_id`, `project_code`),
    INDEX `idx_projects_company_client` (`company_id`, `client_id`),
    INDEX `idx_projects_deleted` (`company_id`, `deleted_at`),
    INDEX `idx_projects_kanban` (`company_id`, `start_date`, `status`),
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 23. Project Tasks Table
CREATE TABLE IF NOT EXISTS `project_tasks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `project_id` INT NOT NULL,
    `assigned_user_id` INT DEFAULT NULL,
    `task_code` VARCHAR(50) NOT NULL,
    `title` VARCHAR(150) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `priority` VARCHAR(30) DEFAULT 'Medium',
    `status` VARCHAR(30) DEFAULT 'Todo',
    `due_date` DATE DEFAULT NULL,
    `estimated_hours` DECIMAL(8,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    UNIQUE KEY `idx_comp_tsk_code` (`company_id`, `task_code`),
    INDEX `idx_tasks_company_project` (`company_id`, `project_id`),
    INDEX `idx_tasks_deleted` (`company_id`, `deleted_at`),
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`assigned_user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 24. Timesheets Table
-- NOTE: Schema reflects the final post-migration state (Phases 9, 9.1, 10).
-- A fresh installation does NOT need to run migrate_v9_1.php separately.
CREATE TABLE IF NOT EXISTS `timesheets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `project_id` INT NOT NULL,
    `task_id` INT DEFAULT NULL,
    `user_id` INT NOT NULL,
    `work_date` DATE NOT NULL,
    `start_time` TIME DEFAULT NULL,
    `end_time` TIME DEFAULT NULL,
    `hours` DECIMAL(6,2) NOT NULL DEFAULT 0.00,
    `billable` TINYINT(1) DEFAULT 1,
    `hourly_rate` DECIMAL(10,2) DEFAULT 0.00,
    -- Financial snapshots – frozen at approval time, used exclusively for billing.
    `approved_hourly_cost` DECIMAL(10,2) DEFAULT 0.00,
    `approved_billable_rate` DECIMAL(10,2) DEFAULT 0.00,
    -- Workflow status
    `status` VARCHAR(30) DEFAULT 'Draft',  -- Draft | Submitted | Approved | Rejected
    `submitted_at` DATETIME DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `approved_by` INT DEFAULT NULL,
    `rejected_at` DATETIME DEFAULT NULL,
    `rejection_reason` TEXT DEFAULT NULL,
    -- Billing & immutability locks (Phase 10.1: billing_batch_id for idempotency)
    `invoiced_at` DATETIME DEFAULT NULL,
    `locked` TINYINT(1) DEFAULT 0,
    `billing_batch_id` VARCHAR(64) DEFAULT NULL,
    `invoice_id` INT DEFAULT NULL,
    `quote_id` INT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at` DATETIME DEFAULT NULL,
    -- Base lookup indexes
    INDEX `idx_timesheets_company_project` (`company_id`, `project_id`),
    INDEX `idx_timesheets_user_date` (`company_id`, `user_id`, `work_date`),
    INDEX `idx_timesheets_deleted` (`company_id`, `deleted_at`),
    INDEX `idx_timesheets_mobile_h` (`company_id`, `user_id`, `status`, `work_date`),
    -- Performance indexes (Phase 9.1 hardening)
    INDEX `idx_ts_comp_proj_status_date` (`company_id`, `project_id`, `status`, `work_date`),
    INDEX `idx_ts_comp_user_date` (`company_id`, `user_id`, `work_date`),
    -- Billing query optimization (Phase 10)
    INDEX `idx_ts_billing` (`company_id`, `status`, `invoice_id`, `locked`),
    FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
    FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 25. Marketplace Categories Table
CREATE TABLE IF NOT EXISTS `marketplace_categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 26. Marketplace Items Table
CREATE TABLE IF NOT EXISTS `marketplace_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) DEFAULT NULL, -- NULL indicates donation
  `location` VARCHAR(150) NOT NULL,
  `status` VARCHAR(30) DEFAULT 'Pending', -- Draft, Pending, Approved, Rejected, Archived
  `request_delivery` TINYINT(1) DEFAULT 0,
  `request_storage` TINYINT(1) DEFAULT 0,
  `rejection_reason` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_m_items_company` (`company_id`),
  INDEX `idx_m_items_client` (`client_id`),
  INDEX `idx_m_items_status` (`status`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 27. Marketplace Photos Table
CREATE TABLE IF NOT EXISTS `marketplace_photos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `original_name` VARCHAR(255) NOT NULL,
  `mime_type` VARCHAR(100) NOT NULL,
  `size` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_m_photos_item` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 28. Marketplace Interests Table
CREATE TABLE IF NOT EXISTS `marketplace_interests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `client_id` INT DEFAULT NULL, -- Null if non-authenticated public user
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `message` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_m_interests_item` (`item_id`),
  FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 29. Marketplace Demands (Preciso de) Table
CREATE TABLE IF NOT EXISTS `marketplace_demands` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `company_id` INT NOT NULL,
  `client_id` INT NOT NULL,
  `category_id` INT DEFAULT NULL,
  `keywords` VARCHAR(255) DEFAULT NULL,
  `max_price` DECIMAL(10,2) DEFAULT NULL,
  `location` VARCHAR(255) DEFAULT NULL,
  `notify_email` TINYINT(1) DEFAULT 1,
  `status` ENUM('active', 'inactive') DEFAULT 'active',
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_m_demands_company` (`company_id`),
  INDEX `idx_m_demands_client` (`client_id`),
  FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `marketplace_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 30. Marketplace Demand Matches Table (to prevent duplicate emails)
CREATE TABLE IF NOT EXISTS `marketplace_demand_matches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `demand_id` INT NOT NULL,
  `item_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_demand_item` (`demand_id`, `item_id`),
  FOREIGN KEY (`demand_id`) REFERENCES `marketplace_demands` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 31. Marketplace Reservations Table (Waitlist Queue)
CREATE TABLE IF NOT EXISTS `marketplace_reservations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `item_id` INT NOT NULL,
  `client_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `position` INT NOT NULL,
  `status` ENUM('active', 'waiting', 'expired', 'completed') DEFAULT 'waiting',
  `expires_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_m_res_item` (`item_id`),
  INDEX `idx_m_res_email` (`email`),
  FOREIGN KEY (`item_id`) REFERENCES `marketplace_items` (`id`) ON DELETE CASCADE,
  FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

