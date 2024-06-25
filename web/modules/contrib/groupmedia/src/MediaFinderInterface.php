<?php

namespace Drupal\groupmedia;

use Drupal\Core\Entity\EntityInterface;
use Drupal\media\MediaInterface;

/**
 * Interface Media Finder Interface.
 *
 * @package Drupal\groupmedia
 */
interface MediaFinderInterface {

  /**
   * Checks if the plugin can be applied.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity in question.
   *
   * @return bool
   *   TRUE if can be applied, FALSE in other case.
   */
  public function applies(EntityInterface $entity);

  /**
   * Search for the attached media entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to search in.
   *
   * @return \Drupal\media\MediaInterface[]
   *   Found media items.
   */
  public function process(EntityInterface $entity);

  /**
   * Returns the field types this plugin is capable of tracking.
   *
   * @return array
   *   An indexed array of field type names, as defined in the plugin's
   *   annotation under the key "field_types".
   */
  public function getApplicableFieldTypes();

  /**
   * Checks whether media item should be considered as group content.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media item to check.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Host entity.
   * @param string $field_name
   *   The field name where the media was found.
   *
   * @return bool
   *   TRUE if item should be processed, FALSE is other case.
   */
  public function shouldBeAdded(MediaInterface $media, EntityInterface $entity, $field_name);

}
