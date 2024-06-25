<?php

namespace Drupal\domain_group\QueryAccess;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;

/**
 * Defines a class for altering entity queries.
 *
 * Extends entity queries to ensure group is always joined - it isn't if a user
 * has 'by pass group' access in group module. This is then used to limit access
 * by group domain.
 *
 * @see Drupal\group\QueryAccess\EntityQueryAlter
 *
 * @internal
 */
class GroupRelationshipQueryAlter extends QueryAlterBase {

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

    $data_table = $this->ensureDataTable();
    $this->query->condition($data_table . '.gid', $group_id);
  }

  /**
   * Ensures the query is joined against the data table.
   *
   * @return string
   *   The data table alias.
   */
  protected function ensureDataTable() {
    if ($this->dataTableAlias === FALSE) {
      $base_table = $this->getBaseTableAlias();

      if (!$data_table = $this->entityType->getDataTable()) {
        $data_table = $base_table;
        $data_table_found = TRUE;
      }
      else {
        $data_table_found = FALSE;

        foreach ($this->query->getTables() as $alias => $table) {
          if (!$data_table_found && ($table['join type'] === 'INNER' || $alias === $base_table) && $table['table'] === $data_table) {
            $data_table = $alias;
            $data_table_found = TRUE;
            break;
          }
        }
      }

      // If the data table wasn't added to the query yet, add it here.
      if (!$data_table_found) {
        $id_key = $this->entityType->getKey('id');
        $this->dataTableAlias = $this->query->join($data_table, $data_table, "$base_table.$id_key=$data_table.$id_key");
      }
      else {
        $this->dataTableAlias = $data_table;
      }
    }

    return $this->dataTableAlias;
  }

}
