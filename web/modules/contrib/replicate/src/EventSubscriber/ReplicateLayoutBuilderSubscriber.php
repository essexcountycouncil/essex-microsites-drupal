<?php

namespace Drupal\replicate\EventSubscriber;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\replicate\Events\AfterSaveEvent;
use Drupal\replicate\Events\ReplicatorEvents;
use Drupal\replicate\Replicator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ReplicateLayoutBuilderSubscriber.
 *
 * @package Drupal\replicate\EventSubscriber
 */
class ReplicateLayoutBuilderSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The uuid generator.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuid;

  /**
   * The replicator service.
   *
   * @var \Drupal\replicate\Replicator
   */
  protected $replicator;

  /**
   * ReplicateSubscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid generator.
   * @param \Drupal\replicate\Replicator $replicator
   *   The replicator service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, UuidInterface $uuid, Replicator $replicator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->uuid = $uuid;
    $this->replicator = $replicator;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      ReplicatorEvents::AFTER_SAVE => 'onReplicateAfterSave',
    ];
  }

  /**
   * Callback for the replicate after save event.
   *
   * @param \Drupal\replicate\Events\AfterSaveEvent $event
   *   The event.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function onReplicateAfterSave(AfterSaveEvent $event) {
    $entity = $event->getEntity();

    if (!$entity instanceof FieldableEntityInterface || !$entity->hasField(OverridesSectionStorage::FIELD_NAME)) {
      return;
    }

    if ($entity instanceof TranslatableInterface) {
      foreach ($entity->getTranslationLanguages() as $translation_language) {
        /** @var \Drupal\Core\Entity\FieldableEntityInterface $translation */
        $translation = $entity->getTranslation($translation_language->getId());
        $this->cloneInlineBlocks($translation);
        $translation->save();
      }
    }
    else {
      $this->cloneInlineBlocks($entity);
      $entity->save();
    }
  }

  /**
   * Clones layout builder inline block components on entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity to clone the components for.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function cloneInlineBlocks(FieldableEntityInterface $entity) {
    /** @var \Drupal\layout_builder\Field\LayoutSectionItemList $field_item_list */
    $field_item_list = $entity->get(OverridesSectionStorage::FIELD_NAME);
    $block_content_storage = $this->entityTypeManager->getStorage('block_content');

    foreach ($field_item_list->getSections() as $section) {
      foreach ($section->getComponents() as $component) {
        $plugin = $component->getPlugin();
        if (!$plugin instanceof InlineBlock) {
          continue;
        }

        if (empty($plugin->getConfiguration()['block_revision_id'])) {
          continue;
        }

        // Create a copy of the original component.
        $new_component = clone $component;
        $new_component->set('uuid', $this->uuid->generate());

        // Remove the original component.
        $section->removeComponent($component->getUuid());

        // Create a duplicate of the inline block. We need to load
        // by the revision id first, since there's no cloneEntityByRevisionId.
        $to_dupe = $block_content_storage->loadRevision($plugin->getConfiguration()['block_revision_id']);
        // Now that we have the entity, use the Replicator service.
        $duplicated_block = $this->replicator->cloneEntity($to_dupe);
        $duplicated_block->set('langcode', $entity->language()->getId());

        // Add the duplicated block the the new component.
        $configuration = $new_component->get('configuration');
        $configuration['block_serialized'] = serialize($duplicated_block);
        // Make sure that the inline block is added in the usage table.
        // By setting the revision id to NULL.
        $configuration['block_revision_id'] = NULL;
        $new_component->setConfiguration($configuration);

        // Add the new component to the section.
        $section->appendComponent($new_component);
      }
    }
  }

}
