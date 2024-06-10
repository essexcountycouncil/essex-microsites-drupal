<?php

namespace Drupal\Tests\domain_group\Functional;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the group and group content access.
 *
 * @group domain_group
 */
class UniqueGroupAccessTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   *
   * @see \Drupal\Core\Config\Testing\ConfigSchemaChecker
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'block',
    'group',
    'gnode',
    'domain',
    'domain_group',
    'views'
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
  protected $testUser;

  /**
   * User administrator of group 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test user.
    $this->testUser = $this->drupalCreateUser([
      'access content',
      'access group overview',
    ]);

    // Create group admin.
    $this->user1 = $this->drupalCreateUser([
      'access content',
      'access group overview',
      'access content overview',
    ]);
    $this->user2 = $this->drupalCreateUser([
      'access content',
      'access group overview',
    ]);
    $this->user3 = $this->drupalCreateUser([
      'access content',
      'access group overview',
    ]);

    // Setup the group types and test groups from the InitializeGroupsTrait.
    $this->initializeTestGroups(
      ['uid' => $this->user1->id()],
      ['uid' => $this->user2->id()],
      ['uid' => $this->user3->id()],
    );
    $this->initializeTestGroupsDomains();
    $this->initializeTestGroupRelationship();

    // Allow anonymous to view groups of type A.
    $this->groupTypeAAnon = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::ANONYMOUS_ID,
      'permissions' => [
        'view group',
      ],
    ]);

    // Allow outsider to view group content article of type A.
    $this->groupTypeAAuth = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'view group_node:article entity',
      ],
    ]);

    // Allow member to view, edit, delete group content article of type A.
    $this->groupTypeARole = $this->createGroupRole([
      'group_type' => $this->groupTypeA->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'access content overview',
        'administer group',
        'administer members',
        'view group_node:article entity',
        'update any group_node:article entity',
        'delete any group_node:article entity',
      ],
    ]);

    // Allow anonymous to view groups, and content of type B.
    $this->groupTypeBAnon = $this->createGroupRole([
      'group_type' => $this->groupTypeB->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::ANONYMOUS_ID,
      'permissions' => [
        'view group',
        'view group_node:article entity',
      ],
    ]);

    // Allow outsider can't view group content article of type B.
    $this->groupTypeBAuth = $this->createGroupRole([
      'group_type' => $this->groupTypeB->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
      ],
    ]);

    // Allow member to view, edit, delete group content article of type B.
    $this->groupTypeBRole = $this->createGroupRole([
      'group_type' => $this->groupTypeB->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'view group',
        'access content overview',
        'administer group',
        'administer members',
        'view group_node:article entity',
        'update any group_node:article entity',
        'delete any group_node:article entity',
      ],
    ]);

  }

  /**
   * Test group access when unique group access is enabled.
   */
  public function testUniqueGroupAccess() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());

    $this->drupalLogin($this->testUser);
    // Listing
    $this->drupalGet('admin/group');
    $this->assertSession()->pageTextContains($this->groupA1->label());
    $this->assertSession()->pageTextContains($this->groupA2->label());
    // Listing
    $this->drupalGet($ga1_domain->getPath() . '/admin/group');
    $this->assertSession()->pageTextContains($this->groupA1->label());
    $this->assertSession()->pageTextNotContains($this->groupA2->label());
    // Listing
    $this->drupalGet($ga2_domain->getPath() . '/admin/group');
    $this->assertSession()->pageTextNotContains($this->groupA1->label());
    $this->assertSession()->pageTextContains($this->groupA2->label());

    $this->drupalLogout();
    // Visiting groups from default domain.
    $this->drupalGet('group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(200);

    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    // Visiting group A1 page.
    $this->drupalGet($ga1_domain->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA3->id());
    $this->assertSession()->statusCodeEquals(403);

    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    // Visiting group A2 page.
    $this->drupalGet($ga2_domain->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups should be denied.
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA3->id());
    $this->assertSession()->statusCodeEquals(403);
    
  }

  /**
   * Test group access when unique group access is disabled.
   */
  public function testUniqueGroupAccessDisabled() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());

    // Disable unique group access.
    $this->setUniqueGroupAccess(FALSE);

    // Visiting groups from default domain.
    $this->drupalGet('group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(200);

    // Visiting other groups from group domain should be allowed.
    $this->drupalGet($ga1_domain->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/group/' . $this->groupA3->id());
    $this->assertSession()->statusCodeEquals(200);

    // Visiting other groups from group domain should be allowed.
    $this->drupalGet($ga2_domain->getPath());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA2->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga2_domain->getPath() . '/group/' . $this->groupA3->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->drupalLogin($this->testUser);
    // Listing
    $this->drupalGet('admin/group');
    $this->assertSession()->pageTextContains($this->groupA1->label());
    $this->assertSession()->pageTextContains($this->groupA2->label());
    // Listing
    $this->drupalGet($ga1_domain->getPath() . '/admin/group');
    $this->assertSession()->pageTextContains($this->groupA1->label());
    $this->assertSession()->pageTextContains($this->groupA2->label());
    // Listing
    $this->drupalGet($ga2_domain->getPath() . '/admin/group');
    $this->assertSession()->pageTextContains($this->groupA1->label());
    $this->assertSession()->pageTextContains($this->groupA2->label());
  }

  /**
   * Test content access when unique group access is enabled.
   */
  public function testUniqueContentNodeAccess() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());

    // First as privileged user with access, then with less access to check
    // caching at the same time.
    $this->drupalLogin($this->user1);
    // Visiting groups from default domain.
    $this->drupalGet('node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet('node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    // Can edit own.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet('node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Can delete own.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet('node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());

    // Visiting group A1 content.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(403);
    // Editing own on correct domain.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting own on correct domain.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet($ga1_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextNotContains($this->nodeA2->label());
    $this->assertSession()->pageTextNotContains($this->nodeA3->label());

    // Visiting group A2 content.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet($ga2_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    $this->assertSession()->pageTextNotContains($this->nodeA1->label());
    $this->assertSession()->pageTextNotContains($this->nodeA3->label());
    // Editing own on other domain denied.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting own on other domain domain.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA3->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);

    // Same checks as user with only view permissions on the group.
    $this->drupalLogin($this->testUser);
    // Visiting groups from default domain.
    $this->drupalGet('node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Editing should be denied.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting should be denied.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());

    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    // Visiting group A1 content.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(403);
    // Editing should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet($ga1_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextNotContains($this->nodeA2->label());
    $this->assertSession()->pageTextNotContains($this->nodeA3->label());

    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    // Visiting group A2 content.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet($ga2_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    $this->assertSession()->pageTextNotContains($this->nodeA1->label());
    $this->assertSession()->pageTextNotContains($this->nodeA3->label());
  }

  /**
   * Test content access when unique group access is disabled.
   */
  public function testUniqueContentNodeAccessDisabled() {
    $this->drupalLogin($this->testUser);
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    // Disable unique group access.
    $this->setUniqueGroupAccess(FALSE);

    // Visiting groups from default domain.
    $this->drupalGet('node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->drupalGet('node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Editing should be denied.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting should be denied.
    $this->drupalGet('node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet('node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());

    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    // Visiting group A1 content.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(200);
    // Editing should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    // Deleting should be denied.
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA1->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA2->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . '/node/' . $this->nodeA3->id() . '/delete');
    $this->assertSession()->statusCodeEquals(403);
    // Content list.
    $this->drupalGet($ga1_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    $this->assertSession()->pageTextContains($this->nodeA3->label());

    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    // Visiting group A2 content.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA2->id());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($ga2_domain->getPath() . '/node/' . $this->nodeA3->id());
    $this->assertSession()->statusCodeEquals(200);
    // Content list.
    $this->drupalGet($ga2_domain->getPath() . '/node');
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    $this->assertSession()->pageTextContains($this->nodeA3->label());
  }

  /**
   * Test content access when unique group access is enabled.
   */
  public function testUniqueGroupRelationshipAccess() {
    $domain_storage = \Drupal::entityTypeManager()->getStorage('domain');
    $ga1_domain = $domain_storage->load('group_' . $this->groupA1->id());
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    $gb1_domain = $domain_storage->load('group_' . $this->groupB1->id());
    $gb2_domain = $domain_storage->load('group_' . $this->groupB2->id());

    $a1_content = $this->groupA1->getRelatedEntities('group_node:article');
    $this->assertCount(1, $a1_content);
    $a1_content = reset($a1_content);
    $a2_content = $this->groupA2->getRelatedEntities('group_node:article');
    $this->assertCount(1, $a2_content);
    $a2_content = reset($a2_content);
    $b1_content = $this->groupB1->getRelatedEntities('group_node:article');
    $this->assertCount(1, $b1_content);
    $b1_content = reset($b1_content);
    $b2_content = $this->groupB2->getRelatedEntities('group_node:article');
    $this->assertCount(1, $b2_content);
    $b2_content = reset($b2_content);

    // First as group A1 user.
    $this->drupalLogin($this->user1);
    // Default domain content accessible.
    $this->drupalGet($a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // But outsider has access to content on other A group.
    $this->drupalGet($a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // Group B own group.
    $this->drupalGet($b1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // But outsider doesn't have access to content on other group.
    $this->drupalGet($b2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);

    // And edit own.
    $this->drupalGet($a1_content->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($a1_content->toUrl('delete-form')->toString());
    $this->assertSession()->statusCodeEquals(200);
    // Other not.
    $this->drupalGet($a2_content->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($a2_content->toUrl('delete-form')->toString());
    $this->assertSession()->statusCodeEquals(403);

    // Visiting group A1 content.
    $this->drupalGet($ga1_domain->getPath() . $a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // Visiting other groups' content should be denied.
    $this->drupalGet($ga1_domain->getPath() . $a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    // Edit delete own on domain.
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($ga1_domain->getPath() . $a1_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(200);
      $this->drupalGet($ga1_domain->getPath() . $a2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }

    // Can't access group 1 that would be visible but on domain 2.
    $this->drupalGet($ga2_domain->getPath() . $a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    // Can view group A content as outsider on correct domain.
    $this->drupalGet($ga2_domain->getPath() . $a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // Edit delete own not as not on domain.
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($ga2_domain->getPath() . $a1_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
      $this->drupalGet($ga2_domain->getPath() . $a2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }

    // Content list.
    $this->drupalGet('admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextContains($this->nodeA2->label());
    $this->assertSession()->pageTextContains($this->nodeB1->label());
    $this->assertSession()->pageTextNotContains($this->nodeB2->label());
    // But on domain only domain content.
    $this->drupalGet($ga1_domain->getPath() . '/admin/content');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains($this->nodeA1->label());
    $this->assertSession()->pageTextNotContains($this->nodeA2->label());
    $this->assertSession()->pageTextNotContains($this->nodeB1->label());
    $this->assertSession()->pageTextNotContains($this->nodeB2->label());

    //
    // Then as anon with access to group content entities.
    //
    $this->drupalLogout();
    // Default domain A content inaccessible.
    $this->drupalGet($a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($b1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($b2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    // Default domain B content accessible.
    // But edit and delete not.
    $this->drupalGet($a1_content->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($a2_content->toUrl('delete-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($b1_content->toUrl('edit-form')->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($b2_content->toUrl('delete-form')->toString());
    $this->assertSession()->statusCodeEquals(403);

    // Visiting group A content all denied.
    $this->drupalGet($ga1_domain->getPath() . $a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga1_domain->getPath() . $a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($ga1_domain->getPath() . $a1_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
      $this->drupalGet($ga1_domain->getPath() . $a2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    $this->drupalGet($ga2_domain->getPath() . $a2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($ga2_domain->getPath() . $a1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($ga2_domain->getPath() . $a1_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
      $this->drupalGet($ga2_domain->getPath() . $a2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }

    // Visiting group B content. View only on correct domain.
    $this->drupalGet($gb1_domain->getPath() . $b1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($gb1_domain->getPath() . $b2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($gb1_domain->getPath() . $b1_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
      $this->drupalGet($gb1_domain->getPath() . $b2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }
    $ga2_domain = $domain_storage->load('group_' . $this->groupA2->id());
    $this->drupalGet($gb2_domain->getPath() . $b1_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($gb2_domain->getPath() . $b2_content->toUrl()->toString());
    $this->assertSession()->statusCodeEquals(200);
    foreach (['edit-form', 'delete-form'] as $form) { 
      $this->drupalGet($gb2_domain->getPath() . $b2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
      $this->drupalGet($gb2_domain->getPath() . $b2_content->toUrl($form)->toString());
      $this->assertSession()->statusCodeEquals(403);
    }
  }


}
