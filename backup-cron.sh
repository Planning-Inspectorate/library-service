# Set this variable to 'dev', 'test', or 'prod' based on your current environment
ENV="dev"

# Load environment variables
if [ -f ".env.$ENV" ]; then
    source ".env.$ENV"
else
    echo "Error: Environment file .env.$ENV not found!"
    exit 1
fi

# Ensure DB_NAME and DB_PASSWORD are set
if [ -z "$DB_NAME" ] || [ -z "$DB_ROOT_PASSWORD" ]; then
    echo "Error: DB_NAME or DB_ROOT_PASSWORD not set in .env.$ENV"
    exit 1
fi

# Login to PHP container
sudo docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
sudo docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -p$DB_ROOT_PASSWORD $DB_NAME | gzip  > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz"

echo "Backup for database '$DB_NAME' completed successfully for environment '$ENV'."

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz /home/KnowledgeDevAdmin/cron-db-backups/backup_pins-$(date +%Y-%m-%d).sql.gz

# End of script
