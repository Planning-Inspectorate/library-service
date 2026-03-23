<?php

namespace Drupal\pins_search_azure\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 * id = "vector_index_processor",
 * label = @Translation("Vector Indexing Processor"),
 * description = @Translation("Calls a REST API to vectorize text fields before indexing."),
 * stages = {
 * "preprocess_index" = 101
 * }
 * )
 */
class VectorIndexProcessor extends ProcessorPluginBase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $azureSettings;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->azureSettings = $config_factory->get('pins_search_azure.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    // 1. Parse config mapping: "source|target,source2|target2"
    $mapping_string = $this->azureSettings->get('field_to_vectorise') ?? '';
    if (empty($mapping_string)) {
      return;
    }

    $mappings = [];
    foreach (explode(',', $mapping_string) as $pair) {
      $parts = explode('|', $pair);
      if (count($parts) === 2) {
        $mappings[trim($parts[0])] = trim($parts[1]);
      }
    }

    foreach ($items as $item) {
      $index = $item->getIndex();
      // 2. Only process for the specific index
      if ($index && $index->id() === 'pins_content_index_azure') {
        $this->processVectorMappings($item, $mappings);
      }
    }
  }

  /**
   * Handles individual field vectorization based on mappings.
   */
  protected function processVectorMappings(ItemInterface $item, array $mappings) {
    $vectorizer = \Drupal::service('pins_search_azure.vectorizer');
    $document_title = $item->getField('title') ? ($item->getField('title')->getValues()[0] ?? '') : '';

    foreach ($mappings as $source_id => $target_id) {
      $source_field = $item->getField($source_id);
      $target_field = $item->getField($target_id);

      // Verify both fields exist in the index configuration
      if (!$source_field || !$target_field) {
        continue;
      }

      $values = $source_field->getValues();
      if (empty($values)) {
        continue;
      }

      // Convert source data to string
      $text = is_array($values) ? implode(' ', $values) : $values;

      // Get the vector (Mean Pooling handled by service)
      $vector = $vectorizer->getVector($text, $document_title);

      if (!empty($vector)) {
        // Clear existing and set the new vector array
        $target_field->setValues($vector);
      }
    }
  }
}