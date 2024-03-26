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
 * Class ExcludeRevisionFromSearchForm.
 *
 * @package Drupal\pins_kl_import\Form
 */
class SearchExcludeRevisionsForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exclude_revision_from_Search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $options = array('kl_document'=> 'Document'); $this->loadAllVocs();

    $form['type'] = array (
      '#type' => 'select',
      '#title' => ('Content types'),
      '#options' => $options,
      '#required' => TRUE,
    );
    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Update revision nodes'),
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
    $type = $form_state->getValue('type');
    if (!$type) {
      $form_state->setErrorByName('type', $this->t('Content type is required.'));
    }
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $type = trim($form_state->getValue('type')); 
    if($type){
      $nids = $this-> getNidByType($type);
    }
    // ksm($nids);


    $operations = [];
    foreach ($nids  as $nid) {
      $operations[] = [
      '\Drupal\pins_kl_import\SearchExcludeRevisions::batchProcess',
      [[
        array('nid' => $nid),$type]
      ]];
    }

    $batch = array(
      'title' => t("Update nodes  ..."),
      'operations' => $operations,
      'init_message' => t('Is checking for nid.'),
      'finished' => '\Drupal\pins_kl_import\SearchExcludeRevisions::batchFinished',
    );
    batch_set($batch);

    \Drupal::messenger() -> addMessage(t("processed >> $type >> node"), 'status');
  }

  public function getTidByVid($vid) {
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', $vid);
    $query->accessCheck(false);
    return $tids = $query->execute();
  }

  public function getNidByType($type) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $type);
    $query->NotExists('field_search_exclude');
    $query->accessCheck(false);
    return $nids = $query->execute();
  }

}

