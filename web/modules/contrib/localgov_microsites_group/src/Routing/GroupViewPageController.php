<?php

namespace Drupal\localgov_microsites_group\Routing;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Routing\ViewPageController;

/**
 * Switches display for microsites type groups.
 */
class GroupViewPageController extends ViewPageController {

  /**
   * {@inheritdoc}
   */
  public function handle($view_id, $display_id, RouteMatchInterface $route_match) {
    // As varying on the bundle in the route isn't working - at the moment -
    // switch just before the views controller loads the display.
    $group = $route_match->getParameter('group');
    if ($group->bundle() == 'microsite') {
      $display_id = 'microsite_page';
    }
    else {
      $display_id = 'page_1';
    }
    return parent::handle($view_id, $display_id, $route_match);
  }

}
