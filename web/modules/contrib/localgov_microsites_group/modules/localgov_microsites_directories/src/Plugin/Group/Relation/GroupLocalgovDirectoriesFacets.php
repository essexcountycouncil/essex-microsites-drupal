<?php

namespace Drupal\localgov_microsites_directories\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a relationship enabler for directory facets types.
 *
 * @GroupRelationType(
 *   id = "group_localgov_directories_facets",
 *   label = @Translation("Directory Facets"),
 *   description = @Translation("Adds directory facets to groups."),
 *   entity_type_id = "localgov_directories_facets",
 * )
 */
class GroupLocalgovDirectoriesFacets extends GroupRelationBase {

}
