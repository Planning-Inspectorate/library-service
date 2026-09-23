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
   * The token usage tracker service.
   *
   * @var \Drupal\pins_search_azure\Services\TokenUsageTracker
   */
  protected $tokenTracker;

  /**
   * Vectorized live search queries for the current request.
   *
   * @var array
   */
  protected $queryVectorCache = [];

  /**
   * Live search usage rows already recorded during the current request.
   *
   * @var array
   */
  protected $recordedQueries = [];

  /**
   * Constructs a PinsAzureQueryParamBuilder object.
   */
  public function __construct(QueryParamBuilder $innerService, AzureVectorizer $vectorizer, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $config_factory, TokenUsageTracker $token_tracker) {
    $this->innerService = $innerService;
    $this->vectorizer = $vectorizer;
    $this->config = $config_factory->get('pins_search_azure.settings'); 
    $this->logger = $logger_factory->get('pins_search_azure');
    $this->tokenTracker = $token_tracker;
  }

  /**
   * {@inheritdoc}
   */
  public function buildQueryParams(QueryInterface $query): array {
    // 1. Get the base parameters from the original service.
    $params = $this->innerService->buildQueryParams($query);
    $vector_mapping = $this->config->get('field_to_vectorise');
    $configured_mode = $this->config->get('search_mode');
    $mode = $configured_mode ?: 'keyword';

    // 2. Extract search keys.
    $search_phrase = $this->normalizeSearchKeys($query->getKeys());
    // 3. Only apply logic for the specific index and if keys are present.
    if ($query->getIndex()->id() === 'pins_content_index_azure' && !empty($search_phrase)) {
      $track_usage = $mode === 'hybrid' || (empty($configured_mode) && !empty($vector_mapping));
      if (!$track_usage) {
        return $params;
      }

      $cache_key = $query->getIndex()->id() . ':' . hash('sha256', $search_phrase);
      if (!isset($this->queryVectorCache[$cache_key])) {
        $this->queryVectorCache[$cache_key] = [
          'vector' => $this->vectorizer->getVector($search_phrase),
          'usage' => $this->vectorizer->getLastUsage(),
        ];
      }

      $query_vector = $this->queryVectorCache[$cache_key]['vector'];
      $usage = $this->queryVectorCache[$cache_key]['usage'];
      if (empty($this->recordedQueries[$cache_key]) && (!empty($usage['prompt_tokens']) || !empty($usage['total_tokens']))) {
        $this->tokenTracker->recordQuery(
          $query->getIndex()->id(),
          $search_phrase,
          (int) $usage['prompt_tokens'],
          (int) $usage['total_tokens']
        );
        $this->recordedQueries[$cache_key] = TRUE;
      }

      if ($mode === 'hybrid' && !empty($query_vector)) {
        $params['queryType'] = 'semantic';
        $params['semanticConfiguration'] = $this->config->get('semantic_config_name');

        // 2. Add vectorQueries at the top level (The BackendClient should handle the JSON conversion).
        $vectorFields = array_map(function($field) {
          return trim(explode('|', $field)[1]);
        }, explode(',', $vector_mapping));
        
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
   * Flattens Search API keys into user-entered search text.
   */
  protected function normalizeSearchKeys($keys): string {
    if (is_string($keys)) {
      return trim($keys);
    }

    if (!is_array($keys)) {
      return '';
    }

    $parts = [];
    foreach ($keys as $key => $value) {
      if (is_string($key) && strpos($key, '#') === 0) {
        continue;
      }
      if (is_array($value)) {
        $nested = $this->normalizeSearchKeys($value);
        if ($nested !== '') {
          $parts[] = $nested;
        }
      }
      elseif (is_scalar($value)) {
        $value = trim((string) $value);
        if ($value !== '') {
          $parts[] = $value;
        }
      }
    }

    return trim(implode(' ', $parts));
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