<?php

namespace Drupal\custom_list;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Source List entity.
 *
 * @see \Drupal\custom_list\Entity\SourceListEntity.
 */
class SourceListEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\custom_list\Entity\SourceListEntityInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view source list entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit source list entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete source list entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add source list entities');
  }

}
