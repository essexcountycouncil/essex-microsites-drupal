<?php

/**
 * @file
 * Describes hooks provided by groupmedia module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\media\MediaInterface;

/**
 * Alters the list of groups entity might belong to.
 *
 * @param array $groups
 *   List of groups used in groupmedia.attach_group service for given $entity.
 *   If make $groups empty array, entity will not be processed.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   Entity object to check.
 */
function hook_groupmedia_entity_group_alter(array &$groups, EntityInterface $entity) {
  if ($entity->id() == 'foo') {
    $groups[] = 'bar';
  }
}

/**
 * Allows to conditionally process the found media items.
 *
 * If media should NOT be processed add FALSE value to the $result array. If at
 * least 1 FALSE will be in the $result array after all modules react on the
 * hook, the media will not be processed. If no value is added to the $result
 * array it is assumed that media is allowed to be processed, no restrictions.
 *
 * @param array $result
 *   Adds reaction to whether media should be processed.
 * @param \Drupal\media\MediaInterface $media
 *   Media item in question.
 * @param array $context
 *   Array with context. Consists of 2 items 'entity' - entity that is processed
 *   to find the media item, 'field_name' - the name of the field connected to
 *   entity where the media was found.
 */
function hook_groupmedia_finder_add_alter(array &$result, MediaInterface $media, array &$context) {
  if ($media->id() == 10 && $context['entity']->getEntityTypeId() == 'paragraph' && $context['field_name'] == 'field_to_exclude') {
    $result[] = FALSE;
  }
}

/**
 * Allows to conditionally add the media to given group.
 *
 * If media should NOT be processed add FALSE value to the $result array. If at
 * least 1 FALSE will be in the $result array after all modules react on the
 * hook, the media will not be processed. If no value is added to the $result
 * array it is assumed that media is allowed to be processed, no restrictions.
 *
 * @param array $result
 *   Sets whether media should be attached to given group.
 * @param \Drupal\media\MediaInterface $media
 *   Media to check.
 * @param \Drupal\group\Entity\GroupInterface $group
 *   Group to check.
 */
function hook_groupmedia_attach_group_alter(array &$result, MediaInterface $media, GroupInterface $group) {
  $account = \Drupal::currentUser();
  if ($media->hasField('field_include') && $group->getMember($account)) {
    $result[] = FALSE;
  }
}
