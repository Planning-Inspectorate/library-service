<?php

namespace Drupal\pins\Services;

use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\Core\Database\Connection;

class LatestVersionFilter {

  protected $connection;

  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * For normal Views.
   */
  public function apply(QueryPluginBase $query, string $bundle = 'kl_document') {

    $sub = $this->buildLatestVersionSubquery();

    $group = $query->setWhereGroup('OR');

    $query->addWhere($group, 'node_field_data.type', $bundle, '<>');
    $query->addWhere($group, 'node_field_data.nid', $sub, 'IN');
  }

  /**
   * For Views using a relationship (referenced content).
   */
  public function applyToRelationship(
    QueryPluginBase $query,
    string $relationship,
    string $bundle = 'kl_document'
  ) {

    $sub = $this->buildLatestVersionSubquery();

    // ✅ Correct alias from relationship
    $base_alias = 'node_field_data_' . $relationship;

    $group = $query->setWhereGroup('OR');

    // 1) Not kl_document → keep it
    $query->addWhere($group, "$base_alias.type", $bundle, '<>');

    // 2) Latest version → keep it
    $query->addWhere($group, "$base_alias.nid", $sub, 'IN');
  }

  /**
   * Subquery for latest version.
   */
  protected function buildLatestVersionSubquery() {

    $connection = $this->connection;

    $sub = $connection->select('node__field_kl_doc_id', 'd');
    $sub->condition('d.deleted', 0);
    $sub->condition('d.delta', 0);

    $sub->join(
      'node__field_vernum',
      'v',
      'v.entity_id = d.entity_id
       AND v.langcode = d.langcode
       AND v.delta = d.delta
       AND v.deleted = d.deleted'
    );

    $sub->condition('v.deleted', 0);
    $sub->condition('v.delta', 0);

    $latest = $connection->select('node__field_kl_doc_id', 'd2');
    $latest->condition('d2.deleted', 0);
    $latest->condition('d2.delta', 0);

    $latest->join(
      'node__field_vernum',
      'v2',
      'v2.entity_id = d2.entity_id
       AND v2.langcode = d2.langcode
       AND v2.delta = d2.delta
       AND v2.deleted = d2.deleted'
    );

    $latest->condition('v2.deleted', 0);
    $latest->condition('v2.delta', 0);

    $latest->addField('d2', 'field_kl_doc_id_value');
    $latest->addField('d2', 'langcode');
    $latest->addExpression('MAX(v2.field_vernum_value)', 'max_ver');

    $latest->groupBy('d2.field_kl_doc_id_value');
    $latest->groupBy('d2.langcode');

    $sub->join(
      $latest,
      'l',
      'l.field_kl_doc_id_value = d.field_kl_doc_id_value
       AND l.langcode = d.langcode
       AND l.max_ver = v.field_vernum_value'
    );

    $sub->addField('v', 'entity_id');

    return $sub;
  }

}
