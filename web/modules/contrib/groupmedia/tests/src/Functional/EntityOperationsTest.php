<?php

namespace Drupal\Tests\groupmedia\Functional;

use Drupal\group\Entity\GroupType;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Functional\EntityOperationsTest as GroupEntityOperationsTest;
use Drupal\Tests\media\Traits\MediaTypeCreationTrait;
use Drupal\user\RoleInterface;

/**
 * Tests that entity operations (do not) show up on the group overview.
 *
 * @see groupmedia_entity_operation()
 *
 * @group groupmedia
 */
class EntityOperationsTest extends GroupEntityOperationsTest {

  use MediaTypeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'flexible_permissions',
    'group',
    'group_test_config',
    'groupmedia',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $group_type = GroupType::load('default');
    // Allow outsider request membership.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view published group'],
    ]);
    // Allow outsider request membership.
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['view published group'],
    ]);

  }

  /**
   * Checks for entity operations under given circumstances.
   *
   * Overriding the parent to provide an extra parameter to the data provider.
   *
   * @param array $visible
   *   The expected visible links.
   * @param array $invisible
   *   The expected invisible links.
   * @param string[] $permissions
   *   A list of group permissions to assign to the user.
   * @param string[] $modules
   *   A list of modules to enable.
   * @param bool $has_media
   *   Whether there are any media types enabled as group content.
   *
   * @dataProvider provideEntityOperationScenarios
   */
  public function testEntityOperations($visible, $invisible, $permissions = [], $modules = [], $has_media = FALSE) {
    $group_type = $this->createGroupType();
    if ($has_media) {
      // Create a media type and enable it as group content.
      $media_type = $this->createMediaType('image');
      $media_type->save();
      $this->entityTypeManager
        ->getStorage('group_relationship_type')
        ->createFromPlugin($group_type, 'group_media:' . $media_type->id(), [
          'group_cardinality' => 0,
          'entity_cardinality' => 1,
          'use_creation_wizard' => FALSE,
        ])
        ->save();
    }

    $this->createGroup(['type' => $group_type->id()]);

    if (!empty($permissions)) {
      $this->createGroupRole([
        'group_type' => $group_type->id(),
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $permissions,
      ]);
    }

    if (!empty($modules)) {
      $this->container->get('module_installer')->install($modules, TRUE);
    }

    $this->drupalGet('admin/group');

    foreach ($visible as $path => $label) {
      $this->assertSession()->linkExists($label);
      $this->assertSession()->linkByHrefExists($path);
    }

    foreach ($invisible as $path => $label) {
      $this->assertSession()->linkNotExists($label);
      $this->assertSession()->linkByHrefNotExists($path);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function provideEntityOperationScenarios() {
    $scenarios['withoutAccess'] = [
      [],
      ['group/1/media' => 'Media'],
    ];

    $scenarios['withAccess'] = [
      [],
      ['group/1/media' => 'Media'],
      [
        'view group',
        'access group_media overview',
      ],
    ];

    $scenarios['withAccessAndViewsNoMedia'] = [
      [],
      ['group/1/media' => 'Media'],
      [
        'view group',
        'access group_media overview',
      ],
      ['views'],
    ];

    $scenarios['withAccessAndViewsAndMedia'] = [
      ['group/1/media' => 'Media'],
      [],
      [
        'view group',
        'access group_media overview',
      ],
      ['views'],
      TRUE,
    ];

    return $scenarios;
  }

}
