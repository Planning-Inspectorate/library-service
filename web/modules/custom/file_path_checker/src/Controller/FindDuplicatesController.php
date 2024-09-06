<?php

namespace Drupal\file_path_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\Url;
use Drupal\Core\Link;

class FindDuplicatesController extends ControllerBase {

  /**
   * Adds a serial number header to the table.
   */
//   public function file_path_checker_results_header($header) {
//     $header['sl_no'] = $header['#title'] = $this->t('SL No.');
//     return $header;
//   }

  /**
   * Finds and displays duplicate titles in the specified content type.
   */
  public function duplicates() {

        // Define the batch
    $batch_builder = (new BatchBuilder())
        ->setTitle($this->t('Checking title duplicates'))
        ->setInitMessage($this->t('Starting title duplicates...'))
        ->setProgressMessage($this->t('Processed @current out of @total.'))
        ->setErrorMessage($this->t('An error occurred during processing.'))
        ->setFinishCallback([$this, 'batchFinished']);


    // Get the content type and field name
    $content_type = 'kl_document';
    $field_name_1 = 'title';
    $field_name_2 = 'field_kl_doc_id';
    // Load node IDs
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->exists($field_name_1)
      ->exists($field_name_2);

    // $query = \Drupal::entityQuery('node')
    //   ->condition('type', 'kl_document')
    //   ->exists('title')
    //   ->exists('field_kl_doc_id')
    //   ->range(0,100);

    $nids = $query->execute();

    // Add operations to the batch
    $batch_builder->addOperation([$this, 'processBatch'], [$nids]);

    // Process the batch
    batch_set($batch_builder->toArray());

    // Return a batch processing page
    return batch_process('/duplicates/results');


    // $batch = [
    //   'title' => $this->t('Finding duplicate titles...'),
    //   'operations' => [],
    //   'finished' => [get_class($this), 'duplicatesFinished'],
    //   'init_message' => $this->t('Starting to find duplicates...'),
    //   'progress_message' => $this->t('Processed @current out of @total.'),
    //   'error_message' => $this->t('An error occurred during processing.'),
    // ];

    // // Create an entity query to get all nodes of the specified content type
    // $query = \Drupal::entityQuery('node')
    //   ->condition('type', 'kl_document')
    //   ->exists('title')
    //   ->exists('field_kl_doc_id')
    //   ->range(0,100);

    // $nids = $query->execute();

    // // Chunk the node IDs into smaller batches
    // $chunks = array_chunk($nids, 1000);
    // foreach ($chunks as $chunk) {
    //   $batch['operations'][] = [
    //     [get_class($this), 'processDuplicatesBatch'],
    //     [$chunk],
    //   ];
    // }

    // batch_set($batch);
    // return batch_process('/duplicates/results');
  }

  /**
   * Batch processing callback to find duplicates.
   */
//   public static function processDuplicatesBatch(array $nids, array &$context) {

//     if (empty($context['sandbox'])) {
//         $context['sandbox']['progress'] = 0;
//         $context['sandbox']['max'] = count($nids);
//         $context['sandbox']['nids'] = $nids;
//       }
  
//     $titles = $context['sandbox']['titles'] ?? [];

//     foreach ($nids as $nid) {
//       $node = Node::load($nid);
//       $title = $node->getTitle();
//       $doc_id = $node->get('field_kl_doc_id')->value;
//       $titles[$title][$nid] = $doc_id;
//     }

//     $context['sandbox']['titles'] = $titles;
//     $context['finished'] = 1;
//   }

  public function processBatch(array $nids, array &$context) {
    // Initialize context
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($nids);
      $context['sandbox']['nids'] = $nids;
    }

    // Process nodes in chunks
    $limit = 100; // Adjust the limit as needed
    $nids_chunk = array_splice($context['sandbox']['nids'], 0, $limit);

    $titleGroups = [];
    foreach ($nids_chunk as $nodeId) {
      $node = Node::load($nodeId);
      $title = $node->getTitle();
      $klDocId = ($node->get('field_kl_doc_id')->value) ? $node->get('field_kl_doc_id')->value : 0;
      $titleGroups[$title][$klDocId] = $nodeId;
 
    }

