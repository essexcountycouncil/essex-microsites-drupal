<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Core\Url;
use Drupal\domain\DomainInterface;
use Drupal\localgov_microsites_group\DomainFromGroupTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\GroupCreationTrait;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the group and group content access.
 *
 * @group localgov_microsites_group
 */
class GroupAdminAccessTest extends BrowserTestBase {

  use UserCreationTrait;
  use InitializeGroupsTrait;
  use AssertMailTrait;
  use GroupCreationTrait, DomainFromGroupTrait {
    GroupCreationTrait::getEntityTypeManager insteadof DomainFromGroupTrait;
  }
  /**
   * Will be removed when issue #3204455 on Domain Site Settings gets merged.
   *
   * See https://www.drupal.org/project/domain_site_settings/issues/3204455.
   *
   * @var bool
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   * phpcs:disable DrupalPractice.Objects.StrictSchemaDisabled.StrictConfigSchema
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
    'localgov_microsites_group',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'localgov_base';

  /**
   * Regular authenticated User for tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $otherUser;

  /**
   * User administrator of group 1.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Test uses groups on control domain, so disable group sites.
    $this->ownerUser = $this->createUser(['use group_sites admin mode']);
    $this->adminUser1 = $this->createUser(['use group_sites admin mode']);
    $this->adminUser2 = $this->createUser(['use group_sites admin mode']);
    $this->memberUser1 = $this->createUser(['use group_sites admin mode']);
    $this->memberUser2 = $this->createUser(['use group_sites admin mode']);
    $this->otherUser = $this->createUser(['use group_sites admin mode']);
    $this->createMicrositeGroups([
      'uid' => $this->ownerUser->id(),
    ]);
    $this->groups[1]->addMember($this->adminUser1, ['group_roles' => 'microsite-admin']);
    $this->groups[2]->addMember($this->adminUser2, ['group_roles' => 'microsite-admin']);

    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test access to manage content.
   */
  public function testGroupAdminAccess() {
    $group1 = $this->groups[1];
    $group2 = $this->groups[2];

    // Admin1 can access content on site 1.
    $group1_domain = $this->getDomainFromGroup($group1);
    $group2_domain = $this->getDomainFromGroup($group2);
    assert($group1_domain instanceof DomainInterface);
    $this->drupalGet($group1_domain->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $this->adminUser1->getAccountName(),
      'pass' => $this->adminUser1->passRaw,
    ], 'Log in');

    // Confirm that adminUser1 can manage content and entities on group2.
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id());
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/nodes');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/menus');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet($group1_domain->getUrl() . '/group/' . $group1->id() . '/members');
    $this->assertSession()->statusCodeEquals(200);

    // First login to group2 domain as adminUser1.
    $this->drupalGet($group2_domain->getUrl() . Url::fromRoute('user.login')->toString());
    $this->submitForm([
      'name' => $this->adminUser1->getAccountName(),
      'pass' => $this->adminUser1->passRaw,
    ], 'Log in');

    // Now confirm that adminUser1 cannot manage content and settings on group2.
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id());
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/nodes');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/domain-settings');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/menus');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/members');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet($group2_domain->getUrl() . '/group/' . $group2->id() . '/nodes');

  }

}
