<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\localgov_microsites_group\ContentTypeHelperInterface;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * @covers \Drupal\localgov_microsites_group\GroupPermisisonsHelper
 *
 * @group localgov_microsites_group
 */
class ContentTypeHelperTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'domain_path',
    'entity_browser',
    'entity_browser_entity_form',
    'entity_reference_revisions',
    'field_formatter_class',
    'field_group',
    'file',
    'image',
    'media',
    'media_library',
    'geo_entity',
    'geofield',
    'gnode',
    'groupmedia',
    'group_content_menu',
    'group_term',
    'group_sites',
    'layout_discovery',
    'layout_paragraphs',
    'layout_paragraphs_permissions',
    'node',
    'override_node_options',
    'paragraphs',
    'path_alias',
    'replicate',
    'taxonomy',
    'toolbar',
    'tour',
    'user',
    'views',
    'localgov_geo',
    'localgov_media',
    'localgov_microsites_events',
    'localgov_microsites_group',
    'localgov_paragraphs_layout',
    'localgov_sa11y',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // You really don't want to install localgov_page or localgov_events in a
    // Kernel test!
    NodeType::create([
      'type' => 'localgov_page',
      'name' => 'Page',
    ])->save();
    NodeType::create([
      'type' => 'example_page',
      'name' => 'Example',
    ])->save();
    NodeType::create([
      'type' => 'localgov_event',
      'name' => 'Event',
    ])->save();
    Vocabulary::create([
      'name' => 'Event categories',
      'vid' => 'localgov_event_category',
    ])->save();
    Vocabulary::create([
      'name' => 'Event locality',
      'vid' => 'localgov_event_locality',
    ])->save();
    Vocabulary::create([
      'name' => 'Event price',
      'vid' => 'localgov_event_price',
    ])->save();

    $this->installEntitySchema('file');
    $this->installEntitySchema('group_content_menu');
    $this->installEntitySchema('user');
    $this->installSchema('node', 'node_access');
    $this->installSchema('file', 'file_usage');

    $this->installConfig([
      'geo_entity',
      'gnode',
      'override_node_options',
      'user',
      'localgov_media',
      'localgov_paragraphs_layout',
      'localgov_microsites_group',
      'localgov_microsites_events',
    ]);

    localgov_microsites_group_modules_installed(['localgov_microsites_events'], FALSE);

    $account = $this->createUser();
    $account->addRole('microsites_controller');
    $account->save();
    $this->setCurrentUser($account);

    $this->group = $this->createGroup([
      'type' => 'microsite',
      'status' => 1,
    ]);
    $this->group->save();
  }

  /**
   * Test enable disable module permissions.
   */
  public function testToggleModule() {
    $permissions_helper = $this->container->get('localgov_microsites_group.content_type_helper');
    $this->assertEquals(ContentTypeHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
    $permissions_helper->moduleDisable('localgov_microsites_events', $this->group);
    $this->assertEquals(ContentTypeHelperInterface::DISABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
    $permissions_helper->moduleEnable('localgov_microsites_events', $this->group);
    $this->assertEquals(ContentTypeHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
  }

  /**
   * Test list of all available modules.
   */
  public function testModulesList() {
    $permissions_helper = $this->container->get('localgov_microsites_group.content_type_helper');
    $this->assertEquals([
      'localgov_microsites_events' => ContentTypeHelperInterface::ENABLED,
    ], $permissions_helper->modulesList($this->group));
  }

}
