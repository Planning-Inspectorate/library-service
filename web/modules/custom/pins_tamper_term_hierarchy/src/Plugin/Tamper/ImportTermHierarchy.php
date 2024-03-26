<?php

namespace Drupal\pins_tamper_term_hierarchy\Plugin\Tamper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\tamper\Exception\TamperException;
use Drupal\tamper\TamperableItemInterface;
use Drupal\tamper\TamperBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Plugin implementation for import_term_hierarchy.
 *
 * @Tamper(
 *   id = "import_term_hierarchy",
 *   label = @Translation("Import Taxonomy Terms Hierarchy"),
 *   description = @Translation("Creates the taxonomy terms if they don't exist already, respecting the term hierarchy."),
 *   category = "Text"
 * )
 */
class ImportTermHierarchy extends TamperBase {

  const DEFAULT_DELIMITER = '>';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['delimiter'] = self::DEFAULT_DELIMITER;
    $config['vocabulary'] = '';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['delimiter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Input delimiter'),
      '#required' => TRUE,
      '#default_value' => $this->getSetting('delimiter'),
    ];

    $vocabularies = Vocabulary::loadMultiple();
    $options = [];
    foreach ($vocabularies as $key => $vocabulary) {
      $options[$key] = $vocabulary->label();
    }

    $form['vocabulary'] = [
      '#type' => 'select',
      '#title' => $this->t('Vocabulary'),
      '#required' => TRUE,
      '#options' => $options,
      '#default_value' => $this->getSetting('vocabulary'),
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->setConfiguration([
      'delimiter' => $form_state->getValue('delimiter'),
      'vocabulary' => $form_state->getValue('vocabulary'),
    ]);
  }

  public function pins_kl_import_get_tid($name, $vid, $ptid=0){
    $tid = 0;
    $term = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties([
            'name' => $name,
            'vid' => $vid,
            'parent' => $ptid,
        ]);

    if ($term) {
        $term = reset($term);
        $tid = $term->id();
    }
    else{
        $term = Term::create([
            'name' => $name,
            'vid' => $vid,
            'parent' => $ptid,
        ]);

        $term->save();
        $tid =  $term->id();
    }
    return $term;
}

  /**
   * {@inheritdoc}
   */
  public function tamper($data, TamperableItemInterface $item = NULL) {
    
    // Check for empty data
    if (!$data) {
      return NULL;
    }

    if (!is_string($data)) {
      throw new TamperException('Input should be a string.');
    }

    // Explode and trim spaces around term names
    $term_row = $terms = array_map('trim', explode($this->getSetting('delimiter'), $data));

    $vid = $vocabulary = $this->getSetting('vocabulary');
    $hierarchy = [];
    for ($i=0; $i < count($term_row) ; $i++) { 
      $name = $term_row[$i];
    
      if($i < 1){
          $ptid = 0;
          $term = $this->pins_kl_import_get_tid($name, $vid, $ptid);
          $tid = $term->id();
          
          $hierarchy['names'][$name]=$tid;
      }
      else{
          //set hierarchy
          $prev_index = $i -1;
          $prev_name =  $term_row[$prev_index];
          $ptid = $hierarchy['names'][$prev_name];
          $term = $this->pins_kl_import_get_tid($name, $vid, $ptid);
          $tid = $term->id();

          $hierarchy['names'][$name]=$tid;
      }

      if(!in_array($tid,(array)$hierarchy['lineage'])){
          $hierarchy['lineage'][] = $tid;
          $hierarchy['terms'][$tid] = $term;
      }  
    }
    if(!isset($hierarchy['last'][$tid]) ){
        $hierarchy['last']['term'] = $term;
        $hierarchy['last']['tid'] = $tid;
    }

    return $tid;




    // $parent = '';
    // foreach ($terms as $key => $term_name) {
    //   $term = \Drupal::entityTypeManager()
    //     ->getStorage('taxonomy_term')
    //     ->loadByProperties([
    //       'name' => $term_name,
    //       'vid' => $vocabulary,
    //       'parent' => $key == 0 ? 0 : $parent,
    //     ]);
    //   if ($term) {
    //     $term = reset($term);
    //     $parent = [$term->id()];
    //   }
    //   // If the term doesn't exists yet, create the term
    //   else {
    //     $term = Term::create([
    //       'name' => $term_name,
    //       'vid' => $vocabulary,
    //       'parent' => $key == 0 ? 0 : $parent,
    //     ]);
    //     $term->save();
    //     $parent = [$term->id()];
    //   }
    // }
    // $data = $term->id();

    // return $data;
  } 

}
