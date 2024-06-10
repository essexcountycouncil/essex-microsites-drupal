<?php

namespace Drupal\ginvite\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface;
use Drupal\group\Plugin\Group\RelationHandler\PermissionProviderTrait;

/**
 * Provides group permissions for the group_invitation relation plugin.
 */
class GroupInvitationPermissionProvider implements PermissionProviderInterface {

  use PermissionProviderTrait;

  /**
   * Constructs a new GroupMembershipPermissionProvider.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\PermissionProviderInterface $parent
   *   The default permission provider.
   */
  public function __construct(PermissionProviderInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getPermission($operation, $target, $scope = 'any') {
    if ($target === 'relationship') {
      if ($operation === 'delete') {
        return "$operation $scope invitation";
      }
      if ($operation === 'create') {
        return 'invite users to group';
      }
      if ($operation === 'view') {
        return 'view group invitations';
      }
      if ($operation === 'update') {
        return FALSE;
      }
    }
    return $this->parent->getPermission($operation, $target, $scope);

  }

  /**
   * {@inheritdoc}
   */
  public function buildPermissions() {
    $permissions = $this->parent->buildPermissions();
    $permissions['invite users to group'] = [
      'title' => 'Invite users to group',
      'description' => 'Allows users with permissions to invite new users to group.',
    ];
    $permissions['view group invitations'] = [
      'title' => 'View group invitations',
      'description' => 'Allows users with permissions view created invitations.',
    ];

    $permissions['delete own invitation'] = [
      'title' => 'Delete own invitation',
      'description' => 'Allows users with permissions to delete own invitation to group.',
    ];
    $permissions['delete any invitation'] = [
      'title' => 'Delete any invitations',
      'description' => 'Allows users with permissions to delete any invitation to group.',
    ];

    return $permissions;
  }

}
