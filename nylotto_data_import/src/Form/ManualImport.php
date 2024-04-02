<?php

namespace Drupal\nylotto_data_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\nylotto_data_import\ImportData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Database;

/**
 * Creates the manual importing file.
 */
class ManualImport extends FormBase {
  use StringTranslationTrait;

  /**
   * Provides the data importing service.
   *
   * @var \Drupal\nylotto_data_import\ImportDataprovidesthedataimportservice
   */
  protected $dataImport;

  /**
   * Class constructor.
   */
  public function __construct(ImportData $dataImportService) {
    $this->dataImport = $dataImportService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
          $container->get('nylotto.data')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nylotto_data_import';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Data File'),
      '#description' => $this->t('Select the data file you wish to import. Upload a txt file to import.'),
      '#multiple' => TRUE,
      '#upload_validators' => [
        'file_validate_extensions' => ['txt csv', 'csv'],
      ],
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Start Import'),
    ];
    return $form;
  }

  // Public function clearImport(array &$form, FormStateInterface $form_state)
  // {
  // }.
  // /**
  //  * {@inheritdoc}
  //  */
  // public function validateForm(array &$form, FormStateInterface $form_state)
  // {
  //     $file = entity_load('file', $form_state->getValue('file')[0]);
  // }.

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue('file') as $value) {
      $file = entity_load('file', $value);
      // Database connection.
      $conn = Database::getConnection();
      $conn->insert('nylotto_import_log')->fields(
          [
            'import_type' => 'Manual',
            'import_source' => $file->get('filename')->getString(),
            'import_time' => date('Y-m-d H:i:s', $file->get('created')->getString()),
          ]
        )->execute();
      $this->dataImport->importFile($file);
    }
  }

}
