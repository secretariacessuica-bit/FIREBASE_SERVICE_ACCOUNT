<?php
// LIMA Solutions ERP - Phase 9 Migration Script (Projects & Timesheets)
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 9 (Módulo de Projetos e Timesheets)...\n";

try {
    // 1. Create projects table
    $sqlProjects = "CREATE TABLE IF NOT EXISTS `projects` (
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
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlProjects);
    echo "Sucesso: Tabela `projects` criada.\n";

    // 2. Create project_tasks table
    $sqlTasks = "CREATE TABLE IF NOT EXISTS `project_tasks` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlTasks);
    echo "Sucesso: Tabela `project_tasks` criada.\n";

    // 3. Create timesheets table
    $sqlTimesheets = "CREATE TABLE IF NOT EXISTS `timesheets` (
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
        `status` VARCHAR(30) DEFAULT 'Draft',
        `submitted_at` DATETIME DEFAULT NULL,
        `approved_at` DATETIME DEFAULT NULL,
        `approved_by` INT DEFAULT NULL,
        `rejected_at` DATETIME DEFAULT NULL,
        `rejection_reason` TEXT DEFAULT NULL,
        `invoice_id` INT DEFAULT NULL,
        `quote_id` INT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `deleted_at` DATETIME DEFAULT NULL,
        INDEX `idx_timesheets_company_project` (`company_id`, `project_id`),
        INDEX `idx_timesheets_user_date` (`company_id`, `user_id`, `work_date`),
        INDEX `idx_timesheets_deleted` (`company_id`, `deleted_at`),
        FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`task_id`) REFERENCES `project_tasks` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
        FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE SET NULL,
        FOREIGN KEY (`quote_id`) REFERENCES `quotes` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sqlTimesheets);
    echo "Sucesso: Tabela `timesheets` criada.\n";

    // 4. Register company modules
    $companies = $pdo->query("SELECT id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $cid) {
        // Active projects
        $stmt = $pdo->prepare("INSERT INTO company_modules (company_id, module_name, enabled) VALUES (:cid, 'projects', 1) ON DUPLICATE KEY UPDATE enabled = 1");
        $stmt->execute(['cid' => $cid]);
        
        // Active timesheets
        $stmt = $pdo->prepare("INSERT INTO company_modules (company_id, module_name, enabled) VALUES (:cid, 'timesheets', 1) ON DUPLICATE KEY UPDATE enabled = 1");
        $stmt->execute(['cid' => $cid]);
        echo "Módulos 'projects' e 'timesheets' ativados para empresa ID $cid.\n";
    }

    // Set roles permissions for projects
    $permissionsProjects = [
        ['super_admin', 1, 1],
        ['admin', 1, 1],
        ['finance', 1, 1],
        ['staff', 1, 1], // staff can create tasks/projects
        ['viewer', 1, 0]
    ];
    foreach ($permissionsProjects as $p) {
        $stmt = $pdo->prepare("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES (:role, 'projects', :cv, :ce) ON DUPLICATE KEY UPDATE can_view = :cv, can_edit = :ce");
        $stmt->execute(['role' => $p[0], 'cv' => $p[1], 'ce' => $p[2]]);
    }

    // Set roles permissions for timesheets
    $permissionsTimesheets = [
        ['super_admin', 1, 1],
        ['admin', 1, 1],
        ['finance', 1, 1],
        ['staff', 1, 1], // staff can log time
        ['viewer', 1, 0]
    ];
    foreach ($permissionsTimesheets as $p) {
        $stmt = $pdo->prepare("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES (:role, 'timesheets', :cv, :ce) ON DUPLICATE KEY UPDATE can_view = :cv, can_edit = :ce");
        $stmt->execute(['role' => $p[0], 'cv' => $p[1], 'ce' => $p[2]]);
    }

    echo "Migração da Fase 9 concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
