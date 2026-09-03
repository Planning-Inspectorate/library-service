<?php

namespace Drupal\pins_sso_enforcement\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SsoEnforcementSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['pins_sso_enforcement.settings'];
  }

  public function getFormId() {
    return 'pins_sso_enforcement_settings_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('pins_sso_enforcement.settings');

    $form['warning'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--warning">' .
        $this->t('This value is normally controlled per-environment via Config Split. Manual changes here may be overwritten on the next config import.') .
        '</div>',
    ];

    $form['enforce_sso'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce SSO-only authentication'),
      '#description' => $this->t('When enabled, local Drupal login, password reset and registration routes are disabled and redirect to Entra SSO.'),
      '#default_value' => $config->get('enforce_sso'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('pins_sso_enforcement.settings')
      ->set('enforce_sso', (bool) $form_state->getValue('enforce_sso'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