    $duplicateNodeIds = [];
    foreach ($titleGroups as $title => $klDocIdGroups) {
      if (count($klDocIdGroups) > 1) {
        // If there are multiple `kl_doc_id` values for the same title, it's a duplicate
        $duplicateNodeIds = array_merge($duplicateNodeIds, array_values($klDocIdGroups));
      }
    }
    foreach ($duplicateNodeIds as $duplicateNodeId) {
      $dnode = Node::load($duplicateNodeId);
      $dtitle = $dnode->getTitle();
      $context['results'][] = [
        'nid' => $duplicateNodeId,
        'title' => $dtitle,
      ];
    }

    // foreach ($nids_chunk as $nid) {
        // $node = Node::load($nid);
        // $title = $node->getTitle();
        // $doc_id = ($node->get('field_kl_doc_id')->value) ? $node->get('field_kl_doc_id')->value : 0;
        // $titles[$title][$doc_id] = $nid;

   

    
    // }

      // Extract the node IDs with duplicate titles (excluding those with identical `kl_doc_id`).



   

 

    // foreach ($nids_chunk as $nid) {
    //   $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
    //   if ($node && isset($node->{$field_name}) && !empty($node->{$field_name})) {
    //     $file_uri = $node->{$field_name}->entity->getFileUri();
    //     $public_path = \Drupal::service('file_system')->realpath($file_uri);
    //     $public_path = str_replace('\\', '/', $public_path);

    //     $cache_key = 'file_existence:' . $nid;
    //     if ($cached = \Drupal::cache()->get($cache_key)) {
    //       $file_exists = $cached->data;
    //     } else {
    //       $file_exists = file_exists($public_path);
    //       \Drupal::cache()->set($cache_key, $file_exists);
    //     }

    //     $context['results'][] = [
    //       'nid' => $nid,
    //       'file_uri' => $file_uri,
    //       'public_path' => $public_path,
    //       'exists' => $file_exists,
    //     ];
    //   }
    // }

    // Update progress
    $context['sandbox']['progress'] += count($nids_chunk);
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }



  public function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = $this->t('Processed @count nodes.', ['@count' => count($results)]);
    } else {
      $message = $this->t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
    \Drupal::logger('file_path_checker')->notice('Batch finished with results: @results', ['@results' => print_r($results, TRUE)]);

    // Store results in session for retrieval on results page
    \Drupal::service('session')->set('file_path_checker_duplicates', $results);
  }

  public function resultsPage() {
    // Retrieve the batch results from the session
    $results = \Drupal::service('session')->get('file_path_checker_duplicates', []);
  
    // Define the header for the table
    $header = [
      $this->t('SL No.'),
      $this->t('Node ID'),
      $this->t('Title'),
      $this->t('View')
    ];

    // Define the rows for the table
    $rows = [];
    $count = 0;
    foreach ($results as $index => $result) {
        $count++;
        $url = Url::fromRoute('entity.node.edit_form', ['node' => $result['nid']]);
        $link = Link::fromTextAndUrl('edit', $url)->toRenderable();
        $rows[] = [
          'data' => [
            $count, // Serial number
            $result['nid'],
            $result['title'],
            \Drupal::service('renderer')->render($link), // Use the renderer service
          ],
        ];
    }
  
    // Render the table
    $output = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No results found.'),
      '#header_callback' => 'file_path_checker_results_header',
    ];
  
    return $output;
  }

  function file_path_checker_results_header($header) {
    // Add the serial number header
    $header['sl_no'] = $header['#title'] = $this->t('SL No.');
    return $header;
  }


  /**
   * Batch finished callback.
   */
//   public static function duplicatesFinished($success, $results, $operations) {
//     if ($success) {
//       $titles = $results['sandbox']['titles'];
//       $duplicates = [];
//       foreach ($titles as $title => $nodes) {
//         if (count($nodes) > 1) {
//           $duplicates[$title] = $nodes;
//         }
//       }

//       if (empty($duplicates)) {
//         return [
//           '#type' => 'table',
//           '#empty' => t('No results found.'),
//         ];
//       }

//       $rows = [];
//       foreach ($duplicates as $title => $nodes) {
//         $doc_ids = implode(', ', $nodes);
//         $rows[] = [
//           'data' => [
//             $title,
//             $doc_ids,
//           ],
//         ];
//       }

//       return [
//         '#type' => 'table',
//         '#header' => ['Title', 'Document IDs'],
//         '#rows' => $rows,
//         '#empty' => t('No results found.'),
//       ];
//     }
//     else {
//       drupal_set_message(t('An error occurred during processing.'), 'error');
//     }
//   }
}
