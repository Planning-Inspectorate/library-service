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

$search_api_index = 'search_api.index.pins_content_index_azure';

// Set index name based on environment
$env = $_ENV['APP_ENV'] ? $_ENV['APP_ENV']: getenv('APP_ENV');


switch ($env) {
  case 'prod':
    $index_name = 'azureblob-prod-index';
    break;
  case 'stage':
    $index_name = 'azureblob-stage-index';
    break;
  default:
    $index_name = 'azureblob-dev-index';
    break;
}

$config[$search_api_index]['third_party_settings']['search_api_aais']['index_name'] = $index_name;


$config['pins_search_azure.settings'] = [
  'api_url'                => getenv('OPENAI_API_URL'),
  'api_key'                => getenv('OPENAI_API_KEY'),
  'api_model'              => getenv('OPENAI_API_MODEL') ?: 'text-embedding-ada-002',
  'resource_url'           => getenv('OPENAI_RESOURCE_URL') ?: 'https://pins-openai-library-poc.openai.azure.com',
  'deployment_id'          => getenv('OPENAI_DEPLOYMENT_ID') ?: 'text-embedding-ada-002',
  'embedding_api_key'      => getenv('OPENAI_EMBEDDING_API_KEY'),
  'metric_name'            => getenv('OPENAI_METRIC'),
  'm'                      => getenv('OPENAI_M'),
  'ef_construction'        => getenv('OPENAI_EFCONSTRUCTION'),
  'ef_search'              => getenv('OPENAI_EFSEARCH'),
  'algorithm_name'         => getenv('OPENAI_ALGORITHAM'),
  'vectorizer_name'        => getenv('OPENAI_VECTORIZER'),
  'profile_name'           => getenv('OPENAI_PROFILE'),
  'semantic_config_name'   => getenv('OPENAI_SEMANTIC_CONFIG'),
  'semantic_title_field'   => getenv('OPENAI_SEMANTIC_TITLE_FIELD'),
  'semantic_content_fields' => getenv('OPENAI_SEMANTIC_CONTENT_FIELDS'),
  'semantic_keyword_fields' => getenv('OPENAI_SEMANTIC_KEYWORD_FIELDS'),
  'field_to_vectorise' => getenv('OPENAI_FIELD_TO_VECTORISE'),
  'field_to_sanitize' => getenv('OPENAI_FIELD_TO_SANITIZE')
];

echo "Environment: " . getenv('APP_ENV') . "\n"; // Debugging line to check the environment variable
echo "Environment URL: " .  $_ENV['APP_ENV'] . "\n"; // Debugging line to check the search index URL
print_r($config['pins_search_azure.settings']); // Debugging line to check the settings array
echo $env;exit;
