<?php

namespace Drupal\groupmedia\Plugin\Group\Relation;

use Drupal\group\Plugin\Group\Relation\GroupRelationBase;

/**
 * Provides a relation type plugin for media types.
 *
 * @GroupRelationType(
 *   id = "group_media_type",
 *   label = @Translation("Group media type"),
 *   description = @Translation("Adds media type to groups both publicly and privately."),
 *   entity_type_id = "media_type",
 * )
 */
class GroupMediaType extends GroupRelationBase {}
