<?php

namespace Drupal\group\Entity;

use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\Storage\GroupRoleStorageInterface;

/**
 * Functionality trait for a group_membership bundle class.
 */
trait GroupMembershipTrait {

  /**
   * {@inheritdoc}
   */
  public function getRoles($include_synchronized = TRUE) {
    $group_role_storage = $this->entityTypeManager()->getStorage('group_role');
    assert($group_role_storage instanceof GroupRoleStorageInterface);
    return $group_role_storage->loadByUserAndGroup($this->getEntity(), $this->getGroup(), $include_synchronized);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    return $this->getGroup()->hasPermission($permission, $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  public static function loadSingle(GroupInterface $group, AccountInterface $account) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership')
      ->execute();

    return $ids ? $storage->load(reset($ids)) : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByGroup(GroupInterface $group, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('gid', $group->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByUser(AccountInterface $account = NULL, $roles = NULL) {
    $storage = \Drupal::entityTypeManager()->getStorage('group_relationship');

    if (!isset($account)) {
      $account = \Drupal::currentUser();
    }

    $query = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('entity_id', $account->id())
      ->condition('plugin_id', 'group_membership');

    if (isset($roles)) {
      $query->condition('group_roles', (array) $roles, 'IN');
    }

    $ids = $query->execute();
    return $ids ? $storage->loadMultiple($ids) : [];
  }

}
