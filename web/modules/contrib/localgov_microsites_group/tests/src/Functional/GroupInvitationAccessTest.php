<?php

namespace Drupal\Tests\localgov_microsites_group\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\localgov_microsites_group\Traits\InitializeGroupsTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the group and group content access.
 *
 * @group domain_group
 */
class GroupInvitationAccessTest extends BrowserTestBase {

  use UserCreationTrait;
  use InitializeGroupsTrait;
  use AssertMailTrait;

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
    'domain_group',
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
    $this->ownerUser = $this->createUser();
    $this->adminUser = $this->createUser();
    $this->memberUser = $this->createUser();
    $this->otherUser = $this->createUser();
    $this->createMicrositeGroups([
      'uid' => $this->ownerUser->id(),
    ]);
    $this->groups[0]->addMember($this->adminUser, ['group_roles' => 'microsite-admin']);
    $this->groups[0]->addMember($this->memberUser);
    $this->createMicrositeGroupsDomains($this->groups);
  }

  /**
   * Test content access when unique group access is enabled.
   */
  public function testInvitationPermissions() {
    $group = $this->groups[0];

    // Admin can check invitations.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/group/' . $group->id() . '/invitations');
    $this->assertSession()->statusCodeEquals(200);

    // Ordinary member can't.
    $this->drupalLogin($this->memberUser);
    $this->drupalGet('/group/' . $group->id() . '/invitations');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/group/' . $group->id() . '/content/add/group_invitation');
    $this->assertSession()->statusCodeEquals(403);

    // Check admin can invite.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('/group/' . $group->id() . '/content/add/group_invitation');
    $this->submitForm([
      'invitee_mail[0][value]' => $this->otherUser->getEmail(),
    ], 'Save');
    $this->assertMailString('to', $this->otherUser->getEmail(), 1);
  }

}
