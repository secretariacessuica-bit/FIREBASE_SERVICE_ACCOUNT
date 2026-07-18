<?php
// LIMA Solutions ERP - CRM Lead Model

class Lead {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Creates a new lead in the database.
     */
    public function create($data) {
        $sql = "INSERT INTO crm_leads (
                    company_id, name, email, phone, origin_address, 
                    destination_address, service_date, volume_m3, status, 
                    notes, utm_source, utm_medium, utm_campaign, referer_url, ip_address
                ) VALUES (
                    :company_id, :name, :email, :phone, :origin_address, 
                    :destination_address, :service_date, :volume_m3, 'New', 
                    :notes, :utm_source, :utm_medium, :utm_campaign, :referer_url, :ip_address
                )";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => !empty($data['phone']) ? $data['phone'] : null,
            'origin_address' => !empty($data['origin_address']) ? $data['origin_address'] : null,
            'destination_address' => !empty($data['destination_address']) ? $data['destination_address'] : null,
            'service_date' => !empty($data['service_date']) ? $data['service_date'] : null,
            'volume_m3' => !empty($data['volume_m3']) ? $data['volume_m3'] : null,
            'notes' => !empty($data['notes']) ? $data['notes'] : null,
            'utm_source' => !empty($data['utm_source']) ? $data['utm_source'] : null,
            'utm_medium' => !empty($data['utm_medium']) ? $data['utm_medium'] : null,
            'utm_campaign' => !empty($data['utm_campaign']) ? $data['utm_campaign'] : null,
            'referer_url' => !empty($data['referer_url']) ? $data['referer_url'] : null,
            'ip_address' => !empty($data['ip_address']) ? $data['ip_address'] : null
        ]);
        $leadId = $this->pdo->lastInsertId();
        
        // Recalculate score immediately
        try {
            $this->updateLeadScore($leadId);
        } catch (Exception $ex) {
            error_log("Failed to calculate score on create: " . $ex->getMessage());
        }
        
        return $leadId;
    }

    /**
     * Gets all leads for a company with optional status filter.
     */
    public function getAll($companyId, $status = null) {
        $sql = "SELECT * FROM crm_leads WHERE company_id = :company_id";
        $params = ['company_id' => $companyId];
        
        if ($status) {
            $sql .= " AND status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Gets a single lead by ID.
     */
    public function getById($id, $companyId) {
        $stmt = $this->pdo->prepare("SELECT * FROM crm_leads WHERE id = :id AND company_id = :company_id LIMIT 1");
        $stmt->execute(['id' => $id, 'company_id' => $companyId]);
        return $stmt->fetch();
    }

    /**
     * Updates only the status of a lead.
     */
    public function updateStatus($id, $status, $companyId) {
        $validStatuses = ['New', 'Contacted', 'Visit Scheduled', 'Proposal Sent', 'Negotiation', 'Won', 'Lost'];
        if (!in_array($status, $validStatuses)) {
            throw new InvalidArgumentException("Statut invalide.");
        }
        
        $stmt = $this->pdo->prepare("UPDATE crm_leads SET status = :status WHERE id = :id AND company_id = :company_id");
        return $stmt->execute(['status' => $status, 'id' => $id, 'company_id' => $companyId]);
    }

    /**
     * Transactional method to convert a Lead to Client.
     * Checks for duplicates by email or phone.
     */
    public function convertToClient($id, $companyId, $userId) {
        $ownsTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $ownsTransaction = true;
        }

        try {
            // 1. Fetch Lead details
            $lead = $this->getById($id, $companyId);
            if (!$lead) {
                throw new Exception("Lead introuvable.");
            }
            if (!empty($lead['converted_client_id'])) {
                throw new Exception("Ce lead a déjà été converti.");
            }

            // 2. Duplicate Check: check if client already exists with same email or phone
            $clientStmt = $this->pdo->prepare("SELECT id, customer_code FROM clients 
                WHERE company_id = :cid AND active = 1 AND (
                    (email IS NOT NULL AND email != '' AND email = :email) OR
                    (phone IS NOT NULL AND phone != '' AND (phone = :phone1 OR mobile = :phone2 OR whatsapp = :phone3)) OR
                    (mobile IS NOT NULL AND mobile != '' AND (phone = :phone4 OR mobile = :phone5 OR whatsapp = :phone6))
                ) LIMIT 1");
            
            $clientStmt->execute([
                'cid' => $companyId,
                'email' => $lead['email'],
                'phone1' => $lead['phone'],
                'phone2' => $lead['phone'],
                'phone3' => $lead['phone'],
                'phone4' => $lead['phone'],
                'phone5' => $lead['phone'],
                'phone6' => $lead['phone']
            ]);
            $existingClient = $clientStmt->fetch();

            if ($existingClient) {
                // Duplicate client exists: Associate lead and update status to Won
                $clientId = $existingClient['id'];
                $isDuplicate = true;
            } else {
                // No duplicate: create a new client dossier
                require_once __DIR__ . '/../../../admin/sequences_helper.php';
                $customerCode = generateSequence($companyId, 'CLI', $this->pdo);

                $insertClient = $this->pdo->prepare("INSERT INTO clients 
                    (company_id, customer_code, name, email, phone, address, city, postal_code, notes, preferred_language, preferred_currency, active) 
                    VALUES (:company_id, :customer_code, :name, :email, :phone, :address, :city, :postal_code, :notes, 'FR', 'CHF', 1)");
                
                $address = !empty($lead['origin_address']) ? $lead['origin_address'] : 'À renseigner';
                $city = 'À renseigner';
                $postalCode = '0000';
                
                // Quick parser to extract postal code and city from the address line if present
                if (!empty($lead['origin_address'])) {
                    if (preg_match('/(\d{4})\s+([a-zA-Z\s\-]+)/', $lead['origin_address'], $matches)) {
                        $postalCode = $matches[1];
                        $city = trim($matches[2]);
                    }
                }

                $insertClient->execute([
                    'company_id' => $companyId,
                    'customer_code' => $customerCode,
                    'name' => $lead['name'],
                    'email' => $lead['email'],
                    'phone' => $lead['phone'],
                    'address' => $address,
                    'city' => $city,
                    'postal_code' => $postalCode,
                    'notes' => "Converti depuis la Lead ID: " . $id . "\n" . ($lead['notes'] ?? '')
                ]);
                $clientId = $this->pdo->lastInsertId();
                $isDuplicate = false;

                // Log to Entity Timeline for the client
                require_once __DIR__ . '/../../../admin/timeline_helper.php';
                logEntityEvent($companyId, 'crm', 'clients', $clientId, 'created', $userId, "Dossier client créé via conversion de Lead.", $this->pdo);
            }

            // 3. Mark Lead as Won (Gagné) and link to the Client
            $updateLead = $this->pdo->prepare("UPDATE crm_leads SET status = 'Won', converted_client_id = :client_id WHERE id = :id AND company_id = :company_id");
            $updateLead->execute([
                'client_id' => $clientId,
                'id' => $id,
                'company_id' => $companyId
            ]);

            if ($ownsTransaction) {
                $this->pdo->commit();
            }

            return [
                'success' => true,
                'client_id' => $clientId,
                'is_duplicate' => $isDuplicate
            ];

        } catch (Exception $e) {
            if ($ownsTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Calculates the lead score based on configured rules.
     * Returns an array with 'score' and 'reasons' (array of rules matched).
     */
    public function calculateScore($leadId) {
        $stmt = $this->pdo->prepare("SELECT * FROM crm_leads WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $leadId]);
        $lead = $stmt->fetch();
        if (!$lead) {
            return ['score' => 0, 'reasons' => []];
        }

        $score = 0;
        $reasons = [];

        // 1. Origem (Source/Origin)
        $utmSource = strtolower(trim($lead['utm_source'] ?? ''));
        $referer = strtolower(trim($lead['referer_url'] ?? ''));
        $notes = strtolower(trim($lead['notes'] ?? ''));
        
        if ($utmSource === 'marketplace' || strpos($referer, 'marketplace') !== false || strpos($notes, 'marketplace') !== false) {
            $score += 15;
            $reasons[] = ['rule' => 'origin_marketplace', 'points' => 15, 'text' => 'Marketplace'];
        } elseif ($utmSource === 'website' || $utmSource === 'site' || strpos($referer, 'limasolutions') !== false) {
            $score += 10;
            $reasons[] = ['rule' => 'origin_website', 'points' => 10, 'text' => 'Website'];
        } elseif ($utmSource === 'referral' || $utmSource === 'recommandation' || $utmSource === 'parrainage') {
            $score += 20;
            $reasons[] = ['rule' => 'origin_referral', 'points' => 20, 'text' => 'Referral'];
        } elseif ($utmSource === 'manual' || $utmSource === 'manuel') {
            $score += 5;
            $reasons[] = ['rule' => 'origin_manual', 'points' => 5, 'text' => 'Manual'];
        } else {
            // Default based on metadata
            if (!empty($lead['ip_address'])) {
                $score += 10;
                $reasons[] = ['rule' => 'origin_website', 'points' => 10, 'text' => 'Website (par défaut)'];
            } else {
                $score += 5;
                $reasons[] = ['rule' => 'origin_manual', 'points' => 5, 'text' => 'Manual (par défaut)'];
            }
        }

        // 2. Interesse Marketplace
        $stmtInterests = $this->pdo->prepare("SELECT COUNT(*) FROM marketplace_interests WHERE email = :email OR (phone IS NOT NULL AND phone = :phone)");
        $stmtInterests->execute([
            'email' => $lead['email'],
            'phone' => !empty($lead['phone']) ? $lead['phone'] : null
        ]);
        $interestsCount = intval($stmtInterests->fetchColumn());

        if ($interestsCount >= 5) {
            $score += 20;
            $reasons[] = ['rule' => 'interests_5_plus', 'points' => 20, 'text' => '5+ interesses'];
        } elseif ($interestsCount >= 3) {
            $score += 10;
            $reasons[] = ['rule' => 'interests_3', 'points' => 10, 'text' => '3 interesses'];
        } elseif ($interestsCount >= 1) {
            $score += 5;
            $reasons[] = ['rule' => 'interests_1', 'points' => 5, 'text' => '1 interesse'];
        }

        // 3. Cliente Existente
        $stmtClient = $this->pdo->prepare("SELECT COUNT(*) FROM clients WHERE active = 1 AND company_id = :company_id AND (email = :email OR (phone IS NOT NULL AND phone = :phone) OR (mobile IS NOT NULL AND mobile = :phone))");
        $stmtClient->execute([
            'company_id' => $lead['company_id'],
            'email' => $lead['email'],
            'phone' => !empty($lead['phone']) ? $lead['phone'] : null
        ]);
        $isExistingClient = intval($stmtClient->fetchColumn()) > 0;
        if ($isExistingClient) {
            $score += 15;
            $reasons[] = ['rule' => 'existing_client', 'points' => 15, 'text' => 'Já é cliente'];
        }

        // 4. Valor Potencial
        $potentialValue = null;
        if (isset($lead['estimated_value'])) {
            $potentialValue = floatval($lead['estimated_value']);
        } elseif (!empty($lead['converted_client_id'])) {
            $stmtQuote = $this->pdo->prepare("SELECT total FROM quotes WHERE client_id = :client_id AND company_id = :company_id AND deleted_at IS NULL ORDER BY total DESC LIMIT 1");
            $stmtQuote->execute([
                'client_id' => $lead['converted_client_id'],
                'company_id' => $lead['company_id']
            ]);
            $quoteVal = $stmtQuote->fetchColumn();
            if ($quoteVal !== false) {
                $potentialValue = floatval($quoteVal);
            }
        }
        
        if ($potentialValue !== null) {
            if ($potentialValue > 3000) {
                $score += 20;
                $reasons[] = ['rule' => 'value_gt_3000', 'points' => 20, 'text' => 'Valor > CHF 3.000'];
            } elseif ($potentialValue > 1500) {
                $score += 10;
                $reasons[] = ['rule' => 'value_gt_1500', 'points' => 10, 'text' => 'Valor > CHF 1.500'];
            } elseif ($potentialValue > 500) {
                $score += 5;
                $reasons[] = ['rule' => 'value_gt_500', 'points' => 5, 'text' => 'Valor > CHF 500'];
            }
        }

        // 5. Recência (Recency)
        $createdAt = strtotime($lead['created_at']);
        $days = (time() - $createdAt) / (60 * 60 * 24);
        if ($days <= 7) {
            $score += 10;
            $reasons[] = ['rule' => 'recent_7_days', 'points' => 10, 'text' => 'Lead recente (7 dias)'];
        } elseif ($days <= 30) {
            $score += 5;
            $reasons[] = ['rule' => 'recent_30_days', 'points' => 5, 'text' => 'Lead recente (30 dias)'];
        }

        // 6. Marketplace repetidos no mesmo item
        $stmtRepeat = $this->pdo->prepare("SELECT SUM(qty - 1) as repeats FROM (
            SELECT COUNT(*) as qty FROM marketplace_interests 
            WHERE email = :email OR (phone IS NOT NULL AND phone = :phone) 
            GROUP BY item_id
        ) as t WHERE qty > 1");
        $stmtRepeat->execute([
            'email' => $lead['email'],
            'phone' => !empty($lead['phone']) ? $lead['phone'] : null
        ]);
        $repeats = intval($stmtRepeat->fetchColumn());
        if ($repeats > 0) {
            $repeatPoints = $repeats * 10;
            $score += $repeatPoints;
            $reasons[] = ['rule' => 'marketplace_repeat_interests', 'points' => $repeatPoints, 'text' => 'Interesses repetidos'];
        }

        return [
            'score' => min(100, $score),
            'reasons' => $reasons
        ];
    }

    /**
     * Gets category description for a score value.
     */
    public function getScoreCategory($score) {
        if ($score <= 25) {
            return 'Cold';
        } elseif ($score <= 50) {
            return 'Warm';
        } elseif ($score <= 75) {
            return 'Hot';
        } else {
            return 'Priority';
        }
    }

    /**
     * Recalculates and updates the lead score and triggers priority alert if applicable.
     */
    public function updateLeadScore($leadId) {
        $scoring = $this->calculateScore($leadId);
        $score = $scoring['score'];
        $reasonsJson = json_encode($scoring['reasons'], JSON_UNESCAPED_UNICODE);

        // Update lead in DB
        $stmt = $this->pdo->prepare("UPDATE crm_leads SET lead_score = :score, lead_score_reasons = :reasons WHERE id = :id");
        $stmt->execute([
            'score' => $score,
            'reasons' => $reasonsJson,
            'id' => $leadId
        ]);

        // Priority Alert Check
        if ($score >= 76) {
            // Check if alert was already sent
            $stmtCheck = $this->pdo->prepare("SELECT company_id, name, email, phone, priority_alert_sent_at FROM crm_leads WHERE id = :id LIMIT 1");
            $stmtCheck->execute(['id' => $leadId]);
            $lead = $stmtCheck->fetch();

            if ($lead && empty($lead['priority_alert_sent_at'])) {
                $stmtCompany = $this->pdo->prepare("SELECT email FROM companies WHERE id = :cid LIMIT 1");
                $stmtCompany->execute(['cid' => $lead['company_id']]);
                $companyEmail = $stmtCompany->fetchColumn() ?: 'info@limasolutions.ch';

                // Build reasons HTML list
                $reasonsHtml = "";
                foreach ($scoring['reasons'] as $r) {
                    $reasonsHtml .= "<li>+" . $r['points'] . " " . htmlspecialchars($r['text']) . "</li>";
                }

                require_once __DIR__ . '/../../../helpers/EmailHelper.php';
                EmailHelper::sendTemplateEmail($lead['company_id'], $companyEmail, 'priority_lead_alert', [
                    'lead_id' => $leadId,
                    'name' => $lead['name'],
                    'email' => $lead['email'],
                    'phone' => !empty($lead['phone']) ? $lead['phone'] : '-',
                    'lead_score' => $score,
                    'lead_category' => $this->getScoreCategory($score),
                    'score_reasons_html' => $reasonsHtml
                ], $this->pdo);

                // Update priority_alert_sent_at
                $stmtSent = $this->pdo->prepare("UPDATE crm_leads SET priority_alert_sent_at = NOW() WHERE id = :id");
                $stmtSent->execute(['id' => $leadId]);
            }
        }

        return $score;
    }
}
