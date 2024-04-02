<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Converts Drupal entity structure for Node{Game} bundles into an array.
 */
class CollectAndWin extends BaseNormalizer {

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
      return ($data->getType() == 'collect_and_win');
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
