<?php
// LIMA Solutions ERP - V1 Seeder
require_once 'config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // 1. Block if users exist
    $checkUsers = $pdo->query("SELECT COUNT(*) FROM users");
    if ($checkUsers->fetchColumn() > 0) {
        http_response_code(403);
        echo "Acesso negado: Banco já possui usuários. Seeder desativado.\n";
        @unlink(__FILE__);
        exit();
    }

    $pdo->beginTransaction();

    // 2. Insert initial company: LIMA Solutions
    $stmtCompany = $pdo->prepare("INSERT INTO companies 
        (name, legal_name, vat_number, iban, bic, address, phone, email, main_color, currency, language) 
        VALUES 
        (:name, :legal_name, :vat_number, :iban, :bic, :address, :phone, :email, :main_color, :currency, :language)");
    
    $stmtCompany->execute([
        'name' => 'LIMA Solutions',
        'legal_name' => 'LIMA DE JESUS WEBERSON',
        'vat_number' => 'CHE-123.456.789 MWST', // Placeholder standard VAT
        'iban' => 'CH0800767000Z54883120',
        'bic' => 'BCVLCH2LXXX',
        'address' => 'Renens – Lausanne, Suisse',
        'phone' => '078 317 04 74',
        'email' => 'Limatransport23@hotmail.com',
        'main_color' => '#007a87',
        'currency' => 'CHF',
        'language' => 'FR'
    ]);
    
    $companyId = $pdo->lastInsertId();

    // 3. Create default settings for LIMA Solutions
    $stmtSettings = $pdo->prepare("INSERT INTO settings 
        (company_id, main_color, default_vat, default_language, default_currency, bank_details) 
        VALUES 
        (:company_id, :main_color, :default_vat, :default_language, :default_currency, :bank_details)");
    
    $stmtSettings->execute([
        'company_id' => $companyId,
        'main_color' => '#007a87',
        'default_vat' => 8.10,
        'default_language' => 'FR',
        'default_currency' => 'CHF',
        'bank_details' => "IBAN: CH08 0076 7000 Z548 8312 0\nBIC: BCVLCH2LXXX\nBanque: Banque Cantonale Vaudoise"
    ]);

    // 4. Create initial super_admin user
    $email = 'admin@limasolutions.ch';
    $password = 'lima2026';
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmtUser = $pdo->prepare("INSERT INTO users 
        (name, email, password_hash, role, active) 
        VALUES 
        (:name, :email, :hash, 'super_admin', 1)");
    
    $stmtUser->execute([
        'name' => 'Super Admin LIMA',
        'email' => $email,
        'hash' => $hash
    ]);

    $userId = $pdo->lastInsertId();

    // 5. Associate user to company
    $stmtUserCompany = $pdo->prepare("INSERT INTO user_companies (user_id, company_id) VALUES (:user_id, :company_id)");
    $stmtUserCompany->execute([
        'user_id' => $userId,
        'company_id' => $companyId
    ]);

    // 6. Activate initial modules for LIMA Solutions (dashboard, crm, invoices, quotes, settings)
    $modules = ['dashboard', 'crm', 'invoices', 'quotes', 'settings'];
    $stmtModule = $pdo->prepare("INSERT INTO company_modules (company_id, module_name, enabled) VALUES (:company_id, :module, 1)");
    foreach ($modules as $module) {
        $stmtModule->execute([
            'company_id' => $companyId,
            'module' => $module
        ]);
    }

    // 7. Seed default role permissions
    // Roles: super_admin, admin, staff, finance, viewer
    // Modules: crm, invoices, quotes, settings, dashboard, companies
    $permissions = [
        // super_admin gets all (will also bypass checks dynamically, but let's register for safety)
        ['role' => 'super_admin', 'module' => 'dashboard', 'view' => 1, 'edit' => 1],
        ['role' => 'super_admin', 'module' => 'crm', 'view' => 1, 'edit' => 1],
        ['role' => 'super_admin', 'module' => 'invoices', 'view' => 1, 'edit' => 1],
        ['role' => 'super_admin', 'module' => 'quotes', 'view' => 1, 'edit' => 1],
        ['role' => 'super_admin', 'module' => 'settings', 'view' => 1, 'edit' => 1],
        ['role' => 'super_admin', 'module' => 'companies', 'view' => 1, 'edit' => 1],

        // admin gets all
        ['role' => 'admin', 'module' => 'dashboard', 'view' => 1, 'edit' => 1],
        ['role' => 'admin', 'module' => 'crm', 'view' => 1, 'edit' => 1],
        ['role' => 'admin', 'module' => 'invoices', 'view' => 1, 'edit' => 1],
        ['role' => 'admin', 'module' => 'quotes', 'view' => 1, 'edit' => 1],
        ['role' => 'admin', 'module' => 'settings', 'view' => 1, 'edit' => 1],
        ['role' => 'admin', 'module' => 'companies', 'view' => 1, 'edit' => 0],

        // staff can read/write crm, read/write invoices, but no settings or companies
        ['role' => 'staff', 'module' => 'dashboard', 'view' => 1, 'edit' => 1],
        ['role' => 'staff', 'module' => 'crm', 'view' => 1, 'edit' => 1],
        ['role' => 'staff', 'module' => 'invoices', 'view' => 1, 'edit' => 1],
        ['role' => 'staff', 'module' => 'quotes', 'view' => 1, 'edit' => 1],
        ['role' => 'staff', 'module' => 'settings', 'view' => 0, 'edit' => 0],
        ['role' => 'staff', 'module' => 'companies', 'view' => 0, 'edit' => 0],

        // finance can read/write invoices, view crm, no settings or companies
        ['role' => 'finance', 'module' => 'dashboard', 'view' => 1, 'edit' => 1],
        ['role' => 'finance', 'module' => 'crm', 'view' => 1, 'edit' => 0],
        ['role' => 'finance', 'module' => 'invoices', 'view' => 1, 'edit' => 1],
        ['role' => 'finance', 'module' => 'quotes', 'view' => 1, 'edit' => 1],
        ['role' => 'finance', 'module' => 'settings', 'view' => 0, 'edit' => 0],
        ['role' => 'finance', 'module' => 'companies', 'view' => 0, 'edit' => 0],

        // viewer can read dashboard, crm, invoices, but no edit rights
        ['role' => 'viewer', 'module' => 'dashboard', 'view' => 1, 'edit' => 0],
        ['role' => 'viewer', 'module' => 'crm', 'view' => 1, 'edit' => 0],
        ['role' => 'viewer', 'module' => 'invoices', 'view' => 1, 'edit' => 0],
        ['role' => 'viewer', 'module' => 'quotes', 'view' => 1, 'edit' => 0],
        ['role' => 'viewer', 'module' => 'settings', 'view' => 0, 'edit' => 0],
        ['role' => 'viewer', 'module' => 'companies', 'view' => 0, 'edit' => 0]
    ];

    $stmtPerm = $pdo->prepare("INSERT INTO module_permissions (role, module_name, can_view, can_edit) VALUES (:role, :module, :view, :edit)");
    foreach ($permissions as $p) {
        $stmtPerm->execute([
            'role' => $p['role'],
            'module' => $p['module'],
            'view' => $p['view'],
            'edit' => $p['edit']
        ]);
    }

    // 8. Seed currencies (Code, Symbol, Name, Decimal Places)
    $currencies = [
        ['code' => 'CHF', 'symbol' => 'CHF', 'name' => 'Franc Suisse', 'decimals' => 2],
        ['code' => 'EUR', 'symbol' => '€', 'name' => 'Euro', 'decimals' => 2],
        ['code' => 'USD', 'symbol' => '$', 'name' => 'US Dollar', 'decimals' => 2],
        ['code' => 'GBP', 'symbol' => '£', 'name' => 'British Pound', 'decimals' => 2]
    ];
    $stmtCurr = $pdo->prepare("INSERT INTO currencies (code, symbol, name, decimal_places) VALUES (:code, :symbol, :name, :decimals)");
    foreach ($currencies as $c) {
        $stmtCurr->execute($c);
    }

    // 9. Seed tax rates (VAT) for LIMA Solutions (0%, 2.6%, 3.8%, 8.1%)
    $taxes = [
        ['name' => 'Exonéré (0%)', 'rate' => 0.00],
        ['name' => 'Taux Réduit (2.6%)', 'rate' => 2.60],
        ['name' => 'Taux Spécial (3.8%)', 'rate' => 3.80],
        ['name' => 'Taux Normal (8.1%)', 'rate' => 8.10]
    ];
    $stmtTax = $pdo->prepare("INSERT INTO tax_rates (company_id, name, rate, active) VALUES (:company_id, :name, :rate, 1)");
    foreach ($taxes as $t) {
        $stmtTax->execute([
            'company_id' => $companyId,
            'name' => $t['name'],
            'rate' => $t['rate']
        ]);
    }

    // 10. Seed units of measure for LIMA Solutions (pcs, h, kg, m², m³, day, month)
    $units = [
        ['code' => 'pcs', 'desc' => 'Pièces / Objets'],
        ['code' => 'h', 'desc' => 'Heures de travail'],
        ['code' => 'kg', 'desc' => 'Poids en Kilogrammes'],
        ['code' => 'm²', 'desc' => 'Surface en Mètre Carré'],
        ['code' => 'm³', 'desc' => 'Volume en Mètre Cube'],
        ['code' => 'day', 'desc' => 'Jours'],
        ['code' => 'month', 'desc' => 'Mois']
    ];
    $stmtUnit = $pdo->prepare("INSERT INTO units (company_id, code, description, active) VALUES (:company_id, :code, :desc, 1)");
    foreach ($units as $u) {
        $stmtUnit->execute([
            'company_id' => $companyId,
            'code' => $u['code'],
            'desc' => $u['desc']
        ]);
    }

    // 11. Seed initial company sequences (CLI, Q, INV, PAY)
    $sequences = [
        ['key' => 'CLI', 'prefix' => 'CLI-', 'padding' => 6],
        ['key' => 'Q', 'prefix' => 'Q-', 'padding' => 6],
        ['key' => 'INV', 'prefix' => 'INV-', 'padding' => 6],
        ['key' => 'PAY', 'prefix' => 'PAY-', 'padding' => 6]
    ];
    $stmtSeq = $pdo->prepare("INSERT INTO company_sequences (company_id, sequence_key, current_value, prefix, padding) VALUES (:company_id, :key, 0, :prefix, :padding)");
    foreach ($sequences as $s) {
        $stmtSeq->execute([
            'company_id' => $companyId,
            'key' => $s['key'],
            'prefix' => $s['prefix'],
            'padding' => $s['padding']
        ]);
    }

    // 12. Seed company settings for LIMA Solutions
    $stmtSettings2 = $pdo->prepare("INSERT INTO company_settings (company_id, default_currency, default_tax_rate, invoice_prefix, quote_prefix, payment_prefix, language, timezone, date_format, number_format) 
        VALUES (:company_id, 'CHF', 8.10, 'INV-', 'Q-', 'PAY-', 'FR', 'Europe/Zurich', 'd.m.Y', 'dot_comma')");
    $stmtSettings2->execute(['company_id' => $companyId]);

    $pdo->commit();

    echo "Sucesso!\n";
    echo "LIMA Solutions ERP inicializado.\n";
    echo "Empresa criada: LIMA Solutions (ID: " . $companyId . ")\n";
    echo "Usuário Administrador de Teste Criado:\n";
    echo "Login: " . $email . "\n";
    echo "Senha: " . $password . "\n";
    echo "Perfil: super_admin\n\n";
    echo "ATENÇÃO: O arquivo seed.php foi deletado automaticamente por segurança.\n";

    // Self-delete
    @unlink(__FILE__);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo "Erro ao semear banco ERP: " . $e->getMessage() . "\n";
}
