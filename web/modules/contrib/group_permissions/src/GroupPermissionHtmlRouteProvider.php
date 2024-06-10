<?php

namespace Drupal\group_permissions;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group permission entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class GroupPermissionHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    $entity_type_id = $entity_type->id();

    if ($history_route = $this->getDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.delete-form", $history_route);
    }

    if ($history_route = $this->getHistoryRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.version-history", $history_route);
    }

    if ($revision_route = $this->getRevisionRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision", $revision_route);
    }

    if ($revert_route = $this->getRevisionRevertRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision-revert", $revert_route);
    }

    if ($delete_route = $this->getRevisionDeleteRoute($entity_type)) {
      $collection->add("entity.{$entity_type_id}.revision-delete", $delete_route);
    }

    return $collection;
  }

  /**
   * Gets the delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteRoute(EntityTypeInterface $entity_type) {
    $link_key = 'delete-form';
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults([
          '_form' => '\Drupal\group_permissions\Form\GroupPermissionDeleteForm',
          '_title' => 'Delete group permissions',
        ])
        ->setRequirement('_permission', 'override group permissions')
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          'group' => [
            'type' => 'entity:group',
          ],
        ]);

      return $route;
    }
  }

  /**
   * Gets the version history route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getHistoryRoute(EntityTypeInterface $entity_type) {
    $link_key = 'version-history';
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults([
          '_title' => "{$entity_type->getLabel()} revisions",
          '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionOverview',
        ])
        ->setRequirement('_permission', 'override group permissions')
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the revision route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRoute(EntityTypeInterface $entity_type) {
    $link_key = 'revision';
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults([
          '_controller' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionShow',
          '_title_callback' => '\Drupal\group_permissions\Controller\GroupPermissionsController::revisionPageTitle',
        ])
        ->setRequirement('_permission', 'override group permissions')
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          'group' => [
            'type' => 'entity:group',
          ],
          'group_permission' => [
            'type' => 'entity:group_permission',
          ],
        ]);

      return $route;
    }
  }

  /**
   * Gets the revision revert route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionRevertRoute(EntityTypeInterface $entity_type) {
    $link_key = 'revision-revert';
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults([
          '_form' => '\Drupal\group_permissions\Form\GroupPermissionRevisionRevertForm',
          '_title' => 'Revert to earlier revision',
        ])
        ->setRequirement('_permission', 'override group permissions')
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          'group' => [
            'type' => 'entity:group',
          ],
          'group_permission' => [
            'type' => 'entity:group_permission',
          ],
        ]);

      return $route;
    }
  }

  /**
   * Gets the revision delete route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getRevisionDeleteRoute(EntityTypeInterface $entity_type) {
    $link_key = 'revision-delete';
    if ($entity_type->hasLinkTemplate($link_key)) {
      $route = new Route($entity_type->getLinkTemplate($link_key));
      $route
        ->setDefaults([
          '_form' => '\Drupal\group_permissions\Form\GroupPermissionRevisionDeleteForm',
          '_title' => 'Delete revision',
        ])
        ->setRequirement('_permission', 'override group permissions')
        ->setOption('_admin_route', TRUE)
        ->setOption('parameters', [
          'group' => [
            'type' => 'entity:group',
          ],
          'group_permission' => [
            'type' => 'entity:group_permission',
          ],
        ]);

      return $route;
    }
  }

}
