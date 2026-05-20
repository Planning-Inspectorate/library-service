<?php

namespace Drupal\pins\Services;

use Drupal\views\Plugin\views\query\QueryPluginBase;

class LatestVersionFilter {

  /**
   * Apply filter to normal Views (no relationship).
   */
  public function apply(QueryPluginBase $query, string $bundle = 'kl_document') {

    // Ensure the field exists
    $alias = $query->ensureTable('node__field_search_exclude', 'node_field_data');

    $group = $query->setWhereGroup('OR');

    // 1) Not kl_document → keep
    $query->addWhere($group, 'node_field_data.type', $bundle, '<>');

    // 2) Latest version → keep
    $query->addWhere($group, "$alias.field_search_exclude_value", 1, '<>');
    $query->addWhere($group, "$alias.field_search_exclude_value", NULL, 'IS NULL');
  }

  /**
   * Apply filter to Views using a relationship (referenced content).
   */
  public function applyToRelationship(
    QueryPluginBase $query,
    string $relationship,
    string $bundle = 'kl_document'
  ) {

    // Relationship base alias
    $base_alias = 'node_field_data_' . $relationship;

    // Ensure the field table on relationship
    $alias = $query->ensureTable('node__field_search_exclude', $relationship);

    $group = $query->setWhereGroup('OR');

    // 1) Not kl_document → keep
    $query->addWhere($group, "$base_alias.type", $bundle, '<>');

    // 2) Not excluded → keep
    $$query->addWhere($group, "$alias.field_search_exclude_value", 1, '<>');
    $query->addWhere($group, "$alias.field_search_exclude_value", NULL, 'IS NULL');
  }

}
