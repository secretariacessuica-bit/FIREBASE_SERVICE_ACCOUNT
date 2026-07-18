<?php
// LIMA Solutions ERP - Entity Timeline Tracker Helper

/**
 * Creates a record in entity_timeline table representing a business transaction event.
 * 
 * @param int $companyId The active company ID
 * @param string $module Module key (e.g. 'crm', 'invoices', 'quotes')
 * @param string $entity Business entity (e.g. 'clients', 'invoices', 'quotes')
 * @param int $entityId Database ID of the target record
 * @param string $action Action key (e.g. 'created', 'sent_by_email', 'status_paid')
 * @param int $userId ID of the active user performing the action
 * @param string $description Optional text description or notes
 * @param PDO $pdo PDO database instance
 * @return bool True on success, false on failure
 */
function logEntityEvent($companyId, $module, $entity, $entityId, $action, $userId, $description, $pdo) {
    if (empty($companyId) || empty($module) || empty($entity) || empty($entityId) || empty($userId)) {
        return false;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO entity_timeline 
            (company_id, module, entity, entity_id, action, user_id, description) 
            VALUES 
            (:cid, :mod, :ent, :ent_id, :act, :uid, :desc)");
            
        return $stmt->execute([
            'cid' => (int)$companyId,
            'mod' => htmlspecialchars(trim($module), ENT_QUOTES, 'UTF-8'),
            'ent' => htmlspecialchars(trim($entity), ENT_QUOTES, 'UTF-8'),
            'ent_id' => (int)$entityId,
            'act' => htmlspecialchars(trim($action), ENT_QUOTES, 'UTF-8'),
            'uid' => (int)$userId,
            'desc' => $description ? htmlspecialchars(trim($description), ENT_QUOTES, 'UTF-8') : null
        ]);
    } catch (Exception $e) {
        error_log("Failed to log entity timeline event: " . $e->getMessage());
        return false;
    }
}
