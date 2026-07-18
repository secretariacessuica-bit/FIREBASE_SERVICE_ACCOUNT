<?php
// Temporary error capture wrapper for reports.php
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Custom error handler that logs to file
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logFile = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/admin/reports_error.log';
    $msg = date('Y-m-d H:i:s') . " ERROR[$errno]: $errstr in " . basename($errfile) . ":$errline\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
    return false;
});

set_exception_handler(function($e) {
    $logFile = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/admin/reports_error.log';
    $msg = date('Y-m-d H:i:s') . " EXCEPTION: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine() . "\n" . $e->getTraceAsString() . "\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal error logged: ' . $e->getMessage()]);
});

// Now include the real reports.php logic
require_once '../../../admin/auth.php';
require_once '../../../admin/modules_helper.php';
require_once '../../../modules/reports/model/Report.php';
require_once '../../../modules/reports/controller/ReportController.php';

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$companyId = getActiveCompanyId();
if (!$companyId) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Aucune entreprise active sélectionnée.']);
    exit();
}

$userRole = $_SESSION['user_role'] ?? 'viewer';
enforceModuleAccess('reports', $userRole, $companyId, 'view', $pdo);

$reportModel = new Report($pdo);
$controller = new ReportController($reportModel);

$filters = $controller->parseFilters($_GET);
$action = $_GET['action'] ?? 'kpis';

try {
    $reportData = null;

    switch ($action) {
        case 'kpis':
            $reportData = $reportModel->getKPIs($companyId, $filters);
            break;
        case 'tax':
            $reportData = $reportModel->getTaxReport($companyId, $filters);
            break;
        case 'cashflow':
            $groupType = $_GET['group_type'] ?? 'month';
            $reportData = $reportModel->getCashFlow($companyId, $groupType, $filters);
            break;
        case 'receivables':
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = min(200, max(1, (int)($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;
            $rawList = $reportModel->getReceivables($companyId, $filters, $limit, $offset);
            $totalRecords = $reportModel->getReceivablesCount($companyId, $filters);
            $summary = $reportModel->getReceivablesSummary($companyId, $filters);
            $reportData = ['list' => $rawList, 'summary' => $summary, 'pagination' => ['page' => $page, 'limit' => $limit, 'total_records' => $totalRecords, 'total_pages' => ceil($totalRecords / $limit)]];
            break;
        case 'customers':
            $reportData = $reportModel->getCustomersReport($companyId, $filters);
            break;
        case 'quotes':
            $reportData = $reportModel->getQuotesReport($companyId, $filters);
            break;
        case 'payments':
            $reportData = $reportModel->getPaymentsReport($companyId, $filters);
            break;
        case 'hours_by_project':
            $reportData = $reportModel->getHoursByProject($companyId, $filters);
            break;
        case 'hours_by_worker':
            $reportData = $reportModel->getHoursByWorker($companyId, $filters);
            break;
        case 'estimated_vs_realized':
            $reportData = $reportModel->getEstimatedVsRealized($companyId);
            break;
        case 'project_profitability':
            $reportData = $reportModel->getProjectProfitability($companyId);
            break;
        default:
            $reportData = ['message' => 'Action not supported: ' . $action];
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'data' => $reportData]);

} catch (Throwable $e) {
    $logFile = '/home/clients/c60c25a0672639c5f81740b42f06902c/sites/limasolutions.ch/admin/reports_error.log';
    $msg = date('Y-m-d H:i:s') . " CATCH: " . $e->getMessage() . " in " . basename($e->getFile()) . ":" . $e->getLine() . "\n";
    file_put_contents($logFile, $msg, FILE_APPEND);
    
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Report error: ' . $e->getMessage()]);
}
