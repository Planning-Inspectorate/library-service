<?php

namespace Drupal\pins_kl_import\EventSubscriber;

use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\EventSubscriber\AfterParseBase;
use Drupal\feeds\Exception\SkipItemException;
use Drupal\feeds\Feeds\Item\DynamicItem;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

//use Drupal\feeds\Event\EntityEvent;
//use Drupal\feeds\Event\FeedsEvents;
//use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Reacts on KL metadata being processed.
 */
class PinsKlImportSubscriber extends AfterParseBase {

  /**
   * {@inheritdoc}
   */
  public function applies(ParseEvent $event) {

    return $event->getFeed()->getType()->id() === 'import_kl_document_files';
    /*
    $id = $event->getFeed()->getType()->id();
    switch ($id) {
      case 'import_kl_document_files':
      case 'another_feed_id':
      case 'and_another':
        return TRUE;
        break;
      default:
        return FALSE;
    }
    */
  }

  /**
   * {@inheritdoc}
   */
  protected function alterItem(ItemInterface $item, ParseEvent $event) {

//    $feed_id = $event->getFeed()->getType()->id();

//    switch ($id) {
//      case 'import_kl_document_files':
        \Drupal::messenger()->addMessage('item event');
        $filename = $item->get('filename');
        //if (strpos($filename, 'tbc') !== FALSE) {
        //  throw new SkipItemException('Do not import files that have "tbc" in the name.');
        //}

        // Get the file path of the file.
        /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
        //$extension_list = \Drupal::service('extension.list.module');
        //$filepath = $extension_list->getPath('MY_MODULE') . '/assets/MY_FILE.txt';
        $filepath = 'public://lib-mig/' . $filename;

        $file = File::create([
          'filename' => basename($filepath),
          'uri' => 'public://lib-mig/' . basename($filepath),
          'status' => 1,
          'uid' => 1,
        ]);
        $file->save();
    
        // Mark the file as used so it is not removed by cron.
        /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
        $file_usage = \Drupal::service('file.usage');
        $file_usage->add($file, 'pins_kl_import', 'node', 1);

        $item->set('field_document', $file);
        //$item->save();

//        break;

//      case 'another_feed_id':
//        break;

//      case 'and_another':
//        break;

//      default:
//        return FALSE;


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
//    }
    
  }

  function SomeCodeStuff() {
    /*
    Let's say that you want to copy a file from your custom module to the local file system path where public files are stored (usually sites/default/files), and then create a File entity.

    The first thing you need to do is to get the file path of your file:
    */
    /** @var \Drupal\Core\Extension\ExtensionList $extension_list */
    $extension_list = \Drupal::service('extension.list.module');
    $filepath = $extension_list->getPath('MY_MODULE') . '/assets/MY_FILE.txt';

    /*
    As you can see the MY_FILE.txt  file is located under MY_MODULE/assets directory and we are using the extension.list.module service to get the file path.

    Now let's copy the file to a public directory by using the following code:
    */

    $directory = 'public://my-dir';
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $file_system->copy($filepath, $directory . '/' . basename($filepath), FileSystemInterface::EXISTS_REPLACE);

    /*
    We have to be sure that the destination directory exists, so we are using the prepareDirectory() method to create it. This method will create the directory if it doesn't exist and it will also make sure that the directory is writable.

    If you don't prepare the directory, you might encounter the following error:

    The specified file 'modules/custom/MY_MODULE/assets/MY_FILE.txt' could not be copied because the destination directory 'public://my-dir' is not properly configured. This may be caused by a problem with file or directory permissions.
    Finally, we can now create a File entity programmatically:
    */


    $file = File::create([
      'filename' => basename($filepath),
      'uri' => 'public://my-dir/' . basename($filepath),
      'status' => 1,
      'uid' => 1,
    ]);
    $file->save();

    /*
    But creating a file entity is not enough. You also have to mark the file as used, otherwise, you risk losing it depending on your file settings. To mark the file as used you can use the file.usage service.
    */

    /** @var \Drupal\file\FileUsage\DatabaseFileUsageBackend $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $file_usage->add($file, 'MY_MODULE', 'node', 1);

    /*
    The 3rd and 4th parameters of the add() method are type and ID, and they can be any string. In our example, we are saying that our file is used by a node with the ID of 1.

    To find more information about the possibility of losing your files please read the following change record.

    Function file_save_data()
    If you don't have a file to copy, but you want to create the file from scratch you can use the file_save_data function. It will save the file to the specified location and create a database entry for it.
    */

    $data = 'Text file example content';
    $file = file_save_data($data, 'public://my-dir/MY_FILE.txt', FileSystemInterface::EXISTS_REPLACE);

    /*
    The function will return a fully loaded file entity or FALSE on error.

    Function file_save_data() is deprecated in Drupal 9.3 and it'll be removed in drupal 10, so you should use the file.repository service instead of.
    */

    $data = 'Text file example content';
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $fileRepository = \Drupal::service('file.repository');
    $fileRepository->writeData($data, "public://my-dir/MY_FILE.txt", FileSystemInterface::EXISTS_REPLACE);

    /*
    How to programmatically attach a file to an entity
    Let's say that you want to upload a file programmatically to a field.

    Just load your node and set the file ID like this:
    */
    $entity_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity = $entity_storage->load(1);
    $entity->set('field_text_file', ['target_id' => $file->id()]);
    $entity->save();

    //or set the entire file entity object like this:

    $entity_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity = $entity_storage->load(1);
    $entity->set('field_text_file', $file);
    $entity->save();

    //Drupal registers a stream wrapper so that URIs such as public://my-dir/MY_FILE.txt are expanded to a normal filesystem path such as sites/default/files/my-dir/MY_FILE.txt. This basically means that even though it might seem that this shouldn't work, it actually works perfectly fine.

    //file_exists('public://my-dir/MY_FILE.txt')

  }

}


