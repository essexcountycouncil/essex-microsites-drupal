<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\group_permissions\Entity\GroupPermission;
use Drupal\group_permissions\Entity\GroupPermissionInterface;
use Drupal\localgov_microsites_group\GroupPermissionsHelperInterface;
use Drupal\localgov_microsites_group\RolesHelper;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\User;

/**
 * @covers \Drupal\localgov_microsites_group\GroupPermisisonsHelper
 *
 * @group localgov_microsites_group
 */
class GroupPermissionsHelperTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'domain_group',
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
    'group_permissions',
    'group_term',
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
    $this->installEntitySchema('group_permission');
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
   * Test generating default content.
   */
  public function testGetPermissions() {
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $permission_entity = $permissions_helper->getGroupPermissions($this->group);
    assert($permission_entity instanceof GroupPermissionInterface);
    $permissions = $permission_entity->getPermissions();
    $this->assertEquals([
      'view group_media:document entity',
      'view group_media:image entity',
      'view group_media:remote_video entity',
      'view group_node:localgov_event entity',
      'view group_node:localgov_page entity',
      'view group_term:localgov_event_category entity',
      'view group_term:localgov_event_locality entity',
      'view group_term:localgov_event_price entity',
    ], $permissions['microsite-' . RolesHelper::GROUP_ANONYMOUS_ROLE]);
    $this->assertTrue($this->group->hasPermission('view group_node:localgov_page entity', User::getAnonymousUser()));
    $permissions['microsite-anonymous'] = [
      'view group_node:localgov_event entity',
    ];
    $permission_entity->setPermissions($permissions);
    $this->assertEmpty($permission_entity->validate());
    $permission_entity->save();
    drupal_flush_all_caches();
    $this->assertFalse($this->group->hasPermission('view group_node:localgov_page entity', User::getAnonymousUser()));
  }

  /**
   * Test enable disable module permissions.
   */
  public function testToggleModulePermissions() {
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals(GroupPermissionsHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
    $this->assertTrue($this->group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
    $permissions_helper->moduleDisable('localgov_microsites_events', $this->group);
    $this->assertEquals(GroupPermissionsHelperInterface::DISABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
    drupal_flush_all_caches();
    $this->assertFalse($this->group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
    $permissions_helper->moduleEnable('localgov_microsites_events', $this->group);
    $this->assertEquals(GroupPermissionsHelperInterface::ENABLED, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
    $this->assertTrue($this->group->hasPermission('view group_node:localgov_event entity', User::getAnonymousUser()));
  }

  /**
   * Test altered permissions.
   *
   * This is in a separate test to testToggleModulePermissions because of the
   * way the group permissions entity is cached in GroupPermissionsManager, and
   * hence GroupPermissionsHelper. Better might be investigating a patch for
   * GroupPermissionsManager that, if the cache is really needed, moves it into
   * a static cache value that can be invalidated from outside if needed to
   * change within a call.
   */
  public function testAlteredGroupPermissions() {
    $permission_entity = GroupPermission::create([
      'gid' => $this->group->id(),
    ]);
    $permissions = $permission_entity->getPermissions();
    $permissions['microsite-anonymous'] = ['view group_node:localgov_event entity'];
    $permission_entity->setPermissions($permissions);
    $permission_entity->validate();
    $permission_entity->save();
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals(GroupPermissionsHelperInterface::UNKNOWN, $permissions_helper->moduleStatus('localgov_microsites_events', $this->group));
  }

  /**
   * Test list of all available modules.
   */
  public function testModulesList() {
    $permissions_helper = $this->container->get('localgov_microsites_group.permissions_helper');
    $this->assertEquals([
      'localgov_microsites_events' => GroupPermissionsHelperInterface::ENABLED,
      'localgov_microsites_group' => GroupPermissionsHelperInterface::NOT_APPLICABLE,
    ], $permissions_helper->modulesList($this->group));
  }

}
