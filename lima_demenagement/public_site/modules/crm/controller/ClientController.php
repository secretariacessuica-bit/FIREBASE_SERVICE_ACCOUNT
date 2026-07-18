<?php
// LIMA Solutions ERP - CRM Client Controller

class ClientController {
    private $clientModel;

    public function __construct($clientModel) {
        $this->clientModel = $clientModel;
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
     * Validates client data.
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = "Le nom du client est obligatoire.";
        }

        if (empty($data['address'])) {
            $errors[] = "L'adresse est obligatoire.";
        }

        if (empty($data['city'])) {
            $errors[] = "La ville est obligatoire.";
        }

        if (empty($data['postal_code'])) {
            $errors[] = "Le code postal est obligatoire.";
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Format d'adresse e-mail invalide.";
        }

        return $errors;
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
