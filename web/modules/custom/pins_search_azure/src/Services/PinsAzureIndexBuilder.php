<?php

namespace Drupal\pins_search_azure\Services;

use Drupal\search_api\IndexInterface;
use Drupal\search_api_aais\Azure\Index\IndexBuilder;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Decorates the IndexBuilder to fix vector algorithm details.
 */
class PinsAzureIndexBuilder extends IndexBuilder {

  /**
   * The original service instance.
   *
   * @var \Drupal\search_api_aais\Azure\Index\IndexBuilder
   */
  protected $innerService;
  protected $config;
  protected $logger;

  /**
   * Constructs the decorator.
   *
   * @param \Drupal\search_api_aais\Azure\Index\IndexBuilder $inner_service
   * The decorated service.
   */
  public function __construct(IndexBuilder $inner_service, ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory) {
    $this->innerService = $inner_service;
    // Calling services directly instead of injecting them
    $this->config = $config_factory->get('pins_search_azure.settings'); 
    $this->logger = $logger_factory->get('pins_search_azure');
  }

  /**
   * {@inheritdoc}
   */
  public function buildIndex(IndexInterface $index): array {
        $build = $this->innerService->buildIndex($index);

        $semantic_config_name = $this->config->get('semantic_config_name');
        $title_field = $this->config->get('semantic_title_field');
        $content_fields = $this->config->get('semantic_content_fields');
        $keyword_fields = $this->config->get('semantic_keyword_fields');
        // Build prioritized content fields array
        $prioritizedContentFields = [];
        foreach (array_filter(array_map('trim', explode(',', $content_fields ?? ''))) as $fieldName) {
            $prioritizedContentFields[] = ['fieldName' => $fieldName];
        }

        // Build prioritized keywords fields array
        $prioritizedKeywordsFields = [];
        foreach (array_filter(array_map('trim', explode(',', $keyword_fields ?? ''))) as $fieldName) {
            $prioritizedKeywordsFields[] = ['fieldName' => $fieldName];
        }

        if (!isset($build['semantic'])) {
            $build['semantic'] = [
                'defaultConfiguration' => $semantic_config_name,
                'configurations' => [
                    [
                        'name' => $semantic_config_name,
                        'prioritizedFields' => [
                            'titleField' => ['fieldName' => $title_field],
                            'prioritizedContentFields' => $prioritizedContentFields,
                            'prioritizedKeywordsFields' => $prioritizedKeywordsFields,
                        ],
                    ],
                ],
            ];
        }

        if (isset($build['vectorSearch'])) {
            $algorithm_name = $this->config->get('algorithm_name');
            $vectorizer_name = $this->config->get('vectorizer_name'); 
            $profile_name =  $this->config->get('profile_name');
            $semantic_config_name = $this->config->get('semantic_config_name');
            // 1. Define Algorithm
            $build['vectorSearch']['algorithms'] = [[
                'name' => $algorithm_name,
                'kind' => 'hnsw',
                'hnswParameters' => [
                    'metric' =>$this->config->get('metric_name'), 
                    'm' => $this->config->get('m'),
                    'efConstruction' => $this->config->get('ef_construction'),
                    'efSearch' => $this->config->get('ef_search'),
                ],
            ]];
            // 2. Define Vectorizer using Managed Identity (System Assigned)
            $build['vectorSearch']['vectorizers'] = [[
                'name' => $vectorizer_name,
                'kind' => 'azureOpenAI',
                'azureOpenAIParameters' => [
                    'resourceUri' => $this->config->get('resource_url'), 
                    'deploymentId' => $this->config->get('deployment_id'), 
                    'modelName' =>  $this->config->get('api_model'), 
                    'apiKey' => $this->config->get('embedding_api_key'), 
                ],
            ]];

            // 3. Define Profile (Append or Replace)
            $build['vectorSearch']['profiles'] = [[
                'name' => $profile_name,
                'algorithm' => $algorithm_name,
                'vectorizer' => $vectorizer_name,
            ]];

            // 4. Force all vector fields to use this new profile
            foreach ($build['fields'] as &$field) {
                if ($field['type'] === 'Collection(Edm.Single)') {
                    $field['vectorSearchProfile'] = $profile_name;
                    $field['dimensions'] = 1536; 
                }
            }
        }
        return $build;
    }
}