<?php

namespace Drupal\groupmedia_paragraphs\Plugin\MediaFinder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides common methods for paragraphs media finders.
 */
trait ParagraphsMediaFinderTrait {

  /**
   * Get all paragraphs by entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return array
   *   The paragraphs.
   */
  protected function getParagraphs(EntityInterface $entity): array {
    if (!$entity instanceof ContentEntityInterface) {
      return [];
    }

    $paragraphs = empty($paragraphs) ? [] : $paragraphs;

    // Loop through all fields on the entity.
    foreach ($entity->getFieldDefinitions() as $key => $field) {
      // Check if the field is an entity reference revisions, referencing
      // paragraph entities.
      if ($field->getType() == 'entity_reference_revisions'
        && $field->getSetting('target_type') === 'paragraph'
        && !$entity->get($key)->isEmpty()) {
        foreach ($entity->get($key)->getIterator() as $item) {
          if ($paragraph = $item->entity) {
            $has_paragraph_ref = FALSE;
            foreach ($paragraph->getFieldDefinitions() as $field) {
              if ($field->getType() === 'entity_reference_revisions') {
                $has_paragraph_ref = TRUE;
                break;
              }
            }

            // Add the paragraph even if it is a parent paragraph because it
            // might have a media reference field.
            $paragraphs[] = $paragraph;

            // Extract and add every paragraph item of the nested paragraphs.
            if ($has_paragraph_ref) {
              array_push($paragraphs, ...$this->getParagraphs($paragraph));
            }
          }
        }
      }
    }

    return $paragraphs;
  }

}
