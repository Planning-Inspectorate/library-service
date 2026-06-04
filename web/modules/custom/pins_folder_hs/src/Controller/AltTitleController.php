<?php

namespace Drupal\pins_folder_hs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class AltTitleController extends ControllerBase {

  public function getAltTitle($tid) {
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($tid);

    if (!$term) {
      return new JsonResponse([]);
    }

    return new JsonResponse([
      'alternative_title' => $term->get('field_alternative_title')->value ?? '',
    ]);
  }

}
