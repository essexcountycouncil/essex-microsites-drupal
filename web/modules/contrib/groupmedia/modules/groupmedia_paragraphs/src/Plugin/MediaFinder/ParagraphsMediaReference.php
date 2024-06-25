<?php

declare(strict_types = 1);

namespace Drupal\groupmedia_paragraphs\Plugin\MediaFinder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\groupmedia\Plugin\MediaFinder\MediaFinderBase;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Plugin for searching media in media paragraphs entity reference fields.
 *
 * @MediaFinder(
 *   id = "paragraphs_media_reference",
 *   label = @Translation("Media paragraphs in entity reference field"),
 *   description = @Translation("Tracks relationships created with media entity reference fields in paragraphs."),
 *   field_types = {"entity_reference_revisions"},
 * )
 */
class ParagraphsMediaReference extends MediaFinderBase {

  use ParagraphsMediaFinderTrait;

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): array {
    $paragraphs = $this->getParagraphs($entity);

    // Get media items from the paragraphs.
    $items = [];
    foreach ($paragraphs as $paragraph) {
      if ($paragraph instanceof ParagraphInterface) {
        // Loop through all fields on the entity.
        foreach ($paragraph->getFieldDefinitions() as $key => $field) {
          // Check if the field is an entity reference, referencing media
          // entities.
          if ($field->getType() === 'entity_reference'
            && $field->getSetting('target_type') === 'media'
            && !$paragraph->get($key)->isEmpty()) {
            foreach ($paragraph->get($key)->getIterator() as $item) {
              if ($item->entity) {
                $items[] = $item->entity;
              }
            }
          }
        }
      }
    }

    return $items;
  }

}
