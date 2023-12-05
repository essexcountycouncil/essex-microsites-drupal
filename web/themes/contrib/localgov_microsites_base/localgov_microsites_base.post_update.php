<?php

/**
 * @file
 * Update hooks for LocalGov Microsites Base theme.
 */

use Drupal\block\Entity\Block;

/**
 * Remove breadcrumbs from the front page.
 */
function localgov_microsites_base_post_update_frontpage_breadcrumb() {
  $breadcrumb_block = Block::load('localgov_microsites_base_breadcrumbs');
  $visibility = $breadcrumb_block->getVisibility();
  if (empty($visibility)) {
    $breadcrumb_block->setVisibilityConfig('request_path', [
      'id' => 'request_path',
      'negate' => TRUE,
      'pages' => '<front>',
    ]);
    $breadcrumb_block->save();
    return \t('Breadcrumb block removed from front pages.');
  }
  else {
    // Changed from default. Just set a note.
    return \t('Breadcrumb block has been updated since install. If you would like it not to be shown on front pages change the block visibility to exclude <front>.');
  }
}
