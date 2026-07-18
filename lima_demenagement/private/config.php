<?php
// LIMA Solutions ERP - Private Credentials File
// Located outside the public root for security.

// Read from Environment Variables (optional, for easy hosting migration)
$dbHost = getenv('DB_HOST') ?: 'localhost';
$dbName = getenv('DB_NAME') ?: 'lima_solutions';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

// Export configuration constants
define('SECURE_DB_HOST', $dbHost);
define('SECURE_DB_NAME', $dbName);
define('SECURE_DB_USER', $dbUser);
define('SECURE_DB_PASS', $dbPass);

// SMTP configuration for real email sending
define('EMAIL_MODE', 'smtp'); // Options: 'simulated' or 'smtp'
define('SMTP_HOST', 'mail.infomaniak.com');
define('SMTP_PORT', 587); // Options: 587 (TLS/STARTTLS) or 465 (SSL)
define('SMTP_USER', 'info@limasolutions.ch');
define('SMTP_PASS', 'Ces124578.');
define('SMTP_SECURE', 'tls'); // Options: 'tls', 'ssl', 'none'
define('SMTP_FROM', 'info@limasolutions.ch');
define('SMTP_FROM_NAME', 'Lima Déménagement');

// Stripe Test Mode Keys (Only loaded from private configuration)
define('STRIPE_TEST_SECRET_KEY', 'sk_test_mock_key_51N...');
define('STRIPE_TEST_WEBHOOK_SECRET', 'whsec_mock_webhook_secret_...');

// Application Environment: 'local', 'staging', 'production'
define('APP_ENV', 'local');
