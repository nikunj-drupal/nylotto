<?php

namespace Drupal\nylotto_data_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 *
 */
class ApiSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'nylotto_custom_json.ftp.settings';

  /**
   * @inheritDoc
   */
  public function getFormId() {
    // @todo Implement getFormId() method.
    return 'api_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['api_endpoint'] = [
      '#type' => 'textfield',
      '#title' => t('API Endpoint'),
      '#description' => t('This is the endpoint used by the API for import, it will override the Acquia environment variable if set.'),
      '#default_value' => $config->get('api_endpoint'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('This is the API that corresponds to the endpoint.'),
      '#default_value' => $config->get('api_key'),
    ];

    $form['date_override'] = [
      '#type' => 'checkbox',
      '#title' => t('Stage API Date Override'),
      '#description' => t('If check this will override the data of draw import and set them to the current day of import. It solves the issue of the stage api only providing 2009 draws.'),
      '#default_value' => $config->get('date_override'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $config->set('api_endpoint', $form_state->getValue('api_endpoint'));
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('date_override', $form_state->getValue('date_override'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
