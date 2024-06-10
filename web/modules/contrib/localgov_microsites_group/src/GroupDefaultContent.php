<?php

namespace Drupal\localgov_microsites_group;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\replicate\Replicator;

/**
 * Generate default group content.
 */
class GroupDefaultContent implements GroupDefaultContentInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The entity replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * Constructs a GroupDefaultContent oindex:.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The config factory.
   * @param \Drupal\replicate\Replicator $replicator
   *   The entity replicator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config, Replicator $replicator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
    $this->replicator = $replicator;
  }

  /**
   * Generate default content for group.
   *
   * Configuration:
   * localgov_microsites_group.settings.default_group_node can contain the node
   * id to clone to place as default content into the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to generate the content.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The new or cloned node added as default content.
   */
  public function generate(GroupInterface $group): ?NodeInterface {
    $node = NULL;
    $nid = $this->config->get('localgov_microsites_group.settings')->get('default_group_node');
    if ($nid && $default = $this->entityTypeManager->getStorage('node')->load($nid)) {
      $plugin_id = 'group_node:' . $default->bundle();
      if ($group->getGroupType()->hasPlugin($plugin_id)) {
        $node = $this->replicator->replicateEntity($default);
        $this->attachMediaToGroup($node, $group);
        // NB not dispatching ReplicatorEvents::AFTER_SAVE here we don't use it,
        // but it could be added if needed.
        $this->entityTypeManager->getStorage('node')->save($node);
      }
    }
    elseif ($group->getGroupType()->hasPlugin('group_node:localgov_page')) {
      $plugin_id = 'group_node:localgov_page';
      $node = Node::create([
        'title' => 'Welcome to your new site',
        'status' => NodeInterface::PUBLISHED,
        'type' => 'localgov_page',
      ]);

      $node->setOwnerId($group->getOwnerId());
      $node->save();
    }

    if ($node instanceof NodeInterface) {
      $group->addRelationship($node, $plugin_id);
      return $node;
    }

    return NULL;
  }

  /**
   * Iterate fields and paragraphs to find media to attach to the group.
   */
  private function attachMediaToGroup(FieldableEntityInterface $entity, GroupInterface $group) {
    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
      $field_instance = $entity->$field_name;
      // Paragraph recurse into.
      if (($field_definition->getType() == 'entity_reference_revisions') &&
        ($field_instance->getItemDefinition()->getSetting('target_type') == 'paragraph')
      ) {
        foreach ($entity->$field_name->referencedEntities() as $referenced_entity) {
          if ($referenced_entity instanceof FieldableEntityInterface) {
            $this->attachMediaToGroup($referenced_entity, $group);
          }
        }
      }
      // Media attach to group.
      if (($field_definition->getType() == 'entity_reference') &&
        ($field_instance->getItemDefinition()->getSetting('target_type') == 'media')
      ) {
        foreach ($entity->$field_name->referencedEntities() as $referenced_entity) {
          $group->addRelationship($referenced_entity, 'group_media:' . $referenced_entity->bundle());
        }
      }
    }
  }

}
