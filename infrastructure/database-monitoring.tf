## https://learn.microsoft.com/en-us/azure/mysql/flexible-server/tutorial-configure-audit#set-up-diagnostics

resource "azurerm_monitor_diagnostic_setting" "mysql_server" {
  name                       = "${local.service_name}-mysql-diagnostics-${var.environment}"
  target_resource_id         = azurerm_mysql_flexible_server.primary.id
  log_analytics_workspace_id = azurerm_log_analytics_workspace.main.id

  # Capture all MySQL logs in Log Analytics (audit/slow/general categories).
  enabled_log {
    category_group = "allLogs"
  }

}

# Metric Alerts (MySQL Flexible Server)
resource "azurerm_monitor_metric_alert" "mysql_db_cpu_alert" {
  name                = "${local.service_name} MySQL CPU Alert ${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Action triggers when MySQL CPU percent is greater than 80."
  window_size         = "PT5M"
  frequency           = "PT1M"
  severity            = 2
  enabled             = true

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

resource "azurerm_monitor_metric_alert" "mysql_db_memory_alert" {
  name                = "${local.service_name} MySQL Memory Alert ${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Action triggers when MySQL memory percent is greater than 80."
  window_size         = "PT5M"
  frequency           = "PT1M"
  severity            = 2
  enabled             = true

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

resource "azurerm_monitor_metric_alert" "mysql_db_io_alert" {
  name                = "${local.service_name} MySQL IO Alert ${local.resource_suffix}"
  resource_group_name = azurerm_resource_group.primary.name
  scopes              = [azurerm_mysql_flexible_server.primary.id]
  description         = "Action triggers when MySQL IO percent is greater than 80."
  window_size         = "PT5M"
  frequency           = "PT1M"
  severity            = 2
  enabled             = true

  criteria {
    metric_namespace = "Microsoft.DBforMySQL/flexibleServers"
    metric_name      = "io_consumption_percent"
    aggregation      = "Average"
    operator         = "GreaterThan"
    threshold        = 80
  }

  action {
    action_group_id = local.action_group_ids.tech
  }

  tags = local.tags
}

