<?php

namespace Drupal\pins_kl_import;

use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger;
Use Drupal\taxonomy\Entity\Vocabulary;

class DeleteVocabularyTerm {

  public static function batchProcess($tids, &$context){
    $message = 'Updating Node invalid references ... ';
    $results = array();
    $vid = $tids[1];
    $updated = false;

    foreach ($tids[0] as $tid) {

      if(!$term = \Drupal\taxonomy\Entity\Term::load($tid)){
        continue;
      }

      $name = $term -> get('name') -> value;
      $message .= "$vid : $tid ::  $name";
      $term->delete();

      $context['results'][] = $tid;
      $context['sandbox']['current_item'] = $name;
      $context['message'] = $message;

    }
  }

  function batchFinished($success, $results, $operations) {

    $nr_results = count($results);
    if ($success) {
      $message = \Drupal::translation()->formatPlural($nr_results, 'A term  processed',
      t("Finished deleting @counter terms", array('@counter' => $nr_results )));
    }
    else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger() -> addMessage($message, 'status');
  }
}
