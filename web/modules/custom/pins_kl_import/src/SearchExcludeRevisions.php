<?php

namespace Drupal\pins_kl_import;

use Drupal\node\Entity\Node;
use Drupal\pathauto\PathautoState;
use Drupal\redirect\Entity\Redirect;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\Core\Messenger;
Use Drupal\taxonomy\Entity\Vocabulary;

class SearchExcludeRevisions {

  public static function batchProcess($nodes, &$context){
    $message = 'Updating revision node  ... ';
    $results = array();
    $nids = $nodes[0];
    $type = $nodes[1];
    $updated = false;

    foreach ($nids as $nid) {

      if(!$node = Node::load($nid)){
        continue;
      }

      $title = $node -> getTitle();
      $versions = (int)trim($node->get('field_kl_doc_version')->value);
      $version_nr = (int)trim($node->get('field_kl_vernum')->value);     

      if($version_nr == $versions){
        continue;
      }

      $message .= "$type : $nid :: $title :: $versions :: $version_nr";
  
      $node->set('field_search_exclude',1);                                
      $node->save(); 

      $context['results'][] = $nid;
      $context['sandbox']['current_item'] = $title;
      $context['message'] = $message;
    }
  }

  function batchFinished($success, $results, $operations) {

    $nr_results = count($results);
    if ($success) {
      $message = \Drupal::translation()->formatPlural($nr_results, 'One revision node  processed',
      t("Finished updating @counter revision nodes", array('@counter' => $nr_results )));
    }
    else {
      $message = t('Finished with an error.');
    }

    \Drupal::messenger() -> addMessage($message, 'status');
  }
}
