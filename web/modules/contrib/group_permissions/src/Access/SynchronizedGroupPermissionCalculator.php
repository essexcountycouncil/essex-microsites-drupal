<?php

namespace Drupal\group_permissions\Access;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flexible_permissions\CalculatedPermissionsItem;
use Drupal\flexible_permissions\PermissionCalculatorBase;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\group_permissions\GroupPermissionsManagerInterface;

/**
 * Calculates synchronized group permissions for an account.
 */
class SynchronizedGroupPermissionCalculator extends PermissionCalculatorBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group permissions manager.
   *
   * @var \Drupal\group_permissions\GroupPermissionsManagerInterface
   */
  protected $groupPermissionsManager;

  /**
   * Constructs a SynchronizedGroupPermissionCalculator object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group_permissions\GroupPermissionsManagerInterface $group_permissions_manager
   *   The group permissions manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GroupPermissionsManagerInterface $group_permissions_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->groupPermissionsManager = $group_permissions_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function calculatePermissions(AccountInterface $account, $scope) {
    $calculated_permissions = parent::calculatePermissions($account, $scope);

    if ($scope !== PermissionScopeInterface::OUTSIDER_ID && $scope !== PermissionScopeInterface::INSIDER_ID) {
      return $calculated_permissions;
    }

    $group_permissions = $this->groupPermissionsManager->getAll();
    if (empty($group_permissions)) {
      return $calculated_permissions;
    }

    // @todo Introduce config:group_role_list:scope:SCOPE cache tag.
    // If a new group role is introduced, we need to recalculate the permissions
    // for the provided scope.
    $calculated_permissions->addCacheTags(['config:group_role_list']);

    $roles = $account->getRoles();
    $group_roles = $this->entityTypeManager->getStorage('group_role')->loadByProperties([
      'scope' => $scope,
      'global_role' => $roles,
    ]);

    foreach ($group_permissions as $group_permission) {
      $custom_permissions = $group_permission->getPermissions();
      foreach ($group_roles as $group_role) {
        assert($group_role instanceof GroupRoleInterface);
        $item = new CalculatedPermissionsItem(
          $group_role->getScope(),
          $group_role->getGroupTypeId(),
          $custom_permissions[$group_role->id()] ?? [],
          $group_role->isAdmin()
        );
        $calculated_permissions->addItem($item);
        $calculated_permissions->addCacheableDependency($group_permission);
        $calculated_permissions->addCacheableDependency($group_role);
      }
    }

    return $calculated_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersistentCacheContexts($scope) {
    if ($scope === PermissionScopeInterface::OUTSIDER_ID || $scope === PermissionScopeInterface::INSIDER_ID) {
      return ['user.roles'];
    }
    return [];
  }

}
