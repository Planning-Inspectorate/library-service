<?php
namespace Drupal\pins_search_azure\Plugin\search_api\processor;

use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 * id = "custom_data_sanitizer",
 * label = @Translation("Custom Data Sanitizer"),
 * description = @Translation("Normalizes specific date and metadata fields to Unix timestamps."),
 * stages = {
 * "preprocess_index" = 100
 * }
 * )
 */
class CustomDataSanitizer extends ProcessorPluginBase {

  /**
   * @var \Drupal\search_api\Utility\FieldsHelperInterface
   */
  protected $fieldsHelper;

  /**
   * Use a unique name to avoid collision with ProcessorPluginBase::$config
   */
  protected $azureSettings;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    FieldsHelperInterface $fields_helper,
    ConfigFactoryInterface $config_factory
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fieldsHelper = $fields_helper;
    // Load the specific global config
    $this->azureSettings = $config_factory->get('pins_search_azure.settings');
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('search_api.fields_helper'),
      $container->get('config.factory')
    );
  }

  public function preprocessIndexItems(array $items) {
    // 1. Move config reading OUTSIDE the loop for better performance
    $sanitize_string = $this->azureSettings->get('field_to_sanitize') ?? '';
    $data_fields = array_filter(array_map('trim', explode(',', $sanitize_string)));
    

    if (empty($data_fields)) {
      return;
    }

    foreach ($items as $item) {
      $index = $item->getIndex();
      // 2. Safety check: Ensure $index is not null before calling id()
      if ($index && $index->id() === 'pins_content_index_azure') {
        foreach ($data_fields as $field_id) {
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
      // Handle array structures (like those from some Search API backends)
      if (is_array($value) && isset($value['value'])) {
        $value = $value['value'];
      }

      // Filter out invalid/empty values
      if ($value === NULL || $value === FALSE || $value === '' || $value === 'False') {
        continue;
      }

      if (is_numeric($value)) {
        $clean[] = (int) $value;
      } else {
        $ts = strtotime($value);
        if ($ts !== FALSE && $ts > 0) {
          $clean[] = (int) $ts;
        }
      }
    }

    if (empty($clean)) {
      // Better way to remove a field value in Search API
      $field->setValues([]); 
    } else {
      $field->setValues($clean);
    }
  }
}