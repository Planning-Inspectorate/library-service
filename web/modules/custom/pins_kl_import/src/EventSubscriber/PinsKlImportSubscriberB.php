<?php

namespace Drupal\pins_kl_import\EventSubscriber;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on KL metadata being processed.
 */
class PinsKlImportSubscriberB implements EventSubscriberInterface {

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

      $file = File::create([
        'filename' => basename($filepath),
        'uri' => 'public://lib-mig/' . basename($filepath),
        'status' => 1,
        'uid' => 1,
      ]);

      $file->save();

      $fid = $file->id();

      // Mark the file as used so it is not removed by cron.
      /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
      $file_usage = \Drupal::service('file.usage');
      $file_usage->add($file, 'pins_kl_import', 'node', 1);

      $item->set('computed_5', $fid);

    }
  }

}
