
# variables should be sorted A-Z

variable "apps_config" {
  description = "Config for the apps"
  type = object({
    app_service_plan = object({
      sku                      = string
      per_site_scaling_enabled = bool
      worker_count             = number
      zone_balancing_enabled   = bool
    })

    # auth = object({
    #   client_id                = string
    #   group_application_access = string
    #   # groups = object({
    #   #   inspectors           = string
    #   #   team_leads           = string
    #   #   national_team        = string
    #   #   api_inspector_groups = list(string)
    #   # })
    # })

    logging = object({
      level = string
    })

    managed_redis = object({
      sku_name                  = string
      high_availability_enabled = bool
      rdb_backup_frequency      = string
    })

    node_environment         = string
    private_endpoint_enabled = bool

  })
}

variable "common_config" {
  description = "Config for the common resources, such as action groups"
  type = object({
    resource_group_name = string
    action_group_names = object({
      iap      = string
      its      = string
      info_sec = string
    })
  })
}

variable "environment" {
  description = "The name of the environment in which resources will be deployed"
  type        = string
}

# variable "front_door_config" {
#   description = "Config for the frontdoor in tooling subscription"
#   type = object({
#     name        = string
#     rg          = string
#     ep_name     = string
#     use_tooling = bool
#   })
# }

variable "monitoring_config" {
  description = "Config for monitoring"
  type = object({
    app_insights_web_test_enabled = bool
    log_daily_cap                 = number
  })
}

variable "mysql_config" {
  description = "Config for the MySQL flexible server"
  type = object({
    admin = object({
      login_username = string
      object_id      = string
    })
    backup_retention_days = number
    sku_name              = string
  })
}

variable "vnet_config" {
  description = "VNet configuration"
  type = object({
    address_space                        = string
    apps_subnet_address_space            = string
    main_subnet_address_space            = string
    mysql_subnet_address_space           = string
    secondary_address_space              = string
    secondary_apps_subnet_address_space  = string
    secondary_subnet_address_space       = string
    secondary_mysql_subnet_address_space = string
  })
}

variable "tooling_config" {
  description = "Config for the tooling subscription resources"
  type = object({
    container_registry_name = string
    container_registry_rg   = string
    network_name            = string
    network_rg              = string
    subscription_id         = string
  })
}

# variable "web_domains" {
#   description = "value for web domain"
#   type = object({
#     web = string
#   })
# }
