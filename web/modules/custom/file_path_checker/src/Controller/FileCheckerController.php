<?php
namespace Drupal\file_path_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

use Drupal\Core\Url;
use Drupal\Core\Link;

class FileCheckerController extends ControllerBase {

  public function content() {
    // Define the batch
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Checking file paths'))
      ->setInitMessage($this->t('Starting file path check...'))
      ->setProgressMessage($this->t('Processed @current out of @total.'))
      ->setErrorMessage($this->t('File path check has encountered an error.'))
      ->setFinishCallback([$this, 'batchFinished']);

    // Load file IDs
    $query = \Drupal::entityQuery('file')
      ->accessCheck(FALSE);

    $fids = $query->execute();

    // Add operations to the batch
    $batch_builder->addOperation([$this, 'processBatch'], [$fids]);

    // Process the batch
    batch_set($batch_builder->toArray());

    // Return a batch processing page
    return batch_process('/file-checker/results');
  }

  public function processBatch(array $fids, array &$context) {
    // Initialize context if it's the first run.
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($fids);
      $context['sandbox']['fids'] = $fids;
      $context['results']['exists'] = 0; // Initialize count for existing files
      $context['results']['not_exists'] = 0; // Initialize count for non-existing files
      $context['results']['items'] = []; // Initialize an array to store individual results
    }

    // Process files in chunks.
    $limit = 50; // Adjust the limit as needed for performance.
    $fids_chunk = array_splice($context['sandbox']['fids'], 0, $limit);

    $file_system = \Drupal::service('file_system');

    foreach ($fids_chunk as $fid) {
      $file = File::load($fid);

      if ($file) {
        $uri = $file->getFileUri();
        $realpath = $file_system->realpath($uri);
        // $arr = ['00 Local Index England', '00 Local Index Wales', '7. Journal of Planning and Environment Law', '5. National Infrastructure', 'feeds', 'generateImage'];
        // foreach ($arr as $exclude_path) {
        //   if (strpos($realpath, $exclude_path) !== false) {
        //     // Skip processing this file if the path contains the excluded string.
        //     continue 2;
        //   }
        // }
        // Determine the public file path. This is crucial for checking existence.
        // The 'public://' stream wrapper will resolve to the public files directory.
        $public_path = \Drupal::service('file_system')->realpath($uri);

        // Ensure paths are using consistent directory separators.
        if ($public_path) {
          if (strpos($public_path, 'documents\\Library') !== false) {
            $public_path = str_replace('\\', '/', $public_path);
           }
        }

        // Check if the file physically exists.
        $file_exists = FALSE;
        if ($public_path) {
          $file_exists = file_exists($public_path);
        }

        $context['results']['items'][] = [
          'fid' => $fid, // Use file ID for clarity
          'file_uri' => $uri,
          'public_path' => $public_path,
          'exists' => $file_exists,
        ];

        if ($file_exists) {
          $context['results']['exists']++;
        } else {
          $context['results']['not_exists']++;
        }
      }

      // Update progress.
      $context['sandbox']['progress']++;
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  public function batchFinished($success, $results, $operations) {
    if ($success) {
      $message = $this->t('Processed @count files.', ['@count' => count($results['items'])]);
    } else {
      $message = $this->t('Finished with an error.');
    }
    \Drupal::messenger()->addMessage($message);
    \Drupal::logger('file_path_checker')->notice('Batch finished with results: @results', ['@results' => print_r($results, TRUE)]);

    // Store results in session for retrieval on results page
    \Drupal::service('session')->set('file_checker_results', $results);
  }

  public function resultsPage() {
    // Retrieve the batch results from the session
    $results = \Drupal::service('session')->get('file_checker_results', []);
    $items = $results['items'] ?? [];
    $countYes = $results['exists'] ?? 0;
    $countNo = $results['not_exists'] ?? 0;

    // Define the header for the table
    $header = [
      $this->t('SL No.'),
      $this->t('File ID'),
      $this->t('File URI'),
      $this->t('Exists'),
    ];

    // Define the rows for the table
    $rows = [];
    $count = 0;
    foreach ($items as $index => $result) {
      if (!$result['exists']) {
        $count++;
        $rows[] = [
          'data' => [
            $count, // Serial number
            $result['fid'],
            $result['file_uri'],
            $result['exists'] ? $this->t('Yes') : $this->t('No'),
          ],
        ];
      }
    }

    // Build the output render array
    $output = [
      'summary' => [
        '#type' => 'item',
        '#markup' => $this->t('Total files: @total, Files exist: @yes, Files not found: @no', [
          '@total' => count($items),
          '@yes' => $countYes,
          '@no' => $countNo,
        ]),
      ],
      'missing_files_table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No missing files found.'),
      ],
    ];

    return $output;
  }
}