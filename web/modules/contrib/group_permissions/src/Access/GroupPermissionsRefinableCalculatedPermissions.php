<?php

namespace Drupal\group_permissions\Access;

use Drupal\flexible_permissions\CalculatedPermissionsInterface;
use Drupal\flexible_permissions\RefinableCalculatedPermissions;

/**
 * Represents a calculated set of group permissions with cacheable metadata.
 *
 * @see \Drupal\flexible_permissions\Access\ChainPermissionCalculator
 */
class GroupPermissionsRefinableCalculatedPermissions extends RefinableCalculatedPermissions {

  /**
   * {@inheritdoc}
   */
  public function merge(CalculatedPermissionsInterface $calculated_permissions, $overwrite = FALSE) {
    foreach ($calculated_permissions->getItems() as $item) {
      $this->addItem($item, $overwrite);
    }
    $this->addCacheableDependency($calculated_permissions);
    return $this;
  }

}
