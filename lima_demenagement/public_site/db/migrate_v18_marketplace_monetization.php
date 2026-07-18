<?php
// LIMA Solutions ERP - Migrate V18 - Marketplace Monetization Fields
// CLI-only execution restriction. Can not be executed via HTTP.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'This script must be run only through the SSH/CLI backend workflow.']);
    exit(1);
}

require_once dirname(__DIR__) . '/api/v1/config.php';

try {
    // 1. Alter marketplace_items table to add request_delivery and request_storage
    $stmt = $pdo->query("SHOW COLUMNS FROM `marketplace_items` LIKE 'request_delivery'");
    $hasDelivery = $stmt->fetch();
    
    if (!$hasDelivery) {
        $pdo->exec("ALTER TABLE `marketplace_items` ADD COLUMN `request_delivery` TINYINT(1) DEFAULT 0 AFTER `status`");
        echo "Added request_delivery column to marketplace_items.\n";
    } else {
        echo "Column request_delivery already exists.\n";
    }
    
    $stmt = $pdo->query("SHOW COLUMNS FROM `marketplace_items` LIKE 'request_storage'");
    $hasStorage = $stmt->fetch();
    
    if (!$hasStorage) {
        $pdo->exec("ALTER TABLE `marketplace_items` ADD COLUMN `request_storage` TINYINT(1) DEFAULT 0 AFTER `request_delivery`");
        echo "Added request_storage column to marketplace_items.\n";
    } else {
        echo "Column request_storage already exists.\n";
    }

    // 2. Alter crm_leads table to add tags, source_entity_type, and source_entity_id
    $stmt = $pdo->query("SHOW COLUMNS FROM `crm_leads` LIKE 'tags'");
    $hasTags = $stmt->fetch();
    
    if (!$hasTags) {
        $pdo->exec("ALTER TABLE `crm_leads` ADD COLUMN `tags` VARCHAR(255) DEFAULT NULL AFTER `notes`");
        echo "Added tags column to crm_leads.\n";
    } else {
        echo "Column tags already exists in crm_leads.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `crm_leads` LIKE 'source_entity_type'");
    $hasSourceType = $stmt->fetch();
    
    if (!$hasSourceType) {
        $pdo->exec("ALTER TABLE `crm_leads` ADD COLUMN `source_entity_type` VARCHAR(50) DEFAULT NULL AFTER `converted_client_id`");
        echo "Added source_entity_type column to crm_leads.\n";
    } else {
        echo "Column source_entity_type already exists in crm_leads.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM `crm_leads` LIKE 'source_entity_id'");
    $hasSourceId = $stmt->fetch();
    
    if (!$hasSourceId) {
        $pdo->exec("ALTER TABLE `crm_leads` ADD COLUMN `source_entity_id` INT DEFAULT NULL AFTER `source_entity_type`");
        echo "Added source_entity_id column to crm_leads.\n";
    } else {
        echo "Column source_entity_id already exists in crm_leads.\n";
    }
    
    echo "Migration V18 Marketplace Monetization completed successfully.\n";

} catch (Exception $e) {
    echo "Migration V18 failed: " . $e->getMessage() . "\n";
    exit(1);
}
