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
  'api_url'                 => $_ENV['OPENAI_API_URL'] ?? getenv('OPENAI_API_URL'),
  'api_key'                 => $_ENV['OPENAI_API_KEY'] ?? getenv('OPENAI_API_KEY'),
  'api_model'               => $_ENV['OPENAI_API_MODEL'] ?? getenv('OPENAI_API_MODEL') ?: 'text-embedding-ada-002',
  'resource_url'            => $_ENV['OPENAI_RESOURCE_URL'] ?? getenv('OPENAI_RESOURCE_URL') ?: 'https://pins-openai-library-poc.openai.azure.com',
  'deployment_id'           => $_ENV['OPENAI_DEPLOYMENT_ID'] ?? getenv('OPENAI_DEPLOYMENT_ID') ?: 'text-embedding-ada-002',
  'embedding_api_key'       => $_ENV['OPENAI_EMBEDDING_API_KEY'] ?? getenv('OPENAI_EMBEDDING_API_KEY'),
  'metric_name'             => $_ENV['OPENAI_METRIC'] ?? getenv('OPENAI_METRIC'),
  'm'                       => $_ENV['OPENAI_M'] ?? getenv('OPENAI_M'),
  'ef_construction'         => $_ENV['OPENAI_EFCONSTRUCTION'] ?? getenv('OPENAI_EFCONSTRUCTION'),
  'ef_search'               => $_ENV['OPENAI_EFSEARCH'] ?? getenv('OPENAI_EFSEARCH'),
  'algorithm_name'          => $_ENV['OPENAI_ALGORITHAM'] ?? getenv('OPENAI_ALGORITHAM'),
  'vectorizer_name'         => $_ENV['OPENAI_VECTORIZER'] ?? getenv('OPENAI_VECTORIZER'),
  'profile_name'            => $_ENV['OPENAI_PROFILE'] ?? getenv('OPENAI_PROFILE'),
  'semantic_config_name'    => $_ENV['OPENAI_SEMANTIC_CONFIG'] ?? getenv('OPENAI_SEMANTIC_CONFIG'),
  'semantic_title_field'    => $_ENV['OPENAI_SEMANTIC_TITLE_FIELD'] ?? getenv('OPENAI_SEMANTIC_TITLE_FIELD'),
  'semantic_content_fields' => $_ENV['OPENAI_SEMANTIC_CONTENT_FIELDS'] ?? getenv('OPENAI_SEMANTIC_CONTENT_FIELDS'),
  'semantic_keyword_fields' => $_ENV['OPENAI_SEMANTIC_KEYWORD_FIELDS'] ?? getenv('OPENAI_SEMANTIC_KEYWORD_FIELDS'),
  'field_to_vectorise'      => $_ENV['OPENAI_FIELD_TO_VECTORISE'] ?? getenv('OPENAI_FIELD_TO_VECTORISE'),
  'field_to_sanitize'       => $_ENV['OPENAI_FIELD_TO_SANITIZE'] ?? getenv('OPENAI_FIELD_TO_SANITIZE')
];