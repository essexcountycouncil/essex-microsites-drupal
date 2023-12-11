<?php

namespace Drupal\groupmedia;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\media\MediaInterface;

/**
 * Class AttachMediaToGroup.
 *
 * @package Drupal\groupmedia
 */
class AttachMediaToGroup {

  use StringTranslationTrait;

  /**
   * The media finder plugin manager.
   *
   * @var \Drupal\groupmedia\MediaFinderManager
   */
  protected $mediaFinder;

  /**
   * The group relation type manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $groupRelationTypeManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Group relationship storage.
   *
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorageInterface
   */
  protected $groupRelationshipStorage;

  /**
   * Group media config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Group media logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * AttachMediaToGroup constructor.
   *
   * @param \Drupal\groupmedia\MediaFinderManager $media_finder_manager
   *   Media finder plugin manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_relationship_type_manager
   *   * The group relation type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    MediaFinderManager $media_finder_manager,
    GroupRelationTypeManagerInterface $group_relationship_type_manager,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    ConfigFactoryInterface $config_factory,
    LoggerChannelInterface $logger
  ) {
    $this->mediaFinder              = $media_finder_manager;
    $this->groupRelationTypeManager = $group_relationship_type_manager;
    $this->moduleHandler            = $module_handler;
    $this->groupRelationshipStorage = $entity_type_manager->getStorage('group_relationship');
    $this->config                   = $config_factory->get('groupmedia.settings');
    $this->logger                   = $logger;
  }

  /**
   * Attach media items from given entity to the same group(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to process.
   */
  public function attach(EntityInterface $entity) {
    if (!$this->config->get('tracking_enabled')) {
      return FALSE;
    }
    $groups = $this->getGroupsByEntity($entity);
    if (empty($groups)) {
      return FALSE;
    }
    $media_items = $this->getMediaFromEntity($entity);
    if (empty($media_items)) {
      return FALSE;
    }
    $this->assignMediaToGroups($media_items, $groups);
  }

  /**
   * Assign media items to groups.
   *
   * @param \Drupal\media\MediaInterface[]         $media_items
   *   List of media items to assign.
   * @param \Drupal\group\Entity\GroupInterface[]  $groups
   *   List of group to assign media.
   * @param bool                                   $check
   *   Flag whether to check assignment conditions.
   */
  public function assignMediaToGroups(array $media_items, array $groups, $check = TRUE) {
    $plugins_by_group_type = [];

    // Get the list of installed group relationship instance IDs.
    $group_type_plugin_map = $this->groupRelationTypeManager->getGroupTypePluginMap();
    $group_relationship_instance_ids = [];

    foreach ($group_type_plugin_map as $plugins) {
      $group_relationship_instance_ids = array_merge(
        $group_relationship_instance_ids,
        $plugins
      );
    }

    /** @var \Drupal\media\MediaInterface $media_item */
    foreach ($media_items as $media_item) {
      $t_arguments = [
        '@label' => $media_item->label(),
        '@id' => $media_item->id(),
      ];

      $plugin_id = 'group_media:' . $media_item->bundle();

      // Check if this media type should be group relationship or not.
      if (!in_array($plugin_id, $group_relationship_instance_ids)) {
        $t_arguments['@name'] = $media_item->bundle->entity->label();
        $this->logger->debug($this->t('Media @label (@id) was not assigned to any group because its bundle (@name) is not enabled in any group type', $t_arguments));
        continue;
      }

      // Check what relations already exist for this media to control the
      // group cardinality.
      $group_relationships = $this->groupRelationshipStorage->loadByEntity($media_item);
      $group_ids = [];
      /** @var \Drupal\group\Entity\GroupRelationshipInterface $instance */
      foreach ($group_relationships as $instance) {
        $group_ids[] = $instance->getGroup()->id();
      }
      $group_count = count(array_unique($group_ids));
      foreach ($groups as $group) {
        $group_type_id = $group->bundle();

        if ($check && !$this->shouldBeAttached($media_item, $group)) {
          $this->logger->debug($this->t('Media @label (@id) was not assigned to any group because of hook results', $t_arguments));
          continue;
        }
        if (!isset($plugins_by_group_type[$group_type_id])) {
          $plugins_by_group_type[$group_type_id] = $this->groupRelationTypeManager->getInstalled($group->getGroupType());
        }

        // Check if the group type supports the plugin of type $plugin_id.
        if ($plugins_by_group_type[$group_type_id]->has($plugin_id)) {
          $plugin = $plugins_by_group_type[$group_type_id]->get($plugin_id);
          $group_cardinality = $plugin->getGroupCardinality();
          // Check if group cardinality still allows to create relation.
          $t_arguments['@group_label'] = $group->label();
          if ($group_cardinality == 0 || $group_count < $group_cardinality) {
            $group_relations = $group->getRelationshipsByEntity($media_item, $plugin_id);
            $entity_cardinality = $plugin->getEntityCardinality();
            // Add this media as group relationship if cardinality allows.
            if ($entity_cardinality == 0 || count($group_relations) < $entity_cardinality) {
              $group->addRelationship($media_item, $plugin_id);
            } else {
              $this->logger->debug($this->t('Media @label (@id) was not assigned to group @group_label because max entity cardinality was reached', $t_arguments));
            }
          } else {
            $this->logger->debug($this->t('Media @label (@id) was not assigned to group @group_label because max group cardinality was reached', $t_arguments));
          }
        }
      }
    }
  }

