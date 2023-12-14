<?php

namespace Drupal\pins_kl_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
Use Drupal\taxonomy\Entity\Vocabulary;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Render\RendererInterface;


/**
 * Class DeleteVocabularyTermForm.
 *
 * @package Drupal\pins_kl_import\Form
 */
class DeleteVocabularyTermForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_vocabulary_term_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = $this->loadAllVocs();

    $form['vid'] = array (
      '#type' => 'select',
      '#title' => ('Vocabulary name'),
      '#options' => $options,
      '#required' => TRUE,
    );
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Delete terms'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  public function loadAllVocs(){
    //Load list of available vocabularies.
    $vocabularies = Vocabulary::loadMultiple();
    $options = array();
    foreach($vocabularies as $key => $voc) {
      $options[$key] = $voc->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $vid = $form_state->getValue('vid');
    if (!$vid) {
      $form_state->setErrorByName('vid', $this->t('Vid is required.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $vid = trim($form_state->getValue('vid')); // $element['#parents']
    if($vid){
      $tids = $this-> getTidByVid($vid);
    }

    $operations = [];
    foreach ($tids  as $tid) {
      $operations[] = [
      '\Drupal\pins_kl_import\DeleteVocabularyTerm::batchProcess',
      [[
        array('tid' => $tid),$vid]
      ]];
    }

    $batch = array(
      'title' => t("Update terms  ..."),
      'operations' => $operations,
      'init_message' => t('Is checking for tid.'),
      'finished' => '\Drupal\pins_kl_import\DeleteVocabularyTerm::batchFinished',
    );
    batch_set($batch);

    \Drupal::messenger() -> addMessage(t("processed >> $vid >> term"), 'status');
  }

  public function getTidByVid($vid) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vid);
    $query->accessCheck(false);
    return $tids = $query->execute();
  }

}

