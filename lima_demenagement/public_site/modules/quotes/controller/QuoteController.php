<?php
// LIMA Solutions ERP - Quotes Controller

require_once __DIR__ . '/../../../helpers/FinanceHelper.php';

class QuoteController {
    private $quoteModel;

    public function __construct($quoteModel) {
        $this->quoteModel = $quoteModel;
    }

    /**
     * Sanitizes general parameters.
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
     * Validates input details.
     */
    public function validate($data, $items) {
        $errors = [];

        if (empty($data['client_id'])) {
            $errors[] = "Le client est obligatoire.";
        }

        if (empty($data['issue_date'])) {
            $errors[] = "La date d'émission est obligatoire.";
        }

        if (empty($data['valid_until'])) {
            $errors[] = "La date de validité est obligatoire.";
        }

        if (empty($items) || !is_array($items)) {
            $errors[] = "Le devis doit contenir au moins une ligne d'article.";
        } else {
            foreach ($items as $idx => $item) {
                if (empty($item['description'])) {
                    $errors[] = "La description de la ligne " . ($idx + 1) . " est obligatoire.";
                }
                if (!isset($item['quantity']) || (float)$item['quantity'] <= 0) {
                    $errors[] = "La quantité de la ligne " . ($idx + 1) . " doit être supérieure à zéro.";
                }
                if (!isset($item['unit_price']) || (float)$item['unit_price'] < 0) {
                    $errors[] = "Le prix unitaire de la ligne " . ($idx + 1) . " ne peut pas être négatif.";
                }
            }
        }

        return $errors;
    }

    /**
     * Recalculates all items and header totals on the server.
     * Prevents clients from sending modified total calculations.
     */
    public function calculateTotals($items, $discountPercent, $companyId, $pdo, $currency = 'CHF') {
        $recalculatedItems = [];
        $subtotalHeader = 0.00;
        $taxTotalHeader = 0.00;

        // Fetch tax rates for this company to match ID -> percentage
        $stmtTax = $pdo->prepare("SELECT id, rate FROM tax_rates WHERE company_id = :cid");
        $stmtTax->execute(['cid' => $companyId]);
        $rates = $stmtTax->fetchAll(PDO::FETCH_KEY_PAIR); // returns array [id => rate]

        foreach ($items as $item) {
            $qty = (float)$item['quantity'];
            $price = (float)$item['unit_price'];
            $itemDiscountPercent = isset($item['discount_percent']) ? (float)$item['discount_percent'] : 0.00;
            
            // Calculate item subtotal before item-level discount
            $itemRawSubtotal = $qty * $price;
            
            // Apply item-level discount if exists
            $itemDiscount = FinanceHelper::calculateDiscount($itemRawSubtotal, $itemDiscountPercent);
            $itemSubtotal = $itemRawSubtotal - $itemDiscount;
            
            // Tax calculation
            $taxId = !empty($item['tax_rate_id']) ? (int)$item['tax_rate_id'] : null;
            $taxRate = ($taxId && isset($rates[$taxId])) ? (float)$rates[$taxId] : 0.00;
            $taxAmount = FinanceHelper::calculateTax($itemSubtotal, $taxRate);
            
            $itemTotal = $itemSubtotal + $taxAmount;

            $recalculatedItems[] = [
                'description' => htmlspecialchars(trim($item['description']), ENT_QUOTES, 'UTF-8'),
                'quantity' => $qty,
                'unit_id' => !empty($item['unit_id']) ? (int)$item['unit_id'] : null,
                'unit_price' => $price,
                'discount_percent' => $itemDiscountPercent,
                'tax_rate_id' => $taxId,
                'subtotal' => FinanceHelper::roundMoney($itemSubtotal, $currency),
                'tax_amount' => FinanceHelper::roundMoney($taxAmount, $currency),
                'total' => FinanceHelper::roundMoney($itemTotal, $currency)
            ];

            $subtotalHeader += $itemSubtotal;
            $taxTotalHeader += $taxAmount;
        }

        // Apply general header discount
        $discountPercent = (float)$discountPercent;
        $discountAmount = FinanceHelper::calculateDiscount($subtotalHeader, $discountPercent);
        
        // Adjust tax total if discount affects tax (standard tax reduction proportion)
        if ($subtotalHeader > 0 && $discountAmount > 0) {
            $ratio = ($subtotalHeader - $discountAmount) / $subtotalHeader;
            $taxTotalHeader = $taxTotalHeader * $ratio;
        }

        $grandTotal = ($subtotalHeader - $discountAmount) + $taxTotalHeader;

        return [
            'items' => $recalculatedItems,
            'subtotal' => FinanceHelper::roundMoney($subtotalHeader, $currency),
            'discount_percent' => $discountPercent,
            'discount_amount' => FinanceHelper::roundMoney($discountAmount, $currency),
            'tax_total' => FinanceHelper::roundMoney($taxTotalHeader, $currency),
            'total' => FinanceHelper::roundMoney($grandTotal, $currency)
        ];
    }
}
