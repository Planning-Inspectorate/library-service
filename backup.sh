#!/bin/bash

# Login to MariaDB container
sudo docker exec -it -u 0 pins_mariadb bash -c "mysqldump -u root -ppins_9001 kl_drupal > /var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql"

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql ../db-backup/backup_pins-$(date +%Y-%m-%d).sql

# End of script
