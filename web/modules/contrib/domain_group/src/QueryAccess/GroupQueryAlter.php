<?php

namespace Drupal\domain_group\QueryAccess;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Alter group queries for Domain Group.
 *
 * A somewhat strange edge case for a list where only one can appear.
 *
 * @internal
 */
class GroupQueryAlter extends QueryAlterBase {

  /**
   * The data table alias.
   *
   * @var string|false
   */
  protected $dataTableAlias = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function doAlter($group_id) {
    $entity_type_id = $this->entityType->id();
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }

    $base_table = $this->getBaseTableAlias();
    $this->query->condition($base_table . '.id', $group_id);
  }

}
