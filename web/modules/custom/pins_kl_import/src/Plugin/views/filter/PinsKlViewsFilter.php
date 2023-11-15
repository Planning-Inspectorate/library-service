<?php

/**
 * @file
 * Definition of Drupal\pins_kl_import\Plugin\views\filter\PinsKlImportViewsFilter.
 */

namespace Drupal\pins_kl_import\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Symfony\Component\HttpFoundation\Request;

/**
 * Filters ......
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("pins_kl_import_views_filter")
 */
class PinsKlImportViewsFilter extends InOperator {

  /**
   * The current display.
   *
   * @var string
   *   The current display of the view.
   */
  protected $currentDisplay;

  /**
   * {@inheritdoc}
   */
/*
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Filter by own events');
    $this->definition['options callback'] =  array($this, 'generateOptions');
    $this->currentDisplay = $view->current_display;
  }
*/

  /**
   * Override the query so that no filtering takes place if the user doesn't
   * select any options.
   */
  public function query() {
    if (!empty($this->value)) {
      parent::query();
    }
  }

  /**
   * Skip validation if no options have been chosen so we can use it as a
   * non-filter.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

  /**
   * Helper function that generates the options.
   *
   * @return array
   *   An array of events and their ids.
   */
/*  
  public function generateOptions() {

    // Get nids of events that user can manage.
    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $user_events = $user->get('field_event')->getValue();

    $ev_list = [];
    foreach($user_events as $event) {
      $ev_list[] = $event['target_id'];
    }

    // Get all bm_event nodes that are connected to this user.
    $results = \Drupal::entityQuery('node')
      ->condition('status', 1)
      ->condition('type', 'bm_event')
      ->condition('nid', $ev_list, 'IN')
      ->sort('field_event_date', 'DESC')
      ->sort('title', 'ASC')
      ->accessCheck(TRUE)
      ->execute();

    $nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($results);

    // Build array of values.
    $opts = [];
    foreach($nodes as $node) {
      $opt_id = $node->id();
      $opt_date = $node->field_event_date->date->format('Y');
      $opt_val = $node->label() . ', ' . $opt_date;
      $opts[$opt_id] = $opt_val;
    }
    return $opts;
  }
*/
}
