<?php

namespace Drupal\Tests\replicate\Unit;

use Drupal\block\BlockInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListInterface;
use Drupal\replicate\Events\AfterSaveEvent;
use Drupal\replicate\EventSubscriber\ReplicateLayoutBuilderSubscriber;
use Drupal\replicate\Replicator;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\replicate\EventSubscriber\ReplicateLayoutBuilderSubscriber
 * @group replicate
 */
class ReplicateLayoutBuilderSubscriberTest extends UnitTestCase {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The replicator service.
   *
   * @var \Drupal\replicate\Replicator|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $replicator;

  /**
   * The uuid generator service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $uuid;

  /**
   * @covers ::onReplicateAfterSave
   */
  public function testReplicateAfterSaveNonFieldableEntity(): void {
    $entity = $this->prophesize(EntityInterface::class);
    $entity->save()->shouldNotBeCalled();

    $this->replicator->cloneEntity(Argument::type(EntityInterface::class))->shouldNotBeCalled();

    $subscriber = new ReplicateLayoutBuilderSubscriber($this->entityTypeManager->reveal(), $this->uuid->reveal(), $this->replicator->reveal());
    $subscriber->onReplicateAfterSave(new AfterSaveEvent($entity->reveal()));
  }

  /**
   * @covers ::onReplicateAfterSave
   */
  public function testReplicateAfterSaveWithoutField(): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(FALSE);
    $entity->save()->shouldNotBeCalled();

    $this->replicator->cloneEntity(Argument::type(EntityInterface::class))->shouldNotBeCalled();

    $subscriber = new ReplicateLayoutBuilderSubscriber($this->entityTypeManager->reveal(), $this->uuid->reveal(), $this->replicator->reveal());
    $subscriber->onReplicateAfterSave(new AfterSaveEvent($entity->reveal()));
  }

  /**
   * @covers ::onReplicateAfterSave
   * @covers ::cloneInlineBlocks
   *
   * @dataProvider replicateAfterSaveFieldableEntityProvider
   */
  public function testReplicateAfterSaveFieldableEntity(FieldableEntityInterface $entity): void {
    $block = $this->prophesize(BlockInterface::class);
    // Using prophecy here will throw Exception: "Serialization of 'Closure' is
    // not allowed".
    $clone = $this->createMock(BlockInterface::class);

    $this->replicator->cloneEntity($block)->willReturn($clone)->shouldBeCalled();

    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->loadRevision(1)->willReturn($block);
    $this->entityTypeManager->getStorage('block_content')->willReturn($storage);

    $subscriber = new ReplicateLayoutBuilderSubscriber($this->entityTypeManager->reveal(), $this->uuid->reveal(), $this->replicator->reveal());
    $subscriber->onReplicateAfterSave(new AfterSaveEvent($entity));
  }

  /**
   * Data provider for testReplicateAfterSaveFieldableEntity.
   *
   * @return array<string, FieldableEntityInterface[]>
   *   Provided data.
   */
  public function replicateAfterSaveFieldableEntityProvider(): array {
    $non_inline_block_component = $this->prophesize(SectionComponent::class);
    $non_inline_block_component->getUuid()->shouldNotBeCalled();

    $empty_revision_id_plugin = $this->prophesize(InlineBlock::class);
    $empty_revision_id_plugin->getConfiguration()->willReturn([]);
    $empty_revision_id_component = $this->prophesize(SectionComponent::class);
    $empty_revision_id_component->getPlugin()->willReturn($empty_revision_id_plugin);
    $empty_revision_id_component->getUuid()->shouldNotBeCalled();

    $plugin = $this->prophesize(InlineBlock::class);
    $plugin->getConfiguration()->willReturn(['block_revision_id' => 1]);
    $component = $this->prophesize(SectionComponent::class);
    $component->get('configuration')->willReturn([]);
    $component->getPlugin()->willReturn($plugin);
    $component->getUuid()->willReturn(NULL);
    $component->set('uuid', NULL)->willReturn($component);
    $component->setConfiguration(Argument::type('array'))->willReturn($component);

    $section = $this->prophesize(Section::class);
    $section->appendComponent(Argument::type(SectionComponent::class))->willReturn($section);
    $section->getComponents()->willReturn([
      $non_inline_block_component,
      $empty_revision_id_component,
      $component,
    ]);
    $section->removeComponent(NULL)->willReturn($section);

    $section_list = $this->prophesize(SectionListInterface::class);
    $section_list->getSections()->willReturn([$section]);

    $non_translatable_entity = $this->prophesize(FieldableEntityInterface::class);
    $non_translatable_entity->get(OverridesSectionStorage::FIELD_NAME)->willReturn($section_list);
    $non_translatable_entity->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(TRUE);
    $non_translatable_entity->language()->willReturn($this->prophesize(LanguageInterface::class));
    $non_translatable_entity->save()->shouldBeCalled();

    $language = $this->prophesize(LanguageInterface::class);
    $language->getId()->willReturn(LanguageInterface::LANGCODE_DEFAULT);
    /** @var \Drupal\Core\Entity\FieldableEntityInterface|\Prophecy\Prophecy\ObjectProphecy $translatable_entity */
    $translatable_entity = $this->prophesize()->willImplement(FieldableEntityInterface::class)->willImplement(TranslatableInterface::class);
    $translatable_entity->getTranslation(LanguageInterface::LANGCODE_DEFAULT)->willReturn($non_translatable_entity);
    $translatable_entity->getTranslationLanguages()->willReturn([$language]);
    $translatable_entity->hasField(OverridesSectionStorage::FIELD_NAME)->willReturn(TRUE);

    return [
      'Non-translatable' => [$non_translatable_entity->reveal()],
      'Translatable' => [$translatable_entity->reveal()],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->uuid = $this->prophesize(UuidInterface::class);
    $this->replicator = $this->prophesize(Replicator::class);
  }

}
