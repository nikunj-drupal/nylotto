<?php

namespace Drupal\nylotto_custom_json;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Drawing entity.
 *
 * @see \Drupal\nylotto_custom_json\Entity\Drawing.
 */
class DrawingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\nylotto_custom_json\Entity\DrawingInterface $entity */

    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished drawing entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published drawing entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit drawing entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete drawing entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add drawing entities');
  }

}
