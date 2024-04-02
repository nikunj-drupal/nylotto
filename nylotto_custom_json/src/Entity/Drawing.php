<?php

namespace Drupal\nylotto_custom_json\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Drawing entity.
 *
 * @ingroup nylotto_custom_json
 *
 * @ContentEntityType(
 *   id = "drawing",
 *   label = @Translation("Drawing"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\nylotto_custom_json\DrawingListBuilder",
 *     "views_data" = "Drupal\nylotto_custom_json\Entity\DrawingViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\nylotto_custom_json\Form\DrawingForm",
 *       "add" = "Drupal\nylotto_custom_json\Form\DrawingForm",
 *       "edit" = "Drupal\nylotto_custom_json\Form\DrawingForm",
 *       "delete" = "Drupal\nylotto_custom_json\Form\DrawingDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\nylotto_custom_json\DrawingHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\nylotto_custom_json\DrawingAccessControlHandler",
 *   },
 *   base_table = "drawing",
 *   translatable = FALSE,
 *   admin_permission = "administer drawing entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/drawing/drawing/{drawing}",
 *     "add-form" = "/admin/content/drawing/drawing/add",
 *     "edit-form" = "/admin/content/drawing/drawing/{drawing}/edit",
 *     "delete-form" = "/admin/content/drawing/drawing/{drawing}/delete",
 *     "collection" = "/admin/content/drawing/drawing",
 *   },
 *   field_ui_base_route = "drawing.settings"
 * )
 */
class Drawing extends ContentEntityBase implements DrawingInterface {
  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Drawing entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Drawing is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
