<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\group\Entity\Group;
use Drupal\localgov_microsites_group\GroupDefaultContent;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * @covers \Drupal\localgov_microsites_group\GroupDefaultContent
 *
 * @group localgov_microsites_group
 */
class GroupDefaultContentTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'domain',
    'entity_reference_revisions',
    'field_formatter_class',
    'field_group',
    'file',
    'image',
    'media',
    'media_library',
    'gnode',
    'groupmedia',
    'group_content_menu',
    'group_permissions',
    'node',
    'paragraphs',
    'replicate',
    'views',
    'localgov_media',
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('group_content_menu');
    $this->installSchema('node', 'node_access');

    // You really don't want to install localgov_page in a Kernel test!
    NodeType::create([
      'type' => 'localgov_page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => 'example_page',
      'name' => 'Example',
    ])->save();

    $this->installConfig([
      'gnode',
      'localgov_media',
      'localgov_microsites_group',

    ]);
  }

  /**
   * Test generating default content.
   */
  public function testGenerate() {
    $config_factory = $this->container->get('config.factory');
    assert($config_factory instanceof ConfigFactoryInterface);

    $service = new GroupDefaultContent($this->entityTypeManager, $config_factory, $this->container->get('replicate.replicator'));
    $group = Group::create([
      'label' => 'Microsite 1',
      'type' => 'microsite',
    ]);
    $group->save();

    // Default config. Create a new node.
    $result = $service->generate($group);
    $this->assertEquals('Welcome to your new site', $result->label());

    // A existing node.
    $node = Node::create([
      'type' => 'localgov_page',
      'title' => 'Other page',
    ]);
    $node->save();
    $config = $config_factory->getEditable('localgov_microsites_group.settings');
    $config->set('default_group_node', $node->id());
    $config->save();
    $result = $service->generate($group);
    $this->assertEquals('Other page', $result->label());

    // A node in a content type that can't go in the group.
    $node = Node::create([
      'type' => 'example_page',
      'title' => 'Not group content page',
    ]);
    $node->save();
    $config = $config_factory->getEditable('localgov_microsites_group.settings');
    $config->set('default_group_node', $node->id());
    $config->save();
    $result = $service->generate($group);
    $this->assertNull($result);
  }

}
