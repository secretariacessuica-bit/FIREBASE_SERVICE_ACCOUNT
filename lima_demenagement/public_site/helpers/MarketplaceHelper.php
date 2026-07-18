<?php
// LIMA Solutions ERP - Marketplace Helper
// Contains business logic for marketplace matching and notifications

require_once __DIR__ . '/EmailHelper.php';

class MarketplaceHelper {

    /**
     * Checks if the newly approved item matches any active demands.
     * If so, sends email notifications.
     */
    public static function matchDemandsAndNotify($pdo, $itemId, $companyId) {
        // 1. Get the item details
        $stmtItem = $pdo->prepare("SELECT i.id, i.title, i.description, i.price, i.category_id, i.location, c.name as category_name
                                   FROM marketplace_items i
                                   LEFT JOIN marketplace_categories c ON i.category_id = c.id
                                   WHERE i.id = :id AND i.company_id = :cid LIMIT 1");
        $stmtItem->execute(['id' => $itemId, 'cid' => $companyId]);
        $item = $stmtItem->fetch();

        if (!$item) return;

        $itemPrice = (float)($item['price'] ?? 0);
        $itemCategory = (int)($item['category_id'] ?? 0);
        
        // Combine title and description for keyword matching
        $itemText = mb_strtolower($item['title'] . ' ' . $item['description']);

        // 2. Fetch all active, non-expired demands for this company
        $stmtDemands = $pdo->prepare("SELECT d.*, cl.name as client_name, cl.email as client_email 
                                      FROM marketplace_demands d
                                      JOIN clients cl ON d.client_id = cl.id
                                      WHERE d.company_id = :cid 
                                      AND d.status = 'active'
                                      AND (d.expires_at IS NULL OR d.expires_at > NOW())");
        $stmtDemands->execute(['cid' => $companyId]);
        $demands = $stmtDemands->fetchAll();

        foreach ($demands as $demand) {
            // Match Category
            if (!empty($demand['category_id']) && (int)$demand['category_id'] !== $itemCategory) {
                continue; // Category mismatch
            }

            // Match Price
            if (!empty($demand['max_price']) && $itemPrice > (float)$demand['max_price']) {
                continue; // Too expensive
            }

            // Match Location
            if (!empty($demand['location']) && !empty($item['location'])) {
                if (mb_stripos($item['location'], $demand['location']) === false && mb_stripos($demand['location'], $item['location']) === false) {
                    continue; // Location mismatch
                }
            }

            // Match Keywords
            if (!empty($demand['keywords'])) {
                $keywords = array_map('trim', explode(',', $demand['keywords']));
                $keywordMatched = false;
                foreach ($keywords as $kw) {
                    if (empty($kw)) continue;
                    if (mb_strpos($itemText, mb_strtolower($kw)) !== false) {
                        $keywordMatched = true;
                        break;
                    }
                }
                if (!$keywordMatched) {
                    continue; // No keywords matched
                }
            }

            // --- ALL CONDITIONS MET ---

            // Check if already notified
            $stmtCheck = $pdo->prepare("SELECT id FROM marketplace_demand_matches WHERE demand_id = :did AND item_id = :iid LIMIT 1");
            $stmtCheck->execute(['did' => $demand['id'], 'iid' => $itemId]);
            if ($stmtCheck->fetch()) {
                continue; // Already notified
            }

            // Notify Email
            if ((int)$demand['notify_email'] === 1 && !empty($demand['client_email'])) {
                $subject = "Nouvelle annonce qui pourrait vous intéresser : " . $item['title'];
                $body = "Bonjour " . htmlspecialchars($demand['client_name']) . ",\n\n";
                $body .= "Une nouvelle annonce correspondant à votre recherche (Preciso de) vient d'être publiée sur le Marketplace.\n\n";
                $body .= "Titre: " . htmlspecialchars($item['title']) . "\n";
                $body .= "Prix: " . number_format($itemPrice, 2) . " CHF\n";
                $body .= "Catégorie: " . htmlspecialchars($item['category_name']) . "\n\n";
                $body .= "Connectez-vous à votre Espace Client pour la voir.\n";

                try {
                    EmailHelper::sendSimulatedEmail($companyId, $demand['client_email'], $subject, $body, null, $pdo);
                } catch (Exception $e) {
                    error_log("Failed to send demand email: " . $e->getMessage());
                }
            }

            // Log the match to prevent duplicates
            $stmtLog = $pdo->prepare("INSERT INTO marketplace_demand_matches (demand_id, item_id) VALUES (:did, :iid)");
            $stmtLog->execute(['did' => $demand['id'], 'iid' => $itemId]);
        }
    }
}
