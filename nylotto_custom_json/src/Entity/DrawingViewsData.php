<?php

namespace Drupal\nylotto_custom_json\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Drawing entities.
 */
class DrawingViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
