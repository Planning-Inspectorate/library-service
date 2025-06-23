#!/bin/bash

PROJECT_PREFIX="pins"

if [ -f "$PWD/$PROJECT_PREFIX/.env" ]; then # Check if the file named ".env" exists
    . "$PWD/$PROJECT_PREFIX/.env"
else
    echo "Error: Environment file $PWD/.env not found in the current directory!"
    exit 1
fi

if [ -z "$DB_NAME" ] || [ -z "$DB_ROOT_PASSWORD" ]; then
    echo "Error: DB_NAME or DB_ROOT_PASSWORD not set or empty in Environment file"
    exit 1
fi

#DB_BACKUP_DEST_DIR="$(dirname "$PWD")/cron-db-backups"

DB_BACKUP_DEST_DIR="$PWD/cron-db-backups"

# Login to PHP container
sudo docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
sudo docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -p$DB_ROOT_PASSWORD $DB_NAME | gzip  > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz"

echo "mysqldump -u root -p$DB_ROOT_PASSWORD $DB_NAME"

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz $DB_BACKUP_DEST_DIR/backup_pins-$(date +%Y-%m-%d).sql.gz

echo "Copied the SQL file to db-backup folder $DB_BACKUP_DEST_DIR"
# End of script
