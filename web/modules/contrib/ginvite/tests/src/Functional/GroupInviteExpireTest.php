<?php

namespace Drupal\Tests\ginvite\Functional;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\group\Entity\GroupRelationship;
use Drupal\group\PermissionScopeInterface;
use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the group invite expire functionality.
 *
 * @group group
 */
class GroupInviteExpireTest extends GroupBrowserTestBase {

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
   * The cron service.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

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

    // Add permissions to the creator of the group.
    $this->createGroupRole([
      'group_type' => $this->group->getGroupType()->id(),
      'scope' => PermissionScopeInterface::INSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => [
        'invite users to group',
      ],
    ]);

    $this->cron = \Drupal::service('cron');
  }

  /**
   * Create invites and let them expire.
   */
  public function testExpireInvites() {
    $this->drupalLogin($this->groupCreator);
    $expire_days = 14;

    // Install and configure the Group Invitation plugin.
    $this->drupalGet('/admin/group/content/install/default/group_invitation');
    $this->assertSession()->fieldExists('invitation_expire');
    $this->submitForm(['invitation_expire' => $expire_days], 'Install plugin');
    $this->assertSession()->statusCodeEquals(200);

    // @todo get rid of this cache clear. But without it the group invitation
    // plugin config doesn't seem to be available.
    drupal_flush_all_caches();

    // Create an invitation.
    $this->drupalGet('/group/1/content/add/group_invitation');
    $this->submitForm(['invitee_mail[0][value]' => 'test@test.local'], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Create another invite.
    $this->drupalGet('/group/1/content/add/group_invitation');
    $this->submitForm(['invitee_mail[0][value]' => 'test2@test.local'], 'Save');
    $this->assertSession()->statusCodeEquals(200);

    // Expire the first invitation.
    /** @var \Drupal\group\Entity\GroupRelationshipInterface $invite */
    $invite = GroupRelationship::load(2);
    $invite->set('created', ($expire_days * 86400) - 1);
    $invite->save();

    // Run the cron.
    $this->container
      ->get('state')
      ->set('ginvite.last_expire_removal', 0);
    $this->cron->run();

    // We forced the first invitation to expire, so that one should be deleted.
    $invite = GroupRelationship::load(2);
    $this->assertNull($invite);

    // Nothing changed here, should still be available.
    $invite = GroupRelationship::load(3);
    $this->assertIsObject($invite);
  }

}
