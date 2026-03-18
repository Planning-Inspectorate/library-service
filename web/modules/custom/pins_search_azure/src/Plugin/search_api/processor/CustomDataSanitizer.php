<?php

namespace Drupal\pins_search_azure\Plugin\search_api\processor;

use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\FieldsHelperInterface;

/**
 * @SearchApiProcessor(
 *   id = "custom_data_sanitizer",
 *   label = @Translation("Custom Data Sanitizer"),
 *   description = @Translation("Normalizes specific date and metadata fields to Unix timestamps, stripping out invalid strings or empty values."),
 *   stages = {
 *     "preprocess_index" = 100
 *   }
 * )
 */
class CustomDataSanitizer extends ProcessorPluginBase {

  /**
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FieldsHelperInterface $fields_helper
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldsHelper = $fields_helper;
  }

  public static function create($container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('search_api.fields_helper')
    );
  }

  public function preprocessIndexItems(array $items) {
    foreach ($items as $item) {
      $index = $item->getIndex();
      if ($index && $index->id() === 'pins_content_index_azure') {
        $date_fields = [
          'custom_value',
          'field_date',
          'field_kl_archived_date',
          'field_kl_reading_lists',
          'field_kl_series',
          'field_kl_authors',
          'field_kl_classification',
        ];
        foreach ($date_fields as $field_id) {
          $this->sanitizeDate($item, $field_id);
        }
      }
    }
  }

  protected function sanitizeDate(ItemInterface $item, string $field_id) {
    $field = $item->getField($field_id);
    if (!$field) {
      return;
    }

    $values = $field->getValues();
    $clean = [];

    foreach ($values as $value) {
      if (is_array($value) && isset($value['value'])) {
        $value = $value['value'];
      }

      if (
        $value === NULL ||
        $value === FALSE ||
        $value === '' ||
        $value === 'False'
      ) {
        continue;
      }

      if (is_numeric($value)) {
        $clean[] = (int) $value;
      }
      else {
        $ts = strtotime($value);
        if ($ts !== FALSE && $ts > 0) {
          $clean[] = (int) $ts;
        }
      }
    }

    if (empty($clean)) {
      $fields = $item->getFields();
      unset($fields[$field_id]);
      $item->setFields($fields);
    }
    else {
      $field->setValues($clean);
    }
  }
}