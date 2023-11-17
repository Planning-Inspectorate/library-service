<?php declare(strict_types = 1);

namespace Drupal\pins_kl_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Entity\Feed;

/**
 * Provides an Upload Vocabs form.
 */
class UploadVocabsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'upload_vocabs_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['intro_text'] = [
      '#markup' => '<p>Creates/updates the taxonomy vocabularies from downloaded CSV files from the Horizon Data Maintenance facility..</p>'
    ];

    $form['feed_type_id'] = [
      '#type' => 'select',
      '#title' => t('Import process to perform'),
      '#options' => [
        'import_kl_authors' => t('Import KL Authors'),
        'import_kl_reading_lists' => t('Import KL Reading Lists'),
        'import_kl_series' => t('Import KL Series'),
      ],
      '#required' => TRUE,
      '#description' => t('Select the import process to apply to each item in your file.'),
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#required' => TRUE,
      '#title' => $this->t('Upload your CSV File'),
      '#upload_validators' => array(
        'file_validate_extensions' => array('csv'),
        // Pass the maximum file size in bytes
        'file_validate_size' => array(1*1024*1024),
      ),
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if (mb_strlen($form_state->getValue('message')) < 10) {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('Message should be at least 10 characters.'),
    //     );
    //   }
    // @endcode
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $vals = $form_state->getValues();

    // Items per feeds batch
    //variable_set('feeds_process_limit', 250);

    // The CSV file.
    $fid = $vals['file'][0];

    // The Feed to run.
    $feed_type_id = $vals['feed_type_id'];

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $file = $file_storage->load($fid);
    $uri = $file->getFileUri();

    $feed = Feed::create([
      'title' => 'Upload KL data - ' . $feed_type_id,
      'type' => $feed_type_id,
      'source' => $uri,
    ]);
    $feed->save();
    //      $feed->import();
    $feed->startBatchImport();
    //      $feed->startCronImport();

    // Delete the temporary uploaded file..?
    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $file = $file_storage->load($fid);
    $references = \Drupal::service('file.usage')->listUsage($file);
    if (empty($references) && file_exists($file->getFileUri())) {
//      $file->delete();
//      \Drupal::logger('pins_kl_import')->notice('Deleted temp file ' . $fid);
    }
    else {
//      \Drupal::logger('pins_kl_import')->warning('Could not delete temp file ' . $fid);
    }

    //$form_state->setRedirect('entity.node.canonical', ['node' => $curr_event]);

    $this->messenger()->addStatus($this->t('Import complete.'));
    $form_state->setRedirect('<front>');
  }

}
