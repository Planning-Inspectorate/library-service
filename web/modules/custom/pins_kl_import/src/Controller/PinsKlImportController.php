<?php declare(strict_types = 1);

namespace Drupal\pins_kl_import\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for PINS KL Import routes.
 */
final class PinsKlImportController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function __invoke(): array {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
