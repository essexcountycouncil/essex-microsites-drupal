<?php

namespace Drupal\Tests\domain_group\Functional;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests access to settings forms.
 *
 * @group domain_group
 */
class DomainGroupFormAccessTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'block',
    'group',
    'domain',
    'domain_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser();
    $admin_role = $this->drupalCreateRole([
      'access administration pages',
      'access group overview',
      'administer account settings',
      'administer group',
      'administer users',
      'domain group settings',
    ]);
    $this->adminUser->addRole($admin_role);
    $this->adminUser->save();
    $this->testUser = $this->drupalCreateUser([]);

    // Setup the group types and test groups from the InitializeGroupsTrait.
    $this->initializeTestGroups();

    // Allow members to admin group domain settings.
    $this->groupTypeARole = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'edit group',
        'administer group domain site settings',
      ],
    ]);
    // Allow non-members with permissions to access group.
    $this->groupTypeAOutsideAdmin = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => $admin_role,
      'permissions' => [
        'view group',
        'edit group',
        'administer group domain settings',
        'administer group domain site settings',
      ],
    ]);

    // Do not allow member to admin group domain settings.
    $this->groupTypeBRole = $this->createGroupRole([
      'group_type' => $this->groupTypeB->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'edit group',
      ],
    ]);

    // Subscribe the testUser to groups 1 in both types (A/B).
    $this->groupA1->addMember($this->testUser, ['group_roles' => [$this->groupTypeARole->id()]]);
    $this->groupB1->addMember($this->testUser, ['group_roles' => [$this->groupTypeBRole->id()]]);

    // Add primary tabs.
    $this->drupalPlaceBlock('local_tasks_block', [
      'id' => 'tabs_block',
      'primary' => TRUE,
      'secondary' => FALSE,
    ]);
  }

  /**
   * Test group domain settings form.
   */
  public function testGroupSettingsAccess() {
    $this->drupalLogin($this->testUser);
    // User is able to access when has the right permissions.
    $this->drupalGet('group/' . $this->groupA1->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Domain status');
    $this->assertSession()->pageTextContains('Site front page');
    $this->groupA1->removeMember($this->testUser);
    $this->drupalGet('group/' . $this->groupA1->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(403);
    // Allow members to admin group domain settings.
    // Access denied when user doesn't have the permissions.
    $this->drupalGet('group/' . $this->groupB1->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('group/' . $this->groupA1->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Domain status');
    $this->assertSession()->pageTextContains('Site front page');
  }

  /**
   * Test group domain general settings form.
   */
  public function testModuleSettingsAccess() {
    // Testing anonymous access.
    $this->drupalGet('admin/config/domain/domain-group');
    $this->assertSession()->statusCodeEquals(403);
    // Testing group member.
    $this->drupalLogin($this->testUser);
    $this->drupalGet('admin/config/domain/domain-group');
    $this->assertSession()->statusCodeEquals(403);
    // Testing admin user.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/config/domain/domain-group');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test group domain settings tab.
   */
  public function testGroupSettingsTab() {
    $this->drupalLogin($this->testUser);
    // User is able to access when has the right permissions.
    $this->drupalGet('group/' . $this->groupA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    // User should see the link on primary tabs.
    $tabs = $this->assertSession()->elementExists('css', '#block-tabs-block');
    $tabs->hasLink('Domain settings');
  }

}
