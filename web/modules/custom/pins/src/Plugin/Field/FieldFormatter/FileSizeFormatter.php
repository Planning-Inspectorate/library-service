<?php

namespace Drupal\pins\Plugin\Field\FieldFormatter;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'file_size_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "file_size_formatter",
 *   label = @Translation("File size formatter"),
 *   field_types = {
 *     "integer",
 *   }
 * )
 */
class FileSizeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'decimal_places' => 2,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Displays file size in human-readable format (KB/MB).');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $size = (int) $item->value;
      $formatted_size = $this->formatSize($size);
      $elements[$delta] = ['#markup' => $formatted_size];
    }

    return $elements;
  }

  /**
   * Formats the size from bytes to KB or MB.
   */
  protected function formatSize($size) {
    if ($size < 1024) {
      return $size . ' bytes';
    }
    elseif ($size < 1048576) {
      return round($size / 1024, $this->getSetting('decimal_places')) . ' KB';
    }
    else {
      return round($size / 1048576, $this->getSetting('decimal_places')) . ' MB';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['decimal_places'] = [
      '#type' => 'number',
      '#title' => $this->t('Decimal places'),
      '#default_value' => $this->getSetting('decimal_places'),
      '#min' => 0,
      '#max' => 5,
      '#required' => TRUE,
    ];

    return $elements;
  }
}