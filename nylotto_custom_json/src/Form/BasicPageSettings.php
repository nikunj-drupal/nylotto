<?php

namespace Drupal\nylotto_custom_json\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class BasicPageSettings extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'nylotto_custom_json.page.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'basic_page_settings';
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
   * This form allows us to setup basic page nodes to be used for the front end
   * pages that require more flexible layout than the other programmatic pages.
   *
   * This is then returned into a restful endpoint that is consumable by the front end.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);

    $form['how_to_claim'] = [
      '#title' => t('How To Claim a Prize'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('how_to_claim') ? entity_load('node', $config->get('how_to_claim')) : '',
    ];

    $form['featured_winners'] = [
      '#title' => t('Featured Winners'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['article', 'page'],
      ],
      '#default_value' => $config->get('featured_winners') ? entity_load('node', $config->get('featured_winners')) : '',
      '#description' => t('Select the basic page to use for the Featured Winners page.'),
    ];

    $form['about_us'] = [
      '#title' => t('About Us'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('about_us') ? entity_load('node', $config->get('about_us')) : '',
    ];

    $form['draw_times'] = [
      '#title' => t('Draw Times'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
        // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('draw_times') ? entity_load('node', $config->get('draw_times')) : '',
    ];

    $form['money_dots'] = [
      '#title' => t('Money Dots'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('money_dots') ? entity_load('node', $config->get('money_dots')) : '',
    ];

    $form['glossary'] = [
      '#title' => t('Glossary'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('glossary') ? entity_load('node', $config->get('glossary')) : '',
    ];

    $form['press'] = [
      '#title' => t('Press'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('press') ? entity_load('node', $config->get('press')) : '',
    ];

    $form['freedom_of_information'] = [
      '#title' => t('Freedom of Information'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('freedom_of_information') ? entity_load('node', $config->get('freedom_of_information')) : '',
    ];

    $form['faq'] = [
      '#title' => t('Faq'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('faq') ? entity_load('node', $config->get('faq')) : '',
    ];

    $form['legal'] = [
      '#title' => t('Legal Page'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('legal') ? entity_load('node', $config->get('legal')) : '',
    ];

    $form['general_rules'] = [
      '#title' => t('General Rules'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('general_rules') ? entity_load('node', $config->get('general_rules')) : '',
    ];

    $form['scams'] = [
      '#title' => t('Scams'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('scams') ? entity_load('node', $config->get('scams')) : '',
    ];

    $form['privacy'] = [
      '#title' => t('Privacy Policy'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('privacy') ? entity_load('node', $config->get('privacy')) : '',
    ];

    $form['language'] = [
      '#title' => t('Language Accessability'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('language') ? entity_load('node', $config->get('language')) : '',
    ];

    $form['retailers'] = [
      '#title' => t('Information for Retailers'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'node',
      // Optional. The default selection handler is pre-populated to 'default'.
      '#selection_handler' => 'default',
      '#selection_settings' => [
        'target_bundles' => ['page'],
      ],
      '#default_value' => $config->get('retailers') ? entity_load('node', $config->get('retailers')) : '',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Retrieve the configuration.
    $config = $this->configFactory->getEditable(static::SETTINGS);
    $data = [];
    $values = $form_state->getValues();
    unset($values['submit']);
    unset($values['form_build_id']);
    unset($values['form_token']);
    unset($values['form_id']);
    unset($values['op']);
    foreach ($values as $id => $value) {
      $data[$id] = $value;
      $config->set($id, $value);
    }
    $config->set('static_pages', array_keys($data));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
