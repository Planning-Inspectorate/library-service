resource "azurerm_mysql_flexible_server" "primary" {
  # checkov:skip=CKV_AZURE_94: "Ensure that My SQL server enables geo-redundant backups"
  name                   = "${local.org}-mysql-${local.resource_suffix}"
  resource_group_name    = azurerm_resource_group.primary.name
  location               = module.primary_region.location
  administrator_login    = random_id.mysql_admin_username.b64_url
  administrator_password = random_password.mysql_admin_password.result
  backup_retention_days  = var.mysql_config.backup_retention_days
  delegated_subnet_id    = azurerm_subnet.mysql.id
  private_dns_zone_id    = data.azurerm_private_dns_zone.mysql.id
  sku_name               = var.mysql_config.sku_name

  azuread_administrator {
    login_username = var.mysql_config.admin.login_username
    object_id      = var.mysql_config.admin.object_id
  }

  lifecycle {
    prevent_destroy = true
  }

  depends_on = [azurerm_private_dns_zone_virtual_network_link.dns_database]

  tags = local.tags
}

resource "azurerm_private_endpoint" "sql_primary" {
  name                = "${local.org}-pe-${local.service_name}-sql-${var.environment}"
  resource_group_name = azurerm_resource_group.primary.name
  location            = module.primary_region.location
  subnet_id           = azurerm_subnet.main.id

  private_dns_zone_group {
    name                 = "sqlserverprivatednszone"
    private_dns_zone_ids = [data.azurerm_private_dns_zone.mysql.id]
  }

  private_service_connection {
    name                           = "privateendpointconnection"
    private_connection_resource_id = azurerm_mysql_flexible_server.primary.id
    subresource_names              = ["mysqlServer"]
    is_manual_connection           = false
  }

  tags = local.tags
}

resource "azurerm_mysql_flexible_database" "primary" {
  name                = "${local.org}-mysqldb-${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  server_name         = azurerm_mysql_flexible_server.primary.name
  charset             = "utf8mb4"
  collation           = "utf8mb4_unicode_ci"
}

resource "random_id" "mysql_admin_username" {
  byte_length = 6
  prefix      = "${local.service_name}_admin_"
}

resource "random_password" "mysql_admin_password" {
  length           = 32
  special          = true
  override_special = "#&-_+"
  min_lower        = 2
  min_upper        = 2
  min_numeric      = 2
  min_special      = 2
}

resource "random_id" "mysql_app_username" {
  byte_length = 6
  prefix      = "${local.service_name}_app_"
}

resource "random_password" "mysql_app_password" {
  length           = 32
  special          = true
  override_special = "#&-_+"
  min_lower        = 2
  min_upper        = 2
  min_numeric      = 2
  min_special      = 2
}

resource "azurerm_key_vault_secret" "mysql_admin_connection_string" {
  #checkov:skip=CKV_AZURE_41: TODO: Secret rotation

  key_vault_id = azurerm_key_vault.main.id
  name         = "${local.service_name}-mysql-admin-connection-string"
  value = join(
    ";",
    [
      "Server=${azurerm_mysql_flexible_server.primary.fqdn}",
      "Database=${azurerm_mysql_flexible_database.primary.name}",
      "user=${random_id.mysql_admin_username.b64_url}",
      "password=${random_password.mysql_admin_password.result}",
      "trustServerCertificate=false"
    ]
  )
  content_type = "connection-string"

  tags = local.tags
}

resource "azurerm_key_vault_secret" "mysql_app_connection_string" {
  #checkov:skip=CKV_AZURE_41: TODO: Secret rotation

  key_vault_id = azurerm_key_vault.main.id
  name         = "${local.service_name}-mysql-app-connection-string"
  value = join(
    ";",
    [
      "Server=${azurerm_mysql_flexible_server.primary.fqdn}",
      "Database=${azurerm_mysql_flexible_database.primary.name}",
      "user=${random_id.mysql_app_username.b64_url}",
      "password=${random_password.mysql_app_password.result}",
      "trustServerCertificate=false"
    ]
  )
  content_type = "connection-string"

  tags = local.tags
}

## Metric Alerts
resource "azurerm_monitor_metric_alert" "mysql_cpu" {
  name                = "mysql-cpu-alert-${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Alert when MySQL CPU exceeds threshold"
  severity            = 2
  frequency           = "PT5M"
  window_size         = "PT15M"

  criteria {
    metric_namespace = "Microsoft.DBforMySQL/flexibleServers"
    metric_name      = "cpu_percent"
    aggregation      = "Average"
    operator         = "GreaterThan"
    threshold        = 80
  }

  action {
    action_group_id = local.action_group_ids.tech
  }

  tags = local.tags
}

resource "azurerm_monitor_metric_alert" "mysql_memory" {
  name                = "mysql-memory-alert-${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Alert when MySQL memory exceeds threshold"
  severity            = 2
  frequency           = "PT5M"
  window_size         = "PT15M"

  criteria {
    metric_namespace = "Microsoft.DBforMySQL/flexibleServers"
    metric_name      = "memory_percent"
    aggregation      = "Average"
    operator         = "GreaterThan"
    threshold        = 80
  }

  action {
    action_group_id = local.action_group_ids.tech
  }

  tags = local.tags
}

resource "azurerm_monitor_metric_alert" "mysql_storage" {
  name                = "mysql-storage-alert-${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Alert when MySQL storage exceeds threshold"
  severity            = 2
  frequency           = "PT5M"
  window_size         = "PT15M"

  criteria {
    metric_namespace = "Microsoft.DBforMySQL/flexibleServers"
    metric_name      = "storage_percent"
    aggregation      = "Average"
    operator         = "GreaterThan"
    threshold        = 80
  }

  action {
    action_group_id = local.action_group_ids.tech
  }

  tags = local.tags
}

resource "azurerm_monitor_metric_alert" "mysql_failed_connections" {
  name                = "mysql-failed-connections-alert-${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Alert when MySQL failed connections exceed threshold"
  severity            = 1
  frequency           = "PT5M"
  window_size         = "PT15M"

  criteria {
    metric_namespace = "Microsoft.DBforMySQL/flexibleServers"
    metric_name      = "aborted_connections"
    aggregation      = "Total"
    operator         = "GreaterThan"
    threshold        = 10
  }

  action {
    action_group_id = local.action_group_ids.tech
  }

  tags = local.tags
}
