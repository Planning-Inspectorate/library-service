<?php 

namespace Drupal\pins\Token;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\file\Entity\File;

/**
 * Provides a service for the parent node title token.
 */
class ParentNodeTitleTokenService {
  use StringTranslationTrait;

  /**
   * Replaces the token with the parent node title.
   *
   * @param array $data
   *   The data array.
   * @param array $options
   *   The options array.
   *
   * @return string
   *   The replaced token value.
   */
  public function replace(array $data, array $options = []) {
    if (isset($data['file']) && $data['file'] instanceof File) {
      $fid = $data['file']->id();
      $field_name = 'field_kl_doc_file'; // Replace with your actual field name
      $query = \Drupal::entityQuery('node');
      $query->condition($field_name . '.target_id', $fid);
      $nodes = $query->execute();
      if (!empty($nodes)) {
        $nid = reset($nodes);
        $connection = \Drupal::database();
        $result = $connection->select('node_field_data', 'n')
          ->fields('n', ['title'])
          ->condition('nid', $nid)
          ->execute()
          ->fetchField();
        return $result;
      }
    }

    return '';
  }
}