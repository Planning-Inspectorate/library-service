#!/bin/bash
docker exec -u 0 pins_php bash -c "drush search-api-index --limit=1000 pins_content_index && drush search-api-index --limit=10 pins_content_index_azure"
