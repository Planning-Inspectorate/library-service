<?php

namespace Drupal\pins_kl_import\Plugin\WebformHandler;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\feeds\Entity\Feed;


/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "process_kl_upload",
 *   label = @Translation("Process KL Upload"),
 *   category = @Translation("Webform Handler"),
 *   description = @Translation("Process the uploaded CSV file."),
 *   cardinality =
 *       \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results =
 *    \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 * )
 */

class PinsKlImportWebFormHandler extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */
  
  public function validateForm(array &$form, FormStateInterface $form_state) {
/*
    $form_id = 'webform_submission_' . $webform_submission->getWebform()
        ->id() . '_form';
    if ($form_id == 'webform_submission_list_upload_form') {
      \Drupal::logger('bmfeed')->notice('Uploader submission processed - validateForm');

      foreach ($form_state->getValues() as $key => $value) {
        // @TODO: Validate fields.
      }
    }
    //parent::validateForm($form, $form_state);
    */
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
/*
    $form_id = 'webform_submission_' . $webform_submission->getWebform()
        ->id() . '_form';
    if ($form_id == 'webform_submission_list_upload_form') {
      \Drupal::logger('bmfeed')->notice('Uploader submission processed - submitForm');

      $values = $webform_submission->getData();

      // Does the current user have either Administrator or Event Manager roles?
      $roles = \Drupal::currentUser()->getRoles();
      if ((!in_array('administrator', $roles))
        && (!in_array('event_organiser',$roles))) {
        // Can't proceed.
        return;
      }

      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $user_events_list = $user->get('field_event')->getValue();
      $fvals = $form_state->getValues();
      $curr_event = $fvals['event'];

      // If user is an Event Manager, they must be linked to the selected event.
      if (in_array('event_organiser', $roles)) {
        $found = false;
        foreach($user_events_list as $values) {
          if ($values['target_id'] == $curr_event) {
            $found = true;
            break;
          }
        }
        if (!$found) {
          return;
        }
      }

      // OK, so should be good to go...
      $fid = $fvals['your_spreadsheet_file_csv_format_'];
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
      $file = $file_storage->load($fid);
      $uri = $file->getFileUri();

      $feed = Feed::create([
        'title' => 'Event '. $curr_event . ' - Scan breweries / producers',
        'type' => 'brewery_list',
        'source' => $uri,
      ]);
      $feed->save();
//      $feed->import();
      $feed->startBatchImport();
//      $feed->startCronImport();

      $feed = Feed::create([
        'title' => 'Event '. $curr_event . ' - Scan beers / products',
        'type' => 'product_list',
        'source' => $uri,
      ]);
      $feed->save();
//      $feed->import();
      $feed->startBatchImport();
//      $feed->startCronImport();


      $feed = Feed::create([
        'title' => 'Event '. $curr_event . ' - Process beer list file',
        'type' => 'beer_list',
        'source' => $uri,
      ]);
      $feed->set('field_event', $curr_event);
      $feed->save();
//      $feed->import();
      $feed->startBatchImport();
//      $feed->startCronImport();

*/
      // Delete the temporary uploaded file..?
      // Submission is not to be stored so hopefully no references but check...
      /*
      $references = \Drupal::service('file.usage')->listUsage($file);
      if (empty($references) && file_exists($file->getFileUri())) {
        $file->delete();
        \Drupal::logger('bmfeed')->notice('Deleted temp file ' . $fid);
      }
      else {
        \Drupal::logger('bmfeed')->warning('Could not delete temp file ' . $fid);
      }
      */

//      $form_state->setRedirect('bmfeed.bmuploader');
//      $form_state->setRedirect('entity.node.canonical', ['node' => $curr_event]);

    }
    

  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
/*
    $form_id = 'webform_submission_' . $webform_submission->getWebform()
        ->id() . '_form';
    if ($form_id == 'webform_submission_list_upload_form') {
      \Drupal::logger('bmfeed')->notice('Uploader submission processed - confirmForm');


//      if (based_on_some_condition) {
//        // Here is the node id where i wanted to redirect to.
//        $form_state->setRedirect('entity.node.canonical', ['node' => 1764]);
//      }
    }
    */
  }

}
