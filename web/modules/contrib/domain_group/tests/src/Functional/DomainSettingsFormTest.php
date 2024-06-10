<?php

namespace Drupal\Tests\domain_group\Functional;

use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\domain_group\Traits\GroupCreationTrait;
use Drupal\Tests\domain_group\Traits\InitializeGroupsTrait;
use Drupal\user\RoleInterface;

/**
 * Tests Domain Settings form.
 *
 * @group domain_group
 */
class DomainSettingsFormTest extends BrowserTestBase {

  use GroupCreationTrait;
  use InitializeGroupsTrait;

  /**
   * Domain Config doesn't, probably can't define a schema for all possible
   * configuration overrides. Maybe it using collections might be the fix. 
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
   * User with group and domain admin.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Regular authenticated User to be member of group.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $groupUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser();
    $admin_role = $this->drupalCreateRole([
      'access administration pages',
      'access group overview',
      'administer group',
      'domain group settings',
    ]);
    $this->adminUser->addRole($admin_role);
    $this->adminUser->save();
    $this->groupUser = $this->drupalCreateUser([]);

    // Setup the group types and test groups from the InitializeGroupsTrait.
    $this->initializeTestGroups();
    // Allow members to admin group domain settings
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

    $this->groupA1->addMember($this->groupUser, ['group_roles' => [$this->groupTypeARole->id()]]);
  }

  /**
   * Test group domain settings form.
   */
  public function testDomainGroupForm() {
    $storage = \Drupal::entityTypeManager()->getStorage('domain');
    // No domains should exist.
    $this->domainTableIsEmpty();
    // Going to domain group settings form.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('group/' . $this->groupA1->id() . '/domain-settings');
    $this->assertSession()->pageTextContains($this->groupA1->label() . ' - Domain Settings');
    // Filling form.
    $admin_form = [
      'hostname' => 'group-a1.' . $this->baseHostname,
      'site_name' => $this->groupA1->label(),
      'site_mail' => 'group-a1@user.com',
    ];
    $this->submitForm($admin_form, 'Submit');
    // First save should fail.
    $this->assertSession()->pageTextContains('In order to enable this Organization domain, a Default one should be set.');
    // Create default domain programmatically.
    $domain = $storage->create();
    $domain->set('id', $storage->createMachineName($domain->getHostname()));
    $keys = [
      'id',
      'name',
      'hostname',
      'scheme',
      'status',
      'weight',
      'is_default',
    ];
    foreach ($keys as $key) {
      $property = $domain->get($key);
      $this->assertNotNull($property, 'Property loaded');
    }
    $domain->save();
    // Resubmit form.
    $this->submitForm($admin_form, 'Submit');
    // Did it save correctly?
    $this->assertSession()->pageTextContains('Changes saved');

    // Check settings saved to domain and config.
    $group_domain = $storage->load('group_' . $this->groupA1->id());
    $group_domain_config = \Drupal::config('domain.config.' . $group_domain->id() . '.system.site');
    $this->assertEquals($admin_form['hostname'], $group_domain->get('hostname'));
    $this->assertEquals($admin_form['site_name'], $group_domain_config->get('name'));
    $this->assertEquals($admin_form['site_mail'], $group_domain_config->get('mail'));
    // Front page defaults to the group page.
    $this->assertEquals('/group/' . $this->groupA1->id(), $group_domain_config->get('page.front'));
    $this->assertEmpty($group_domain_config->get('slogan'));

    // Group user with permission for only part of the form.
    $this->drupalLogin($this->groupUser);
    $this->drupalGet('group/' . $this->groupA1->id() . '/domain-settings');
    $this->assertSession()->pageTextContains($this->groupA1->label() . ' - Domain Settings');
    $this->submitForm([
      'site_name' => 'New site name',
      'site_slogan' => 'Slogan',
    ], 'Submit');
    $this->assertSession()->pageTextContains('Changes saved');
    // Check settings saved to domain and config.
    $storage->resetCache();
    $group_domain = $storage->load('group_' . $this->groupA1->id());
    $group_domain_config = \Drupal::config('domain.config.' . $group_domain->id() . '.system.site');
    $this->assertEquals($admin_form['hostname'], $group_domain->get('hostname'));
    $this->assertEquals('New site name', $group_domain_config->get('name'));
    $this->assertEquals($admin_form['site_mail'], $group_domain_config->get('mail'));
    $this->assertEquals('Slogan', $group_domain_config->get('slogan'));
  }

}
