#!/bin/bash
drush search-api-index --limit=1000 pins_content_index >> /var/log/drush_cron.log 2>&1

