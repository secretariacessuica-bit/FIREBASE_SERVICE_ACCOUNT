<?php
// LIMA Solutions ERP - Automated UAT Test Suite for Leads, CRM, and Emails
// Run via CLI/SSH to verify database operations, template formatting, and file logging.
//
// Usage: php run_uat_tests.php

require_once dirname(__DIR__) . '/api/v1/config.php';
require_once dirname(__DIR__) . '/helpers/EmailHelper.php';
require_once dirname(__DIR__) . '/modules/crm/model/Lead.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== LIMA solutions ERP - Automated UAT Test Suite ===\n\n";

try {
    $leadModel = new Lead($pdo);
    
    // Clean up any old test data first
    $pdo->exec("DELETE FROM crm_leads WHERE name LIKE '%UAT Test%'");
    $pdo->exec("DELETE FROM clients WHERE name LIKE '%UAT Test%'");
    $pdo->exec("DELETE FROM simulated_emails WHERE recipient = 'uat.client@example.com' OR recipient = 'info@limasolutions.ch'");
    
    $success = true;

    // ─── Test 1: Lead Creation and Creation Emails ───────────────────────────
    echo "Test 1: Criando Lead de Teste e enviando e-mails de criação...";
    $leadData = [
        'company_id' => 1,
        'name' => 'John Doe UAT Test',
        'email' => 'uat.client@example.com',
        'phone' => '+41 78 555 12 34',
        'origin_address' => '1000 Lausanne',
        'destination_address' => '1200 Genève',
        'service_date' => '2026-09-01',
        'volume_m3' => 18.5,
        'notes' => 'Observações de teste UAT.',
        'utm_source' => 'google_test',
        'utm_medium' => 'cpc',
        'utm_campaign' => 'uat_campaign',
        'referer_url' => 'https://test-referer.com',
        'ip_address' => '127.0.0.1'
    ];

    $leadId = $leadModel->create($leadData);
    if ($leadId) {
        // Trigger lead_confirmation and internal_lead_alert templates
        $replacements = [
            'lead_id' => $leadId,
            'name' => $leadData['name'],
            'email' => $leadData['email'],
            'phone' => $leadData['phone'],
            'service_date' => $leadData['service_date'],
            'volume_m3' => $leadData['volume_m3'],
            'origin_address' => $leadData['origin_address'],
            'destination_address' => $leadData['destination_address'],
            'notes' => $leadData['notes'],
            'utm_source' => $leadData['utm_source'],
            'utm_medium' => $leadData['utm_medium'],
            'utm_campaign' => $leadData['utm_campaign'],
            'referer_url' => $leadData['referer_url'],
            'ip_address' => $leadData['ip_address'],
            'company_name' => 'Lima Déménagement'
        ];
        
        $sentConf = EmailHelper::sendTemplateEmail(1, $leadData['email'], 'lead_confirmation', $replacements, $pdo);
        $sentAlert = EmailHelper::sendTemplateEmail(1, 'info@limasolutions.ch', 'internal_lead_alert', $replacements, $pdo);
        
        if ($sentConf && $sentAlert) {
            echo " [OK] ID: $leadId\n";
        } else {
            echo " [FALHOU] (Erro no envio de e-mails de criação)\n";
            $success = false;
        }
    } else {
        echo " [FALHOU] (Erro ao criar Lead)\n";
        $success = false;
    }

    // ─── Test 2: Pipeline Status Change and Email ────────────────────────────
    echo "Test 2: Alterando Status da Lead e enviando status change...";
    $lead = $leadModel->getById($leadId, 1);
    $oldStatus = $lead['status'];
    $newStatus = 'Contacted';
    
    $statusUpdated = $leadModel->updateStatus($leadId, $newStatus, 1);
    if ($statusUpdated) {
        $sentStatus = EmailHelper::sendTemplateEmail(1, 'info@limasolutions.ch', 'pipeline_status_change', [
            'lead_id' => $leadId,
            'lead_name' => $lead['name'],
            'old_status' => $oldStatus,
            'new_status' => $newStatus
        ], $pdo);
        
        if ($sentStatus) {
            echo " [OK]\n";
        } else {
            echo " [FALHOU] (Erro ao enviar email de status)\n";
            $success = false;
        }
    } else {
        echo " [FALHOU] (Erro ao atualizar status)\n";
        $success = false;
    }

    // ─── Test 3: Conversion to Client (Unique Case) & Welcome Emails ──────────
    echo "Test 3: Convertendo Lead para Novo Cliente e enviando e-mails...";
    $convResult = $leadModel->convertToClient($leadId, 1, 1); // 1 is system admin user ID
    
    if ($convResult['success'] && !$convResult['is_duplicate']) {
        $clientId = $convResult['client_id'];
        
        // Fetch created client code
        $stmtClient = $pdo->prepare("SELECT customer_code FROM clients WHERE id = :id");
        $stmtClient->execute(['id' => $clientId]);
        $customerCode = $stmtClient->fetchColumn();
        
        // Trigger conversion emails
        $sentWelcome = EmailHelper::sendTemplateEmail(1, $leadData['email'], 'client_welcome', [
            'name' => $leadData['name'],
            'customer_code' => $customerCode,
            'company_name' => 'Lima Déménagement'
        ], $pdo);
        
        $sentConvAlert = EmailHelper::sendTemplateEmail(1, 'info@limasolutions.ch', 'internal_conversion_alert', [
            'lead_name' => $leadData['name'],
            'lead_email' => $leadData['email'],
            'customer_code' => $customerCode,
            'client_id' => $clientId,
            'is_duplicate' => 'Nouveau dossier client unique',
            'converted_by_user_id' => 1
        ], $pdo);
        
        if ($sentWelcome && $sentConvAlert) {
            echo " [OK] Cliente criado com código: $customerCode\n";
        } else {
            echo " [FALHOU] (Erro no envio dos e-mails de conversão)\n";
            $success = false;
        }
    } else {
        echo " [FALHOU] Msg: " . ($convResult['message'] ?? 'Erro desconhecido') . "\n";
        $success = false;
    }

    // ─── Test 4: Conversion with Duplicate Check ─────────────────────────────
    echo "Test 4: Validando Deteção de Duplicados e associação...";
    // Create another lead with same email
    $dupLeadId = $leadModel->create($leadData);
    $convDupResult = $leadModel->convertToClient($dupLeadId, 1, 1);

    if ($convDupResult['success'] && $convDupResult['is_duplicate'] && $convDupResult['client_id'] == $clientId) {
        echo " [OK] Duplicado detetado e associado ao cliente ID $clientId corretamente.\n";
    } else {
        echo " [FALHOU] (is_duplicate: " . ($convDupResult['is_duplicate'] ?? 'N/A') . ", client_id: " . ($convDupResult['client_id'] ?? 'N/A') . ")\n";
        $success = false;
    }

    // ─── Test 5: Verify Lead Converted References ────────────────────────────
    echo "Test 5: Validando Estado dos Leads pós-conversão...";
    $leadA = $leadModel->getById($leadId, 1);
    $leadB = $leadModel->getById($dupLeadId, 1);

    if ($leadA['status'] === 'Won' && $leadA['converted_client_id'] == $clientId &&
        $leadB['status'] === 'Won' && $leadB['converted_client_id'] == $clientId) {
        echo " [OK]\n";
    } else {
        echo " [FALHOU] (Lead A status: " . $leadA['status'] . ", Link: " . $leadA['converted_client_id'] . ")\n";
        $success = false;
    }

    // ─── Test 6: Verify Placeholders, Google Maps, and Log Files ──────────────
    echo "Test 6: Validando Integridade do Conteúdo dos E-mails e Ficheiro de Log...\n";
    
    // A. Check for raw placeholder tags (e.g. {placeholder}) in the DB
    $stmtEmails = $pdo->prepare("SELECT id, subject, body FROM simulated_emails WHERE recipient IN ('uat.client@example.com', 'info@limasolutions.ch')");
    $stmtEmails->execute();
    $simulatedEmails = $stmtEmails->fetchAll();
    
    $rawPlaceholdersFound = 0;
    foreach ($simulatedEmails as $em) {
        if (preg_match('/\{[a-zA-Z0-9_]+\}/', $em['body']) || preg_match('/\{[a-zA-Z0-9_]+\}/', $em['subject'])) {
            $rawPlaceholdersFound++;
            echo "   [ERRO] Placeholder cru encontrado no e-mail ID {$em['id']} (Assunto: {$em['subject']})\n";
        }
    }
    
    if ($rawPlaceholdersFound === 0) {
        echo "   [OK] Nenhum placeholder cru encontrado no assunto ou corpo de nenhum e-mail simulado.\n";
    } else {
        echo "   [FALHOU] Detetados e-mails com placeholders crus.\n";
        $success = false;
    }

    // B. Check Google Maps Links inside internal_lead_alert
    $stmtAlert = $pdo->prepare("SELECT body FROM simulated_emails WHERE recipient = 'info@limasolutions.ch' AND subject LIKE '%Nouvelle lead%' LIMIT 1");
    $stmtAlert->execute();
    $alertBody = $stmtAlert->fetchColumn();
    
    $expectedOriginLink = 'https://www.google.com/maps/search/?api=1&query=1000+Lausanne';
    $expectedDestLink = 'https://www.google.com/maps/search/?api=1&query=1200+Gen%C3%A8ve';
    
    $hasOriginLink = strpos($alertBody, $expectedOriginLink) !== false;
    $hasDestLink = strpos($alertBody, $expectedDestLink) !== false;
    
    if ($hasOriginLink && $hasDestLink) {
        echo "   [OK] Links Google Maps gerados e codificados corretamente no alerta interno.\n";
    } else {
        echo "   [FALHOU] Links Google Maps ausentes ou codificados incorretamente no alerta interno.\n";
        $success = false;
    }

    // C. Check that physical log file exists and contains the expected structured blocks
    $privatePath = dirname(__DIR__) . '/../private_lima';
    if (!is_dir($privatePath)) {
        $privatePath = dirname(__DIR__) . '/../private';
    }
    $logFile = $privatePath . '/logs/emails.log';
    
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        
        $hasLogConfirmation = strpos($logContent, 'Confirmation de votre demande de devis') !== false;
        $hasLogAlert = strpos($logContent, 'Nouvelle lead commerciale reçue') !== false;
        $hasLogPipeline = strpos($logContent, 'Mise à jour du statut du lead') !== false;
        $hasLogWelcome = strpos($logContent, 'Bienvenue chez nous') !== false;
        $hasLogConversion = strpos($logContent, 'Lead convertie en client') !== false;
        
        if ($hasLogConfirmation && $hasLogAlert && $hasLogPipeline && $hasLogWelcome && $hasLogConversion) {
            echo "   [OK] O ficheiro emails.log contém todos os blocos estruturados de teste UAT.\n";
        } else {
            echo "   [FALHOU] O ficheiro emails.log não contém todos os blocos estruturados esperados.\n";
            $success = false;
        }
    } else {
        echo "   [FALHOU] Ficheiro de log físico não encontrado em: $logFile\n";
        $success = false;
    }

    // ─── Clean up test data ──────────────────────────────────────────────────
    echo "Limpando massa de dados de teste...";
    $pdo->exec("DELETE FROM crm_leads WHERE name LIKE '%UAT Test%'");
    $pdo->exec("DELETE FROM clients WHERE name LIKE '%UAT Test%'");
    $pdo->exec("DELETE FROM simulated_emails WHERE recipient = 'uat.client@example.com' OR recipient = 'info@info.ch' OR recipient = 'info@limasolutions.ch'");
    echo " [OK]\n";

    echo "\n=== RESULTADO FINAL: " . ($success ? "TUDO APROVADO [PASSED]" : "FALHAS ENCONTRADAS [FAILED]") . " ===\n";

} catch (Exception $e) {
    echo "\n[ERRO FATAL] " . $e->getMessage() . "\n";
    exit(1);
}
