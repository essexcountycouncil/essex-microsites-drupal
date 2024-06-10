<?php

namespace Drupal\group_permissions;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the group permissions schema handler.
 */
class GroupPermissionStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'node_revision') {
      switch ($field_name) {

        case 'revision_uid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'users', 'uid');
          break;

        case 'gid':
          $this->addSharedTableFieldForeignKey($storage_definition, $schema, 'groups', 'id');
          break;

      }
    }

    return $schema;
  }

}
