<?php

namespace Drupal\ginvite\Plugin\Group\RelationHandler;

use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;

/**
 * Checks access for the group_invitation relation plugin.
 */
class GroupInvitationAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupInvitationAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   */
  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsOperation($operation, $target) {
    // Close access to edit group invitations.
    // It will not be supported for now.
    if ($operation === 'update' && $target === 'relationship') {
      return FALSE;
    }
    return $this->parent->supportsOperation($operation, $target);
  }

}
