<?php

namespace Drupal\pins_folder_hs\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AltTitleController extends ControllerBase {

  public function getAltTitle($tid) {
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->load($tid);

     if (!$term instanceof TermInterface) {
       return new JsonResponse([], 404);
     }

     if (!$term->access('view')) {
       throw new AccessDeniedHttpException();
     }
     
     $alt_title = $term->hasField('field_alternative_title')
       ? ($term->get('field_alternative_title')->value ?? '')
       : '';

     return new JsonResponse(['alternative_title' => $alt_title]);
  }

}
