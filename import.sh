#!/bin/bash

# Copy the SQL file to db-backup folder in parent directory
sudo docker cp ../db-backup/backup_pins-$(date +%Y-%m-%d).sql pins_mariadb:/var/lib/mysql/backup_pins-$(date +%Y-%m-%d).sql 

# DB Import in MariaDB container
sudo docker exec -it -u 0 pins_mariadb bash -c "mysql -u root -ppins_9001 d10_pins_test_library < backup_pins-$(date +%Y-%m-%d).sql"

# Cache Clear in php container
sudo docker exec -it -u 0 pins_php bash -c "drush cr"

# End of script