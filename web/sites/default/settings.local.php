<?php

$settings['config_sync_directory'] = '../config/sync';
$app_env = getenv('APP_ENV');
$config["config_split.config_split.dev"]["status"] = ($app_env === 'dev');
$config['config_split.config_split.stage']['status'] = ($app_env === 'test');
$config['config_split.config_split.prod']['status'] = ($app_env === 'prod');

// In web/sites/default/settings.php
$config['pins_search_azure.settings']['api_url'] = getenv('OPENAI_API_URL');
$config['pins_search_azure.settings']['api_key'] = getenv('OPENAI_API_KEY');
$config['pins_search_azure.settings']['api_model'] = getenv('OPENAI_API_MODEL') ?: 'text-embedding-ada-002';


$config['pins_search_azure.settings']['resource_url'] = getenv('OPENAI_RESOURCE_URL') ?: 'https://pins-openai-library-poc.openai.azure.com';
$config['pins_search_azure.settings']['deployment_id'] = getenv('OPENAI_DEPLOYMENT_ID') ?: 'text-embedding-ada-002';    
$config['pins_search_azure.settings']['embedding_api_key'] = getenv('OPENAI_EMBEDDING_API_KEY');  

$config['pins_search_azure.settings']['metric_name'] = getenv('OPENAI_METRIC');  
$config['pins_search_azure.settings']['m'] = getenv('OPENAI_M');  
$config['pins_search_azure.settings']['ef_construction'] = getenv('OPENAI_EFCONSTRUCTION');  
$config['pins_search_azure.settings']['ef_search'] = getenv('OPENAI_EFSEARCH'); 
$config['pins_search_azure.settings']['algorithm_name'] = getenv('OPENAI_ALGORITHAM');
$config['pins_search_azure.settings']['vectorizer_name'] = getenv('OPENAI_VECTORIZER');
$config['pins_search_azure.settings']['profile_name'] = getenv('OPENAI_PROFILE');
$config['pins_search_azure.settings']['semantic_config_name'] = getenv('OPENAI_SEMANTIC_CONFIG');


$config['pins_search_azure.settings']['semantic_title_field'] = getenv('OPENAI_SEMANTIC_TITLE_FIELD');
$config['pins_search_azure.settings']['semantic_content_fields'] = getenv('OPENAI_SEMANTIC_CONTENT_FIELDS');
$config['pins_search_azure.settings']['semantic_keyword_fields'] = getenv('OPENAI_SEMANTIC_KEYWORD_FIELDS');
