<?php

declare(strict_types = 1);

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
      '#markup' => '<p>Creates/updates the taxonomy vocabularies from downloaded CSV files from the Horizon Data Maintenance facility.</p>',
    ];

    $form['feed_type_id'] = [
      '#type' => 'select',
      '#title' => t('Import process to perform'),
      '#options' => [
        'kl_import_authors' => t('Import KL Authors'),
        'kl_import_reading_lists' => t('Import KL Reading Lists'),
        'kl_import_series' => t('Import KL Series'),
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
        'file_validate_size' => [20 * 1024 * 1024],
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
//    variable_set('feeds_process_limit', 250);

    // The CSV file.
    $fid = $vals['file'][0];

    // The Feed to run.
    $feed_type_id = $vals['feed_type_id'];

    $file_storage = \Drupal::entityTypeManager()->getStorage('file');
    $file = $file_storage->load($fid);
    $uri = $file->getFileUri();

    $feed = Feed::create([
      'title' => 'Upload KL Vocabs - ' . $feed_type_id,
      'type' => $feed_type_id,
      'source' => $uri,
    ]);
    $feed->save();
    $feed->startBatchImport();

    $this->messenger()->addStatus($this->t('Import complete.'));
    $form_state->setRedirect('pins_kl_import.kl_upload_vocabs');
  }

}
