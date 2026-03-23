<?php
namespace Drupal\pins_search_azure\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class PinsAzureSearchSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['pins_search_azure.settings'];
  }

  public function getFormId() {
    return 'pins_search_azure_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pins_search_azure.settings');

    $form['search_mode'] = [
      '#type' => 'radios',
      '#title' => $this->t('Search Mode'),
      '#options' => [
        'keyword' => $this->t('Keyword Only (Standard)'),
        'hybrid' => $this->t('Hybrid (Vector + Semantic Ranking)'),
      ],
      '#default_value' => $config->get('search_mode') ?: 'keyword',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pins_search_azure.settings')
      ->set('search_mode', $form_state->getValue('search_mode'))
      ->save();
    parent::submitForm($form, $form_state);
  }
}