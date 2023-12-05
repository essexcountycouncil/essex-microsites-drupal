<?php

namespace Drupal\localgov_microsites_group\EventSubscriber;

use Drupal\replicate\Events\ReplicateEntityFieldEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Replicator Event subscriber.
 */
class ReplicateGroupContent implements EventSubscriberInterface {

  /**
   * The entity replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * Constructs a ReplicatorGroupContent event subscriber.
   *
   * @param \Drupal\replicate\Replicator $replicator
   *   The entity replicator service.
   */
  public function __construct(Replicator $replicator) {
    $this->replicator = $replicator;
  }

  /**
   * Replicate media attached to an entity reference field.
   *
   * @param \Drupal\replicate\Events\ReplicateEntityFieldEvent $event
   *   The event we're working on.
   */
  public function onEntityReferenceClone(ReplicateEntityFieldEvent $event) {
    $field_item_list = $event->getFieldItemList();
    if ($field_item_list->getItemDefinition()->getSetting('target_type') == 'media') {
      foreach ($field_item_list as $delta => $field_item) {
        $field_item_list->set($delta, $this->replicator->replicateEntity($field_item->entity));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[ReplicatorEvents::replicateEntityField('entity_reference')][] = 'onEntityReferenceClone';
    return $events;
  }

}
