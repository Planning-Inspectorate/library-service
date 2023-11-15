<?php


namespace Drupal\pins_kl_import\Plugin\views\access;

use Drupal\views\Plugin\views\access\AccessPluginBase;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\node\Entity\Node;

/**
 * Class PinsKlImportViewsAccess
 *
 * @ingroup views_access_plugins
 *
 * @ViewsAccess(
 *     id = "PinsKlImportViewsAccesss",
 *     title = @Translation("This checks access to..."),
 *     help = @Translation("Add custom logic to access() method"),
 * )
 */
class PinsKlImportViewsAccess extends AccessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    return $this->t('Special access');
  }


  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {

    // Check the user.
    $roles = $account->getRoles();

    // If current user has role Administrator, allow access.
    if (in_array("administrator", $roles)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRouteDefinition(Route $route) {
    $route->setRequirement('_access', 'TRUE');
  }
}
