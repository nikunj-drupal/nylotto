<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Converts Drupal entity structure for Node{Game} bundles into an array.
 */
class EventPromotions extends BaseNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!is_object($data) && !in_array($format, $this->format)) {
      return FALSE;
    }
    if ($data instanceof Node) {
      if ($data->getType() == 'promotions_events') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    switch ($entity->field_event_type->value) {
      case 'promotion':
        $images = $this->getImages($entity, 'field_logo');

        $data = [
          'type' => $entity->field_event_type->value,
          'title' => $entity->title->value,
          'image' => $images,
          'description' => $entity->body->value,
          'cta_button' => $this->getCTAButtons($entity),
        ];
        break;

      case 'event':
        $data = [
          'type' => $entity->field_event_type->value,
          'title' => $entity->title->value,
          'date' => $entity->field_date->value,
          'time' => $entity->field_time->value,
          'location' => $entity->field_location->value,
          'cta_button' => $this->getCTAButtons($entity),
          'description' => $entity->body->value,
        ];
        break;
    }

    return $data + parent::normalize($entity, $format, $context);
  }

}
