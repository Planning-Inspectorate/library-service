<?php

namespace Drupal\pins_kl_import\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;

/**
 * Reacts on KL metadata being processed.
 */
class PinsKlImportSubscriber extends AfterParseBase {

  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {
    $id = $event->getFeed()->getType()->id();
    switch ($id) {
      case 'beer_list':
      case 'brewery_list':
      case 'product_list':
        return TRUE;
        break;
      default:
        return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

    $id = $event->getFeed()->getType()->id();
/*
    switch ($id) {
      case 'beer_list':
        $beer_name = $item->get('beer');
        if (strpos($beer_name, 'tbc') !== FALSE) {
          throw new SkipItemException('Do not import beers that have "tbc" in the name.');
        }
        break;

      case 'brewery_list':
        $beer_name = $item->get('beer');
        if (strpos($beer_name, 'tbc') !== FALSE) {
          throw new SkipItemException('Do not import beers that have "tbc" in the name.');
        }
        break;

      case 'product_list':
        $beer_name = $item->get('beer');
        if (strpos($beer_name, 'tbc') !== FALSE) {
          throw new SkipItemException('Do not import beers that have "tbc" in the name.');
        }
        break;
      default:
        return FALSE;


      // Example 1: split single value into multiple values.
      // Get an item's value.
      //    $authors = $item->get('authors');
      // Manipulate value.
      // 'authors' is for example: 'Morticia,Fester,Gomez'.
      //    $authors = explode(',', $authors);
      // And set an item's value.
      // 'authors' is now: [
      //   'Morticia',
      //   'Fester','
      //   'Gomez',
      // ].
      //    $item->set('authors', $authors);

      // Example 2: conditionally skip an item.
      //    if (strpos($beer_name, 'tbc') !== FALSE) {
      //      throw new SkipItemException('Do not import beers that have "tbc" in the name.');
      //    }

      // Example 3: add an item.
      //    if ($item->get('title') === 'Article 3a') {
      //      $item = new DynamicItem();
      //      $item->set('title', 'Article 3b');
      //      $event->getParserResult()->push($item);
      //    }
    }
    */
  }
}
