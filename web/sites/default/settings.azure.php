<?php

// phpcs:ignoreFile

/**
 * @file
 * This is a settings.azure.php file for setting up Azure.
 *
 * Add environment-specific overrides for Azure settings here.
 * This file is managed by the project and should be updated as needed for Azure integration.
 */

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$search_api_config = 'search_api.server.pins_content_index_azure';

// Fetch values (Dotenv handles the heavy lifting)
$search_index_url =  $_ENV['SEARCH_INDEX_URL'] ? $_ENV['SEARCH_INDEX_URL']:  getenv('SEARCH_INDEX_URL');
$search_index_api_version = $_ENV['SEARCH_INDEX_API_VERSION'] ? $_ENV['SEARCH_INDEX_API_VERSION']:  getenv('SEARCH_INDEX_API_VERSION');
$search_index_api_key = $_ENV['SEARCH_INDEX_API_KEY'] ? $_ENV['SEARCH_INDEX_API_KEY']:  getenv('SEARCH_INDEX_API_KEY');

// Apply overrides only if values exist
if ($search_index_url) {
  $config[$search_api_config]['backend_config']['connector_config']['url'] = $search_index_url;
}
if ($search_index_api_version) {
  $config[$search_api_config]['backend_config']['connector_config']['api_version'] = $search_index_api_version;
}
if ($search_index_api_key) {
  $config[$search_api_config]['backend_config']['connector_config']['api_key'] = $search_index_api_key;
}