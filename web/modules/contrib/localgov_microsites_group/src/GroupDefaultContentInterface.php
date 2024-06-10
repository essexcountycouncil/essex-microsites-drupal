<?php

namespace Drupal\localgov_microsites_group;

use Drupal\group\Entity\GroupInterface;
use Drupal\node\NodeInterface;

/**
 * GroupDefaultContent interface.
 */
interface GroupDefaultContentInterface {

  /**
   * Generate default content for group.
   *
   * Configuration:
   * localgov_microsites_group.settings.default_group_node can contain the node
   * id to clone to place as default content into the group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group for which to generate the content.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The new or cloned node added as default content.
   */
  public function generate(GroupInterface $group): ?NodeInterface;

}
