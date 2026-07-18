# Operations Observability Foundation

Documentation of the observability, logging, and metrics collection system implemented for LIMA Solutions ERP.

## Logging System

All centralized application logs are recorded in the secure directory:
```text
/private_lima/logs/application.log
```

### Log Format
Each entry in the log follows this pattern:
```text
[TIMESTAMP] [SEVERITY] CATEGORY: Message {JSON_DETAILS}
```

### Categories
- `SMTP_SUCCESS`: Successful email deliveries.
- `SMTP_FAIL`: Failed email deliveries.
- `FAILED_LOGIN`: Failed authentication attempts.
- `SECURITY`: Security related alerts or actions.
- `MOBILE_SYNC_SUCCESS`: Successful offline and real-time mobile API data synchronization.
- `MOBILE_SYNC_FAIL`: Failed mobile API data synchronization.
- `API_ERROR`: Handled PHP run-time errors.
- `EXCEPTION`: Unhandled exceptions captured by the global error handler.

### Severity Levels
- `INFO`
- `WARNING`
- `ERROR`
- `CRITICAL`

---

## Daily Operational Metrics

Operational metrics are persisted in the database inside the `system_metrics_daily` table. This provides fast dashboard loading and historical reporting without needing to parse text files.

### Table Schema (`system_metrics_daily`)
- `metric_date` (DATE, Primary Key)
- `active_users` (INT): Count of unique logged-in users in the last 24h.
- `failed_logins` (INT): Total failed login attempts.
- `smtp_success` (INT): Total emails sent successfully.
- `smtp_failures` (INT): Total email failures.
- `mobile_sync_success` (INT): Total successful mobile data synchronizations.
- `mobile_sync_failures` (INT): Total failed mobile data synchronizations.
- `api_errors` (INT): Total API errors and captured exceptions.
- `created_at` (TIMESTAMP)

---

## Observability Dashboard

Admin and Super Admin users can monitor platform health in real-time at:
```text
public_site/admin/observability.php
```

### KPIs Displayed
- **Application Health**: Active users (24h), failed logins, API error counts, email volumes, active projects, uploads, and pending mobile syncs.
- **Mobile Health**: Total syncs, failed syncs, average synchronization latency, and count of active device UUIDs.
- **Marketplace Health**: Modering stats (Pending, Approved, Rejected, Sold, Donated) and leads generated count.
- **Alert Banner**: Report breaches on the system's threshold values.
- **Log Viewer**: Live view of the last 50 entries in `application.log`.
- **Test Event Generator**: Allows administrators to emit simulated log events of any category or severity to safely test alerts and database metrics increment without breaking actual connections.

### Alert Thresholds
An alert is triggered dynamically when:
- **Critical Errors**: `>10` critical/error log events in the last hour.
- **Sync Failures**: `>20` mobile sync failures in the past 24 hours.
- **SMTP Failures**: `>5` consecutive SMTP failures without any success in between.
- **Marketplace Backlog**: `>50` marketplace ads waiting in `Pending` moderation status.
