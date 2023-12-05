<?php

namespace Drupal\group_content_menu;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a group content menu entity type.
 */
interface GroupContentMenuInterface extends ContentEntityInterface {

  /**
   * The menu name prefix.
   */
  const MENU_PREFIX = 'group_menu_link_content-';

}
