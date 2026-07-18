<?php
// LIMA Solutions ERP - Operations Observability Helper

class ObservabilityHelper {

    /**
     * Centralized Logger that writes to /private_lima/logs/application.log
     * and increments the corresponding system metrics in the database.
     */
    public static function log($message, $category, $severity = 'INFO', $details = null, $pdo = null) {
        // 1. Get path to private logs folder
        $configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
        if (!file_exists($configPath)) {
            $configPath = dirname(__DIR__, 2) . '/private/config.php';
        }
        
        $privateDir = dirname($configPath);
        $logDir = $privateDir . '/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/application.log';
        $timestamp = date('Y-m-d H:i:s');
        
        // 2. Format and write the log line
        $detailsStr = $details ? ' ' . json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $logLine = sprintf(
            "[%s] [%s] %s: %s%s\n",
            $timestamp,
            strtoupper($severity),
            strtoupper($category),
            $message,
            $detailsStr
        );
        
        @file_put_contents($logFile, $logLine, FILE_APPEND);

        // 3. Increment database metric
        self::incrementMetric($category, $pdo);
    }

    /**
     * Increments the daily metric counter in `system_metrics_daily` for today.
     */
    public static function incrementMetric($category, $pdo = null) {
        if (!$pdo) {
            global $pdo; // Fallback to global config PDO
        }
        if (!$pdo) {
            return; // Safe fallback if no database connection is initialized
        }

        $column = null;
        switch (strtoupper($category)) {
            case 'SMTP_SUCCESS':
                $column = 'smtp_success';
                break;
            case 'SMTP_FAIL':
                $column = 'smtp_failures';
                break;
            case 'FAILED_LOGIN':
                $column = 'failed_logins';
                break;
            case 'MOBILE_SYNC_SUCCESS':
                $column = 'mobile_sync_success';
                break;
            case 'MOBILE_SYNC_FAIL':
                $column = 'mobile_sync_failures';
                break;
            case 'API_ERROR':
            case 'EXCEPTION':
                $column = 'api_errors';
                break;
        }

        if (!$column) {
            return;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO system_metrics_daily (metric_date, {$column}, created_at)
                VALUES (CURDATE(), 1, NOW())
                ON DUPLICATE KEY UPDATE {$column} = {$column} + 1");
            $stmt->execute();
        } catch (Exception $e) {
            // Suppress metric increments exceptions to avoid breaking runtime flow
            error_log('[LIMA][Observability] Failed to increment metric: ' . $e->getMessage());
        }
    }

