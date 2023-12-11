<?php

namespace Drupal\groupmedia\Plugin\MediaFinder;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TextFieldEmbedBase.
 *
 * @package Drupal\entity_usage\Plugin\EntityUsage\Track
 */
class TextFieldEmbedBase extends MediaFinderBase {

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->entityRepository = $container->get('entity.repository');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function parseEntitiesFromText($text) {
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);
    $entities = [];
    foreach ($xpath->query('//' . $this->pluginDefinition['element'] . '[@data-entity-type="media" and @data-entity-uuid]') as $node) {
      $entities[] = $node->getAttribute('data-entity-uuid');
    }
    return $entities;
  }

  /**
   * {@inheritdoc}
   */
  public function getTargetEntities(FieldItemInterface $item) {
    $item_value = $item->getValue();
    if (empty($item_value['value'])) {
      return [];
    }
    $text = $item_value['value'];
    if ($item->getFieldDefinition()->getType() === 'text_with_summary') {
      $text .= $item_value['summary'];
    }
    $entities_in_text = $this->parseEntitiesFromText($text);
    $valid_entities = [];
    foreach ($entities_in_text as $uuid) {
      // Check if the target entity exists since text fields are not
      // automatically updated when an entity is removed.
      /** @var \Drupal\media\MediaInterface $target_entity */
      if ($target_entity = $this->entityRepository->loadEntityByUuid('media', $uuid)) {
        if ($target_entity && $this->shouldBeAdded($target_entity, $item->getEntity(), $item->getFieldDefinition()->getName())) {
          $valid_entities[$target_entity->id()] = $target_entity;
        }
      }
    }
    return $valid_entities;
  }

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
        if (in_array($field->getType(), $this->getApplicableFieldTypes()) && !$entity->get($key)->isEmpty()) {
          foreach ($entity->get($key)->getIterator() as $item) {
            $media_entities = $this->getTargetEntities($item);
            $items = array_merge($items, $media_entities);
          }
        }
      }
    }

    return $items;
  }

}
