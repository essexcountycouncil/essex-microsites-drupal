<?php

namespace Drupal\group_content_menu\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provide group permissions for group_content_menu entities.
 */
class GroupContentMenuPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupContentMenuPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The parent permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if (($target === 'relationship' || $target === 'entity') && $operation == 'create') {
      return "manage group_content_menu menu items";
    }
    if ($target == 'entity' && in_array($operation, ['view', 'update', 'delete'])) {
      return 'manage group_content_menu';
    }
    if ($target == 'relationship' && in_array($operation, ['view'])) {
      return 'manage group_content_menu';
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = [];

    $permissions["create {$this->pluginId} content"] = [
      'title' => 'Relate menu',
      'description' => 'Allows you to relate a menu to the group.',
    ];

    $permissions['access group content menu overview'] = $this->buildPermission(
      'Access group content menu overview',
      'Access the overview of all menus'
    );
    $permissions['manage group_content_menu'] = $this->buildPermission(
      'Manage menus',
      'Create, update and delete menus'
    );
    $permissions['manage group_content_menu menu items'] = $this->buildPermission(
      'Manage menu items',
      'Create, update and delete menu items within group menus'
    );

    return $permissions;
  }

}
