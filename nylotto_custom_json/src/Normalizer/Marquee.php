<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Converts Drupal entity structure for Node{Game} bundles into an array.
 */
class Marquee extends BaseNormalizer {

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
      if ($data->getType() == 'marquee') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    $game = $entity->field_game->entity;
    $gameData = [];
    if ($game) {
      $gameData = [
        'logo' => $this->getStyles($game->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
        'title' => $game->label(),
        'id' => $game->id(),
      ];
    }

    return [
      'image' => $this->getStyles($entity->field_image->entity->image->entity, $entity->field_image->entity, 'image'),
      'game' => $gameData,
    ] + parent::normalize($entity, $format, $context);
  }

}
