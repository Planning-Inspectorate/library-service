<?php

namespace Drupal\pins_search_azure\Plugin\AzureAiSearch\Connector;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pins_search_azure\Services\IdentityBackendClientFactory;
use Drupal\search_api_aais\BackendClient\BackendClientInterface;
use Drupal\search_api_aais\Connector\AzureAiSearchConnectorInterface;
use Drupal\search_api_aais\Connector\AzureAiSearchConnectorPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @AzureAiSearchConnector(
 *   id = "pins_identity",
 *   label = @Translation("Microsoft Entra ID"),
 *   description = @Translation("Connect using Azure identity credentials instead of an API key.")
 * )
 */
class IdentityConnector extends AzureAiSearchConnectorPluginBase implements AzureAiSearchConnectorInterface, ContainerFactoryPluginInterface {

  protected IdentityBackendClientFactory $identityClientFactory;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->identityClientFactory = $container->get('pins_search_azure.identity_backend_client_factory');
    return $instance;
  }

  public function createClient(array &$configuration): BackendClientInterface {
    return $this->identityClientFactory->create($this->configuration);
  }

}