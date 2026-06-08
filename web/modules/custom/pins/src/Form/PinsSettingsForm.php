<?php

namespace Drupal\pins\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for PINS Document Revisions.
 */
class PinsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['pins.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pins_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pins.settings');

    $form['enable_archiving'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable File Revision Archiving'),
      '#description' => $this->t('If enabled, old file versions will be moved to the revisions folder to break bookmarked URLs.'),
      '#default_value' => $config->get('enable_archiving') ?? FALSE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pins.settings')
      ->set('enable_archiving', $form_state->getValue('enable_archiving'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}