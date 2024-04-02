<?php

namespace Drupal\nylotto_custom_json;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Drawing entities.
 *
 * @ingroup nylotto_custom_json
 */
class DrawingListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Drawing ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\nylotto_custom_json\Entity\Drawing $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
          $entity->label(),
          'entity.drawing.edit_form',
          ['drawing' => $entity->id()]
      );
    return $row + parent::buildRow($entity);
  }

}
