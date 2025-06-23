#!/bin/bash

echo "$OLDPWD/.env"

if [ -f "$OLDPWD/.env" ]; then # Check if the file named ".env" exists
    . "$OLDPWD/.env"
else
    echo "Error: Environment file not found in the current directory!"
    exit 1
fi

if [ -z "$DB_NAME" ] || [ -z "$DB_ROOT_PASSWORD" ]; then
    echo "Error: DB_NAME or DB_ROOT_PASSWORD not set or empty in Environment file"
    exit 1
fi

PARENT_DIR="$(dirname "$OLDPWD")"

DB_BACKUP_DEST_DIR="$PARENT_DIR/cron-db-backups"


# Login to PHP container
sudo docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
sudo docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -p$DB_ROOT_PASSWORD $DB_NAME | gzip  > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz"

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz $DB_BACKUP_DEST_DIR/backup_pins-$(date +%Y-%m-%d).sql.gz

# End of script
