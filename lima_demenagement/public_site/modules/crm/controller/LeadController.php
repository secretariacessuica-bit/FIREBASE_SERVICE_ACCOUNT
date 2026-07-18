<?php
// LIMA Solutions ERP - CRM Lead Controller

class LeadController {
    private $leadModel;

    public function __construct($leadModel = null) {
        $this->leadModel = $leadModel;
    }

    /**
     * Sanitizes inputs to prevent XSS.
     */
    public function sanitize($data) {
        $clean = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $clean[$key] = $val;
            } elseif ($val === null) {
                $clean[$key] = null;
            } else {
                $clean[$key] = htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
            }
        }
        return $clean;
    }

    /**
     * Validates lead data and fields size limits.
     */
    public function validate($data) {
        $errors = [];

        // Required fields
        if (empty($data['name'])) {
            $errors[] = "Le nom est obligatoire.";
        } elseif (mb_strlen($data['name']) > 150) {
            $errors[] = "Le nom ne doit pas dépasser 150 caractères.";
        }

        if (empty($data['email'])) {
            $errors[] = "L'adresse e-mail est obligatoire.";
        } elseif (mb_strlen($data['email']) > 150) {
            $errors[] = "L'adresse e-mail ne doit pas dépasser 150 caractères.";
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format de courriel invalide.";
        }

        if (!empty($data['phone']) && mb_strlen($data['phone']) > 30) {
            $errors[] = "Le numéro de téléphone ne doit pas dépasser 30 caractères.";
        }

        if (!empty($data['origin_address']) && mb_strlen($data['origin_address']) > 255) {
            $errors[] = "L'adresse d'origine ne doit pas dépasser 255 caractères.";
        }

        if (!empty($data['destination_address']) && mb_strlen($data['destination_address']) > 255) {
            $errors[] = "L'adresse de destination ne doit pas dépasser 255 caractères.";
        }

        if (!empty($data['notes']) && mb_strlen($data['notes']) > 2000) {
            $errors[] = "Les notes ne doivent pas dépasser 2000 caractères.";
        }

        return $errors;
    }

    /**
     * Checks if submission rate limit has been exceeded for an IP.
     * Allowed: 5 submissions per hour per IP.
     */
    public function checkRateLimit($ip, $pdo) {
        if (empty($ip)) {
            return true; // Fail-safe: let it proceed if IP detection fails
        }
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM crm_leads WHERE ip_address = :ip AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
            $stmt->execute(['ip' => $ip]);
            $count = (int)$stmt->fetchColumn();
            return ($count < 5);
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
            return true; // Fail-safe
        }
    }

    /**
     * Helper to return consistent JSON error response.
     */
    public function sendJSONError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message
        ]);
        exit();
    }

    /**
     * Helper to return consistent JSON success response.
     */
    public function sendJSONSuccess($data = [], $message = 'Opération réussie') {
        echo json_encode(array_merge([
            'success' => true,
            'message' => $message
        ], $data));
        exit();
    }
}
