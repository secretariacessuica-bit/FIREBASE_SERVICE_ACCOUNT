<?php
// LIMA Solutions ERP - Shared Finance Calculation Helper

class FinanceHelper {

    /**
     * Calculates the subtotal of an array of invoice/quote items.
     * Each item must have 'quantity' and 'unit_price'.
     * 
     * @param array $items List of items
     * @return float Calculated subtotal
     */
    public static function calculateSubtotal($items) {
        $subtotal = 0.00;
        foreach ($items as $item) {
            $qty = isset($item['quantity']) ? (float)$item['quantity'] : 0.00;
            $price = isset($item['unit_price']) ? (float)$item['unit_price'] : 0.00;
            $subtotal += ($qty * $price);
        }
        return self::roundMoney($subtotal);
    }

    /**
     * Calculates discount amount based on subtotal and discount percentage.
     * 
     * @param float $subtotal Base subtotal
     * @param float $discountRate Percentage (e.g. 10.00 for 10%)
     * @return float Discount amount
     */
    public static function calculateDiscount($subtotal, $discountRate) {
        $rate = (float)$discountRate;
        if ($rate <= 0.00) return 0.00;
        $discount = $subtotal * ($rate / 100.00);
        return self::roundMoney($discount);
    }

    /**
     * Calculates tax amount based on taxable amount and tax rate.
     * 
     * @param float $taxableAmount Amount subject to tax
     * @param float $taxRate Percentage (e.g. 8.10 for 8.1%)
     * @return float Tax amount
     */
    public static function calculateTax($taxableAmount, $taxRate) {
        $rate = (float)$taxRate;
        if ($rate <= 0.00) return 0.00;
        $tax = $taxableAmount * ($rate / 100.00);
        return self::roundMoney($tax);
    }

    /**
     * Calculates the grand total.
     * 
     * @param float $subtotal Base subtotal
     * @param float $discount Discount amount to subtract
     * @param float $tax Tax amount to add
     * @return float Grand total
     */
    public static function calculateGrandTotal($subtotal, $discount, $tax) {
        $total = ($subtotal - $discount) + $tax;
        return self::roundMoney($total);
    }

    /**
     * Arredondamento financeiro adequado.
     * Regra Suíça (CHF): Arredondamento para os 5 cêntimos mais próximos (0.05).
     * Outras moedas: Arredondamento padrão para 2 casas decimais.
     * 
     * @param float $value Raw amount
     * @param string $currencyCode Currency key (default 'CHF')
     * @return float Rounded amount
     */
    public static function roundMoney($value, $currencyCode = 'CHF') {
        $val = (float)$value;
        if (strtoupper($currencyCode) === 'CHF') {
            // Rounded to the nearest 0.05
            return round($val * 20.00) / 20.00;
        }
        return round($val, 2);
    }

    /**
     * Formats currency amounts matching localization guidelines.
     * Swiss format: 1'250.00
     * Default format: 1,250.00
     * 
     * @param float $value Amount
     * @param string $currencyCode Currency key
     * @param string $numberFormat Style identifier
     * @return string Formatted string
     */
    public static function formatCurrency($value, $currencyCode = 'CHF', $numberFormat = 'dot_comma') {
        $rounded = self::roundMoney($value, $currencyCode);
        
        switch ($numberFormat) {
            case 'apostrophe':
                // Swiss style (1'250.00)
                $formatted = number_format($rounded, 2, '.', "'");
                break;
            case 'comma_dot':
                // European style (1.250,00)
                $formatted = number_format($rounded, 2, ',', '.');
                break;
            case 'dot_comma':
            default:
                // Standard style (1,250.00)
                $formatted = number_format($rounded, 2, '.', ',');
                break;
        }

        return $formatted . ' ' . strtoupper($currencyCode);
    }
}
