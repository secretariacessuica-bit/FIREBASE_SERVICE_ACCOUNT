<?php
// LIMA Solutions ERP - Module Activation and Permissions Helper

/**
 * Checks if a specific module is enabled for the active company.
 * 
 * @param string $moduleName Name of the module (e.g. 'crm', 'invoices', 'settings')
 * @param int $companyId The active company ID
 * @param PDO $pdo The PDO connection instance
 * @return bool True if enabled, false otherwise
 */
function isModuleEnabled($moduleName, $companyId, $pdo) {
    if (empty($companyId)) {
        return false;
    }
    
    // The "dashboard" is a core module, always enabled
    if ($moduleName === 'dashboard') {
        return true;
    }

    try {
        $stmt = $pdo->prepare("SELECT enabled FROM company_modules WHERE company_id = :cid AND module_name = :mod LIMIT 1");
        $stmt->execute(['cid' => $companyId, 'mod' => $moduleName]);
        $result = $stmt->fetch();
        if ($result && ((int)$result['enabled'] === 1)) {
            return true;
        }
        
        // Fallback for quotes -> invoices
        if ($moduleName === 'quotes') {
            return isModuleEnabled('invoices', $companyId, $pdo);
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Checks if a specific role has permission for a given module.
 * 
 * @param string $role The user role (e.g., 'super_admin', 'staff')
 * @param string $moduleName The name of the module
 * @param string $permissionType Either 'view' or 'edit'
 * @param PDO $pdo The PDO connection instance
 * @return bool True if allowed, false otherwise
 */
function hasModulePermission($role, $moduleName, $permissionType, $pdo) {
    // super_admin has master access and bypasses checking
    if ($role === 'super_admin') {
        return true;
    }

    $field = ($permissionType === 'edit') ? 'can_edit' : 'can_view';

    try {
        $stmt = $pdo->prepare("SELECT $field FROM module_permissions WHERE role = :role AND module_name = :mod LIMIT 1");
        $stmt->execute(['role' => $role, 'mod' => $moduleName]);
        $result = $stmt->fetch();
        if ($result) {
            return ((int)$result[$field] === 1);
        }
        
        // Fallback for quotes -> invoices
        if ($moduleName === 'quotes') {
            return hasModulePermission($role, 'invoices', $permissionType, $pdo);
        }
        
        return false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Validates access constraints for APIs or Pages.
 * Terminates process with a 403 response if constraints fail.
 * 
 * @param string $moduleName Name of the module to validate
 * @param string $role The user role
 * @param int $companyId The active company ID
 * @param string $permissionType 'view' or 'edit'
 * @param PDO $pdo PDO instance
 */
function enforceModuleAccess($moduleName, $role, $companyId, $permissionType, $pdo) {
    if (!isModuleEnabled($moduleName, $companyId, $pdo)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => "Erreur: Le module '$moduleName' est désactivé pour cette entreprise."
        ]);
        exit();
    }

    if (!hasModulePermission($role, $moduleName, $permissionType, $pdo)) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => "Erreur: Droits d'accès insuffisants pour le module '$moduleName'."
        ]);
        exit();
    }
}

/**
 * Creates a record in activity_logs table for audit trail.
 */
function logActivity($userId, $companyId, $module, $entity, $entityId, $action, $pdo, $beforeData = null, $afterData = null, $requestId = null, $reversalPaymentId = null) {
    try {
        $beforeJson = $beforeData ? (is_string($beforeData) ? $beforeData : json_encode($beforeData)) : null;
        $afterJson = $afterData ? (is_string($afterData) ? $afterData : json_encode($afterData)) : null;

        $stmt = $pdo->prepare("INSERT INTO activity_logs 
            (user_id, company_id, module, entity, entity_id, action, before_data, after_data, request_id, reversal_payment_id, ip_address, user_agent) 
            VALUES 
            (:uid, :cid, :mod, :ent, :ent_id, :act, :before, :after, :req_id, :reversal_pay_id, :ip, :ua)");
        $stmt->execute([
            'uid' => $userId,
            'cid' => $companyId,
            'mod' => $module,
            'ent' => $entity,
            'ent_id' => $entityId,
            'act' => $action,
            'before' => $beforeJson,
            'after' => $afterJson,
            'req_id' => $requestId,
            'reversal_pay_id' => $reversalPaymentId,
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '127.0.0.1',
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown'
        ]);
    } catch (Exception $e) {
        // Silently fail or log to error log to avoid interrupting the main flow
        error_log("Failed to log activity: " . $e->getMessage());
    }
}
