<?php

namespace Drupal\ecc_waste\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\Routing\Route;

/**
 * Checks if passed parameter matches the route configuration.
 *
 */
class WasteAccessChecker implements AccessInterface {

  /**
   * Access callback.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param $parameter
   *   The parameter to test.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route) {
    $groups =  \Drupal::config('ecc_waste.settings')->get('allowed_groups');
    $plugin_id = 'group_node:waste_disposal_option';

    return AccessResult::allowedIf($plugin_id == 'group_node:waste_disposal_option' && in_array(\Drupal::routeMatch()->getParameter('group')->id(), $groups));
  }

}
