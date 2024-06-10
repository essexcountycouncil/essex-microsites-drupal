<?php

namespace Drupal\group_content_menu\Routing;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider;
use Drupal\group_content_menu\Controller\GroupContentMenuController;
use Drupal\group_content_menu\Entity\GroupContentMenuType;
use Symfony\Component\Routing\Route;

/**
 * Provides routes for group_content_menu content.
 */
class GroupContentMenuRouteProvider extends DefaultHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    if ($add_menu_link = $this->getAddMenuLink($entity_type)) {
      $collection->add('entity.group_content_menu.add_menu_link', $add_menu_link);
    }
    if ($edit_menu_link = $this->getEditMenuLink($entity_type)) {
      $collection->add('entity.group_content_menu.edit_menu_link', $edit_menu_link);
    }
    if ($delete_menu_link = $this->getDeleteMenuLink($entity_type)) {
      $collection->add('entity.group_content_menu.delete_menu_link', $delete_menu_link);
    }

    return $collection;
  }

  /**
   * Gets the add-menu-link route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getAddMenuLink(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('add-menu-link')) {
      $route = new Route($entity_type->getLinkTemplate('add-menu-link'));
      return $route
        ->setDefaults([
          '_title' => 'Add menu link',
          '_controller' => sprintf('%s::addLink', GroupContentMenuController::class),
        ])
        ->setRequirement('_group_permission', 'manage group_content_menu menu items')
        ->setRequirement('_group_installed_content', implode('+', $this->getPluginIds()))
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
        ])
        ->setOption('_group_operation_route', TRUE);
    }
  }

  /**
   * Gets the edit-menu-link route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getEditMenuLink(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('edit-menu-link')) {
      $route = new Route($entity_type->getLinkTemplate('edit-menu-link'));
      return $route
        ->setDefaults([
          '_title' => 'Edit menu link',
          '_controller' => sprintf('%s::editLink', GroupContentMenuController::class),
        ])
        ->setRequirement('_group_permission', 'manage group_content_menu menu items')
        ->setRequirement('_group_installed_content', implode('+', $this->getPluginIds()))
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
          'menu_link_content' => ['type' => 'entity:menu_link_content'],
        ])
        ->setOption('_group_operation_route', TRUE);
    }
  }


  /**
   * Gets the delete-menu-link route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDeleteMenuLink(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('delete-menu-link')) {
      $route = new Route($entity_type->getLinkTemplate('delete-menu-link'));
      return $route
        ->setDefaults([
          '_title' => 'Delete menu link',
          '_controller' => sprintf('%s::deleteLink', GroupContentMenuController::class),
        ])
        ->setRequirement('_group_permission', 'manage group_content_menu menu items')
        ->setRequirement('_group_installed_content', implode('+', $this->getPluginIds()))
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
          'menu_link_content' => ['type' => 'entity:menu_link_content'],
        ])
        ->setOption('_group_operation_route', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddPageRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddPageRoute($entity_type)) {
      $requirements = $route->getRequirements();
      // Remove entity access requirement since we only want group permissions,
      // not some global user permission.
      unset($requirements['_entity_create_any_access']);
      $route->setRequirements($requirements);
      return $route
        ->setDefaults([
          '_title' => 'Add new menu',
          '_controller' => sprintf('%s::addPage', GroupContentMenuController::class),
        ])
        ->setRequirement('_group_permission', 'manage group_content_menu')
        ->setRequirement('_group_installed_content', implode('+', $this->getPluginIds()))
        ->setOption('_group_operation_route', TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getAddFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getAddFormRoute($entity_type)) {
      $requirements = $route->getRequirements();
      // Remove entity access requirement since we only want group permissions,
      // not some global user permission.
      unset($requirements['_entity_create_access']);
      $route->setRequirements($requirements);
      return $route
        ->setDefault('_title', 'Add new menu')
        ->setDefault('_controller', sprintf('%s::createForm', GroupContentMenuController::class))
        ->setRequirement('_group_permission', 'manage group_content_menu')
        ->setRequirement('_group_installed_content', implode('+', $this->getPluginIds()))
        ->setOption('_group_operation_route', TRUE);
    }
  }

  /**
   * Gets the collection route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getCollectionRoute(EntityTypeInterface $entity_type) {
    if ($entity_type->hasLinkTemplate('collection') && $entity_type->hasListBuilderClass()) {
      /** @var \Drupal\Core\StringTranslation\TranslatableMarkup $label */
      $label = $entity_type->getCollectionLabel();
      $route = new Route($entity_type->getLinkTemplate('collection'));
      return $route
        ->addDefaults([
          '_entity_list' => $entity_type->id(),
          '_title' => $label->getUntranslatedString(),
          '_title_arguments' => $label->getArguments(),
          '_title_context' => $label->getOption('context'),
        ])
        ->setOption('_group_operation_route', TRUE)
        ->setRequirement('_group_permission', 'access group content menu overview')
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getCanonicalRoute($entity_type)) {
      $requirements = $route->getRequirements();
      // Remove entity access requirement since we only want group permissions,
      // not some global user permission.
      unset($requirements['_entity_access']);
      $route->setRequirements($requirements);
      return $route
        ->setRequirement('_group_menu_owns_content', 'TRUE')
        ->setRequirement('_group_permission', 'manage group_content_menu')
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getEditFormRoute($entity_type)) {
      $requirements = $route->getRequirements();
      // Remove entity access requirement since we only want group permissions,
      // not some global user permission.
      unset($requirements['_entity_access']);
      $route->setRequirements($requirements);
      return $route
        ->setRequirement('_group_menu_owns_content', 'TRUE')
        ->setRequirement('_group_permission', 'manage group_content_menu menu items')
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getDeleteFormRoute(EntityTypeInterface $entity_type) {
    if ($route = parent::getDeleteFormRoute($entity_type)) {
      $requirements = $route->getRequirements();
      // Remove entity access requirement since we only want group permissions,
      // not some global user permission.
      unset($requirements['_entity_access']);
      $route->setRequirements($requirements);
      return $route
        ->setRequirement('_group_menu_owns_content', 'TRUE')
        ->setRequirement('_group_permission', 'manage group_content_menu')
        ->setOption('_group_operation_route', TRUE)
        ->setOption('parameters', [
          'group' => ['type' => 'entity:group'],
          'group_content_menu' => ['type' => 'entity:group_content_menu'],
        ]);
    }
  }

  /**
   * Get plugin IDs.
   *
   * @return array
   *   The plugin IDs.
   */
  protected function getPluginIds() {
    $plugin_ids = [];
    foreach (array_keys(GroupContentMenuType::loadMultiple()) as $entity_type_id) {
      $plugin_ids[] = "group_content_menu:$entity_type_id";
    }
    return $plugin_ids ?: ['group_content_menu'];
  }

}
