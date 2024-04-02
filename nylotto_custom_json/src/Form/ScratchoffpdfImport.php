<?php

namespace Drupal\nylotto_custom_json\Form;

use Drupal\file\Entity\File;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * * Configuration form definition for the salutation message. */
class ScratchoffpdfImport extends ConfigFormBase {

  /**
   * * {@inheritdoc} */
  protected function getEditableConfigNames() {
    return ['scratch_off_pdf.custom_settings'];
  }

  /**
   * * {@inheritdoc} */
  public function getFormId() {
    return 'scratch_off_pdf_configuration_form';
  }

  /**
   * * {@inheritdoc} */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scratch_off_pdf.custom_settings');
    $form['scratch_off_pdf_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Data File'),
      '#description' => $this->t('Select the data file you wish to import. Upload a pdf file to import.'),
      '#multiple' => FALSE,
      '#upload_location' => "public://",
      '#upload_validators' => [
        'file_validate_extensions' => ['pdf'],
      ],
      '#default_value' => $config->get('scratch_off_pdf_file'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * * {@inheritdoc} */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('scratch_off_pdf.custom_settings')

      ->set('scratch_off_pdf_file', $form_state->getValue('scratch_off_pdf_file'))

      ->save();

    parent::submitForm($form, $form_state);

    /* Fetch the array of the file stored temporarily in database */
    $uploaded_file = $form_state->getValue('scratch_off_pdf_file');

    /* Load the object of the file by it's fid */
    $file = File::load($uploaded_file[0]);

    /* Set the status flag permanent of the file object */
    $file->setPermanent();

    /* Save the file in database */
    $file->save();
  }

}
