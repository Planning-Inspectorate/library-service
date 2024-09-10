# Login to PHP container
#sudo docker exec -it -u 0 pins_php bash -c "drush watchdog:delete all --yes && drush cr"

# Login to MariaDB container
#sudo docker exec -it -u 0 pins_mariadb bash -c "mysqldump -u root -ppins_9001 kl_drupal > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql"

# Copy the SQL file to db-backup folder in parent directory
#sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# Compress the SQL files
#tar -zcvf /home/KnowledgeDevAdmin/cron-db-backups/backup_pins-$(date +%Y-%m-%d).tar.gz /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# Sleep for 20 seconds
#sleep 20

# Remove created db backup from mysql container and db backup folder
#sudo docker exec -it -u 0 pins_mariadb bash -c "rm /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql" 

# Remove created db backup from db backup folder.
#rm -f /home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql

# End of script

#!/bin/bash

# Function to execute a command within a Docker container and handle potential errors
function execute_command {
  container_name="$1"
  command="$2"

  if ! sudo docker exec -it -u 0 "$container_name" bash -c "$command"; then
    echo "Error executing command: $command in container $container_name"
    exit 1
  fi
}

# Clear watchdog messages and rebuild cache in the Drupal container
execute_command pins_php "drush watchdog:delete all --yes && drush cr"

# Dump the MySQL database and save the backup to the host
backup_file="/home/KnowledgeDevAdmin/db-backup/backup_pins-$(date +%Y-%m-%d).sql"
execute_command pins_mariadb "mysqldump -u root -ppins_9001 kl_drupal > $backup_file"

# Compress the backup file
tar -zcvf /home/KnowledgeDevAdmin/cron-db-backups/backup_pins-$(date +%Y-%m-%d).tar.gz "$backup_file"

# Sleep for 20 seconds
sleep 20

# Remove the backup files from both the container and the host
execute_command pins_mariadb "rm $backup_file"
rm -f "$backup_file"

# End of script