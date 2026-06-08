<?php

namespace Drupal\pins_search_azure\Services;

use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_aais\Azure\Query\QueryParamBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Decorates the QueryParamBuilder to inject vector and semantic parameters.
 */
class PinsAzureQueryParamBuilder extends QueryParamBuilder {

  /**
   * The original service being decorated.
   *
   * @var \Drupal\search_api_aais\Azure\Query\QueryParamBuilder
   */
  protected $innerService;

  /**
   * The vectorizer service.
   *
   * @var \Drupal\pins_search_azure\Services\AzureVectorizer
   */
  protected $vectorizer;

  protected $config;
  protected $logger;

  /**
   * Constructs a PinsAzureQueryParamBuilder object.
   */
  public function __construct(QueryParamBuilder $innerService, AzureVectorizer $vectorizer, LoggerChannelFactoryInterface $logger_factory,ConfigFactoryInterface $config_factory) {
    $this->innerService = $innerService;
    $this->vectorizer = $vectorizer;
    $this->config = $config_factory->get('pins_search_azure.settings'); 
    $this->logger = $logger_factory->get('pins_search_azure');
  }

  /**
   * {@inheritdoc}
   */
  public function buildQueryParams(QueryInterface $query): array {
    // 1. Get the base parameters from the original service.
    $params = $this->innerService->buildQueryParams($query);
    $mode = $this->config->get('search_mode') ?: 'keyword';

    if ($mode === 'keyword') {
      return $params; // No changes for keyword mode.
    }

    // 2. Extract search keys.
    $keys = $query->getKeys();

    $search_phrase = is_array($keys) ? ($keys ?? '') : $keys;
    // 3. Only apply logic for the specific index and if keys are present.
    if ($query->getIndex()->id() === 'pins_content_index_azure' && !empty($search_phrase)) {
      $query_vector = $this->vectorizer->getVector($search_phrase);
      if (!empty($query_vector)) {
        $params['queryType'] = 'semantic';
        $params['semanticConfiguration'] = $this->config->get('semantic_config_name');

        // 2. Add vectorQueries at the top level (The BackendClient should handle the JSON conversion).
        $vectorFields = array_map(function($field) {
          return trim(explode('|', $field)[1]);
        }, explode(',', $this->config->get('field_to_vectorise')));
        
        $params['vectorQueries'] = [
          [
            'kind' => 'vector',
            'vector' => $query_vector,
            'fields' => implode(',', $vectorFields),
            'k' => 10,
          ],
        ];
      }
    }
    return $params;
  }

  /**
   * Proxy calls to the inner service for methods we aren't overriding.
   * * This ensures that if the BackendClient calls other methods on the 
   * builder (like filter or sort getters), it gets the correct data.
   */
  public function getFilterBuilder() {
    return $this->innerService->getFilterBuilder();
  }

  public function getSortBuilder() {
    return $this->innerService->getSortBuilder();
  }

  public function getFacetParamBuilder() {
    return $this->innerService->getFacetParamBuilder();
  }
}