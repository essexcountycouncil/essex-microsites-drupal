<?php

declare(strict_types = 1);

namespace Drupal\groupmedia_paragraphs\Plugin\MediaFinder;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\groupmedia\Plugin\MediaFinder\EntityEmbed;

/**
 * Plugin for searching embed media in paragraphs entity reference fields.
 *
 * @MediaFinder(
 *   id = "paragraphs_media_embed",
 *   label = @Translation("Embed medias in paragraphs"),
 *   description = @Translation("Tracks relationships created with embed media in paragraphs."),
 *   field_types = {"text", "text_long", "text_with_summary"},
 *   element = "drupal-media",
 * )
 */
class ParagraphsMediaEmbed extends EntityEmbed {

  use ParagraphsMediaFinderTrait;

  /**
   * {@inheritdoc}
   */
  public function process(EntityInterface $entity): array {
    $items = [];

    if (!$entity instanceof ContentEntityInterface) {
      return $items;
    }

    $paragraphs = $this->getParagraphs($entity);

    foreach ($paragraphs as $paragraph) {
      // Loop through all fields on the entity.
      foreach ($paragraph->getFieldDefinitions() as $key => $field) {
        // Check if the field is an entity reference, referencing
        // media entities, and retriever the media entity.
        if (in_array($field->getType(), $this->getApplicableFieldTypes()) && !$paragraph->get($key)->isEmpty()) {
          foreach ($paragraph->get($key)->getIterator() as $item) {
            $media_entities = $this->getTargetEntities($item);
            $items = array_merge($items, $media_entities);
          }
        }
      }
    }

    return $items;
  }

}
