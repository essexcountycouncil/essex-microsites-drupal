<?php

namespace Drupal\ecc_waste;

use Drupal\group\Plugin\Group\RelationHandler\AccessControlInterface;
use Drupal\group\Plugin\Group\RelationHandler\AccessControlTrait;
use Drupal\group\Plugin\Group\RelationHandler\GroupNodeAccessControlHandlerDecorator;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\Core\Access\AccessResult;


class CustomGroupNodeAccessControlDecorator implements AccessControlInterface {

  use AccessControlTrait;

  public $groups;

  public function __construct(AccessControlInterface $parent) {
    $this->parent = $parent;
    $this->groups =  \Drupal::config('ecc_waste.settings')->get('allowed_groups');
  }

  /**
   * {@inheritdoc}
   */
  public function relationshipCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!empty($this->groups)) {
      if (!in_array($group->id(), $this->groups) && $this->pluginId === 'group_node:waste_disposal_option') {
        return $return_as_object ? AccessResult::forbidden()->addCacheContexts(['user.group_permissions']) : FALSE;
      }
    }

    return $this->parent->relationshipCreateAccess($group, $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function entityCreateAccess(GroupInterface $group, AccountInterface $account, $return_as_object = FALSE) {
    if (!empty($this->groups)) {
      if (!in_array($group->id(), $this->groups) && $this->pluginId === 'group_node:waste_disposal_option') {
        return $return_as_object ? AccessResult::forbidden()->addCacheContexts(['user.group_permissions']) : FALSE;
      }
    }

    return $this->parent->entityCreateAccess($group, $account, $return_as_object);
  }

}
