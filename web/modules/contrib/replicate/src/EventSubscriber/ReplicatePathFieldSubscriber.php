<?php

namespace Drupal\replicate\EventSubscriber;

use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReplicatePathFieldSubscriber.
 *
 * @package Drupal\replicate\EventSubscriber
 */
class ReplicatePathFieldSubscriber implements EventSubscriberInterface {

  /**
   * Cleans a path on an entity being replicated.
   *
   * @param ReplicateEntityFieldEvent $event
   *   The event we're working on.
   */
  public function onPathClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();

    foreach ($field_item_list as $field_item) {
      $field_item->alias = NULL;
      $field_item->pid = NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::replicateEntityField('path')][] = 'onPathClone';
    return $events;
  }

}
