#!/bin/bash

# Goyaa Database Backup Script
# This script dumps the MySQL database from the Docker container to the host filesystem.

set -e

BACKUP_DIR="/root/goya/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="$BACKUP_DIR/db_backup_$TIMESTAMP.sql"
DB_CONTAINER="goya-db"

# Create backup directory if it doesn't exist
mkdir -p "$BACKUP_DIR"

echo "Starting backup of $DB_CONTAINER to $BACKUP_FILE..."

# Perform the dump
# Using root user since we have the password 'root'
docker exec "$DB_CONTAINER" mysqldump -u root -proot laravel > "$BACKUP_FILE"

# Compress the backup
gzip "$BACKUP_FILE"

echo "Backup completed: ${BACKUP_FILE}.gz"

# Keep only last 7 days of backups
find "$BACKUP_DIR" -name "db_backup_*.sql.gz" -mtime +7 -delete

echo "Old backups cleaned up."
