<?php

namespace Drupal\pins\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a dummy exposed checkbox filter.
 *
 * @ViewsFilter("dummy_checkbox")
 */
class DummyCheckbox extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function adminSummary() {
    return $this->t('Dummy checkbox filter (no DB condition)');
  }

  /**
   * {@inheritdoc}
   */
  public function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable special filter'),
      '#default_value' => !empty($this->value),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing — this filter does not alter the query directly.
  }
}
