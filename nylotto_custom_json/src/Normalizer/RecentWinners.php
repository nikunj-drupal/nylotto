<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Converts Drupal entity structure for Node{Game} bundles into an array.
 */
class RecentWinners extends BaseNormalizer {

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
      return ($data->getType() == 'recent_winners');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    return parent::normalize($entity, $format, $context);
  }

  /**
   * Returns the last drawing date and results.
   */
  protected function getLastDraw($entity) {
    $drawCounts = count($entity->field_draw_dates) - 1;
    $paragraph = $entity->field_draw_dates[$drawCounts]->entity;
    return [
      'date' => $paragraph->field_draw_date->value,
      'winning_numbers' => $paragraph->field_winning_numbers->getValue(),
    ];
  }

}
