#!/bin/bash
sudo docker exec -it -u 0 pins_php bash -c "drush search-api-index --limit=1000 pins_content_index"

