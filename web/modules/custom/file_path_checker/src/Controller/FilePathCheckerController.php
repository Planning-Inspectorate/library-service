<?php

namespace Drupal\file_path_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Batch\BatchBuilder;

class FilePathCheckerController extends ControllerBase {

  public function content() {
    // Define the batch
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Checking file paths'))
      ->setInitMessage($this->t('Starting file path check...'))
      ->setProgressMessage($this->t('Processed @current out of @total.'))
      ->setErrorMessage($this->t('File path check has encountered an error.'))
      ->setFinishCallback([$this, 'batchFinished']);

    // Get the content type and field name
    $content_type = 'kl_document';
    $field_name = 'field_kl_doc_file';

    // Load node IDs
    $query = \Drupal::entityQuery('node')
      ->condition('type', $content_type)
      ->exists($field_name)
      ->range(0,100);

    $nids = $query->execute();

    // Add operations to the batch
    $batch_builder->addOperation([$this, 'processBatch'], [$nids, $field_name]);

    // Process the batch
    batch_set($batch_builder->toArray());

    // Return a batch processing page
    return batch_process('/file-path-checker/results');
  }

  public function processBatch(array $nids, $field_name, array &$context) {
    // Initialize context
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($nids);
      $context['sandbox']['nids'] = $nids;
    }

    // Process nodes in chunks
    $limit = 100; // Adjust the limit as needed
    $nids_chunk = array_splice($context['sandbox']['nids'], 0, $limit);

    foreach ($nids_chunk as $nid) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node && isset($node->{$field_name}) && !empty($node->{$field_name})) {
        $file_uri = $node->{$field_name}->entity->getFileUri();
        $public_path = \Drupal::service('file_system')->realpath($file_uri);
        $symlink_path = '/mnt/library-documents/' . basename($public_path);
        $cache_key = 'file_existence:' . $nid;

        if ($cached = \Drupal::cache()->get($cache_key)) {
          $file_exists = $cached->data;
        } else {
          $file_exists = file_exists($public_path) || file_exists($symlink_path);
          \Drupal::cache()->set($cache_key, $file_exists);
        }

        $context['results'][] = [
          'nid' => $nid,
          'file_uri' => $file_uri,
          'exists' => $file_exists,
        ];
      }
    }
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
    \Drupal::service('session')->set('file_path_checker_results', $results);
  }

  public function resultsPage() {
    // Retrieve the batch results from the session
    $results = \Drupal::service('session')->get('file_path_checker_results', []);
  
    // Define the header for the table
    $header = [
      $this->t('SL No.'),
      $this->t('Node ID'),
      $this->t('File URI'),
      $this->t('Exists'),
    ];
  
    // Define the rows for the table
    $rows = [];
    foreach ($results as $index => $result) {
      if (!$result['exists']) {
        $rows[] = [
          'data' => [
            $index + 1, // Serial number
            $result['nid'],
            $result['file_uri'],
            $result['exists'] ? $this->t('Yes') : $this->t('No'),
          ],
        ];
      }
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
  
}