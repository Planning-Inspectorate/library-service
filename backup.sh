#!/bin/bash

# Login to MariaDB container
sudo docker exec -it -u 0 pins_mariadb bash

# Perform MySQL dump
mysqldump -u root -ppins_9001 kl_drupal > backup_pins-$(date +%Y-%m-%d).sql

# Exit from the container
exit

# Navigate to home folder
cd /home/KnowledgeDevAdmin/

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql db-backup/backup_pins-$(date +%Y-%m-%d).sql

# End of script