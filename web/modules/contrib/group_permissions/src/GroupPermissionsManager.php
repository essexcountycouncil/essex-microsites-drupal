<?php

namespace Drupal\group_permissions;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;

/**
 * Service to handle custom group permissions.
 */
class GroupPermissionsManager implements GroupPermissionsManagerInterface {

  /**
   * The array of the group custom permissions.
   *
   * @var array
   */
  protected $customPermissions = [];

  /**
   * The array of the group permissions objects.
   *
   * @var array
   */
  protected $groupPermissions = [];

  /**
   * The cache backend to use.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Group role storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupRoleStorage;

  /**
   * Group permissions storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $groupPermissionStorage;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(CacheBackendInterface $cache_backend, EntityTypeManagerInterface $entity_type_manager) {
    $this->cacheBackend = $cache_backend;
    $this->entityTypeManager = $entity_type_manager;
    $this->groupRoleStorage = $entity_type_manager->getStorage('group_role');
    $this->groupPermissionStorage = $entity_type_manager->getStorage('group_permission');
    $this->groupPermissionStorage = $entity_type_manager->getStorage('group_permission');
  }

  /**
   * {@inheritdoc}
   */
  public function getCustomPermissions(GroupInterface $group) {
    $group_id = $group->id();
    $this->customPermissions[$group_id] = [];
    if (empty($this->customPermissions[$group_id])) {
      $cid = "custom_group_permissions:$group_id";
      $data_cached = $this->cacheBackend->get($cid);
      if (!$data_cached) {
        /** @var \Drupal\group_permissions\Entity\GroupPermission $group_permission */
        $group_permission = $this->loadByGroup($group);
        if (!empty($group_permission) && $group_permission->isPublished()) {
          $this->groupPermissions[$group_id] = $group_permission;
          $tags = [];
          $tags[] = "group:$group_id";
          $tags[] = "group_permission:{$group_permission->id()}";
          $this->customPermissions[$group_id] = $group_permission->getPermissions();
          // Store the tree into the cache.
          $this->cacheBackend->set($cid, $this->customPermissions[$group_id], CacheBackendInterface::CACHE_PERMANENT, $tags);
        }
      }
      else {
        $this->customPermissions[$group_id] = $data_cached->data;
      }
    }

    return $this->customPermissions[$group_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupPermission(GroupInterface $group) {
    $group_id = $group->id();
    if (empty($this->groupPermissions[$group_id])) {
      $this->groupPermissions[$group_id] = $this->loadByGroup($group);
    }

    return $this->groupPermissions[$group_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getAll() {
    return $this->groupPermissionStorage->getAllActive();
  }

  /**
   * {@inheritdoc}
   */
  public function loadByGroup(GroupInterface $group) {
    return $this->groupPermissionStorage->loadByGroup($group);
  }

  /**
   * {@inheritdoc}
   */
  public function getNonAdminRoles($group) {
    return $this->entityTypeManager
      ->getStorage('group_role')
      ->loadByProperties([
        'group_type' => $group->getGroupType()->id(),
        'admin' => 0,
      ]);
  }

}
