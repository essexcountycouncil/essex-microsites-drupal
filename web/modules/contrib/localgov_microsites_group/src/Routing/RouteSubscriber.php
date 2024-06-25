<?php

namespace Drupal\localgov_microsites_group\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * LocalGov Microsites Group event subscriber.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_relationship.canonical')) {
      $defaults = $route->getDefaults();
      unset($defaults['_entity_view']);
      $defaults['_controller'] = 'localgov_microsites_group_redirect_group_relationship';
      $route->setDefaults($defaults);
    }
    if ($route = $collection->get('view.group_nodes.page_1')) {
      $route->setDefault('_controller', GroupViewPageController::class . '::handle');
    }
    if ($route = $collection->get('view.group_nodes.microsite_page')) {
      $route->setDefault('_controller', GroupViewPageController::class . '::handle');
    }
  }

}
