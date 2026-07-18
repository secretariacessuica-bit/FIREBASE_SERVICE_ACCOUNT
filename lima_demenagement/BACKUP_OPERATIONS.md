# Backup Operations Manual — LIMA Solutions ERP

This document outlines the backup scope, schedule, and execution commands for the LIMA Solutions ERP platform.

---

## 1. Backup Scope & Retention

All platform data is categorized into three critical scopes:

### Database (MySQL/MariaDB)
- **Schedule**: Full database export executed every 24 hours.
- **Retention**: **30 Days** of local/remote daily archives.
- **Compression**: `gzip` format.
- **Validation**: Compare export file size and run a syntax/integrity check before archiving.

### Private Storage
- **Directory**: `/private_lima/storage/`
- **Scope**:
  - `project_photos/`: Uploaded field operations photos.
  - `project_signatures/`: Customer sign-off PNG images.
  - `marketplace_photos/`: Images of furniture listed for moderation/sale/donation.
  - `documents/` & `attachments/`: Shared files.
- **Schedule**: Daily sync/archive.
- **Retention**: 30 Days.
- **Compression**: Tarball (`.tar.gz`).

### Configuration & Credentials
- **File**: `/private_lima/config.php` (contains production DB credentials, API keys, and SMTP server tokens).
- **Schedule**: Backup on change.

---

## 2. Reference Backup Scripts

### Database Backup Command Block
```bash
#!/bin/bash
# LIMA Solutions ERP - Daily DB Backup Script
BACKUP_DIR="/path/to/backups/db"
DB_NAME="6o9v7p_erp"
DATE=$(date +%Y-%m-%d)
FILENAME="${BACKUP_DIR}/db_backup_${DATE}.sql.gz"

# 1. Create full compressed dump
mysqldump -u [DB_USER] -p[DB_PASSWORD] --single-transaction --quick --lock-tables=false ${DB_NAME} | gzip > ${FILENAME}

# 2. Verify file size and checksum
if [ -s "${FILENAME}" ]; then
    echo "Backup completed successfully: ${FILENAME}"
    # Generate checksum
    sha256sum ${FILENAME} > "${FILENAME}.sha256"
else
    echo "CRITICAL: Database backup file is empty!"
    exit 1
fi

# 3. Retention policy: clean backups older than 30 days
find ${BACKUP_DIR} -name "db_backup_*.sql.gz" -mtime +30 -exec rm {} \;
```

### Storage Backup Command Block
```bash
#!/bin/bash
# LIMA Solutions ERP - Daily Storage Backup Script
BACKUP_DIR="/path/to/backups/storage"
SOURCE_DIR="/path/to/private_lima/storage"
DATE=$(date +%Y-%m-%d)
FILENAME="${BACKUP_DIR}/storage_backup_${DATE}.tar.gz"

# Create archive
tar -czf ${FILENAME} -C ${SOURCE_DIR} .

# Verify integrity and retention
if [ -s "${FILENAME}" ]; then
    echo "Storage backup completed: ${FILENAME}"
    find ${BACKUP_DIR} -name "storage_backup_*.tar.gz" -mtime +30 -exec rm {} \;
else
    echo "CRITICAL: Storage backup failed!"
    exit 1
fi
```
