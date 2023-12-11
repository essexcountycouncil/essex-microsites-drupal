<?php

namespace Drupal\groupmedia\Routing;

use Drupal\media\Entity\MediaType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_media group content.
 */
class GroupMediaRouteProvider {

  /**
   * Provides the shared collection route for group media plugins.
   */
  public function getRoutes() {
    $routes = $plugin_ids = $permissions_add = $permissions_create = [];

    foreach (MediaType::loadMultiple() as $name => $media_bundle) {
      $plugin_id = "group_media:$name";

      $plugin_ids[] = $plugin_id;
      $permissions_add[] = "create $plugin_id content";
      $permissions_create[] = "create $plugin_id entity";
    }

    // If there are no media types yet, we cannot have any plugin IDs and should
    // therefore exit early because we cannot have any routes for them either.
    if (empty($plugin_ids)) {
      return $routes;
    }

    $routes['entity.group_relationship.group_media_relate_page'] = new Route('group/{group}/media/add');
    $routes['entity.group_relationship.group_media_relate_page']
      ->setDefaults([
        '_title' => 'Relate media',
        '_controller' => '\Drupal\groupmedia\Controller\GroupMediaController::addPage',
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_add))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    $routes['entity.group_relationship.group_media_add_page'] = new Route('group/{group}/media/create');
    $routes['entity.group_relationship.group_media_add_page']
      ->setDefaults([
        '_title' => 'Create media',
        '_controller' => '\Drupal\groupmedia\Controller\GroupMediaController::addPage',
        'create_mode' => TRUE,
      ])
      ->setRequirement('_group_permission', implode('+', $permissions_create))
      ->setRequirement('_group_installed_content', implode('+', $plugin_ids))
      ->setOption('_group_operation_route', TRUE);

    return $routes;
  }

}
