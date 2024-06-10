<?php

namespace Drupal\group_content_menu;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Menu\MenuParentFormSelector;

/**
 * Group content menu implementation of the menu parent form selector service.
 *
 * The form selector is a list of all appropriate menu links.
 */
class GroupContentMenuParentFormSelector extends MenuParentFormSelector {

  /**
   * Determine if menu is a group menu.
   *
   * @var bool
   */
  protected $isGroupMenu = FALSE;

  /**
   * {@inheritdoc}
   */
  public function parentSelectElement($menu_parent, $id = '', array $menus = NULL) {
    if (strpos($menu_parent, GroupContentMenuInterface::MENU_PREFIX) !== FALSE) {
      $this->isGroupMenu = TRUE;
    }
    $element = parent::parentSelectElement($menu_parent, $id, $menus);
    // Add the group content list tag in case a menu is created, deleted, etc.
    $element['#cache']['tags'] = $element['#cache']['tags'] ?? [];
    $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], ['group_content_list']);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function getMenuOptions(array $menu_names = NULL) {
    if (!$this->isGroupMenu) {
      return parent::getMenuOptions($menu_names);
    }
    if (!$route_group = \Drupal::routeMatch()->getParameter('group')) {
      return [];
    }
    $group_content_menus = $this->entityTypeManager->getStorage('group_content_menu')->loadMultiple($menu_names);
    $options = [];
    /** @var \Drupal\group_content_menu\GroupContentMenuInterface[] $menus */
    foreach ($group_content_menus as $group_content_menu) {
      $group_relationships = $this->entityTypeManager->getStorage('group_relationship')->loadByEntity($group_content_menu);
      if ($group_relationships) {
        $menu_group = array_pop($group_relationships)->getGroup();
        if ($menu_group->id() === $route_group->id()) {
          $options[GroupContentMenuInterface::MENU_PREFIX . $group_content_menu->id()] = $group_content_menu->label() . " ({$menu_group->label()})";
        }
      }
    }
    return $options;
  }

}
