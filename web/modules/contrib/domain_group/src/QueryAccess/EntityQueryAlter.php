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
class EntityQueryAlter extends QueryAlterBase {

  /**
   * {@inheritdoc}
   */
  protected function doAlter($group_id) {
    $entity_type_id = $this->entityType->id();
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    if (!$storage instanceof SqlContentEntityStorage) {
      return;
    }

    // Find all of the group relations that define access.
    $plugin_ids = $this->pluginManager->getPluginIdsByEntityTypeAccess($entity_type_id);
    if (empty($plugin_ids)) {
      return;
    }

    /** @var \Drupal\group\Entity\Storage\GroupRelationshipTypeStorageInterface $gct_storage */
    $group_relationship_data_table = $this->entityTypeManager->getDefinition('group_relationship')->getDataTable();

    // Join the relationship table, but only for used plugins.
    $base_table = $this->getBaseTableAlias();
    $id_key = $this->entityType->getKey('id');
    $this->query->innerJoin(
      $group_relationship_data_table,
      'gcfd_domain',
      "$base_table.$id_key=%alias.entity_id AND %alias.plugin_id IN (:plugin_ids[])",
      [':plugin_ids[]' => $plugin_ids]
    );
    $this->query->condition('gcfd_domain.gid', $group_id);

    // If any new group content entity is added using any of the retrieved
    // plugins, it might change access.
    $cache_tags = [];
    foreach ($plugin_ids as $plugin_id) {
      $cache_tags[] = "group_relationship_list:plugin:$plugin_id";
    }
    $this->cacheableMetadata->addCacheTags($cache_tags);
    // @todo ensure domain is included.
  }

}
