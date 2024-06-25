<?php

namespace Drupal\replicate\Events;

use Drupal\Core\Entity\EntityInterface;

class ReplicateAlterEvent extends ReplicateEventBase {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $original;

  public function __construct(EntityInterface $entity, EntityInterface $original) {
    parent::__construct($entity);
    $this->original = $original;
  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getOriginal() {
    return $this->original;
  }

}
