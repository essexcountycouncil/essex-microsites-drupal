<?php

namespace Drupal\groupmedia\Controller;

use Drupal\group\Entity\Controller\GroupRelationshipController;
use Drupal\group\Entity\GroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for 'group_media' GroupRelationship routes.
 */
class GroupMediaController extends GroupRelationshipController {

  /**
   * The group relationship type plugin manager.
   *
   * @var \Drupal\group\Plugin\Group\Relation\GroupRelationTypeManagerInterface
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginManager = $container->get('group_relation_type.manager');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function addPage(GroupInterface $group, $create_mode = FALSE, $base_plugin_id = NULL) {
    $build = parent::addPage($group, $create_mode);

    // Do not interfere with redirects.
    if (!is_array($build)) {
      return $build;
    }

    // Overwrite the label and description for all displayed bundles.
    $media_type_storage = $this->entityTypeManager->getStorage('media_type');
    $group_type = $group->getGroupType();
    foreach ($this->addPageBundles($group, $create_mode, $base_plugin_id) as $plugin_id => $bundle) {
      $bundle_name = $bundle->getOriginalId();
      if (!empty($build['#bundles'][$bundle_name])) {
        $plugin_id = $bundle->get('content_plugin');
        $plugin = $group_type->getPlugin($plugin_id);
        $bundle_type = $plugin->getRelationType()->getEntityBundle();
        $bundle_label = $media_type_storage->load($bundle_type)->label();
        $t_args = ['%media_type' => $bundle_label];
        $description = $create_mode
          ? $this->t('Create a media of type %media_type in the group.', $t_args)
          : $this->t('Add an existing media of type %media_type to the group.', $t_args);

        $build['#bundles'][$bundle_name]['label'] = $bundle_label;
        $build['#bundles'][$bundle_name]['description'] = $description;
      }
    }

    // Display the bundles in alpha order by label.
    if (is_array($build['#bundles'])) {
      uasort($build['#bundles'], function ($a, $b) {
        return strnatcmp($a['label'], $b['label']);
      });
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function addPageBundles(GroupInterface $group, $create_mode, $base_plugin_id) {
    // Retrieve all group_media plugins for the group's type.
    $plugin_ids = $this->pluginManager->getInstalledIds($group->getGroupType());
    foreach ($plugin_ids as $key => $plugin_id) {
      if (strpos($plugin_id, 'group_media:') !== 0) {
        unset($plugin_ids[$key]);
      }
    }

    // Retrieve all responsible group content types, keyed by plugin ID.
    $storage = $this->entityTypeManager->getStorage('group_relationship_type');
    $properties = [
      'group_type' => $group->bundle(),
      'content_plugin' => $plugin_ids,
    ];

    return $storage->loadByProperties($properties);
  }

}
