<?php

namespace Drupal\pins_kl_import\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\file\Entity\File;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on KL metadata being processed.
 */
class PinsKlImportSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    return $events;
  }

  /**
   * Act on parser result.
   */
  public function afterParse(ParseEvent $event) {

    /** @var \Drupal\feeds\FeedInterface */
    $feed = $event->getFeed();
    /** @var \Drupal\feeds\Result\ParserResultInterface */
    $parser_result = $event->getParserResult();

    // Check if this is the feed type we want to manipulate.
    if ($feed->getType()->id() !== 'import_kl_document_files') {
      // Not the feed type that we are interested in. Abort.
      return;
    }

    /** @var \Drupal\feeds\Feeds\Item\ItemInterface */
    foreach ($parser_result as $item) {

      $filename = $item->get('filename');

      $filepath = 'public://lib-mig/' . $filename;

      // Determine if file is already managed by Drupal.
      $query = \Drupal::entityQuery('file');
      $query->condition('uri', 'public://lib-mig/' . basename($filepath));
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      if ($entity_ids) {
          $file = File::load(reset($entity_ids));
      }
      else {
        $file = File::create([
          'filename' => basename($filepath),
          'uri' => 'public://lib-mig/' . basename($filepath),
          'status' => 1,
          'uid' => 1,
        ]);
        $file->save();
      }

      $fid = $file->id();

      $item->set('computed_5', $fid);

    }
  }

}
