<?php
// LIMA Solutions ERP - Payments Controller

class PaymentController {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Sanitizes raw HTTP request input parameters
     */
    public function sanitize($input) {
        $clean = [];
        $clean['invoice_id'] = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;
        $clean['payment_date'] = isset($input['payment_date']) ? trim(strip_tags($input['payment_date'])) : '';
        $clean['amount'] = isset($input['amount']) ? (float)$input['amount'] : 0.00;
        $clean['currency'] = isset($input['currency']) ? trim(strip_tags($input['currency'])) : 'CHF';
        $clean['payment_method'] = isset($input['payment_method']) ? trim(strip_tags($input['payment_method'])) : '';
        $clean['reference'] = isset($input['reference']) ? trim(strip_tags($input['reference'])) : null;
        $clean['transaction_reference'] = isset($input['transaction_reference']) ? trim(strip_tags($input['transaction_reference'])) : null;
        $clean['notes'] = isset($input['notes']) ? trim(strip_tags($input['notes'])) : null;
        return $clean;
    }

    /**
     * Validates payment attributes
     */
    public function validate($data) {
        $errors = [];
        
        if (empty($data['invoice_id']) || $data['invoice_id'] <= 0) {
            $errors[] = "ID de facture invalide.";
        }

        if (empty($data['payment_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['payment_date'])) {
            $errors[] = "Date de paiement invalide (format attendu YYYY-MM-DD).";
        }

        if ($data['amount'] <= 0) {
            $errors[] = "Le montant doit être supérieur à zero.";
        }

        $methodsWhitelist = ['Cash', 'Bank Transfer', 'TWINT', 'Credit Card', 'Debit Card', 'QR-Bill', 'Other'];
        if (empty($data['payment_method']) || !in_array($data['payment_method'], $methodsWhitelist)) {
            $errors[] = "Méthode de paiement non autorisée.";
        }

        return $errors;
    }
}
