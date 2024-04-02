<?php

namespace Drupal\nylotto_custom_json\Normalizer;

use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\nylotto_drawing\Entity\Drawing;

/**
 * Provides the base class for other custom normalizers.
 */
class DrawingDataNormalization extends ContentEntityNormalizer {

  /**
   * Provides the allowed formats.
   *
   * @var array
   */
  public $format = ['json', 'api_json'];
  protected $pathAlias;

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = 'Drupal\nylotto_drawing\Entity\DrawingInterface';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!is_object($data) && !in_array($format, $this->format)) {
      return FALSE;
    }
    if ($data instanceof Drawing) {
      return ($data->bundle() == 'drawing_data');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, $context = []) {
    $service = \Drupal::service('ny_lotto.prewarm_normalizer');
    $data = $service->generateNormalizeArray($entity, FALSE);
    return $data;
  }

}