    /**
     * Query and update the active_users count (unique user logins) in today's metrics.
     */
    public static function updateActiveUsers($pdo) {
        if (!$pdo) return;
        try {
            // Count unique logins in the last 24 hours
            $stmt = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM activity_logs 
                WHERE action = 'Login' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $activeCount = (int)$stmt->fetchColumn();

            $stmtMetric = $pdo->prepare("INSERT INTO system_metrics_daily (metric_date, active_users, created_at)
                VALUES (CURDATE(), :cnt, NOW())
                ON DUPLICATE KEY UPDATE active_users = :cnt");
            $stmtMetric->execute(['cnt' => $activeCount]);
        } catch (Exception $e) {
            error_log('[LIMA][Observability] Failed to update active users: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve the last N lines of application.log.
     */
    public static function getRecentLogs($linesCount = 50) {
        $configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
        if (!file_exists($configPath)) {
            $configPath = dirname(__DIR__, 2) . '/private/config.php';
        }
        
        $logFile = dirname($configPath) . '/logs/application.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $handle = fopen($logFile, 'r');
        if (!$handle) {
            return [];
        }

        $lines = [];
        $lineCounter = 0;
        $pos = -2;
        $beginning = false;

        while ($lineCounter < $linesCount) {
            $t = "";
            while (true) {
                if (fseek($handle, $pos, SEEK_END) === -1) {
                    $beginning = true;
                    rewind($handle);
                    break;
                }
                $char = fgetc($handle);
                if ($char === "\n") {
                    break;
                }
                $t = $char . $t;
                $pos--;
            }
            if ($beginning) {
                $line = fgets($handle);
                if ($line !== false) {
                    $trimmed = trim($line);
                    if ($trimmed !== '') {
                        $lines[] = $trimmed;
                    }
                }
                break;
            }
            $trimmed = trim($t);
            if ($trimmed !== '' || $pos < -2) {
                $lines[] = $trimmed;
                $lineCounter++;
            }
            $pos--;
        }
        fclose($handle);
        return $lines;

    }

    /**
     * Get aggregated metrics from database and logs for dashboard health tracking.
     */
    public static function getTodayMetrics($pdo) {
        // Initial values
        $metrics = [
            'active_users' => 0,
            'failed_logins' => 0,
            'smtp_success' => 0,
            'smtp_failures' => 0,
            'mobile_sync_success' => 0,
            'mobile_sync_failures' => 0,
            'api_errors' => 0,
            'consecutive_smtp_failures' => 0,
            'avg_sync_time' => 0.0,
            'active_devices' => 0
        ];

        if (!$pdo) return $metrics;

        try {
            // Fetch today's database metrics
            $stmt = $pdo->query("SELECT * FROM system_metrics_daily WHERE metric_date = CURDATE() LIMIT 1");
            $row = $stmt->fetch();
            if ($row) {
                $metrics['active_users'] = (int)$row['active_users'];
                $metrics['failed_logins'] = (int)$row['failed_logins'];
                $metrics['smtp_success'] = (int)$row['smtp_success'];
                $metrics['smtp_failures'] = (int)$row['smtp_failures'];
                $metrics['mobile_sync_success'] = (int)$row['mobile_sync_success'];
                $metrics['mobile_sync_failures'] = (int)$row['mobile_sync_failures'];
                $metrics['api_errors'] = (int)$row['api_errors'];
            }

            // Fallback / log metrics: calculate average sync latency, active devices, and consecutive SMTP failures
            $configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
            if (!file_exists($configPath)) {
                $configPath = dirname(__DIR__, 2) . '/private/config.php';
            }
            $logFile = dirname($configPath) . '/logs/application.log';

            if (file_exists($logFile)) {
                $lines = file($logFile);
                $smtpFails = 0;
                $syncTimes = [];
                $devices = [];

                foreach ($lines as $line) {
                    if (preg_match('/^\[([^\]]+)\]\s+\[([^\]]+)\]\s+([^:]+):\s+(.*)$/', $line, $matches)) {
                        $category = strtoupper(trim($matches[3]));
                        $message = trim($matches[4]);

                        // Track consecutive SMTP failures
                        if ($category === 'SMTP_FAIL') {
                            $smtpFails++;
                        } elseif ($category === 'SMTP_SUCCESS') {
                            $smtpFails = 0;
                        }

                        // Mobile sync latency & devices
                        if ($category === 'MOBILE_SYNC_SUCCESS' || $category === 'MOBILE_SYNC_FAIL') {
                            if (preg_match('/"duration_ms":\s*(\d+)/', $message, $dMatches)) {
                                $syncTimes[] = (int)$dMatches[1] / 1000.0;
                            }
                            if (preg_match('/"device_uuid":\s*"([^"]+)"/', $message, $devMatches)) {
                                $devices[$devMatches[1]] = true;
                            }
                        }
                    }
                }
                $metrics['consecutive_smtp_failures'] = $smtpFails;
                $metrics['active_devices'] = count($devices);
                if (count($syncTimes) > 0) {
                    $metrics['avg_sync_time'] = round(array_sum($syncTimes) / count($syncTimes), 2);
                }
            }

        } catch (Exception $e) {
            error_log('[LIMA][Observability] Failed to load today metrics: ' . $e->getMessage());
        }

        return $metrics;
    }

    /**
     * Helper to log mobile synchronization events.
     */
    public static function logMobileSync($requestStart, $endpoint, $success, $clientUuid = '', $projectId = 0, $errorMsg = '', $pdo = null) {
        $durationMs = round((microtime(true) - $requestStart) * 1000);
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $deviceUuid = $headers['X-Device-UUID'] ?? $headers['x-device-uuid'] ?? $_SERVER['HTTP_X_DEVICE_UUID'] ?? 'unknown';

        $category = $success ? 'MOBILE_SYNC_SUCCESS' : 'MOBILE_SYNC_FAIL';
        $severity = $success ? 'INFO' : 'ERROR';

        $details = [
            'endpoint' => $endpoint,
            'duration_ms' => $durationMs,
            'device_uuid' => $deviceUuid,
            'client_uuid' => $clientUuid,
            'project_id' => $projectId
        ];
        if (!$success && $errorMsg) {
            $details['error'] = $errorMsg;
        }

        $message = sprintf(
            "%s sync %s (duration_ms: %d, device_uuid: %s)",
            $endpoint,
            $success ? 'success' : 'failed',
            $durationMs,
            $deviceUuid
        );

        self::log($message, $category, $severity, $details, $pdo);
    }

    /**
     * Reads the backup status placeholder.
     */
    public static function getBackupStatus() {
        $configPath = dirname(__DIR__, 2) . '/private_lima/config.php';
        if (!file_exists($configPath)) {
            $configPath = dirname(__DIR__, 2) . '/private/config.php';
        }
        $privateDir = dirname($configPath);
        $statusFile = $privateDir . '/backup_status.json';

        if (file_exists($statusFile)) {
            $data = json_decode(file_get_contents($statusFile), true);
            if ($data) {
                if (!empty($data['last_backup'])) {
                    $lastBackupTime = strtotime($data['last_backup']);
                    if ($lastBackupTime) {
                        $diffHrs = round((time() - $lastBackupTime) / 3600, 1);
                        $data['age_hours'] = $diffHrs >= 0 ? $diffHrs : 0;
                        if ($diffHrs > 28.0) {
                            $data['status'] = 'Stale';
                            $data['info'] = 'Alerte: Backup Desatualizado/Pendente (>28h).';
                        }
                    }
                }
                return $data;
            }
        }


        return [
            'last_backup' => 'N/A',
            'status' => 'Unknown',
            'age_hours' => 'N/A',
            'info' => 'Ficheiro backup_status.json não encontrado.'
        ];
    }
}
