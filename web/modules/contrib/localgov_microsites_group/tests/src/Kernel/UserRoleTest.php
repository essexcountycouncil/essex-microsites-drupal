<?php

namespace Drupal\Tests\localgov_microsites_group\Kernel;

use Drupal\group\Entity\Group;
use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\user\Entity\User;

/**
 * Test the addition and removal of Drupal roles to group members.
 *
 * @group localgov_microsites_group
 */
class UserRoleTest extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'gnode',
    'group',
    'group_content_menu',
    'group_sites',
    'image',
    'media',
    'media_library',
    'node',
    'replicate',
    'views',
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('group');
    $this->installEntitySchema('group_relationship');
    $this->installEntitySchema('user');
    $this->installConfig([
      'group',
      'user',
    ]);

    $this->createUser();
    $this->setCurrentUser($this->createUser());

    $storage = $this->entityTypeManager->getStorage('group_type');
    $group_type = $storage->create([
      'id' => 'microsite',
      'label' => 'Microsite',
    ]);
    $storage->save($group_type);
  }

  /**
   * Test the addition and removal of Trusted Editor role to group members.
   */
  public function testTrustedEditor() {

    // Create some groups.
    $group1 = Group::create([
      'label' => 'Microsite 1',
      'type' => 'microsite',
    ]);
    $group1->save();
    $group2 = Group::create([
      'label' => 'Microsite 2',
      'type' => 'microsite',
    ]);
    $group2->save();

    // Create a user.
    $user = User::create([
      'name' => $this->randomString(),
      'mail' => $this->randomMachineName() . '@example.com',
    ]);
    $user->save();

    // Expected roles.
    $roles_outsider = [
      'authenticated',
    ];
    $roles_member = [
      'authenticated',
      'microsites_trusted_editor',
    ];

    $user = User::load($user->id());
    $this->assertSame($roles_outsider, $user->getRoles());

    // Ensure user gets Trusted Editor role.
    $group1->addRelationship($user, 'group_membership');
    $user = User::load($user->id());
    $this->assertSame($roles_member, $user->getRoles());
    $group2->addMember($user);
    $user = User::load($user->id());
    $this->assertSame($roles_member, $user->getRoles());

    // Ensure Trusted Editor role is removed correctly.
    $group1->removeMember($user);
    $user = User::load($user->id());
    $this->assertSame($roles_member, $user->getRoles());
    $group2->removeMember($user);
    $user = User::load($user->id());
    $this->assertSame($roles_outsider, $user->getRoles());
  }

}
