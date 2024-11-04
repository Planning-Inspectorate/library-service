# Login to PHP container
sudo docker exec -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
sudo docker exec -u 0 pins_mariadb bash -c "mysqldump -u root -ppins_9001 kl_drupal > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql"

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# Compress the SQL files
sudo tar -zcvf /home/KnowledgeDevAdmin/cron-db-backups/backup_pins-$(date +%Y-%m-%d).tar.gz /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# Sleep for 200 seconds
sleep 200

# Remove created db backup from mysql container and db backup folder
sudo docker exec -u 0 pins_mariadb bash -c "rm /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql" 

# Remove created db backup from db backup folder.
rm -f /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# End of script

