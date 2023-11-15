<?php

namespace Drupal\pins_kl_import\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides route responses for the bmfeed module.
 */
class PinsKlImportController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  /*
  public function bmUploader() {
    return [
      '#theme' => 'bm_dip_result',
      '#markup' => 'Hello, I am the Bm Uploader page',
    ];
  }
  */

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  /*
  public function bmdipresult(NodeInterface $node, Request $request) {

      $ev_title = $node->field_event->entity->getTitle();
      $event_date = $node->field_event->entity->get('field_event_date')->date;
      $date_formatted = $event_date->format('Y');

      $pr_title = $node->field_product->entity->getTitle();
      $ev_url = $node->field_event->entity->toUrl()->setAbsolute()->toString();


    $paras = $node->field_dipstick->referencedEntities();
    $dips = [];
    foreach ( $paras as $para ) {
//      $p = \Drupal\paragraphs\Entity\Paragraph::load( $element['target_id'] );
//      $p = \Drupal::entityManager()->getStorage('paragraph')->load($para['target_id'] );
      $dip_barrel = $para->field_barrel->getValue()[0]["value"];
      $dip_bay = $para->field_bay->getValue()[0]["value"];
      $dip_objdate = $para->field_date->date;
      $dip_date = $dip_objdate->format('d-m-Y h:i');
      $dip_val = $para->field_dipstick_value->getValue()[0]["value"];
      $dips[] = [
        'barrel' => $dip_barrel,
        'bay' => $dip_bay,
        'date' => $dip_date,
        'dipval' => $dip_val,
      ];
    }
    krsort($dips);
    return [
      '#theme' => 'bm_dip_result',
      '#result' => $this->t('SUCCESS'),
      '#product' => $pr_title,
      '#event' => $ev_title . ', ' . $date_formatted,
      '#dipval' => $dips,
      '#event_beer_list_url' => $ev_url,
    ];
  }
  */

  /**
   * Handles a bm_stock node update of the status field..
   *
   * @return array
   *   A simple renderable array.
   */
  /*
  public function bmStatusUpdate(NodeInterface $node, Request $request, $bm_stock_status) {

    // Lookup the TID of the status term.
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['name' => $bm_stock_status, 'vid' => 'bm_stock_status_type']);
    $term = reset($term);
    // If exists, update the field in the node and return.
    if($term) {
      $term_id = $term->id();
      $node->set('field_stock_status', ['target_id' => $term_id]);
      $node->save();
//      return [
//        '#markup' => 'Hello, I am the Bm Stock Status Update page - status set to ' . $bm_stock_status,
//      ];
      $ev_title = $node->field_event->entity->getTitle();
      $event_date = $node->field_event->entity->get('field_event_date')->date;
      $date_formatted = $event_date->format('Y');

      $pr_title = $node->field_product->entity->getTitle();
      $ev_url = $node->field_event->entity->toUrl()->setAbsolute()->toString();
      return [
        '#theme' => 'bm_status_update',
        '#result' => $this->t('SUCCESS'),
        '#product' => $pr_title,
        '#event' => $ev_title . ', ' . $date_formatted,
        '#status' => $bm_stock_status,
        '#msg' => $this->t('Status set successfully.'),
        '#event_beer_list_url' => $ev_url,
      ];
    }
    else {
//      return [
//        '#markup' => 'Hello, I am the Bm Stock Status Update page - couldn\'t set that status, sorry.',
//      ];
      return [
        '#theme' => 'bm_status_update',
        '#result' => $this->t('FAIL'),
        '#product' => $this->t('Product Name'),
        '#event' => $this->t('Event Name'),
        '#status' => $this->t('Status requested'),
        '#msg' => $this->t('Hello, I am the Bm Stock Status Update page - couldn\'t set that status, sorry.'),
        '#event_beer_list_url' => $this->t('Beer list page URL'),
      ];

    }
  }
  */
}
