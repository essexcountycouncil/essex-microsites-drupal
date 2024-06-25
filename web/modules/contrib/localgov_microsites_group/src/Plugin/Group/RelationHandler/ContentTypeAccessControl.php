<?php

namespace Drupal\localgov_microsites_group\Plugin\Group\RelationHandler;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\localgov_microsites_group\ContentTypeHelperInterface;

/**
 * Checks access for the node relations plugin.
 */
class ContentTypeAccessControl implements AccessControlInterface {

  use AccessControlTrait;

  /**
   * Constructs a new GroupMembershipAccessControl.
   *
   * @param \Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface $parent
   *   The parent access control handler.
   * @param \Drupal\localgov_microsites_group\ContentTypeHelperInterface $contentTypeHelper
   *   The microsite content type helper.
   */
  public function __construct(AccessControlInterface $parent, protected ContentTypeHelperInterface $contentTypeHelper) {
    $this->parent = $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!$this->typeEnabled($group)) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }
    return $this->parent->relationshipCreateAccess($group, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!$this->typeEnabled($group)) {
      return $return_as_object ? AccessResult::forbidden() : FALSE;
    }
    return $this->parent->entityCreateAccess($group, $account, $return_as_object);
  }

  /**
   * Check if new posts should be created in this group for content type.
   *
   * Light check to remove disabled content types from the UI of individual
   * sites.
   */
  private function typeEnabled(GroupInterface $group): bool {
    $content_types = $this->contentTypeHelper->enabledContentTypes($group);
    return in_array($this->pluginId, $content_types);
  }

}