  /**
   * Gets media items from give entity.
   *
   * Media items are collected with media finder plugins.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity object to search media items in.
   *
   * @return \Drupal\media\MediaInterface[]|array
   *   List of media items found for given entity.
   */
  public function getMediaFromEntity(EntityInterface $entity) {
    $items = [];
    foreach ($this->mediaFinder->getDefinitions() as $plugin_id => $definition) {
      /** @var \Drupal\groupmedia\MediaFinderInterface $plugin_instance */
      $plugin_instance = $this->mediaFinder->createInstance($plugin_id);
      if ($plugin_instance && $plugin_instance->applies($entity)) {
        $found_items = $plugin_instance->process($entity);
        $items = array_merge($items, $found_items);
        if ($entity instanceof GroupRelationshipInterface) {
          $child_entity = $entity->getEntity();
          $found_items = $plugin_instance->process($child_entity);
          $items = array_merge($items, $found_items);
        }
      }
    }
    return $items;
  }

  /**
   * Gets the groups by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to check.
   *
   * @return \Drupal\group\Entity\GroupInterface[]
   *   Groups that the current entity belongs too.
   */
  public function getGroupsByEntity(EntityInterface $entity) {
    $groups = [];
    if ($entity instanceof GroupRelationshipInterface) {
      $groups[] = $entity->getGroup();
    }
    elseif ($entity instanceof GroupInterface){
      $groups[] = $entity;
    }
    elseif ($entity instanceof ContentEntityInterface) {
      $group_relationships = $this->groupRelationshipStorage->loadByEntity($entity);
      foreach ($group_relationships as $group_relationship) {
        $groups[] = $group_relationship->getGroup();
      }
    }
    // Allow other modules to alter.
    $this->moduleHandler->alter('groupmedia_entity_group', $groups, $entity);
    return $groups;
  }

  /**
   * Allow other modules to check whether media should be attached to group.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item to check.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group item to check.
   *
   * @return bool
   *   Returns TRUE if the media should be attached to the group, FALSE in other
   *   case.
   */
  private function shouldBeAttached(MediaInterface $media, GroupInterface $group) {
    $result = [];
    $this->moduleHandler->alter('groupmedia_attach_group', $result, $media, $group);
    if (!is_array($result)) {
      return FALSE;
    }
    // If at least 1 module says "No", the media will not be attached.
    foreach ($result as $item) {
      if (!$item) {
        return FALSE;
      }
    }
    // Otherwise - process.
    return TRUE;
  }

}
