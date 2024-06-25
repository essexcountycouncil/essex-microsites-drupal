<?php

namespace Drupal\group_term\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Group Term routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_relationship.create_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/term/create');
      $copy->setDefault('base_plugin_id', 'group_term');
      $collection->add('entity.group_relationship.group_term_create_page', $copy);
    }

    if ($route = $collection->get('entity.group_relationship.add_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/term/add');
      $copy->setDefault('base_plugin_id', 'group_term');
      $collection->add('entity.group_relationship.group_term_add_page', $copy);
    }
  }

}
