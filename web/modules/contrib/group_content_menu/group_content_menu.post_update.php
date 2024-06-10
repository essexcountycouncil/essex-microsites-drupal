<?php

/**
 * @file
 * Install hooks for group_content_menu module.
 */

use Drupal\Core\Site\Settings;
use Drupal\group\Entity\GroupRole;
use Drupal\group\Entity\GroupRoleInterface;
use Drupal\group_content_menu\GroupContentMenuInterface;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Re-write the menu prefix of all group content menus.
 */
function group_content_menu_post_update_menu_prefix_rewrite(&$sandbox) {
  // If 'progress' is not set, this will be the first run of the batch.
  if (!isset($sandbox['progress'])) {
    $sandbox['ids'] = \Drupal::entityTypeManager()->getStorage('menu_link_content')
      ->getQuery()
      ->condition('menu_name', 'menu_link_content-group-menu-', 'STARTS_WITH')
      ->accessCheck(FALSE)
      ->sort('id', 'ASC')
      ->execute();

    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['progress'] = 0;
  }
  $ids = array_slice($sandbox['ids'], $sandbox['progress'], Settings::get('entity_update_batch_size', 50));
  foreach (MenuLinkContent::loadMultiple($ids) as $menu_link) {
    $id = str_replace('menu_link_content-group-menu-', '', $menu_link->getMenuName());
    $updated_menu_name = GroupContentMenuInterface::MENU_PREFIX . $id;
    $menu_link->set('menu_name', $updated_menu_name);
    // Fix #3144156 as well.
    $menu_link->set('bundle', 'menu_link_content');
    $menu_link->save();
    // After the first menu link, there won't be any more tree entries,
    // so this isn't as bad performance as you would think.
    \Drupal::database()->update('menu_tree')
      ->fields([
        'menu_name' => $updated_menu_name,
      ])
      ->condition('menu_name', $menu_link->getMenuName())
      ->execute();
    $sandbox['progress']++;
  }
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  return t("Updated: @progress out of @max menu links.", ['@progress' => $sandbox['progress'], '@max' => $sandbox['max']]);
}

/**
 * Add additional group role permission to manage menu links.
 */
function group_content_menu_post_update_group_role_permissions(&$sandbox) {
  // If 'progress' is not set, this will be the first run of the batch.
  if (!isset($sandbox['progress'])) {
    $sandbox['ids'] = \Drupal::entityTypeManager()->getStorage('group_role')
      ->getQuery()
      ->condition('permissions.*', 'manage group_content_menu')
      ->accessCheck(FALSE)
      ->sort('id', 'ASC')
      ->execute();

    $sandbox['max'] = count($sandbox['ids']);
    $sandbox['progress'] = 0;
  }
  $ids = array_slice($sandbox['ids'], $sandbox['progress'], Settings::get('entity_update_batch_size', 50));
  foreach (GroupRole::loadMultiple($ids) as $group_role) {
    assert($group_role instanceof GroupRoleInterface);
    $group_role->grantPermission('manage group_content_menu menu items')
      ->save();
    $sandbox['progress']++;
  }
  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);
  return t("Updated: @progress out of @max group roles.", ['@progress' => $sandbox['progress'], '@max' => $sandbox['max']]);
}
