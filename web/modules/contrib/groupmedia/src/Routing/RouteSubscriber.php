<?php

namespace Drupal\groupmedia\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Group Media routes.
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('entity.group_relationship.create_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/media/create');
      $copy->setDefault('base_plugin_id', 'group_media');
      $collection->add('entity.group_relationship.group_media_create_page', $copy);
    }

    if ($route = $collection->get('entity.group_relationship.add_page')) {
      $copy = clone $route;
      $copy->setPath('group/{group}/media/add');
      $copy->setDefault('base_plugin_id', 'group_media');
      $collection->add('entity.group_relationship.group_media_add_page', $copy);
    }
  }

}
