<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\taxonomy\Entity\Term;

/**
 * Converts Drupal entity structure for Term{Game Category} into an array.
 */
class GameCategory extends BaseNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\Entity\Term';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!is_object($data) && !in_array($format, $this->format)) {
      return FALSE;
    }

    if ($data instanceof Term) {
      if ($data->vid->target_id == 'game_category') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    return [
      'id' => $entity->id(),
      'title' => $entity->label(),
      'logo' => $this->getStyles($entity->field_logo->entity->image->entity, $entity->field_logo->entity, 'image'),
      'url' => str_replace('internal:', '', $entity->field_endpoint->uri),
    ];
  }

}
