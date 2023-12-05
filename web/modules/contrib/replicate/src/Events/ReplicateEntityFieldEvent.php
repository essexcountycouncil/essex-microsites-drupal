<?php

namespace Drupal\replicate\Events;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;

class ReplicateEntityFieldEvent extends ReplicateEventBase {

  /**
   * The field item list.
   *
   * @var \Drupal\Core\Field\FieldItemListInterface
   */
  protected $fieldItemList;

  /**
   * Creates a new ReplicateEntityFieldEvent instance.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $field_item_list
   *   The field item list.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function __construct(FieldItemListInterface $field_item_list, EntityInterface $entity) {
    parent::__construct($entity);
    $this->fieldItemList = $field_item_list;
  }

  /**
   * @return FieldItemListInterface
   */
  public function getFieldItemList() {
    return $this->fieldItemList;
  }

}
