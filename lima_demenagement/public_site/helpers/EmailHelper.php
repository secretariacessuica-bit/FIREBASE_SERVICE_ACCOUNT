<?php
// LIMA Solutions ERP - Central Email Helper (Supports SMTP & Simulation)

class EmailHelper {
    /**
     * Simulates sending an email by logging it to the database and a log file.
     */
    public static function sendSimulatedEmail($companyId, $recipient, $subject, $body, $additionalHeaders, $pdo) {
        try {
            $headersStr = $additionalHeaders ? json_encode($additionalHeaders) : null;
            
            // 1. Insert into simulated_emails table
            $stmt = $pdo->prepare("INSERT INTO simulated_emails (company_id, recipient, subject, body, headers) 
                VALUES (:cid, :rec, :sub, :body, :head)");
            $stmt->execute([
                'cid' => $companyId,
                'rec' => $recipient,
                'sub' => $subject,
                'body' => $body,
                'head' => $headersStr
            ]);
            $emailId = $pdo->lastInsertId();

            // 2. Write to physical log file inside private folder
            $privatePath = dirname(__DIR__) . '/../private_lima';
            if (!is_dir($privatePath)) {
                $privatePath = dirname(__DIR__) . '/../private';
            }
            
            $logDir = $privatePath . '/logs';
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . '/emails.log';
            $timestamp = date('Y-m-d H:i:s');
            
            $logContent = "=================================================================\n";
            $logContent .= "TIMESTAMP:   $timestamp\n";
            $logContent .= "EMAIL ID:    $emailId\n";
            $logContent .= "COMPANY ID:  $companyId\n";
            $logContent .= "RECIPIENT:   $recipient\n";
            $logContent .= "SUBJECT:     $subject\n";
            if ($headersStr) {
                $logContent .= "HEADERS:     $headersStr\n";
            }
            $logContent .= "CONTENT:\n$body\n";
            $logContent .= "=================================================================\n\n";
            
            file_put_contents($logFile, $logContent, FILE_APPEND | LOCK_EX);
            return true;
        } catch (Exception $e) {
            error_log("Failed to send simulated email: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sends an email using native PHP socket client via authenticated SMTP server.
     */
    public static function sendSMTPReal($to, $subject, $body) {
        if (!defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_USER') || !defined('SMTP_PASS')) {
            throw new Exception("SMTP configuration constants not fully defined.");
        }

        $host = SMTP_HOST;
        $port = SMTP_PORT;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $secure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
        $from = defined('SMTP_FROM') ? SMTP_FROM : $user;
        $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Lima Déménagement';

        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$from>",
            "To: <$to>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "Date: " . date('r'),
            "Message-ID: <" . time() . "-" . md5($to . $subject) . "@" . parse_url($host, PHP_URL_HOST) . ">"
        ];
        $headerStr = implode("\r\n", $headers);
        $message = $headerStr . "\r\n\r\n" . $body;

        $encryption = "";
        if (strtolower($secure) === 'ssl') {
            $encryption = "ssl://";
        }
        
        $socket = @fsockopen($encryption . $host, $port, $errno, $errstr, 15);
        if (!$socket) {
            throw new Exception("Could not connect to SMTP server $host:$port ($errno: $errstr)");
        }

        $getResponse = function($socket) {
            $response = "";
            while (($line = fgets($socket, 512)) !== false) {
                $response .= $line;
                if (substr($line, 3, 1) == " ") {
                    break;
                }
            }
            return $response;
        };

        $sendCommand = function($socket, $cmd) use ($getResponse) {
            fputs($socket, $cmd . "\r\n");
            return $getResponse($socket);
        };

        $getResponse($socket); // read greeting
        
        // EHLO
        $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        
        // STARTTLS if TLS
        if (strtolower($secure) === 'tls') {
            $resp = $sendCommand($socket, "STARTTLS");
            if (strpos($resp, '220') === false) {
                fclose($socket);
                throw new Exception("STARTTLS failed: " . $resp);
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                throw new Exception("Encryption handshaking failed");
            }
            $sendCommand($socket, "EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        }

        // Authentication
        if (!empty($user) && !empty($pass)) {
            $resp = $sendCommand($socket, "AUTH LOGIN");
            if (strpos($resp, '334') === false) {
                fclose($socket);
                throw new Exception("AUTH LOGIN failed: " . $resp);
            }
            $resp = $sendCommand($socket, base64_encode($user));
            if (strpos($resp, '334') === false) {
                fclose($socket);
                throw new Exception("Username base64 login failed: " . $resp);
            }
            $resp = $sendCommand($socket, base64_encode($pass));
            if (strpos($resp, '235') === false) {
                fclose($socket);
                throw new Exception("Password base64 login failed: " . $resp);
            }
        }

        // MAIL FROM
        $resp = $sendCommand($socket, "MAIL FROM:<$from>");
        if (strpos($resp, '250') === false) {
            fclose($socket);
            throw new Exception("MAIL FROM rejected: " . $resp);
        }

        // RCPT TO
        $resp = $sendCommand($socket, "RCPT TO:<$to>");
        if (strpos($resp, '250') === false && strpos($resp, '251') === false) {
            fclose($socket);
            throw new Exception("RCPT TO rejected: " . $resp);
        }

        // DATA
        $resp = $sendCommand($socket, "DATA");
        if (strpos($resp, '354') === false) {
            fclose($socket);
            throw new Exception("DATA rejected: " . $resp);
        }

        // Send content
        fputs($socket, $message . "\r\n.\r\n");
        $resp = $getResponse($socket);
        if (strpos($resp, '250') === false) {
            fclose($socket);
            throw new Exception("Message body rejected: " . $resp);
        }

        // QUIT
        $sendCommand($socket, "QUIT");
        fclose($socket);
        return true;
    }

    /**
     * Renders a template and logs/dispatches the email.
     */
    public static function sendTemplateEmail($companyId, $recipient, $templateName, $replacements, $pdo) {
        $templates = self::getTemplates();
        if (!isset($templates[$templateName])) {
            throw new InvalidArgumentException("Template '$templateName' does not exist.");
        }
        
        $template = $templates[$templateName];
        $subject = $template['subject'];
        $bodyTemplate = $template['body'];
        
        // Google Maps URL encoding helper for internal_lead_alert
        if ($templateName === 'internal_lead_alert') {
            $origin = $replacements['origin_address'] ?? '';
            $dest = $replacements['destination_address'] ?? '';
            
            if (!empty($origin) && $origin !== '-') {
                $encoded = urlencode($origin);
                $replacements['origin_maps_link'] = '<a href="https://www.google.com/maps/search/?api=1&query=' . $encoded . '" target="_blank" style="color: #007a87; text-decoration: underline; font-size: 12px;">(Voir sur Google Maps)</a>';
            } else {
                $replacements['origin_maps_link'] = '';
            }
            
            if (!empty($dest) && $dest !== '-') {
                $encoded = urlencode($dest);
                $replacements['destination_maps_link'] = '<a href="https://www.google.com/maps/search/?api=1&query=' . $encoded . '" target="_blank" style="color: #007a87; text-decoration: underline; font-size: 12px;">(Voir sur Google Maps)</a>';
            } else {
                $replacements['destination_maps_link'] = '';
            }
        }
        
        // Perform replacement on Subject and Body
        $subject = self::replacePlaceholders($subject, $replacements);
        $bodyContent = self::replacePlaceholders($bodyTemplate, $replacements);
        
        // Wrap content in HTML Layout
        $body = self::getHtmlLayout($subject, $bodyContent);
        
        // Always store simulation/audit record
        $logged = self::sendSimulatedEmail($companyId, $recipient, $subject, $body, ['Content-Type' => 'text/html; charset=UTF-8'], $pdo);
        
        // Dispatch real SMTP if enabled
        if (defined('EMAIL_MODE') && EMAIL_MODE === 'smtp') {
            require_once __DIR__ . '/ObservabilityHelper.php';
            try {
                self::sendSMTPReal($recipient, $subject, $body);
                ObservabilityHelper::log("Email successfully sent to: $recipient", 'SMTP_SUCCESS', 'INFO', ['to' => $recipient, 'subject' => $subject], $pdo);
            } catch (Exception $e) {
                error_log("Real SMTP send error for $recipient: " . $e->getMessage());
                ObservabilityHelper::log("Failed to send email to $recipient: " . $e->getMessage(), 'SMTP_FAIL', 'ERROR', ['to' => $recipient, 'subject' => $subject, 'error' => $e->getMessage()], $pdo);
                // Fallback to simulated log (already created)
            }
        }

        return $logged;
    }

    /**
     * Replaces bracket placeholders in a template with their clean values.
     */
    private static function replacePlaceholders($text, $replacements) {
        foreach ($replacements as $key => $value) {
            $valStr = ($value !== null && $value !== '') ? $value : '-';
            $text = str_replace('{' . $key . '}', $valStr, $text);
        }
        return preg_replace('/\{[a-zA-Z0-9_]+\}/', '-', $text);
    }

    /**
     * Wraps raw HTML content inside the unified Teal-themed email wrapper layout.
     */
    private static function getHtmlLayout($subject, $content) {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</title>
    <style>
        body {
            font-family: \'Inter\', Helvetica, Arial, sans-serif;
            color: #333333;
            line-height: 1.6;
            background-color: #f4f9fa;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border-top: 6px solid #007a87;
        }
        .header {
            background-color: #ffffff;
            padding: 30px 20px 20px 20px;
            text-align: center;
            border-bottom: 1px solid #eef2f3;
        }
        .logo-text {
            font-size: 22px;
            font-weight: 700;
            color: #007a87;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .content {
            padding: 30px 20px;
        }
        .footer {
            background-color: #f9f9f9;
            padding: 20px;
            text-align: center;
            font-size: 11px;
            color: #888888;
            border-top: 1px solid #eef2f3;
        }
        .footer a {
            color: #007a87;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-text">Lima Déménagement</div>
        </div>
        <div class="content">
            ' . $content . '
        </div>
        <div class="footer">
            <p><strong>LIMA Solutions ERP</strong> — Central de Notificação</p>
            <p>Este é um e-mail transacional automatizado enviado a partir do seu ERP.</p>
            <p>&copy; ' . date('Y') . ' Lima Déménagement. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>';
    }

    /**
     * Map of available templates.
     */
    private static function getTemplates() {
        return [
            'lead_confirmation' => [
                'subject' => '[LIMA Déménagement] Confirmation de votre demande de devis',
                'body' => '<p>Bonjour <strong>{name}</strong>,</p>
<p>Nous vous remercions pour votre demande de devis de déménagement sur notre site. Notre équipe commerciale étudie actuellement votre dossier et vous contactera sous 24 à 48 heures pour affiner notre proposition.</p>
<div style="background-color: #f4f9fa; border-left: 4px solid #007a87; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #007a87; margin-top: 0; margin-bottom: 10px;">Récapitulatif de votre demande</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 40%;">Nom complet :</td><td style="padding: 5px 0;">{name}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">E-mail :</td><td style="padding: 5px 0;">{email}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Téléphone :</td><td style="padding: 5px 0;">{phone}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Date de service souhaitée :</td><td style="padding: 5px 0;">{service_date}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Volume estimé :</td><td style="padding: 5px 0;">{volume_m3} m³</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Adresse de départ :</td><td style="padding: 5px 0;">{origin_address}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Adresse d\'arrivée :</td><td style="padding: 5px 0;">{destination_address}</td></tr>
    </table>
</div>
<p>Si vous avez des informations complémentaires ou des documents à nous faire parvenir, vous pouvez répondre directement à cet e-mail.</p>
<p>Nous vous remercions pour votre confiance.</p>
<p>Cordialement,<br>L\'équipe <strong>LIMA Déménagement</strong></p>'
            ],
            'internal_lead_alert' => [
                'subject' => '[CRM Alerte] Nouvelle lead commerciale reçue - {name}',
                'body' => '<p>Bonjour,</p>
<p>Une nouvelle demande de devis a été soumise sur le site public. Voici les détails de la lead :</p>
<div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #007a87; margin-top: 0; margin-bottom: 10px;">Informations de la Lead #{lead_id}</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 40%;">Nom complet :</td><td style="padding: 5px 0;"><strong>{name}</strong></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">E-mail :</td><td style="padding: 5px 0;"><a href="mailto:{email}" style="color: #007a87;">{email}</a></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Téléphone :</td><td style="padding: 5px 0;">{phone}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Date souhaitée :</td><td style="padding: 5px 0;">{service_date}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Volume estimé :</td><td style="padding: 5px 0;">{volume_m3} m³</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Adresse de départ :</td><td style="padding: 5px 0;">{origin_address} {origin_maps_link}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Adresse d\'arrivée :</td><td style="padding: 5px 0;">{destination_address} {destination_maps_link}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Notes :</td><td style="padding: 5px 0; font-style: italic;">{notes}</td></tr>
    </table>
</div>
<div style="background-color: #f4f9fa; border: 1px solid #d0e7e9; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h4 style="color: #005a63; margin-top: 0; margin-bottom: 8px;">Métadonnées et Rastreabilidade</h4>
    <table style="width: 100%; border-collapse: collapse; font-size: 12px; color: #555;">
        <tr><td style="padding: 3px 0; font-weight: bold; width: 40%;">UTM Source :</td><td style="padding: 3px 0;">{utm_source}</td></tr>
        <tr><td style="padding: 3px 0; font-weight: bold;">UTM Medium :</td><td style="padding: 3px 0;">{utm_medium}</td></tr>
        <tr><td style="padding: 3px 0; font-weight: bold;">UTM Campaign :</td><td style="padding: 3px 0;">{utm_campaign}</td></tr>
        <tr><td style="padding: 3px 0; font-weight: bold;">URL Referer :</td><td style="padding: 3px 0; word-break: break-all;">{referer_url}</td></tr>
        <tr><td style="padding: 3px 0; font-weight: bold;">Adresse IP :</td><td style="padding: 3px 0;">{ip_address}</td></tr>
    </table>
</div>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/crm/leads/{lead_id}" style="background-color: #007a87; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Traiter dans le CRM Admin</a>
</div>'
            ],
            'client_welcome' => [
                'subject' => '[LIMA Déménagement] Bienvenue chez nous - Création de votre compte client',
                'body' => '<p>Bonjour <strong>{name}</strong>,</p>
<p>Nous sommes ravis de vous compter parmi nos clients chez <strong>{company_name}</strong>.</p>
<p>Votre dossier a été validé et votre compte client a été officiellement configuré. Voici vos informations d\'identification uniques :</p>
<div style="text-align: center; margin: 25px 0; padding: 20px; background-color: #f4f9fa; border: 2px dashed #007a87; border-radius: 6px; display: inline-block; min-width: 250px;">
    <span style="font-size: 11px; color: #666666; display: block; text-transform: uppercase; letter-spacing: 1.5px; font-weight: bold;">Code Client de Référence</span>
    <strong style="font-size: 28px; color: #007a87; font-family: monospace; display: block; margin-top: 8px; letter-spacing: 1px;">{customer_code}</strong>
</div>
<p>À l\'aide de ce code client, vous pourrez accéder à votre Espace Client pour suivre l\'avancement de votre déménagement, signer électroniquement vos documents et consulter vos factures.</p>
<p>Notre équipe administrative reste à votre entière disposition pour toute question relative aux prochaines étapes opérationnelles.</p>
<p>Nous vous remercions pour votre confiance et nous réjouissons de collaborer avec vous.</p>
<p>Cordialement,<br>L\'équipe de <strong>{company_name}</strong></p>'
            ],
            'internal_conversion_alert' => [
                'subject' => '[CRM Conversion] Lead convertie en client avec succès - {lead_name}',
                'body' => '<p>Bonjour,</p>
<p>Le lead commercial <strong>{lead_name}</strong> ({lead_email}) a été qualificado e converti en dossier client actif avec succès.</p>
<div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #007a87; margin-top: 0; margin-bottom: 10px;">Données du dossier Client</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 45%;">Code Client généré :</td><td style="padding: 5px 0;"><strong style="font-family: monospace;">{customer_code}</strong></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Client ID dans le système :</td><td style="padding: 5px 0;">#{client_id}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Type d\'enregistrement :</td><td style="padding: 5px 0;">{is_duplicate}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Opération effectuée par :</td><td style="padding: 5px 0;">Utilisateur ID #{converted_by_user_id}</td></tr>
    </table>
</div>
<p>Le nouveau dossier client est dès à présent disponible dans le module Clients pour la planification logistique et l\'émission de factures.</p>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/clients/{client_id}" style="background-color: #007a87; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Ouvrir le dossier Client</a>
</div>'
            ],
            'pipeline_status_change' => [
                'subject' => '[CRM Pipeline] Mise à jour du statut du lead - {lead_name}',
                'body' => '<p>Bonjour,</p>
<p>Le statut du lead commercial <strong>{lead_name}</strong> (Lead ID #{lead_id}) a été modifié dans le pipeline commercial.</p>
<div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; padding: 20px; margin: 20px 0; border-radius: 4px; text-align: center;">
    <div style="display: inline-block; vertical-align: middle; text-align: left; margin: 0 15px;">
        <span style="font-size: 11px; color: #777777; display: block; text-transform: uppercase; font-weight: bold;">Statut Précédent</span>
        <span style="display: inline-block; padding: 4px 10px; background-color: #e0e0e0; border-radius: 12px; font-size: 13px; margin-top: 6px; font-weight: bold; color: #555555;">{old_status}</span>
    </div>
    <div style="display: inline-block; vertical-align: middle; margin: 0 15px; font-size: 24px; color: #999999;">&rarr;</div>
    <div style="display: inline-block; vertical-align: middle; text-align: left; margin: 0 15px;">
        <span style="font-size: 11px; color: #777777; display: block; text-transform: uppercase; font-weight: bold;">Nouveau Statut</span>
        <span style="display: inline-block; padding: 4px 10px; background-color: #007a87; color: #ffffff; border-radius: 12px; font-size: 13px; margin-top: 6px; font-weight: bold;">{new_status}</span>
    </div>
</div>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/crm/leads/{lead_id}" style="background-color: #007a87; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Gérer le lead dans le CRM</a>
</div>'
            ],
            'new_quote_alert' => [
                'subject' => '[LIMA Déménagement] Votre devis {quote_number} est disponible',
                'body' => '<p>Bonjour <strong>{client_name}</strong>,</p>
<p>Nous avons le plaisir de vous informer que notre proposition tarifaire pour votre déménagement a été établie.</p>
<div style="background-color: #f4f9fa; border-left: 4px solid #007a87; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #007a87; margin-top: 0; margin-bottom: 10px;">Détails du Devis</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 40%;">Numéro de devis :</td><td style="padding: 5px 0;"><strong>{quote_number}</strong></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Montant total :</td><td style="padding: 5px 0;">{total_amount} {currency}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Dont TVA (8.1%) :</td><td style="padding: 5px 0;">{vat_amount} {currency}</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Valable jusqu\'au :</td><td style="padding: 5px 0;">{valid_until}</td></tr>
    </table>
</div>
<p>Vous pouvez consulter ce devis en vous connectant à votre Espace Client avec votre identifiant client.</p>
<p>Cordialement,<br>L\'équipe <strong>LIMA Déménagement</strong></p>'
            ],
            'quote_accepted_alert' => [
                'subject' => '[CRM Alerte] Devis accepté - {quote_number} - {client_name}',
                'body' => '<p>Bonjour,</p>
<p>Le devis <strong>{quote_number}</strong> d\'un montant de <strong>{total_amount} CHF</strong> pour le client <strong>{client_name}</strong> a été validé et accepté le {accepted_date}.</p>
<p>Le dossier client est maintenant prêt pour la génération automatique de la facture correspondante et la planification de la logistique terrain.</p>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/quotes/{quote_id}" style="background-color: #007a87; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Ouvrir le Devis</a>
</div>'
            ],
            'new_invoice_alert' => [
                'subject' => '[LIMA Déménagement] Nouvelle facture émise {invoice_number}',
                'body' => '<p>Bonjour <strong>{client_name}</strong>,</p>
<p>Nous vous informons qu\'une nouvelle facture a été émise sur votre compte.</p>
<div style="background-color: #f4f9fa; border-left: 4px solid #007a87; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #007a87; margin-top: 0; margin-bottom: 10px;">Détails de la Facture</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 40%;">Numéro de facture :</td><td style="padding: 5px 0;"><strong>{invoice_number}</strong></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Montant total :</td><td style="padding: 5px 0;">{total_amount} CHF</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Solde à payer :</td><td style="padding: 5px 0;"><strong>{balance_due} CHF</strong></td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Échéance :</td><td style="padding: 5px 0;">{due_date}</td></tr>
    </table>
</div>
<p>Vous pouvez consulter votre facture et la télécharger en format PDF en cliquant sur le lien ci-dessous :</p>
<div style="text-align: center; margin: 25px 0;">
    <a href="{payment_link}" style="background-color: #007a87; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Voir et Télécharger ma Facture</a>
</div>
<p>Cordialement,<br>L\'équipe <strong>LIMA Déménagement</strong></p>'
            ],
            'payment_received_alert' => [
                'subject' => '[LIMA Déménagement] Confirmation de paiement reçu - Facture {invoice_number}',
                'body' => '<p>Bonjour <strong>{client_name}</strong>,</p>
<p>Nous confirmons la réception de votre paiement de <strong>{amount_paid} CHF</strong> pour la facture <strong>{invoice_number}</strong>.</p>
<p>Voici l\'état mis à jour de votre facture :</p>
<div style="background-color: #f4f9fa; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
        <tr><td style="padding: 5px 0; font-weight: bold; width: 40%;">Montant payé :</td><td style="padding: 5px 0;">{amount_paid} CHF</td></tr>
        <tr><td style="padding: 5px 0; font-weight: bold;">Solde restant dû :</td><td style="padding: 5px 0;"><strong>{balance_due} CHF</strong></td></tr>
    </table>
</div>
<p>Nous vous remercions pour votre règlement rapide.</p>
<p>Cordialement,<br>L\'équipe <strong>LIMA Déménagement</strong></p>'
            ],
            'new_message_alert' => [
                'subject' => '[LIMA Portal] Nouveau message reçu de {sender_name}',
                'body' => '<p>Bonjour <strong>{recipient_name}</strong>,</p>
<p>Vous avez reçu un nouveau message sur votre Espace Client LIMA.</p>
<div style="background-color: #f9f9f9; border: 1px solid #e0e0e0; padding: 15px; margin: 20px 0; border-radius: 4px; font-style: italic;">
    <p>"{message_excerpt}..."</p>
</div>
<p>Cliquez sur le lien ci-dessous pour accéder au portail et répondre :</p>
<div style="text-align: center; margin: 25px 0;">
    <a href="{portal_link}" style="background-color: #007a87; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Accéder au portail de messagerie</a>
</div>
<p>Cordialement,<br>L\'équipe <strong>LIMA Déménagement</strong></p>'
            ],
            'priority_lead_alert' => [
                'subject' => '[CRM Alerte] Nova oportunidade Priority - {name}',
                'body' => '<p>Bonjour,</p>
<p>Une nouvelle opportunité commerciale avec un score <strong>Priority</strong> ({lead_score}/100) a été identifiée.</p>
<div style="background-color: #fff5f5; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <h3 style="color: #ef4444; margin-top: 0; margin-bottom: 10px;">Détails de l\'opportunité</h3>
    <p><strong>Nom :</strong> {name}</p>
    <p><strong>Email :</strong> {email}</p>
    <p><strong>Téléphone :</strong> {phone}</p>
    <p><strong>Score :</strong> {lead_score} ({lead_category})</p>
    <p><strong>Motifs :</strong></p>
    <ul>{score_reasons_html}</ul>
</div>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/crm/leads/{lead_id}" style="background-color: #ef4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Traiter l\'opportunité d\'urgence</a>
</div>'
            ],
            'lead_uncontacted_reminder' => [
                'subject' => '[CRM Rappel] Lead sans réponse depuis 7 jours - {name}',
                'body' => '<p>Bonjour,</p>
<p>Le lead commercial <strong>{name}</strong> ({email}) n\'a reçu aucun contact depuis 7 jours.</p>
<div style="background-color: #fffbeb; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;">
    <p><strong>Dernière mise à jour :</strong> {last_updated}</p>
    <p><strong>Score :</strong> {lead_score} ({lead_category})</p>
</div>
<div style="text-align: center; margin-top: 25px;">
    <a href="https://limasolutions.ch/admin/index.html#/crm/leads/{lead_id}" style="background-color: #f59e0b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; font-weight: bold; display: inline-block;">Contacter le prospect</a>
</div>'
            ]
        ];
    }
}
