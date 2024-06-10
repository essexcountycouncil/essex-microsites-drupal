<?php

namespace Drupal\replicate\Events;

use Drupal\Component\EventDispatcher\Event;
use Drupal\Core\Entity\EntityInterface;

abstract class ReplicateEventBase extends Event {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  public function __construct(EntityInterface $entity) {
    $this->entity = $entity;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getEntity() {
    return $this->entity;
  }

}
