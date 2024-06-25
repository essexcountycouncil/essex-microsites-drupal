<?php

namespace Drupal\group_sites\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\group_sites\GroupSitesAdminModeInterface;
use Symfony\Component\Routing\Route;

/**
 * Determines access to routes based on whether admin mode is enabled.
 */
class GroupSitesAdminModeAccessCheck implements AccessInterface {

  public function __construct(protected GroupSitesAdminModeInterface $adminMode) {}

  /**
   * Checks access.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route) {
    $admin_mode_on = $route->getRequirement('_group_sites_admin_mode') === 'TRUE';

    // Only allow access if admin mode is on and _group_sites_admin_mode is set
    // to TRUE or the other way around.
    return AccessResult::allowedIf($this->adminMode->isActive() xor !$admin_mode_on);
  }

}
