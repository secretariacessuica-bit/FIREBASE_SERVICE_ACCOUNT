<?php
// LIMA Solutions ERP - Phase 8 Migration Script (Reports & BI Module)
require_once __DIR__ . '/../api/v1/config.php';

header('Content-Type: text/plain; charset=utf-8');

echo "Iniciando migração da Fase 8 (Módulo de Relatórios e BI)...\n";

try {
    // 1. Register module seeds
    // Enable module 'reports' for all existing companies
    $companies = $pdo->query("SELECT id FROM companies")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($companies as $cid) {
        try {
            $stmt = $pdo->prepare("INSERT INTO company_modules (company_id, module_name, enabled) VALUES (:cid, 'reports', 1) ON DUPLICATE KEY UPDATE enabled = 1");
            $stmt->execute(['cid' => $cid]);
            echo "Módulo 'reports' ativado para empresa ID $cid.\n";
        } catch (Exception $e) {
            echo "Erro ao ativar módulo para empresa $cid: " . $e->getMessage() . "\n";
        }
    }

    // Set roles permissions for reports
    $permissions = [
        ['super_admin', 1, 1],
        ['admin', 1, 1],
        ['finance', 1, 1],
        ['staff', 1, 0],
        ['viewer', 1, 0]
    ];
    foreach ($permissions as $p) {
        try {
            $stmt = $pdo->prepare("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES (:role, 'reports', :cv, :ce) ON DUPLICATE KEY UPDATE can_view = :cv, can_edit = :ce");
            $stmt->execute(['role' => $p[0], 'cv' => $p[1], 'ce' => $p[2]]);
            echo "Permissões de 'reports' configuradas para o papel {$p[0]}.\n";
        } catch (Exception $e) {
            echo "Erro ao inserir permissão para papel {$p[0]}: " . $e->getMessage() . "\n";
        }
    }

    echo "Migração do Módulo de Relatórios (Fase 8) concluída com sucesso!\n";

} catch (Exception $e) {
    echo "Erro catastrófico na migração: " . $e->getMessage() . "\n";
}
