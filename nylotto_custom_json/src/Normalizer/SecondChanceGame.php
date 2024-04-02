<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Converts Drupal entity structure for Node{Game} bundles into an array.
 */
class SecondChanceGame extends BaseNormalizer {

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
      return ($data->getType() == 'second_chance');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    return parent::normalize($entity, $format, $context);
  }

}
