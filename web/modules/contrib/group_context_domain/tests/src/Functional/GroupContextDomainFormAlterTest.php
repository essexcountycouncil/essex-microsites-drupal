<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Functional;

use Drupal\domain\DomainInterface;
use Drupal\group\PermissionScopeInterface;
use Drupal\user\RoleInterface;

/**
 * Tests the form alterations of the domain entity form.
 *
 * @group group_context_domain
 */
class GroupContextDomainFormAlterTest extends GroupContextDomainBrowserTestBase {

  /**
   * Data to submit on a Domain form.
   *
   * @var array
   */
  protected $formData = [
    'hostname' => 'subdomain.example.com',
    'name' => 'Test domain',
    'validate_url' => FALSE,
  ];

  /**
   * Checks that no alterations take place if you do not have the permission.
   */
  public function testFormWithoutPermission(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer domains']));
    $this->drupalGet('admin/config/domain/add');
    $this->assertSession()->pageTextNotContains('Select the group that represents this domain.');
  }

  /**
   * Checks that the alterations take place if you have the permission.
   */
  public function testFormWithPermission(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer domains', 'set domain group']));
    $this->drupalGet('admin/config/domain/add');
    $this->assertSession()->pageTextContains('Select the group that represents this domain.');
  }

  /**
   * Checks that the domain's group can be set.
   *
   * @depends testFormWithPermission
   */
  public function testSetDomainGroup(): void {
    $group_type = $this->createGroupType();
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['edit group'],
    ]);

    $group = $this->createGroup(['type' => $group_type->id()]);
    $domain = $this->createDomain();

    $this->drupalLogin($this->drupalCreateUser(['administer domains', 'set domain group']));
    $this->drupalGet('admin/config/domain/edit/' . $domain->id());
    $this->submitForm(['group_uuid' => $group->uuid()] + $this->formData, 'Save');

    $storage = $this->entityTypeManager->getStorage('domain');
    $storage->resetCache();

    $domain = $storage->load($domain->id());
    assert($domain instanceof DomainInterface);
    $this->assertSame($group->uuid(), $domain->getThirdPartySetting('group_context_domain', 'group_uuid'));
  }

  /**
   * Checks that the domain's group can be unset.
   *
   * @depends testFormWithPermission
   */
  public function testUnsetDomainGroup(): void {
    $group_type = $this->createGroupType();
    $this->createGroupRole([
      'group_type' => $group_type->id(),
      'scope' => PermissionScopeInterface::OUTSIDER_ID,
      'global_role' => RoleInterface::AUTHENTICATED_ID,
      'permissions' => ['edit group'],
    ]);

    $group = $this->createGroup(['type' => $group_type->id()]);
    $domain = $this->createDomain(['third_party_settings' => ['group_context_domain' => ['group_uuid' => $group->uuid()]]]);

    $this->drupalLogin($this->drupalCreateUser(['administer domains', 'set domain group']));
    $this->drupalGet('admin/config/domain/edit/' . $domain->id());
    $this->submitForm(['group_uuid' => ''] + $this->formData, 'Save');

    $storage = $this->entityTypeManager->getStorage('domain');
    $storage->resetCache();

    $domain = $storage->load($domain->id());
    assert($domain instanceof DomainInterface);
    $this->assertNull($domain->getThirdPartySetting('group_context_domain', 'group_uuid'));
  }

}
