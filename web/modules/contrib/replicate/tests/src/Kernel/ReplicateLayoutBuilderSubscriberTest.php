<?php

namespace Drupal\Tests\replicate\Kernel;

use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\entity_test\Entity\EntityTestMulRevPub;
use Drupal\KernelTests\KernelTestBase;
use Drupal\layout_builder\Entity\LayoutBuilderEntityViewDisplay;
use Drupal\layout_builder\Plugin\Block\InlineBlock;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListInterface;

/**
 * Test description.
 *
 * @group replicate
 */
class ReplicateLayoutBuilderSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'replicate',
    'layout_builder',
    'layout_discovery',
    'block_content',
    'entity_test',
    'field',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('layout_builder', ['inline_block_usage']);

    $this->installEntitySchema('block_content');
    $this->installEntitySchema('entity_test_mulrevpub');

    BlockContentType::create([
      'id' => 'basic',
      'label' => 'Basic block',
      'revision' => 1,
    ])->save();

    $display = LayoutBuilderEntityViewDisplay::create([
      'targetEntityType' => 'entity_test_mulrevpub',
      'bundle' => 'entity_test_mulrevpub',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $display->enableLayoutBuilder();
    $display->setOverridable();
    $display->save();
  }

  /**
   * Tests replicating entity with layout builder inline block.
   */
  public function testReplicateLayoutBuilderSubscriber(): void {
    $section_data = new Section('layout_onecol');
    $section_data->appendComponent($this->createSectionComponent('first-uuid'));
    $section_data->appendComponent($this->createSectionComponent('second-uuid'));
    $section_data->appendComponent($this->createSectionComponent('third-uuid'));

    $entity = EntityTestMulRevPub::create();
    $list = $entity->get(OverridesSectionStorage::FIELD_NAME);
    assert($list instanceof SectionListInterface);
    $list->appendSection($section_data);
    $entity->save();

    $components = array_values($list->getSection(0)->getComponents());

    $clone = $this->container->get('replicate.replicator')->replicateEntity($entity);
    assert($clone instanceof EntityTestMulRevPub);
    $this->assertNotEquals($entity->id(), $clone->id());

    $list = $clone->get(OverridesSectionStorage::FIELD_NAME);
    assert($list instanceof SectionListInterface);
    $cloned_components = array_values($list->getSection(0)->getComponents());

    foreach ($cloned_components as $delta => $component) {
      $plugin = $components[$delta]->getPlugin();
      assert($plugin instanceof InlineBlock);
      $revision_id = $plugin->getConfiguration()['block_revision_id'];

      $cloned_plugin = $component->getPlugin();
      assert($cloned_plugin instanceof InlineBlock);
      $cloned_revision_id = $cloned_plugin->getConfiguration()['block_revision_id'];

      // Assert that block is cloned by comparing their revision id.
      $this->assertNotEquals($revision_id, $cloned_revision_id);

      // Assert that usage is added to inline block usage table.
      $cloned_block = $this->container->get('entity_type.manager')->getStorage('block_content')->loadRevision($cloned_revision_id);
      $this->assertNotNull($cloned_block);
      $usage = $this->container->get('inline_block.usage')->getUsage($cloned_block->id());
      $this->assertNotFalse($usage);
      $this->assertEquals((object) ['layout_entity_id' => $clone->id(), 'layout_entity_type' => 'entity_test_mulrevpub'], $usage);
    }
  }

  /**
   * Creates section component.
   */
  protected function createSectionComponent(string $uuid): SectionComponent {
    return new SectionComponent($uuid, 'content', [
      'id' => 'inline_block:basic',
      'block_serialized' => serialize(BlockContent::create([
        'type' => 'basic',
        'reusable' => FALSE,
      ])),
    ]);
  }

}
