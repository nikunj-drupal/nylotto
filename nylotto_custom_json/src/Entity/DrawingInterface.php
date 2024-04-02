<?php

namespace Drupal\nylotto_custom_json\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface for defining Drawing entities.
 *
 * @ingroup nylotto_custom_json
 */
interface DrawingInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the Drawing name.
   *
   * @return string
   *   Name of the Drawing.
   */
  public function getName();

  /**
   * Sets the Drawing name.
   *
   * @param string $name
   *   The Drawing name.
   *
   * @return \Drupal\nylotto_custom_json\Entity\DrawingInterface
   *   The called Drawing entity.
   */
  public function setName($name);

  /**
   * Gets the Drawing creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Drawing.
   */
  public function getCreatedTime();

  /**
   * Sets the Drawing creation timestamp.
   *
   * @param int $timestamp
   *   The Drawing creation timestamp.
   *
   * @return \Drupal\nylotto_custom_json\Entity\DrawingInterface
   *   The called Drawing entity.
   */
  public function setCreatedTime($timestamp);

}
