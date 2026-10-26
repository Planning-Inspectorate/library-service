resource "azurerm_storage_account" "storage" {
  # checkov:skip=CKV_AZURE_33: "Ensure Storage logging is enabled for Queue service for read, write and delete requests"
  # checkov:skip=CKV2_AZURE_40: "Ensure storage account is not configured with Shared Key authorization
  # checkov:skip=CKV2_AZURE_41: "Ensure storage account is configured with SAS expiration policy"
  # checkov:skip=CKV2_AZURE_38: "Ensure soft-delete is enabled on Azure storage account"
  # checkov:skip=CKV2_AZURE_1: "Ensure storage for critical data are encrypted with Customer Managed Key"
  # checkov:skip=CKV_AZURE_43: "Ensure Storage Accounts adhere to the naming rules"

  name                             = "pinsstlibrary${var.environment}"
  resource_group_name              = azurerm_resource_group.primary.name
  location                         = module.primary_region.location
  account_tier                     = "Standard"
  account_replication_type         = "GRS"
  allow_nested_items_to_be_public  = false
  cross_tenant_replication_enabled = false
  https_traffic_only_enabled       = true
  min_tls_version                  = "TLS1_2"
  public_network_access_enabled    = false

  network_rules {
    default_action = "Deny"
    bypass         = ["AzureServices"]
  }

  tags = local.tags
}


resource "azurerm_private_endpoint" "storage_file" {
  name                = "${local.org}-pe-st-storage-file-${local.resource_suffix}"
  location            = module.primary_region.location
  resource_group_name = azurerm_resource_group.primary.name
  subnet_id           = azurerm_subnet.main.id

  private_dns_zone_group {
    name                 = "${local.org}-pdns-${local.service_name}-storage-file-${var.environment}"
    private_dns_zone_ids = [data.azurerm_private_dns_zone.storage_file.id]
  }

  private_service_connection {
    name                           = "${local.org}-psc-storage-file-${local.resource_suffix}"
    private_connection_resource_id = azurerm_storage_account.storage.id
    subresource_names              = ["file"]
    is_manual_connection           = false
  }

  tags = local.tags
}

resource "azurerm_storage_share" "library_documents" {
  name               = "library-documents"
  storage_account_id = azurerm_storage_account.storage.id
  quota              = 100
}

#### RBAC
resource "azurerm_role_assignment" "app_php_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_php.principal_id
}

resource "azurerm_role_assignment" "app_php_staging_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_php.staging_principal_id
}

resource "azurerm_role_assignment" "app_crond_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_crond.principal_id
}

resource "azurerm_role_assignment" "app_crond_staging_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_crond.staging_principal_id
}

resource "azurerm_role_assignment" "app_tika_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_tika.principal_id
}

resource "azurerm_role_assignment" "app_tika_staging_storage_file_share_contributor" {
  scope                = azurerm_storage_account.storage.id
  role_definition_name = "Storage File Data SMB Share Contributor"
  principal_id         = module.app_tika.staging_principal_id
}
