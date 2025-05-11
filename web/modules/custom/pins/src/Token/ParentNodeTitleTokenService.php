<?php 
namespace Drupal\pins\Token;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\Entity\File;
use Drupal\pins\Services\IndexingLogService;

/**
 * Provides a service for the parent node title token.
 */
class ParentNodeTitleTokenService {
  use StringTranslationTrait;
  protected $indexingLogService;

  /**
   * Constructs a new ParentNodeTitleTokenService object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   * The string translation service.
   * @param \Drupal\pins\Services\IndexingLogService $indexingLogService
   * The indexing log service.
   */
  public function __construct(TranslationInterface $stringTranslation, IndexingLogService $indexingLogService) {
    $this->stringTranslation = $stringTranslation;
    $this->indexingLogService = $indexingLogService;
  }

  /**
   * Replaces the token with the parent node title.
   *
   * @param array $data
   * The data array.
   * @param array $options
   * The options array.
   *
   * @return string
   * The replaced token value.
   */
  public function replace(array $data, array $options = []) {
    try {
      if (isset($data['file']) && $data['file'] instanceof File) {
        $file = $data['file'];
        $fid = $file->id();
        $field_name = 'field_kl_doc_file';

        $query = \Drupal::entityQuery('node');
        $query->condition($field_name . '.target_id', $fid);
        $nodes = $query->execute();

        if (!empty($nodes)) {
          $nid = reset($nodes);
          $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

          if ($node) {
            $title = $node->getTitle();
            return $nid . '::' . $title;
          } else {
            $this->indexingLogService->IndexLog($file, 'ParentNodeTitleTokenService', 'node_not_found');
            \Drupal::logger('ParentNodeTitleTokenService')->warning('Node with ID @nid not found while processing file ID @fid.', ['@nid' => $nid, '@fid' => $fid]);
            return '';
          }
          
        } else {
          $this->indexingLogService->IndexLog($file, 'ParentNodeTitleTokenService', 'no_node_reference');
          return '';
        }
      } else {
        $this->indexingLogService->IndexLog($file, 'ParentNodeTitleTokenService', 'no_file_reference');
        return '';
      }
    } catch (\Exception $e) {
      \Drupal::logger('ParentNodeTitleTokenService')->error('An error occurred during file replacement: @error', ['@error' => $e->getMessage()]);
      return '';
    }
  }
}