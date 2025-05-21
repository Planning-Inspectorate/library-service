<?php
namespace Drupal\file_path_checker\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

class FileReferenceController extends ControllerBase {
  use DependencySerializationTrait;


  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a new FileReferenceController.
   */
  public function __construct(Connection $database, FileSystemInterface $file_system) {
    $this->database = $database;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('file_system')
    );
  }

  /**
   * Entry point for the file reference check.
   */
  public function content() {
    // Load file IDs to check
    $fids = \Drupal::entityQuery('file')
      ->accessCheck(FALSE)
      //->range(0, 5000)
      ->execute();

    // Build batch
    $batch_builder = (new BatchBuilder())
      ->setTitle($this->t('Checking file reference with nodes'))
      ->setInitMessage($this->t('Starting file reference check...'))
      ->setProgressMessage($this->t('Processed @current out of @total.'))
      ->setErrorMessage($this->t('File reference check has encountered an error.'))
      ->setFinishCallback([$this, 'batchFinished']);

    // Add batch operation
    $batch_builder->addOperation([$this, 'processBatch'], [array_values($fids)]);

    // Process the batch
    batch_set($batch_builder->toArray());

    // Return a batch processing page
    return batch_process('/file-reference-checker/results');
  }

  /**
   * Processes file references in a batch operation.
   */
  public function processBatch(array $fids, array &$context) {
    if (empty($context['sandbox'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['max'] = count($fids);
      $context['sandbox']['fids_to_process'] = $fids;
      $context['results']['items'] = [];
    }
  
    $limit = 100;
    $chunk = array_splice($context['sandbox']['fids_to_process'], 0, $limit);
  
    if (empty($chunk)) {
      $context['finished'] = 1;
      return;
    }
  
    $files = File::loadMultiple($chunk);
    $referencing_data = $this->findReferencingNodesWithTitles($chunk);
  
    foreach ($chunk as $fid) {
      $file = $files[$fid] ?? NULL;
      if (!$file) {
        continue;
      }
  
      $uri = $file->getFileUri();
      $public_path = \Drupal::service('file_system')->realpath($uri);
      if ($public_path) {
        if (strpos($public_path, 'documents\\Library') !== false) {
          $public_path = str_replace('\\', '/', $public_path);
         }
      }

      $file_exists = file_exists($public_path) ? 1 : 0;
      $referenced_nodes = $referencing_data[$fid] ?? [];
  
      if (empty($referenced_nodes)) {
        // Insert files **without references** into the database
        $this->database->insert('fpc_node_reference')
          //->key(['fid' => $fid])
          ->fields([
            'fid' => $fid,
            'file_uri' => $uri,
            'nid' => NULL,  // No node reference
            'node_title' => 'No reference found',
            'file_exists' => $file_exists,
            'created' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
      } else {
        foreach ($referenced_nodes as $nid => $title) {
          // Insert files **with references** into the database
          $this->database->insert('fpc_node_reference')
            //->key(['fid' => $fid, 'nid' => $nid])
            ->fields([
              'fid' => $fid,
              'file_uri' => $uri,
              'nid' => $nid,
              'node_title' => $title,
              'file_exists' => $file_exists,
              'created' => \Drupal::time()->getRequestTime(),
            ])
            ->execute();
        }
      }
  
      $context['sandbox']['progress']++;
    }
  
    $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
  }
  

  protected function findReferencingNodesWithTitles(array $fids) {
    $referencing_data = [];
  
    foreach ($fids as $fid) {
      $referencing_data[$fid] = [];
    }
  
    // This assumes field_kl_doc_file is used to store file reference
    $query = $this->database->select('node__field_kl_doc_file', 'f');
    $query->join('node_field_data', 'n', 'f.entity_id = n.nid');
    $query->fields('f', ['field_kl_doc_file_target_id', 'entity_id']);
    $query->addField('n', 'title');
    $query->condition('f.field_kl_doc_file_target_id', $fids, 'IN');
    $result = $query->execute();
  
    foreach ($result as $record) {
      $referencing_data[$record->field_kl_doc_file_target_id][$record->entity_id] = $record->title;
    }
  
    return $referencing_data;
  }
  
  /**
   * Batch finish callback.
   */
  public function batchFinished($success, $results, $operations) {
    if ($success) {
      \Drupal::messenger()->addMessage($this->t('Processed @count files.', ['@count' => count($results['items'])]));
    }
    else {
      \Drupal::messenger()->addError($this->t('Batch processing failed.'));
    }

    \Drupal::service('session')->set('file_reference_results', $results);
  }

  /**
   * Results display page.
   */
  public function resultsPage() {
    $header = [
      ['data' => $this->t('FID')],
      ['data' => $this->t('File URI')],
      ['data' => $this->t('Node ID')],
      ['data' => $this->t('Node Title')],
    ];
  
    $query = $this->database->select('fpc_node_reference', 'f')
      ->fields('f', ['fid', 'file_uri', 'nid', 'node_title'])
      ->orderBy('f.created', 'DESC');
  
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $results = $pager->execute();
  
    $rows = [];
    foreach ($results as $row) {
      if (!empty($row->nid) && !empty($row->node_title)) {
        $url = Url::fromRoute('entity.node.canonical', ['node' => $row->nid]);
        $link = Link::fromTextAndUrl($row->node_title, $url)->toRenderable();
        $rows[] = [
          $row->fid,
          $row->file_uri,
          $row->nid,
          [
          '#markup' => \Drupal::service('renderer')->render($link),
          ],
        ];
      }
    }
  
    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No file-node references found.'),
      'pager' => [
        '#type' => 'pager',
      ],
    ];
  }

  /**
   * Returns an array of nodes that reference the given FIDs.
   */
  protected function findReferencingNodesForBatch(array $fids) {
    $referencing_data = [];

    foreach ($fids as $fid) {
      $referencing_data[$fid] = [];
    }

    // Adjust field name/table as needed
    $query = $this->database->select('node__field_kl_doc_file', 'f');
    $query->fields('f', ['entity_id', 'field_kl_doc_file_target_id']);
    $query->condition('f.field_kl_doc_file_target_id', $fids, 'IN');
    $result = $query->execute();

    foreach ($result as $record) {
      $referencing_data[$record->field_kl_doc_file_target_id][] = $record->entity_id;
    }

    return $referencing_data;
  }

}
