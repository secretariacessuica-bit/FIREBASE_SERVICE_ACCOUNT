<?php
// One-shot production schema sync (payments + invoices)
header('Content-Type: text/plain; charset=utf-8');
require_once '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/private_lima/config.php';

$pdo = new PDO(
    'mysql:host=' . SECURE_DB_HOST . ';dbname=' . SECURE_DB_NAME . ';charset=utf8mb4',
    SECURE_DB_USER,
    SECURE_DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function hasColumn(PDO $pdo, string $table, string $column): bool {
    $stmt = $pdo->prepare('SHOW COLUMNS FROM `' . $table . '` LIKE :col');
    $stmt->execute(['col' => $column]);
    return (bool)$stmt->fetch();
}

function runStep(PDO $pdo, string $label, callable $fn): void {
    try {
        $fn();
        echo "OK: $label\n";
    } catch (Throwable $e) {
        echo "WARN [$label]: " . $e->getMessage() . "\n";
    }
}

echo "=== LIMA production schema sync ===\n\n";

// --- Payments ---
runStep($pdo, 'payments.currency', function () use ($pdo) {
    if (!hasColumn($pdo, 'payments', 'currency')) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `currency` VARCHAR(10) DEFAULT 'CHF' AFTER `amount`");
    }
});

runStep($pdo, 'payments.payment_number', function () use ($pdo) {
    if (!hasColumn($pdo, 'payments', 'payment_number')) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `payment_number` VARCHAR(50) NULL AFTER `company_id`");
    }
    $pdo->exec("UPDATE `payments` SET `payment_number` = CONCAT('PAY-', LPAD(id, 6, '0')) WHERE `payment_number` IS NULL OR `payment_number` = ''");
});

runStep($pdo, 'payments.payment_method', function () use ($pdo) {
    if (!hasColumn($pdo, 'payments', 'payment_method')) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `payment_method` VARCHAR(50) NULL AFTER `currency`");
    }
    if (hasColumn($pdo, 'payments', 'method')) {
        $pdo->exec("UPDATE `payments` SET `payment_method` = `method` WHERE `payment_method` IS NULL OR `payment_method` = ''");
        $pdo->exec('ALTER TABLE `payments` DROP COLUMN `method`');
    }
    $pdo->exec("UPDATE `payments` SET `payment_method` = 'Other' WHERE `payment_method` IS NULL OR `payment_method` = ''");
});

runStep($pdo, 'payments.received_by', function () use ($pdo) {
    if (!hasColumn($pdo, 'payments', 'received_by') && hasColumn($pdo, 'payments', 'created_by')) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `received_by` INT DEFAULT NULL AFTER `notes`");
        $pdo->exec('UPDATE `payments` SET `received_by` = `created_by` WHERE `received_by` IS NULL');
    }
});

runStep($pdo, 'payments.transaction_reference', function () use ($pdo) {
    if (!hasColumn($pdo, 'payments', 'transaction_reference')) {
        $pdo->exec("ALTER TABLE `payments` ADD COLUMN `transaction_reference` VARCHAR(100) DEFAULT NULL AFTER `reference`");
    }
});

runStep($pdo, 'payments.reversal columns', function () use ($pdo) {
    foreach ([
        'reversed_by' => "INT DEFAULT NULL AFTER `reversed_at`",
        'reversal_reason' => "TEXT DEFAULT NULL AFTER `reversed_by`",
        'reversal_payment_id' => "INT DEFAULT NULL AFTER `reversal_reason`",
    ] as $col => $def) {
        if (!hasColumn($pdo, 'payments', $col)) {
            $pdo->exec("ALTER TABLE `payments` ADD COLUMN `$col` $def");
        }
    }
});

// --- Invoices ---
runStep($pdo, 'invoices.tax_total', function () use ($pdo) {
    if (!hasColumn($pdo, 'invoices', 'tax_total')) {
        $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `tax_total` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`");
    }
    if (hasColumn($pdo, 'invoices', 'tax_amount')) {
        $pdo->exec('UPDATE `invoices` SET `tax_total` = `tax_amount` WHERE `tax_total` = 0');
    }
});

runStep($pdo, 'invoices.discount columns', function () use ($pdo) {
    if (!hasColumn($pdo, 'invoices', 'discount_amount')) {
        $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`");
    }
    if (!hasColumn($pdo, 'invoices', 'discount_percent')) {
        $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `discount_percent` DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `discount_amount`");
    }
    if (!hasColumn($pdo, 'invoices', 'internal_notes')) {
        $pdo->exec("ALTER TABLE `invoices` ADD COLUMN `internal_notes` TEXT DEFAULT NULL AFTER `notes`");
    }
});

// --- Invoice items ---
runStep($pdo, 'invoice_items extended columns', function () use ($pdo) {
    $alters = [
        'company_id' => "INT NOT NULL DEFAULT 1 AFTER `id`",
        'position' => "INT NOT NULL DEFAULT 1 AFTER `invoice_id`",
        'unit_id' => "INT DEFAULT NULL AFTER `quantity`",
        'discount_percent' => "DECIMAL(5,2) NOT NULL DEFAULT 0.00 AFTER `unit_price`",
        'tax_rate_id' => "INT DEFAULT NULL AFTER `discount_percent`",
        'subtotal' => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `tax_rate_id`",
        'tax_amount' => "DECIMAL(12,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`",
    ];
    foreach ($alters as $col => $def) {
        if (!hasColumn($pdo, 'invoice_items', $col)) {
            $pdo->exec("ALTER TABLE `invoice_items` ADD COLUMN `$col` $def");
        }
    }
    if (hasColumn($pdo, 'invoice_items', 'sort_order') && hasColumn($pdo, 'invoice_items', 'position')) {
        $pdo->exec('UPDATE `invoice_items` SET `position` = `sort_order` WHERE `position` = 1');
    }
    if (hasColumn($pdo, 'invoice_items', 'tax_rate') && hasColumn($pdo, 'invoice_items', 'subtotal')) {
        $pdo->exec('UPDATE `invoice_items` SET `subtotal` = `quantity` * `unit_price` WHERE `subtotal` = 0');
    }
});

echo "\nDone.\n";
