<?php

namespace Drupal\pins_search_azure\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
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
 *  "preprocess_index" = 101
 *  }
 * )
 */
class VectorIndexProcessor extends ProcessorPluginBase {

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $azureSettings;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ConfigFactoryInterface $config_factory,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->azureSettings = $config_factory->get('pins_search_azure.settings');
    $this->logger = $logger_factory->get('pins_search_azure');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('logger.factory')
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
    $token_tracker = \Drupal::service('pins_search_azure.token_usage_tracker');
    $title_values = $item->getField('title') ? $item->getField('title')->getValues() : [];
    $document_title = $this->normalizeSourceValues($title_values);
    $processed_count = 0;
    $vectorized_count = 0;
    $prompt_tokens = 0;
    $total_tokens = 0;
    foreach ($mappings as $source_id => $target_id) {
      $source_field = $item->getField($source_id);
      $target_field = $item->getField($target_id);

      // Verify both fields exist in the index configuration
      if (!$source_field || !$target_field) {
        $this->logger->debug('Skipping vectorization for @item: field @source or @target not present in index.', [
          '@item' => $item->getId(),
          '@source' => $source_id,
          '@target' => $target_id,
        ]);
        continue;
      }

      $values = $source_field->getValues();
      if (empty($values)) {
        $this->logger->debug('Skipping vectorization for @item: source field @source has no value (e.g. missing/unreadable attachment).', [
          '@item' => $item->getId(),
          '@source' => $source_id,
        ]);
        continue;
      }
      // Convert source data to a plain string while ignoring null/empty values.
      $text = $this->normalizeSourceValues($values);
      if ($text === '') {
        $this->logger->debug('Skipping vectorization for @item: source field @source normalized to an empty string.', [
          '@item' => $item->getId(),
          '@source' => $source_id,
        ]);
        continue;
      }

      // Get the vector (Mean Pooling handled by service)
      $processed_count++;
      $vector = $vectorizer->getVector($text, $document_title);
      $usage = $vectorizer->getLastUsage();
      $prompt_tokens += (int) ($usage['prompt_tokens'] ?? 0);
      $total_tokens += (int) ($usage['total_tokens'] ?? 0);
      if (!empty($vector)) {
        // Clear existing and set the new vector array
        $target_field->setValues($vector);
        $vectorized_count++;
      }
    }

    // Persist only when we actually processed text and have meaningful output.
    if ($processed_count > 0 && ($vectorized_count > 0 || $prompt_tokens > 0 || $total_tokens > 0)) {
      $token_tracker->record($item->getIndex()->id(), $item->getId(), $vectorized_count, $prompt_tokens, $total_tokens);
    }
  }

  /**
   * Flattens Search API field values into a normalized plain-text string.
   */
  protected function normalizeSourceValues($values): string {
    $parts = [];
    $this->flattenScalarValues($values, $parts);
    return trim(implode(' ', $parts));
  }

  /**
   * Recursively collects scalar-ish values from nested arrays.
   */
  protected function flattenScalarValues($value, array &$parts): void {
    if (is_array($value)) {
      foreach ($value as $entry) {
        $this->flattenScalarValues($entry, $parts);
      }
      return;
    }

    if ($value === NULL) {
      return;
    }

    if (is_scalar($value)) {
      $text = trim((string) $value);
      if ($text !== '') {
        $parts[] = $text;
      }
      return;
    }

    if (is_object($value) && method_exists($value, '__toString')) {
      $text = trim((string) $value);
      if ($text !== '') {
        $parts[] = $text;
      }
    }
  }
}