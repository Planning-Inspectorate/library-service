<?php

namespace Drupal\pins\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Annotation\ViewsFilter;

/**
 * Dummy Yes/No filter with default value and no "- Any -" option.
 *
 * @ViewsFilter("yes_no_filter")
 */
class YesNoFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, ?array &$options = NULL) {
    parent::init($view, $display, $options);

    // Ensure default value is set if not already.
    if (empty($this->value)) {
      $this->value = [1]; // Default to "Yes".
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value'] = ['default' => [1]]; // Default to "Yes".
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Enable filter'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#default_value' => $this->value ?? [1],
      '#required' => TRUE, // Prevents "- Any -" from being added in admin UI.
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    $identifier = $this->options['expose']['identifier'] ?? NULL;
    if ($identifier && isset($form[$identifier]['#options'])) {
      // Remove the "- Any -" option if it exists.
      if (isset($form[$identifier]['#options']['All'])) {
        unset($form[$identifier]['#options']['All']);
      }
      if (isset($form[$identifier]['#options'][''])) {
        unset($form[$identifier]['#options']['']);
      }

      // Ensure default value is set.
      if (empty($form_state->getValue($identifier))) {
        $form[$identifier]['#default_value'] = $this->value ?? [1];
      }

      // Make it required so user must choose Yes or No.
      $form[$identifier]['#required'] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Intentionally empty.
  }
}
