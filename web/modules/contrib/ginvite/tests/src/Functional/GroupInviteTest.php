<?php

namespace Drupal\Tests\ginvite\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the group invite functionality.
 *
 * @group group
 */
class GroupInviteTest extends GroupBrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'group',
    'group_test_config',
    'ginvite',
  ];

  /**
   * The group we will use to test methods on.
   *
   * @var \Drupal\group\Entity\Group
   */
  protected $group;

  /**
   * The normal user we will use.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $account;

  /**
   * Gets the global (site) permissions for the group creator.
   *
   * @return string[]
   *   The permissions.
   */
  protected function getGlobalPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access group overview',
      'create default group',
      'administer group',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->group = $this->createGroup([
      'uid' => $this->groupCreator->id(),
      'type' => 'default',
    ]);

    $this->account = $this->drupalCreateUser();
    $this->group->addMember($this->account);
    $this->group->save();
  }

  /**
   * Create invites and test general group invite behavior.
   */
  public function testInviteRolePermission() {
    $this->drupalLogin($this->groupCreator);

    // Install and configure the Group Invitation plugin.
    $this->drupalGet('/admin/group/content/install/default/group_invitation');
    $this->submitForm([], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // @todo get rid of this cache clear. But without it the group invitation
    // plugin config doesn't seem to be available.
    drupal_flush_all_caches();

    $this->drupalLogin($this->account);

    // Add permissions to invite users to members of the group.
    $member_role = $this->createGroupRole([
      'group_type' => $this->group->getGroupType()->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'invite users to group',
      ],
    ]);

    $custom_role = $this->createGroupRole([
      'group_type' => $this->group->getGroupType()->id(),
      'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
      'permissions' => [],
    ]);

    // Verify the user cannot add roles to users on invite.
    $this->drupalGet('/group/1/content/add/group_invitation');
    $this->assertSession()->fieldNotExists("group_roles[{$custom_role->id()}]");

    // Add permissions to administer members to members of the group.
    $member_role->grantPermissions(['administer members']);
    $member_role->save();

    // Verify the normal member without the permission cannot add roles to users on invite.
    $this->drupalGet('/group/1/content/add/group_invitation');
    $this->assertSession()->fieldExists("group_roles[{$custom_role->id()}]");
  }

}
