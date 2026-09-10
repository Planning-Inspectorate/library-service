locals {
  org                = "pins"
  service_name       = "library-service"
  primary_location   = "uk-south"
  secondary_location = "uk-west"

  resource_suffix           = "${local.service_name}-${var.environment}"
  secondary_resource_suffix = "${local.service_name}-secondary-${var.environment}"
  short_resource_suffix     = "lib-service-${var.environment}"

  secrets = [
    "library-service-client-secret",
    "mysql-admin-password"
  ]

  # tflint-ignore: terraform_unused_declarations
  key_vault_refs = merge(
    {
      for k, v in azurerm_key_vault_secret.manual_secrets : k => "@Microsoft.KeyVault(SecretUri=${v.versionless_id})"
    },
    {
      "app-insights-connection-string" = "@Microsoft.KeyVault(SecretUri=${azurerm_key_vault_secret.app_insights_connection_string.versionless_id})",
      "session-secret-php"             = "@Microsoft.KeyVault(SecretUri=${azurerm_key_vault_secret.php_session_secret.versionless_id})",
      "session-secret-crond"           = "@Microsoft.KeyVault(SecretUri=${azurerm_key_vault_secret.crond_session_secret.versionless_id})",
      "session-secret-tika"            = "@Microsoft.KeyVault(SecretUri=${azurerm_key_vault_secret.tika_session_secret.versionless_id})",
      "sql-app-connection-string"      = "@Microsoft.KeyVault(SecretUri=${azurerm_key_vault_secret.mysql_app_connection_string.versionless_id})"
    }
  )

  # tflint-ignore: terraform_unused_declarations
  tech_emails = [for rec in azurerm_monitor_action_group.library_tech.email_receiver : rec.email_address]
  action_group_ids = {
    tech            = azurerm_monitor_action_group.library_tech.id
    service_manager = azurerm_monitor_action_group.library_service_manager.id
    iap             = data.azurerm_monitor_action_group.common["iap"].id,
    its             = data.azurerm_monitor_action_group.common["its"].id,
    info_sec        = data.azurerm_monitor_action_group.common["info_sec"].id
  }
  tags = {
    CreatedBy   = "terraform"
    Environment = var.environment
    ServiceName = local.service_name
    location    = local.primary_location
  }
}
