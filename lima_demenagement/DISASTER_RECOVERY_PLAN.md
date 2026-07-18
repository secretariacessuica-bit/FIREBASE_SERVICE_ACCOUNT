# Disaster Recovery Plan (DRP) — LIMA Solutions ERP

This document outlines the objectives and protocols for recovering the LIMA Solutions ERP platform in the event of critical server failures, database corruption, accidental file deletion, or malfunctioning deployments.

---

## 1. Recovery Objectives

- **RPO (Recovery Point Objective)**: **24 Hours** (maximum acceptable data loss since the last backup).
- **RTO (Recovery Time Objective)**: **4 Hours** (maximum target time to restore fully operational services).

---

## 2. Recovery Procedures

### Scenario 1: Database Corruption or Loss
If the database becomes corrupted, unreadable, or accidentally dropped:
1. **Prepare clean state**: Stop any write operations if possible, or put the site in temporary maintenance mode.
2. **Retrieve latest dump**: Locate the most recent valid SQL backup (`.sql.gz`) in the backup storage.
3. **Execute restore**:
   ```bash
   gunzip -c db_backup_YYYY-MM-DD.sql.gz | mysql -u [DB_USER] -p[DB_PASSWORD] [DB_NAME]
   ```
4. **Validate schema integrity**: Check tables (`users`, `crm_leads`, `projects`, `system_metrics_daily`) using the database information tool `admin/db_info.php`.
5. **Verify ERP logic**: Log in to the administrative portal, view the dashboard, and check the timeline logs.

### Scenario 2: Private Storage Data Loss
If files under `/private_lima/storage/` (project photos, signatures, marketplace media, documents) are corrupted or deleted:
1. **Retrieve storage archive**: Locate the corresponding daily storage archive (`storage_backup_YYYY-MM-DD.tar.gz`).
2. **Extract to destination**:
   ```bash
   tar -xzf storage_backup_YYYY-MM-DD.tar.gz -C /path/to/private_lima/storage/
   ```
3. **Verify files**:
   - Ensure the directory hierarchy is preserved: `project_photos/`, `project_signatures/`, `marketplace_photos/`.
   - Access a project signature or photo download API and verify image rendering.
   - Verify marketplace listing attachments.

### Scenario 3: Malfunctioning Deployment
If a code deploy breaks APIs or the admin dashboard:
1. **Retrieve stable release**: Check the repository tags or stable build zip archives.
2. **Restore build**: Extract the stable code bundle over the active `public_site` directory.
3. **Verify APIs**: Run smoke tests or access `/api/v1/mobile/team.php` and verify a successful json response is returned.
4. **Check admin dashboard**: Log in to `/admin/index.php` and verify widgets load correctly.

---

## 3. Health Verification Checklist

Once any recovery procedure is completed, the administrator must verify and tick all items in this checklist before returning the platform to active production status:

- [ ] **Database OK**: Verify standard query execution and table count.
- [ ] **Storage OK**: Validate download and upload capability of signatures, photos, and general documents.
- [ ] **SMTP OK**: Execute an email send check to verify communication channels.
- [ ] **Portal OK**: Verify that client authentication and portal dashboards are responsive.
- [ ] **Mobile API OK**: Test mobile endpoints (`/api/v1/mobile/timesheets.php`, `/api/v1/mobile/projects.php`) to ensure offline-first API synchronization functions correctly.
- [ ] **Marketplace OK**: Confirm marketplace ads render properly and listing creation succeeds.
- [ ] **Observability OK**: Check that `application.log` is writable and the Observability dashboard loads telemetry correctly.
