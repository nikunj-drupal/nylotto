<?php

namespace Drupal\nylotto_data_import\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures forms module settings.
 */
class ImportConfig extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'nylotto_data_import_data_config';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'nylotto_data_import_data_config.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('nylotto_data_import_data_config.settings');
    $form['track_files'] = [
      '#type' => 'radios',
      '#title' => t('Track files'),
      '#options' => ['Yes' => 'Yes', 'No' => 'No'],
      '#required' => TRUE,
      '#default_value' => $config->get('track_files'),
    ];

    $form['altrenative_formats'] = [
      '#type' => 'fieldset',
      '#title' => t('Alternative Formats'),
      '#description' => t('Some importers have alternative formats to support variations from the normal file.'),
      '#tree' => TRUE,
    ];

    $type = \Drupal::service('plugin.manager.lotto_data');
    foreach ($type->getDefinitions() as $id => $definition) {
      $plugin = $type->createInstance($id, []);
      if ($plugin->hasAlternativeFormat()) {
        $form['altrenative_formats'][$plugin->alternative_format_setting] = [
          '#type' => 'checkbox',
          '#default_value' => $config->get($plugin->alternative_format_setting),
          '#title' => ucfirst((str_replace('_', ' ', $definition['id']))),
        ];
      }

    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $track_files = $form_state->getValue('track_files');
    $this->config('nylotto_data_import_data_config.settings')
      ->set('track_files', $track_files)
      ->save();
    parent::submitForm($form, $form_state);

    $altFormats = $form_state->getValue('altrenative_formats');
    $type = \Drupal::service('plugin.manager.lotto_data');
    foreach ($type->getDefinitions() as $id => $definition) {
      $plugin = $type->createInstance($id, []);
      if ($plugin->hasAlternativeFormat()) {
        $this->config('nylotto_data_import_data_config.settings')
          ->set($plugin->alternative_format_setting, $altFormats[$plugin->alternative_format_setting]);
      }
      $this->config('nylotto_data_import_data_config.settings')->save();

    }
  }

}
