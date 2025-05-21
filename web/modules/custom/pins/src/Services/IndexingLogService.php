<?php 
namespace Drupal\pins\Services;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;

class IndexingLogService {

  protected $database;
  protected $logger;
  private $tableExists = NULL;

  public function __construct(Connection $database) {
    $this->database = $database;
    $this->logger = \Drupal::service('logger.factory')->get('indexing_log_service');
  }
  /**
   * Checks if the saa_indexing_log table exists.
   *
   * @return bool
   * TRUE if the table exists, FALSE otherwise.
   */
  private function checkTableExists(): bool {
    if ($this->tableExists === NULL) { // Check if the property is still unset
      $this->tableExists = $this->database->schema()->tableExists('saa_indexing_log');
    }
    return $this->tableExists;
  }

  public function IndexLog(object $file, string $from, string $type) {  
    try {
        $fileUri = $file->getFileUri() ?? '';
        $fid = $file->id() ?? 0;
        $fileName = $file->getFileName() ?? '';
        $fileSize = $file->getSize() ?? 0;

        $replacements = [
          '@fileName' => $fileName,
          '@fileUri' => $fileUri,
          '@fid' => $fid,
          '@fileSize' => $this->displayByteSize($fileSize),
        ];

        if ($from == 'ParentNodeTitleTokenService' ) {
          switch ($type) {
            case 'node_not_found':
              $status = 'error';          
              $message = 'Node not found while processing file [ID: @fid, NAME: @fileName, URI: @fileUri]'; 
              break;
            case 'no_node_reference':
              $status = 'error';          
              $message = 'Node reference missing while processing file [ID: @fid, NAME: @fileName, URI: @fileUri]'; 
              break;
            case 'no_file_reference':
              $status = 'error';          
              $message = 'File reference is missing from the data provided.'; 
              break;
            default:
              $status = 'error';          
              $message = 'ParentNodeTitleTokenService :: Unknown error occurred for file [ID: @fid, NAME: @fileName, URI: @fileUri]'; 
          }
        } 
        if ($from == 'CustomFilesExtractor' ) {
          switch ($type) {
            case 'file_not_found_after_url_update':
              $status = 'error';          
              $message = 'File does not exist after URI correction - [size: @fileSize, fid: @fid, name: @fileName, uri: @fileUri]'; 
              break;
            case 'file_path_updated':
                $status = 'warning';          
                $message = 'File path updated - [uri: @fileUri]'; 
                break;              
            case 'file_not_found':
              $status = 'error';          
              $message = 'File does not exist - [size: @fileSize, fid: @fid, name: @fileName, uri: @fileUri]'; 
              break;
            case 'file_path_exists':
              $status = 'error';          
              $message = 'File exist without path alteration - [size: @fileSize, fid: @fid, name: @fileName, uri: @fileUri]'; 
              break;
            case 'file_indexed':
              $status = 'error';          
              $message = 'File indexed - [size: @fileSize, fid: @fid, name: @fileName, uri: @fileUri]'; 
              break;
            default:
              $status = 'error';          
              $message = 'CustomFilesExtractor :: Unknown error occurred for file [ID: @fid, NAME: @fileName, URI: @fileUri]'; 
          }
        } 
        $formattedMessage = str_replace(array_keys($replacements), array_values($replacements), $message);
        if ($formattedMessage) {
          $this->logIndexing($fid, $fileName, $fileUri, $fileSize, $type , $formattedMessage);
        }
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }

  public function displayByteSize($byteSize) {
    $mbSize = round($byteSize / 1024 / 1024, 2);
    return $mbSize . ' MB';
  }
  
  public function logIndexing(int $fid, string $fileName, string $fileUri, int $fileSize, string $status, string $message = null) {
    try {
      $this->logger->error($message);

      if ($this->checkTableExists()) {
        $this->database->insert('saa_indexing_log')
          ->fields([
            'fid' => $fid,
            'file_name' => $fileName,
            'file_uri' => $fileUri,
            'file_size' => $fileSize,
            'status' => $status,
            'message' => $message,
            'created' => \Drupal::time()->getRequestTime(),
          ])
          ->execute();
      } 
      
    } catch (\Exception $e) {
      $this->logger->error($e->getMessage());
    }
  }
}