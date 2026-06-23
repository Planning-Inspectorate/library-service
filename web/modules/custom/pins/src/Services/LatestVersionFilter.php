<?php

namespace Drupal\pins\Services;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\Core\Database\Connection;

/**
 * Filter Views to only include latest document versions
 * using the field_search_exclude flag.
 */
class LatestVersionFilter {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Apply filter to normal Views (no relationship).
   */
  public function apply(QueryPluginBase $query, string $bundle = 'kl_document') {

    $connection = $this->connection;

    // Use an explicit, named OR group instead of letting Views auto-generate
    // the group ID.
    $group = $query->setWhereGroup('OR', 'pins_latest_version');

    // 1) Not kl_document → keep it
    $query->addWhere($group, 'node_field_data.type', $bundle, '<>');

    // 2) Exclude nodes marked as search_exclude = 1
    $sub = $connection->select('node__field_search_exclude', 'se')
      ->fields('se', ['entity_id'])
      ->condition('se.field_search_exclude_value', 1);

    $query->addWhere($group, 'node_field_data.nid', $sub, 'NOT IN');
  }

  /**
   * Apply filter to Views using a relationship (e.g. referenced nodes).
   */
  public function applyToRelationship(
    QueryPluginBase $query,
    string $relationship,
    string $bundle = 'kl_document'
  ) {

    $connection = $this->connection;

    // This is the alias Views uses for the related node
    $base_alias = 'node_field_data_' . $relationship;
    $group = $query->setWhereGroup('OR', 'pins_latest_version_' . $relationship);

    // 1) Not kl_document → keep it
    $query->addWhere($group, "$base_alias.type", $bundle, '<>');

    // 2) Exclude nodes where search_exclude = 1
    $sub = $connection->select('node__field_search_exclude', 'se')
      ->fields('se', ['entity_id'])
      ->condition('se.field_search_exclude_value', 1);

    $query->addWhere($group, "$base_alias.nid", $sub, 'NOT IN');
  }

}
