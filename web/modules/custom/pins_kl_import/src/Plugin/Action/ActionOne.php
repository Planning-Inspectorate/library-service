<?php

namespace Drupal\pins_kl_import\Plugin\Action;

use Drupal\Core\Session\AccountInterface;
use Drupal\views_bulk_operations\Action\ViewsBulkOperationsActionBase;

/**
 * ActionOne action with default confirmation form.
 *
 * @Action(
 *   id = "pins_kl_import_action_one",
 *   label = @Translation("Set things..."),
 *   type = "",
 *   confirm = TRUE
 * )
 */
class ActionOne extends ViewsBulkOperationsActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
  //  if ($entity->bundle() == 'bm_stock') {
  //    $entity->field_stock_status->target_id = 15;
  //    $entity->save();
  //    return $this->t('Stock set to COMING');
  //  }
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = $object->access('update', $account, TRUE);
    return $return_as_object ? $result : $result->isAllowed();
  }

}
