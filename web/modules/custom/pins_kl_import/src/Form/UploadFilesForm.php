<?php

declare(strict_types = 1);

namespace Drupal\pins_kl_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Entity\Feed;

/**
 * Provides an Upload Files form.
 */
class UploadFilesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'upload_files_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['intro_text'] = [
      '#markup' => '<p>This function processes the extracted Horizon files, creates Document pages and creates/updates the Folder terms at the same time .</p>',
    ];

    $form['del_files'] = array(
      '#type' => 'checkbox',
      '#title' => t('Delete all existing file entities first?'),
      '#description' => t('WARNING! This is a dev-only option.  Do not use this when imports from lve system are being performed.'),
    );

    $form['feed_type_id'] = [
      '#type' => 'select',
      '#title' => t('Import process to perform'),
      '#options' => [
        'import_kl_document_files' => t('Import KL Document Files'),
      ],
      '#required' => TRUE,
      '#description' => t('Select the import process to apply to each item in your file.'),
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#required' => TRUE,
      '#title' => $this->t('Upload your CSV File'),
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
        // Pass the maximum file size in bytes.
        'file_validate_size' => [1 * 1024 * 1024],
      ],
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $vals = $form_state->getValues();

    // Items per feeds batch.
    $config = \Drupal::service('config.factory')->getEditable('variable_get_set.api');
    $config->set('feeds_process_limit', 250);
    $config->save();
#    variable_set('feeds_process_limit', 250);

    $del_files = FALSE;
    if (array_key_exists('del_files', $vals)) {
      $del_files = ($vals['del_files'] == 1) ? TRUE : FALSE;
    }

    // Delete all existing file entities owned by this module.
    if ($del_files) {
      // Search for all file entities...
      // $file = \Drupal::entityTypeManager()->getStorage('file')->load(1007);
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    }

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
    $feed->startBatchImport();

    $this->messenger()->addStatus($this->t('Import complete.'));
    $form_state->setRedirect('pins_kl_import.kl_upload_files');
  }

}
