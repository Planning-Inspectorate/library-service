<?php

namespace Drupal\pins_kl_import\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;

use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\EntityEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;


use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Reacts on KL metadata being processed.
 */
class PinsKlImportSubscriber implements EventSubscriberInterface { 

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    $events[FeedsEvents::PROCESS_ENTITY_PREVALIDATE][] = 'prevalidate';
    return $events;
  }

  /**
   * Act on parser result.
   */
  public function afterParse(ParseEvent $event) {

    /** @var \Drupal\feeds\FeedInterface */
    $feed = $event->getFeed();
    /** @var \Drupal\feeds\Result\ParserResultInterface */
    $parser_result = $event->getParserResult();
    $feed_type_id = $feed->getType()->id();

    switch ($feed_type_id) {

      case 'import_files':
        foreach ($parser_result as $item) {
          $filename = $item->get('filename');
          $documenturi = $item->get('documenturi');
          $path = $item->get('path');
          // $documenturi = $item->get('documenturi');

          $filepath = 'public://' . $documenturi;
          $basename = basename($filepath);

          $query = \Drupal::entityQuery('file');
          $query->condition('uri', 'public://' . $basename);
          $query->accessCheck(FALSE);
          $entity_ids = $query->execute();
          if ($entity_ids) {
            $file = File::load(reset($entity_ids));
          }
          else {
            $file = File::create([
              'filename' => $filename,
              'uri' => 'public://' . $basename,
              'status' => 1,
              'uid' => 1,
            ]);
            $file->save();
          }
          $fid = $file->id();
       
          $item->set('documenturi', $fid);
        }
      break;
    }

   if (
      //($feed->getType()->id() == 'kl_import_files') ||
      ($feed->getType()->id() == 'kl_import_compound_subdocs') ||
      ($feed->getType()->id() == 'kl_import_metadata')
      ) {

      /** @var \Drupal\feeds\Feeds\Item\ItemInterface */
      foreach ($parser_result as $item) {

        // Fix-up link to parent entity.
        if ($feed->getType()->id() == 'kl_import_compound_subdocs') {
          $parentid = $item->get('parentid');
          $query = \Drupal::entityQuery('node')
            ->condition('type', 'kl_compound_document')
            ->condition('field_kl_doc_id', $parentid)
            ->accessCheck(FALSE);
          $results = $query->execute();
          if (!empty($results)) {
            $nid = array_values($results)[0];
            $item->set('computed2', $nid);
          }
        }

        // TODO: The following code should be used when files are available.
        /*
        $filename = $item->get('filename');
        $filepath = 'public://lib-mig/' . $filename;
        $query = \Drupal::entityQuery('file');
        $query->condition('uri', 'public://lib-mig/' . basename($filepath));
        $query->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if ($entity_ids) {
            $file = File::load(reset($entity_ids));
        }
        else {
          $file = File::create([
            'filename' => basename($filepath),
            'uri' => 'public://lib-mig/' . basename($filepath),
            'status' => 1,
            'uid' => 1,
          ]);
          $file->save();
        }
        $fid = $file->id();
        $item->set('computed_5', $fid);
        */
      }
      return;
    }

    return;
  }

    /**
   * Acts on prevalidate an entity.
   * @param Drupal\feeds\Event\EntityEvent $event
   *
   */
  public function prevalidate(EntityEvent $event) {
    $entity = $event->getEntity();
    $item = $event->getItem();
    $feed = $event->getFeed();
    $feed_type_id = $feed->getType()->id();
    // ksm( $item, $feed_type_id);

    switch ($feed_type_id) {

      case 'kl_import_compoundfolders':
        if($published = $item->get('publicationdate')){
            $publication_date_str = $this->getFormattedDate($published);
           $event->getEntity() ->set('field_date',$publication_date_str);
         }
 
         if($archived = $item->get('archiveddate')){
           $archived_date_str = $this->getFormattedDate($archived);
           $event->getEntity() ->set('field_kl_archived_date',$archived_date_str);
           $event->getEntity() ->set('field_kl_archived',1);
         }
 
        break;

      case 'kl_import_metadata':
      case 'kl_import_compound_subdocs':
      // case 'kl_import_compoundfolders':

        //entity fields
        $fieldname_series = 'field_kl_series';
        $fieldname_authors = 'field_kl_authors';
        $fieldname_series = 'field_kl_classification';

        //Item fields
        $fieldname_series = 'field_kl_series';
        $fieldname_authors = 'field_kl_authors';
        $fieldname_series = 'field_kl_classification';

        
        $item_fields = $item->toArray();

        if($created = $item->get('vercdate')){
          $unixTime = strtotime(str_replace('/','-',$created));
          $date_str = date('Y-m-d\TH:i:s', $unixTime);
          
          $event->getEntity() ->set('created',$unixTime);
//          $event->getEntity() ->set('field_date',$date_str);
          $event->getEntity() ->set('field_kl_vercdate',$date_str);
        }
        
        if($published = $item->get('publicationdate')){
          $publication_date_str = $this->getFormattedDate($published);
          $event->getEntity() ->set('field_date',$publication_date_str);
        }

        if($archived = $item->get('archiveddate')){
          $archived_date_str = $this->getFormattedDate($archived);
          $event->getEntity() ->set('field_kl_archived_date',$archived_date_str);
          $event->getEntity() ->set('field_kl_archived',1);
        }


        if($modified = $item->get('vermdate')){
          $modified_date_str = $this->getFormattedDate($modified);
          $event->getEntity() ->set('field_kl_vermdate',$modified_date_str);
        }

        if($fcreated = $item->get('filecdate')){
          $fcreated_date_str =  $this->getFormattedDate($fcreated);
          $event->getEntity() ->set('field_kl_filecdate',$fcreated_date_str);
        }
        
        if($fmodified = $item->get('filemdate')){
          $fmodified_date_str =  $this->getFormattedDate($fmodified);
          $event->getEntity() ->set('field_kl_filemdate',$fmodified_date_str);
        }

        $version_id = $item->get('VersionID');
        $name = $item->get('Name');

        // classification tids
        $classification_tags = $item->get('classifications');

        // series
        if($term_rows = $item->get('series')){
          $vid ='kl_series';
          $series_tags = [];
          foreach ($term_rows as $delta => $name) {
            $term_row = array($name);
            $hierarchy = $this->getHierarchyTerm($term_row,$vid);
            $series_tags [] = $hierarchy['last']['tid'];
          }
        }

        // author
        if($term_rows = $item->get('authors')){
          $vid ='kl_authors';
          $author_tags = [];
          foreach ($term_rows as $delta => $name) {
            $term_row = array($name);
            $hierarchy = $this->getHierarchyTerm($term_row,$vid);
            $author_tags [] = $hierarchy['last']['tid'];
          }
        }

        // Folder hierarchy
        if($folder_tid = $item->get('folders')){
          $doc_folder = $entity->get('field_kl_doc_folder')->referencedEntities();
          // ksm($doc_folder);
          foreach($doc_folder as $folder_term) {

          // }

          // $folder_term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->load($folder_tid);
          // if(!is_null($folder_term) && is_array($folder_term)){
          //   $folder_term = reset($folder_term);
          // }
            
          // if(!is_null($folder_term)){
            if($series_tags){
              if($target_ids = $folder_term ->get('field_kl_series')->target_id){
                $target_ids_str = str_replace(" ","",$folder_term->field_kl_series->getString());
                $target_ids = explode(",", $target_ids_str);
                $series_tags = array_merge($target_ids,$series_tags);    
              }
              $series_tags = array_unique($series_tags);
              $folder_term ->get('field_kl_series')->setValue($series_tags);
            }

            if($classification_tags){
              if($target_ids = $folder_term ->get('field_kl_classification')->target_id){
                $target_ids_str = str_replace(" ","",$folder_term->field_kl_classification->getString());
                $target_ids = explode(",", $target_ids_str);
                $classification_tags = array_merge($target_ids,$classification_tags);    
              }
              $classification_tags = array_unique($classification_tags);
              $folder_term ->get('field_kl_classification')->setValue($classification_tags);
            }

            if($author_tags){
              if($target_ids = $folder_term ->get('field_kl_authors')->target_id){
                $target_ids_str = str_replace(" ","",$folder_term->field_kl_authors->getString());
                $target_ids = explode(",", $target_ids_str);
                $author_tags = array_merge($target_ids,$author_tags);    
              }
              $author_tags = array_unique($author_tags);
              $folder_term ->get('field_kl_authors')->setValue($author_tags);
            } 
            //save predefined folder
            // $folder_term->save();
          }
          
          // exclude revision from search
          $versions = (int)trim($event->getEntity()->get('field_kl_doc_version')->value);
          $version_nr = (int)trim($event->getEntity()->get('field_kl_vernum')->value);     

          if(!empty($version_nr) && !empty($versions) && $version_nr != $versions){
            $event->getEntity()->set('field_search_exclude',1); 
          }

          
          }
        break;
    }
  }


  public function getFormattedDate($inputDate, $returnTimestamp = 0){
    $unixTime = strtotime(str_replace('/','-',$inputDate));
    $date_str = date('Y-m-d\TH:i:s', $unixTime);
    if ($returnTimestamp == 1){
      return $unixTime;
    }
    else  {
      return $date_str;
    }
  }

  public function getTid($name, $vid, $ptid=0){
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

  public function getHierarchyTerm($term_row, $vid){
    $hierarchy = [];
    $tid =0;
    for ($i=0; $i < count($term_row) ; $i++) { 
      $name = trim($term_row[$i]);
      if(empty($name)) continue;
      if($i < 1){
        $ptid = 0;
        $term = $this->getTid($name, $vid, $ptid);
        $tid = $term->id();
        
        $hierarchy['names'][$name]=$tid;
      }
      else{
        //set hierarchy
        $prev_index = $i -1;
        $prev_name =  $term_row[$prev_index];
        $ptid = $hierarchy['names'][$prev_name];
        $term = $this->getTid($name, $vid, $ptid);
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
    return $hierarchy;
  }

}
