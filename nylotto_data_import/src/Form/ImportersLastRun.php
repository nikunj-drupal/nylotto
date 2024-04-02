<?php

namespace Drupal\nylotto_data_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ImportersLastRun extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nylotto_importers_last_run_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nylotto_importers_last_run_config.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nylotto_importers_last_run_config.settings');
    $default_cron_on_api = $default_cron_on_ftp = $default_cron_on_retailor = '';
    $form['cron_on_api'] = [
      '#type' => 'textfield',
      '#title' => t('Last API import run time with Cron'),
      '#default_value' => $config->get('cron_on_api'),
      '#size' => 45,
      '#maxlength' => 255,
      '#disabled' => TRUE,

    ];
    $form['cron_on_ftp'] = [
      '#type' => 'textfield',
      '#title' => t('Last FTP import run time with Cron'),
      '#default_value' => $config->get('cron_on_ftp'),
      '#size' => 45,
      '#maxlength' => 255,
      '#disabled' => TRUE,

    ];
    $form['cron_on_retailor'] = [
      '#type' => 'textfield',
      '#title' => t('Last Retailor import run time with Cron'),
      '#default_value' => $config->get('cron_on_retailor'),
      '#size' => 45,
      '#maxlength' => 255,
      '#disabled' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $cron_on_api = $form_state->getValue('cron_on_api');
    $cron_on_ftp = $form_state->getValue('cron_on_ftp');
    $cron_on_retailor = $form_state->getValue('cron_on_retailor');
    $this->config('nylotto_importers_last_run_config.settings')
      ->set('cron_on_api', $cron_on_api)
      ->set('cron_on_ftp', $cron_on_ftp)
      ->set('cron_on_retailor', $cron_on_retailor)
      ->save();
    parent::submitForm($form, $form_state);
  }

}
