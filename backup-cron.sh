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

# Login to PHP container and clear cache
echo "Attempting to clear Drush cache and watchdog logs..."
docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"
if [ $? -ne 0 ]; then
    echo "Error: Failed to execute Drush commands in PHP container."
    exit 1
fi

# Login to MariaDB container
echo "Attempting to dump MariaDB database '$DB_NAME'..."
docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -p$DB_ROOT_PASSWORD $DB_NAME | gzip  > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz"
if [ $? -ne 0 ]; then
    echo "Error: Failed to dump MariaDB database '$DB_NAME'."
    exit 1
fi
echo "Database '$DB_NAME' dumped successfully inside the container."


# Copy the SQL file to db-backup folder in parent directory
echo "Attempting to copy the SQL file to db-backup folder..."
docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz $DB_BACKUP_DEST_DIR/backup_pins-$(date +%Y-%m-%d).sql.gz
if [ $? -ne 0 ]; then
    echo "Error: Failed to copy the SQL file to $DB_BACKUP_DEST_DIR."
    exit 1
fi
echo "Copied the SQL file to db-backup folder $DB_BACKUP_DEST_DIR"

# End of script
