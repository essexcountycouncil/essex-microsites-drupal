<?php

/**
 * @file
 * Post update functions for the embed module.
 */

/**
 * Change views context to user.group_permissions.
 */
function groupmedia_post_update_group_permissions() {
  if (\Drupal::moduleHandler()->moduleExists('views')) {
    $view = \Drupal::entityTypeManager()->getStorage('view')->load('group_media');
    if ($view) {
      // Resave the existing view to recalculate cache contexts.
      $view->save();
    }
  }
}

/**
 * Install groupmedia configuration.
 */
function groupmedia_post_update_add_config(&$sandbox) {
  \Drupal::configFactory()->getEditable('groupmedia.settings')
    ->set('bundles', [])
    ->set('tracking_enabled', TRUE)
    ->save();
}
