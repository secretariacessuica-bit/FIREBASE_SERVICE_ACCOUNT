<?php
// LIMA Solutions ERP - Base PDF Document Template Class
// Designed to render consistent visual structure for Quotes, Invoices and Receipts.

class PdfTemplate {

    /**
     * Generates complete HTML template representing the PDF document.
     * Can be converted to PDF using Dompdf, wkhtmltopdf or browser print.
     */
    public static function generateHtml($company, $client, $items, $totals, $docTitle, $docNumber, $dateInfo = []) {
        $primaryColor = $company['main_color'] ?? '#007a87';
        
        $headerHtml = self::renderHeader($company, $docTitle, $docNumber, $dateInfo, $primaryColor);
        $clientBox = self::renderClientBox($company, $client);
        $itemsTable = self::renderItemsTable($items, $primaryColor);
        $summaryBox = self::renderSummaryBox($totals, $company['currency'] ?? 'CHF');
        $footerHtml = self::renderFooter($company);

        return "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <style>
                @page {
                    size: A4;
                    margin: 20mm;
                }
                body {
                    font-family: 'Arial', sans-serif;
                    color: #2c3e50;
                    font-size: 12px;
                    line-height: 1.5;
                    margin: 0;
                    padding: 0;
                }
                .pdf-container {
                    width: 100%;
                }
                a {
                    color: $primaryColor;
                    text-decoration: none;
                }
                .divider {
                    border-bottom: 2px solid $primaryColor;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class='pdf-container'>
                $headerHtml
                $clientBox
                $itemsTable
                $summaryBox
                $footerHtml
            </div>
        </body>
        </html>
        ";
    }

    private static function renderHeader($company, $title, $number, $dateInfo, $color) {
        $logoHtml = !empty($company['logo']) 
            ? "<img src='{$company['logo']}' style='max-height: 60px; max-width: 200px;'>"
            : "<span style='font-size: 24px; font-weight: bold; color: $color;'>{$company['name']}</span>";

        $issueDate = $dateInfo['issue_date'] ?? date('d.m.Y');
        $dueDate = isset($dateInfo['due_date']) ? "<br><strong>Échéance:</strong> {$dateInfo['due_date']}" : '';

        return "
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
            <tr>
                <td style='vertical-align: top;'>
                    $logoHtml
                    <div style='margin-top: 10px; font-size: 11px; color: #7f8c8d;'>
                        <strong>{$company['legal_name']}</strong><br>
                        {$company['address']}<br>
                        Tél: {$company['phone']} | {$company['email']}
                    </div>
                </td>
                <td style='text-align: right; vertical-align: top;'>
                    <h1 style='font-size: 20px; color: $color; margin: 0 0 5px 0; text-transform: uppercase;'>$title</h1>
                    <div style='font-size: 13px; font-weight: bold; margin-bottom: 10px;'>N°: $number</div>
                    <div style='font-size: 11px;'>
                        <strong>Date d'émission:</strong> $issueDate
                        $dueDate
                    </div>
                </td>
            </tr>
        </table>
        <div class='divider'></div>
        ";
    }

    private static function renderClientBox($company, $client) {
        $name = $client['client_name'] ?? $client['name'] ?? '';
        $companyName = $client['client_company'] ?? $client['company'] ?? '';
        $address = $client['client_address'] ?? $client['address'] ?? '';
        $postalCode = $client['client_postal_code'] ?? $client['postal_code'] ?? '';
        $city = $client['client_city'] ?? $client['city'] ?? '';
        $country = $client['client_country'] ?? $client['country'] ?? '';

        return "
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 35px;'>
            <tr>
                <td style='width: 50%; vertical-align: top;'>
                    <div style='color: #7f8c8d; font-size: 10px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;'>Prestataire</div>
                    <strong>{$company['name']}</strong><br>
                    {$company['address']}<br>
                    VAT/UID: {$company['vat_number']}
                </td>
                <td style='width: 50%; vertical-align: top; padding-left: 20px; border-left: 1px solid #e2e8f0;'>
                    <div style='color: #7f8c8d; font-size: 10px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;'>Facturé à (Client)</div>
                    <strong>" . (!empty($companyName) ? htmlspecialchars($companyName) . "<br>" : "") . "</strong>
                    " . htmlspecialchars($name) . "<br>
                    " . htmlspecialchars($address) . "<br>
                    " . htmlspecialchars($postalCode) . " " . htmlspecialchars($city) . "<br>
                    " . htmlspecialchars($country) . "
                </td>
            </tr>
        </table>
        ";
    }

    private static function renderItemsTable($items, $color) {
        $rowsHtml = '';
        foreach ($items as $idx => $item) {
            $num = $idx + 1;
            $qty = number_format((float)$item['quantity'], 2);
            $price = number_format((float)$item['unit_price'], 2);
            $total = number_format((float)$item['total'], 2);

            $rowsHtml .= "
            <tr style='border-bottom: 1px solid #e2e8f0;'>
                <td style='padding: 8px; text-align: center;'>$num</td>
                <td style='padding: 8px;'>" . htmlspecialchars($item['description']) . "</td>
                <td style='padding: 8px; text-align: center;'>$qty</td>
                <td style='padding: 8px; text-align: right;'>$price</td>
                <td style='padding: 8px; text-align: right;'>$total</td>
            </tr>
            ";
        }

        return "
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
            <thead>
                <tr style='background-color: $color; color: white;'>
                    <th style='padding: 8px; width: 40px; text-align: center;'>#</th>
                    <th style='padding: 8px; text-align: left;'>Description</th>
                    <th style='padding: 8px; width: 60px; text-align: center;'>Qté</th>
                    <th style='padding: 8px; width: 100px; text-align: right;'>P.U.</th>
                    <th style='padding: 8px; width: 120px; text-align: right;'>Total</th>
                </tr>
            </thead>
            <tbody>
                $rowsHtml
            </tbody>
        </table>
        ";
    }

    private static function renderSummaryBox($totals, $currency) {
        $subtotal = number_format((float)($totals['subtotal'] ?? 0.00), 2);
        $discountHtml = '';
        if (isset($totals['discount']) && $totals['discount'] > 0) {
            $disc = number_format((float)$totals['discount'], 2);
            $discountHtml = "
            <tr>
                <td style='padding: 5px 0; color: #7f8c8d;'>Remise:</td>
                <td style='padding: 5px 0; text-align: right;'>- $disc $currency</td>
            </tr>
            ";
        }
        $vat = number_format((float)($totals['vat'] ?? 0.00), 2);
        $total = number_format((float)($totals['total'] ?? 0.00), 2);

        return "
        <table style='width: 100%; border-collapse: collapse; margin-bottom: 50px;'>
            <tr>
                <td style='width: 60%; vertical-align: top; font-size: 11px; color: #7f8c8d;'>
                    <strong>Notes/Conditions:</strong><br>
                    Sauf indication contraire, le paiement est dû sous 30 jours à compter de la date d'émission.
                </td>
                <td style='width: 40%; vertical-align: top;'>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 5px 0; color: #7f8c8d;'>Sous-Total:</td>
                            <td style='padding: 5px 0; text-align: right;'>$subtotal $currency</td>
                        </tr>
                        $discountHtml
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 5px 0; color: #7f8c8d;'>TVA:</td>
                            <td style='padding: 5px 0; text-align: right;'>$vat $currency</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; font-size: 14px; font-weight: bold;'>Total Général:</td>
                            <td style='padding: 8px 0; font-size: 14px; font-weight: bold; text-align: right;'>$total $currency</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        ";
    }

    private static function renderFooter($company) {
        $iban = !empty($company['iban']) ? "IBAN: {$company['iban']}" : '';
        $bic = !empty($company['bic']) ? "BIC: {$company['bic']}" : '';
        $bankInfo = implode(' | ', array_filter([$iban, $bic]));

        return "
        <div style='border-top: 1px solid #e2e8f0; margin-top: 50px; padding-top: 15px; font-size: 9px; color: #7f8c8d; text-align: center;'>
            {$company['legal_name']} | {$company['address']}<br>
            Tél: {$company['phone']} | {$company['email']}<br>
            <strong>$bankInfo</strong>
        </div>
        ";
    }
}
