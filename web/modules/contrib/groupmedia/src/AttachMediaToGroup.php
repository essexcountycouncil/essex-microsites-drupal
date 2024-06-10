<?php

namespace Drupal\groupmedia;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\Entity\GroupRelationshipInterface;
use Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface;
use Drupal\media\MediaInterface;

/**
 * Class Attach Media To Group.
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
   * @var \Drupal\group\Entity\Storage\GroupRelationshipStorage
   */
  protected $groupRelationshipStorage;

  /**
   * Groupmedia logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * List of plugins by group type.
   *
   * @var array
   */
  protected $pluginsByGroupType = [];

  /**
   * Media item group counts.
   *
   * @var array
   */
  protected $groupCount = [];

  /**
   * AttachMediaToGroup constructor.
   *
   * @param \Drupal\groupmedia\MediaFinderManager $media_finder_manager
   *   Media finder plugin manager.
   * @param \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface $group_relationship_type_manager
   *   The group relation type manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel.
   */
  public function __construct(
    MediaFinderManager $media_finder_manager,
    GroupRelationTypeManagerInterface $group_relationship_type_manager,
    ModuleHandlerInterface $module_handler,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelInterface $logger
  ) {
    $this->mediaFinder = $media_finder_manager;
    $this->groupRelationTypeManager = $group_relationship_type_manager;
    $this->moduleHandler = $module_handler;
    $this->groupRelationshipStorage = $entity_type_manager->getStorage('group_relationship');
    $this->logger = $logger;
  }

  /**
   * Attach media items from given entity to the same group(s).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to process.
   */
  public function attach(EntityInterface $entity) {

    $groups = $this->getGroups($entity);
    if (empty($groups)) {
      return FALSE;
    }

    $items = $this->getMediaFromEntity($entity);
    if (empty($items)) {
      return FALSE;
    }

    $this->assignMediaToGroups($items, $groups);
  }

  /**
   * Assign media items to groups.
   *
   * @param \Drupal\media\MediaInterface[] $media_items
   *   List of media items to assign.
   * @param \Drupal\group\Entity\GroupInterface[] $groups
   *   List of groups to assign media.
   */
  public function assignMediaToGroups(array $media_items, array $groups) {
    $media_plugins_cache = [];

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
      // Build the instance ID.
      $plugin_id = 'group_media:' . $media_item->bundle();

      // Check if this media type should be group relationship or not.
      if (!in_array($plugin_id, $group_relationship_instance_ids)) {
        $this->logger->debug($this->t('Media @label (@id) was not assigned to any group because its bundle (@name) is not enabled in any group', [
          '@label' => $media_item->label(),
          '@id' => $media_item->id(),
          '@name' => $media_item->bundle->entity->label(),
        ]));
        continue;
      }

      foreach ($groups as $group) {
        if (!$this->shouldBeAttached($media_item, $group)) {
          $this->logger->debug($this->t('Media @label (@id) was not assigned to any group because of hook results', [
            '@label' => $media_item->label(),
            '@id' => $media_item->id(),
          ]));
          continue;
        }

        if (!isset($media_plugins_cache[$plugin_id])) {
          $media_plugins_cache[$plugin_id] = $this->getMediaGroupRelationshipEnablerPlugin($group, $plugin_id);
        }

        $plugin = $media_plugins_cache[$plugin_id];
        if (empty($plugin)) {
          continue;
        }

        $group_cardinality = $plugin->getGroupCardinality();
        $group_count = $this->getGroupCount($media_item);

        // Check if group cardinality still allows to create relation.
        if ($group_cardinality == 0 || $group_count < $group_cardinality) {
          $group_relations = $group->getRelationshipsByEntity($media_item, $plugin_id);
          $entity_cardinality = $plugin->getEntityCardinality();
          // Add this media as group relationship if cardinality allows.
          if ($entity_cardinality == 0 || count($group_relations) < $plugin->getEntityCardinality()) {
            $group->addRelationship($media_item, $plugin_id);
          }
          else {
            $this->logger->debug($this->t('Media @label (@id) was not assigned to group @group_label because max entity cardinality was reached', [
              '@label' => $media_item->label(),
              '@id' => $media_item->id(),
              '@group_label' => $group->label(),
            ]));
          }
        }
        else {
          $this->logger->debug($this->t('Media @label (@id) was not assigned to group @group_label because max group cardinality was reached', [
            '@label' => $media_item->label(),
            '@id' => $media_item->id(),
            '@group_label' => $group->label(),
          ]));
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
  public function getGroups(EntityInterface $entity) {
    $groups = [];
    if ($entity instanceof GroupRelationshipInterface) {
      $groups[] = $entity->getGroup();
    }
    elseif ($entity instanceof GroupInterface) {
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

  /**
   * Get media group relationship type plugin.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   Group.
   * @param string $instance_id
   *   Instance id.
   *
   * @return \Drupal\group\Plugin\GroupRelationInterface|null
   *   Media group relationship instance or null.
   */
  private function getMediaGroupRelationshipEnablerPlugin(GroupInterface $group, $instance_id) {
    $group_type_plugins = $this->groupRelationTypeManager->getInstalled($group->getGroupType());

    // Check if the group type supports the plugin of type $instance_id.
    if ($group_type_plugins->has($instance_id)) {
      $plugin = $group_type_plugins->get($instance_id);
      // Tracking is not enabled.
      if ($plugin->isTrackingEnabled()) {
        return $plugin;
      }
    }

    return NULL;
  }

  /**
   * Get group count for media item.
   *
   * @param Drupal\Core\Entity\EntityInterface $item
   *   Media entity.
   *
   * @return int
   *   Group count.
   */
  private function getGroupCount(EntityInterface $item) {
    // Check if it was calculated already.
    if (!isset($this->groupCount[$item->id()])) {
      // Check what relations already exist for this media to control the
      // group cardinality.
      $group_relationships = $this->groupRelationshipStorage->loadByEntity($item);
      $group_ids = [];

      /** @var \Drupal\group\Entity\GroupRelationshipInterface $group_relationship */
      foreach ($group_relationships as $group_relationship) {
        $group_ids[] = $group_relationship->getGroup()->id();
      }
      $this->groupCount[$item->id()] = count(array_unique($group_ids));
    }

    return $this->groupCount[$item->id()];
  }

}
