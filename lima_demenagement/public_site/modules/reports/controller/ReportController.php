<?php
// LIMA Solutions ERP - Reports Controller

class ReportController {
    private $model;

    public function __construct($model) {
        $this->model = $model;
    }

    /**
     * Parse and sanitize filtering parameters
     */
    public function parseFilters($input) {
        $filters = [];
        $filters['start_date'] = !empty($input['start_date']) ? trim(strip_tags($input['start_date'])) : null;
        $filters['end_date'] = !empty($input['end_date']) ? trim(strip_tags($input['end_date'])) : null;
        $filters['client_id'] = !empty($input['client_id']) ? (int)$input['client_id'] : null;
        $filters['currency'] = !empty($input['currency']) ? trim(strip_tags($input['currency'])) : null;
        $filters['status'] = !empty($input['status']) ? trim(strip_tags($input['status'])) : null;
        $filters['sort'] = !empty($input['sort']) ? trim(strip_tags($input['sort'])) : null;
        $filters['overdue_type'] = !empty($input['overdue_type']) ? trim(strip_tags($input['overdue_type'])) : null;
        return $filters;
    }

    /**
     * Export report data to CSV format
     */
    public function exportCsv($reportName, $data) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $reportName . '_' . date('Ymd_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        if (!empty($data)) {
            // Write Header
            fputcsv($output, array_keys($data[0]), ';');
            // Write Rows
            foreach ($data as $row) {
                fputcsv($output, $row, ';');
            }
        }
        fclose($output);
        exit();
    }

    /**
     * Export report data to XLSX format (using Excel-compatible HTML tables)
     */
    public function exportXlsx($reportName, $data) {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $reportName . '_' . date('Ymd_His') . '.xls"');
        
        echo "<html xmlns:o=\"urn:schemas-microsoft-com:office:office\" xmlns:x=\"urn:schemas-microsoft-com:office:excel\" xmlns=\"http://www.w3.org/TR/REC-html40\">";
        echo "<head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\"></head>";
        echo "<body><table border='1'>";
        
        if (!empty($data)) {
            // Header
            echo "<tr>";
            foreach (array_keys($data[0]) as $header) {
                echo "<th style='background-color:#007a87; color:white; padding:5px;'>".htmlspecialchars($header)."</th>";
            }
            echo "</tr>";
            // Rows
            foreach ($data as $row) {
                echo "<tr>";
                foreach ($row as $val) {
                    echo "<td style='padding:5px;'>".htmlspecialchars($val ?? "")."</td>";
                }
                echo "</tr>";
            }
        }
        echo "</table></body></html>";
        exit();
    }

    /**
     * Export report data to a printer-friendly HTML PDF layout
     */
    public function exportPdf($reportTitle, $headers, $rows, $company) {
        $primaryColor = $company['main_color'] ?? '#007a87';
        
        $rowsHtml = '';
        foreach ($rows as $idx => $row) {
            $num = $idx + 1;
            $rowCells = "";
            foreach ($row as $cell) {
                $rowCells .= "<td style='padding: 8px; border-bottom: 1px solid #e2e8f0;'>" . htmlspecialchars($cell ?? "") . "</td>";
            }
            $rowsHtml .= "<tr><td style='padding: 8px; text-align: center; border-bottom: 1px solid #e2e8f0;'>$num</td>$rowCells</tr>";
        }

        $headersHtml = "";
        foreach ($headers as $header) {
            $headersHtml .= "<th style='padding: 8px; text-align: left;'>".htmlspecialchars($header)."</th>";
        }

        $html = "
        <!DOCTYPE html>
        <html lang='fr'>
        <head>
            <meta charset='UTF-8'>
            <style>
                @page { size: A4 landscape; margin: 15mm; }
                body { font-family: 'Arial', sans-serif; color: #2c3e50; font-size: 11px; line-height: 1.4; }
                .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                .color-primary { color: $primaryColor; }
                .divider { border-bottom: 2px solid $primaryColor; margin: 15px 0; }
                .data-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .data-table th { background-color: $primaryColor; color: white; padding: 8px; }
                .footer { border-top: 1px solid #e2e8f0; margin-top: 40px; padding-top: 10px; font-size: 8px; color: #7f8c8d; text-align: center; }
            </style>
        </head>
        <body>
            <table class='header-table'>
                <tr>
                    <td>
                        <h2 class='color-primary' style='margin:0; text-transform:uppercase;'>LIMA SOLUTIONS ERP</h2>
                        <span style='font-size:10px; color:#7f8c8d;'>{$company['name']} ({$company['legal_name']})</span>
                    </td>
                    <td style='text-align:right;'>
                        <h1 class='color-primary' style='margin:0; font-size:18px; text-transform:uppercase;'>$reportTitle</h1>
                        <span style='font-size:10px;'>Généré le: " . date('d.m.Y H:i') . "</span>
                    </td>
                </tr>
            </table>
            <div class='divider'></div>

            <table class='data-table'>
                <thead>
                    <tr>
                        <th style='width:30px; text-align:center;'>#</th>
                        $headersHtml
                    </tr>
                </thead>
                <tbody>
                    $rowsHtml
                </tbody>
            </table>

            <div class='footer'>
                Document généré automatiquement par le module de Business Intelligence - LIMA Solutions ERP.<br>
                Raison sociale: {$company['legal_name']} | NIF/TVA: {$company['vat_number']}
            </div>
        </body>
        </html>
        ";

        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit();
    }
}
