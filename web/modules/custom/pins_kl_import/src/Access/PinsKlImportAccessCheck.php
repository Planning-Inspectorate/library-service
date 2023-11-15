<?php

namespace Drupal\pins_kl_import\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;

/**
* Checks access for ....
*/
class PinsKlImportAccessCheck implements AccessInterface {

  /**
  * A custom access check.
  *
  * @param \Drupal\Core\Session\AccountInterface $account
  *   Run access checks for this account.
  *
  * @return \Drupal\Core\Access\AccessResultInterface
  *   The access result.
  */
  public function access(AccountInterface $account, NodeInterface $node) {
    // Check roles.
    /*
    $user_roles = $account->getRoles();
    if (!in_array("administrator", $user_roles) && !in_array("event_organiser", $user_roles) && !in_array("event_staff", $user_roles)) {
      return AccessResult::forbidden();
    }

    // Check owner or administrator.
    $owner_user_object = $node->getOwner();
    if (in_array("administrator", $user_roles) || $owner_user_object->uid() == $account->id()) {
      return AccessResult::allowed();
    }

    // Can user admin stock items for this event?
    $user = \Drupal\user\Entity\User::load($account->id());
    $user_events = $user->get('field_event')->getValue();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $loaded_node = $node_storage->load($node->id());
    $stock_event = $loaded_node->get('field_event')->target_id;
    $matched_event = FALSE;
    foreach($user_events as $event) {
      if ($event['target_id'] == $stock_event) {
        $matched_event = TRUE;
        break;
      }
    }
    if ($matched_event) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
    */
    return AccessResult::allowed();
  }

}
