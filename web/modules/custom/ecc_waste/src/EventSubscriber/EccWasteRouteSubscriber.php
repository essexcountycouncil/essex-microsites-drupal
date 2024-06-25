<?php

namespace Drupal\ecc_waste\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Route subscriber.
 */
class EccWasteRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    foreach ($collection->all() as $id => $route) {
      if ($id === 'entity.group_relationship.create_form') {
        // Alter the access requirements for the route.
        $route->setRequirement('_custom_access', 'access_check.ecc_waste.microsite:access');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];

    return $events;
  }
}
