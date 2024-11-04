# Login to PHP container
sudo docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
sudo docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -ppins_9001 kl_drupal | gzip  > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz"

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql.gz /home/KnowledgeDevAdmin/cron-db-backups/backup_pins-$(date +%Y-%m-%d).sql.gz

# End of script
