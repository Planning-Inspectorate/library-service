
module "app_crond" {
  #checkov:skip=CKV_TF_1: Use of commit hash are not required for our Terraform modules
  source = "github.com/Planning-Inspectorate/infrastructure-modules.git//modules/node-app-service?ref=1.56"

  resource_group_name = azurerm_resource_group.primary.name
  location            = module.primary_region.location

  # naming
  app_name        = "crond"
  resource_suffix = var.environment
  service_name    = "library-service"
  tags            = local.tags

  # service plan & scaling
  app_service_plan_id                  = azurerm_service_plan.apps.id
  app_service_plan_resource_group_name = azurerm_resource_group.primary.name
  worker_count                         = var.apps_config.app_service_plan.worker_count

  # container
  container_registry_name = var.tooling_config.container_registry_name
  container_registry_rg   = var.tooling_config.container_registry_rg
  image_name              = "libraryservice/crond"

  # networking
  app_service_private_dns_zone_id = data.azurerm_private_dns_zone.app_service.id
  inbound_vnet_connectivity       = var.apps_config.private_endpoint_enabled
  integration_subnet_id           = azurerm_subnet.apps.id
  endpoint_subnet_id              = azurerm_subnet.main.id
  outbound_vnet_connectivity      = true
  # public access via Front Door
  front_door_restriction = false
  public_network_access  = true

  # monitoring
  action_group_ids           = local.action_group_ids
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id
  monitoring_alerts_enabled  = var.alerts_enabled

  app_settings = {
    APPLICATIONINSIGHTS_CONNECTION_STRING      = local.key_vault_refs["app-insights-connection-string"]
    ApplicationInsightsAgent_EXTENSION_VERSION = "~3"
    NODE_ENV                                   = var.apps_config.node_environment
    ENVIRONMENT                                = var.environment

    # APP_HOSTNAME                               = var.domains.crond
    # AUTH_GROUP_APPLICATION_ACCESS              = var.apps_config.auth.group_application_access
    # AUTH_CLIENT_ID                             = var.apps_config.auth.client_id
    # AUTH_CLIENT_SECRET                         = local.key_vault_refs["library-service-client-secret"]
    AUTH_TENANT_ID = data.azurerm_client_config.current.tenant_id

    # logging
    LOG_LEVEL = var.apps_config.logging.level

    # database connection
    SQL_CONNECTION_STRING = local.key_vault_refs["sql-app-connection-string"]

    # retries
    RETRY_MAX_ATTEMPTS = "3"
    # got default retry codes
    # https://github.com/sindresorhus/got/blob/main/documentation/7-retry.md
    RETRY_STATUS_CODES = "408,413,429,500,502,503,504,521,522,524"

    # sessions
    MANAGED_REDIS_URL = local.managed_redis_url
    SESSION_SECRET    = local.key_vault_refs["session-secret-crond"]
  }

  providers = {
    azurerm         = azurerm
    azurerm.tooling = azurerm.tooling
  }
}

## RBAC for secrets
resource "azurerm_role_assignment" "app_secrets_user" {
  scope                = azurerm_key_vault.main.id
  role_definition_name = "Key Vault Secrets User"
  principal_id         = module.app_crond.principal_id
}

# RBAC for secrets (staging slot)
resource "azurerm_role_assignment" "app_crond_staging_secrets_user" {
  scope                = azurerm_key_vault.main.id
  role_definition_name = "Key Vault Secrets User"
  principal_id         = module.app_crond.staging_principal_id
}

## sessions
resource "random_password" "crond_session_secret" {
  length  = 32
  special = true
}

resource "azurerm_key_vault_secret" "crond_session_secret" {
  #checkov:skip=CKV_AZURE_41: TODO: Secret rotation
  key_vault_id = azurerm_key_vault.main.id
  name         = "${local.service_name}-crond-session-secret"
  value        = random_password.crond_session_secret.result
  content_type = "session-secret"

  tags = local.tags
}

# managed redis access
resource "azurerm_managed_redis_access_policy_assignment" "crond" {
  managed_redis_id = azurerm_managed_redis.cache.id
  object_id        = module.app_crond.principal_id
}
resource "azurerm_managed_redis_access_policy_assignment" "crond_staging" {
  managed_redis_id = azurerm_managed_redis.cache.id
  object_id        = module.app_crond.staging_principal_id
}
