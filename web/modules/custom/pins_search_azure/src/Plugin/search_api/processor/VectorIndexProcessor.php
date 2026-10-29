<?php

namespace Drupal\pins_search_azure\Plugin\search_api\processor;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
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
   * Validated metadata availability rules for this processor instance.
   *
   * @var array|null
   */
  protected $availableContentRules;

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
    $this->getAvailableContentRules();
    // 1. Parse config mapping: "source|target,source2|target2"
    $mapping_string = $this->azureSettings->get('field_to_vectorise') ?? '';
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
        $this->populateAvailableContentField($item, $mappings);
        if ($mappings) {
          $this->processVectorMappings($item, $mappings);
        }
      }
    }
  }

  /**
   * Records content availability independently of vector generation.
   */
  protected function populateAvailableContentField(ItemInterface $item, array $mappings): void {
    $configured_field_id = $this->azureSettings->get('field_available_content_flag');
    $field_id = trim($configured_field_id === FALSE || $configured_field_id === NULL ? 'has_available_content' : $configured_field_id);
    if ($field_id === '') {
      return;
    }
    $this->logger->debug('Populating available content field for item @item with field ID @field.', [
      '@item' => $item->getId(),
      '@field' => $field_id,
    ]);

    $field = $item->getField($field_id);
    if (!$field || $field->getType() !== 'boolean') {
      $this->logger->error('Cannot record content availability for @item: configure @field as a boolean Search API field.', [
        '@item' => $item->getId(),
        '@field' => $field_id,
      ]);
      return;
    }

    $has_content = FALSE;
    foreach ($mappings as $source_id => $target_id) {
      $source = $item->getField($source_id);
      if (!$source) {
        $this->logger->error('Cannot check content availability for @item: source field @source is missing from the index.', [
          '@item' => $item->getId(),
          '@source' => $source_id,
        ]);
        continue;
      }

      if ($this->normalizeSourceValues($source->getValues()) !== '') {
        $has_content = TRUE;
      }
    }

    $entity = $item->getOriginalObject()->getValue();
    if (!$entity instanceof FieldableEntityInterface) {
      $this->logger->error('Cannot check content availability for @item: original object is not a fieldable entity.', [
        '@item' => $item->getId(),
      ]);
      return;
    }

    $field->setValues([$has_content || $this->hasAvailableEntityContent($entity)]);
  }

  /**
   * Loads and validates the configured OR-ed metadata rules.
   */
  protected function getAvailableContentRules(): array {
    if ($this->availableContentRules !== NULL) {
      return $this->availableContentRules;
    }

    $json = $this->azureSettings->get('available_content_rules');
    try {
      if (!is_string($json)) {
        throw new \InvalidArgumentException('Rules must be a JSON string.');
      }
      $rules = json_decode($json, TRUE, 512, JSON_THROW_ON_ERROR);
      if (!is_array($rules) || !array_is_list($rules) || substr(ltrim($json), 0, 1) !== '[') {
        throw new \InvalidArgumentException('Rules must be a JSON array.');
      }
      foreach ($rules as $position => $rule) {
        if (!is_array($rule)
          || array_diff(array_keys($rule), ['content_type', 'field', 'property', 'operator', 'value'])
          || !is_string($rule['content_type'] ?? NULL) || trim($rule['content_type']) === ''
          || !is_string($rule['field'] ?? NULL) || !preg_match('/^[a-z][a-z0-9_]*$/D', $rule['field'])
          || !in_array($rule['operator'] ?? NULL, ['not_empty', 'reference_label_equals'], TRUE)) {
          throw new \InvalidArgumentException("Invalid rule at position $position.");
        }
        if ($rule['operator'] === 'reference_label_equals') {
          if (!is_string($rule['value'] ?? NULL) || trim($rule['value']) === '' || array_key_exists('property', $rule)) {
            throw new \InvalidArgumentException("Rule $position requires a non-empty label value and no property.");
          }
        }
        elseif (array_key_exists('value', $rule)
          || (array_key_exists('property', $rule) && (!is_string($rule['property']) || !preg_match('/^[a-z][a-z0-9_]*$/D', $rule['property'])))) {
          throw new \InvalidArgumentException("Rule $position has an invalid property or unexpected value.");
        }
      }
    }
    catch (\JsonException | \InvalidArgumentException $exception) {
      $this->logger->error('Invalid OPENAI_AVAILABLE_CONTENT_RULES: @message', [
        '@message' => $exception->getMessage(),
      ]);
      throw new \InvalidArgumentException('Invalid OPENAI_AVAILABLE_CONTENT_RULES: ' . $exception->getMessage(), 0, $exception);
    }

    return $this->availableContentRules = $rules;
  }

  /**
   * Checks metadata rules against the original entity.
   */
  protected function hasAvailableEntityContent(FieldableEntityInterface $entity): bool {
    foreach ($this->getAvailableContentRules() as $rule) {
      if (($rule['content_type'] !== '*' && $entity->bundle() !== $rule['content_type'])
        || !$entity->hasField($rule['field'])) {
        continue;
      }

      $field = $entity->get($rule['field']);
      if ($rule['operator'] === 'reference_label_equals') {
        if (!$field instanceof \Drupal\Core\Field\EntityReferenceFieldItemListInterface) {
          $this->logger->error('Availability rule field @field must be an entity reference field.', ['@field' => $rule['field']]);
          throw new \InvalidArgumentException('Availability rule requires an entity reference field: ' . $rule['field']);
        }
        foreach ($field->referencedEntities() as $reference) {
          if (strcasecmp(trim($reference->label()), trim($rule['value'])) === 0) {
            return TRUE;
          }
        }
      }
      elseif (!isset($rule['property'])) {
        if (!$field->isEmpty()) {
          return TRUE;
        }
      }
      else {
        foreach ($field as $field_item) {
          if ($this->normalizeSourceValues($field_item->get($rule['property'])->getValue()) !== '') {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
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