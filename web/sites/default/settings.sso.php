<?php

// phpcs:ignoreFile

/**
 * @file
 * This is a settings.sso.php file for setting up OpenID Connect configuration.
 *
 * Add environment-specific overrides for OpenID Connect client settings here.
 * This file is managed by the project and should be updated as needed for SSO integration.
 */

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();


//Override openid settings
$openid_config_name = 'openid_connect.settings';

// 1. Override Role Mappings
$role_mappings_env  = $_ENV['OIDC_ROLE_MAPPING'] ? $_ENV['OIDC_ROLE_MAPPING']: getenv('OIDC_ROLE_MAPPING');
if ($role_mappings_env) {
   $new_mappings = [];
   $pairs = explode(',', $role_mappings_env);
   foreach ($pairs as $pair_string) {
        $parts = explode('|', trim($pair_string));
        if (count($parts) === 2) {
            $role_name = trim($parts[0]);
            $azure_id = trim($parts[1]);
            $new_mappings[$role_name] = [$azure_id];
        }
   }

   if (!empty($new_mappings)) {
    $existing_mappings = $config[$openid_config_name]['role_mappings'] ?? [];
    $config[$openid_config_name]['role_mappings'] = array_merge($existing_mappings, $new_mappings);
   }  
 }
$config[$openid_config_name]['user_login_display'] = 'below';
$config[$openid_config_name]['redirect_login'] = 'user/login';
$config[$openid_config_name]['redirect_logout'] = 'user/logout';
$config[$openid_config_name]['connect_existing_users'] = TRUE;
$config[$openid_config_name]['always_save_userinfo'] = TRUE;

//Override client settings

$openid_client_config_name = 'openid_connect.client.library_open_id';

// 1. Override Client ID
$client_id = $_ENV['OIDC_CLIENT_ID'] ? $_ENV['OIDC_CLIENT_ID'] : getenv('OIDC_CLIENT_ID');
if ($client_id) {
  $config[$openid_client_config_name]['settings']['client_id'] = $client_id;
}

// 2. Override Client Secret
$client_secret = $_ENV['OIDC_CLIENT_SECRET'] ? $_ENV['OIDC_CLIENT_SECRET'] : getenv('OIDC_CLIENT_SECRET');
if ($client_secret) {
  $config[$openid_client_config_name]['settings']['client_secret'] = $client_secret;
}

// 3. Optional: Override other endpoints using environment variables
$tenancy_id = $_ENV['OIDC_TENANCY_ID'] ? $_ENV['OIDC_TENANCY_ID'] : getenv('OIDC_TENANCY_ID');
if ($tenancy_id) {
  $config[$openid_client_config_name]['settings']['authorization_endpoint'] = 'https://login.microsoftonline.com/' . $tenancy_id . '/oauth2/v2.0/authorize';
  $config[$openid_client_config_name]['settings']['token_endpoint'] = 'https://login.microsoftonline.com/' . $tenancy_id . '/oauth2/v2.0/token';
  $config[$openid_client_config_name]['settings']['userinfo_endpoint'] = 'https://graph.microsoft.com/oidc/userinfo';
}