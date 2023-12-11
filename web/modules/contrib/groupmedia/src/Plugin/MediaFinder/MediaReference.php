<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Plugin for searching media in entity reference fields.
 *
 * @MediaFinder(
 *   id = "media_reference",
 *   label = @Translation("Media in entity reference field"),
 *   description = @Translation("Tracks relationships created with entity reference fields."),
 *   field_types = {"entity_reference"},
 * )
 */
class MediaReference extends MediaFinderBase {

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity) {
    $items = [];

    if ($entity instanceof ContentEntityInterface) {
      // Loop through all fields on the entity.
      foreach ($entity->getFieldDefinitions() as $key => $field) {
        // Check if the field is an entity reference, referencing media entities,
        // and retriever the media entity.
        if (in_array($field->getType(), $this->getApplicableFieldTypes()) &&
          $field->getSetting('target_type') == 'media' &&
          !$entity->get($key)->isEmpty()) {
          foreach ($entity->get($key)->getIterator() as $item) {
            if ($item->entity) {
              $items[] = $item->entity;
            }
          }
        }
      }
    }

    return $items;
  }

}
