#!/usr/bin/env bash
set -e
DB_NAME=${1:-yenipazaryeri}
OUT=./backups
mkdir -p "$OUT"
STAMP=$(date +"%F-%H%M")
mysqldump "$DB_NAME" | gzip > "$OUT/${DB_NAME}-${STAMP}.sql.gz"
echo "Backup => $OUT/${DB_NAME}-${STAMP}.sql.gz"
