<?php

namespace Drupal\Tests\group_sites\Kernel;

use Drupal\group\PermissionScopeInterface;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;

/**
 * Tests the behavior of the single site access policy.
 *
 * @group group_sites
 */
class SingleSiteAccessPolicyTest extends GroupSitesKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_sites_test'];

  /**
   * The group permission calculator.
   *
   * @var \Drupal\group\Access\GroupPermissionCalculatorInterface
   */
  protected $groupPermissionCalculator;

  /**
   * The group sites admin mode.
   *
   * @var \Drupal\group_sites\GroupSitesAdminModeInterface
   */
  protected $groupSitesAdminMode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->groupPermissionCalculator = $this->container->get('group_permission.calculator');
    $this->groupSitesAdminMode = $this->container->get('group_sites.admin_mode');
  }

  /**
   * Tests that only the active site retains permissions.
   *
   * @param array $individual_permissions
   *   Permissions for the individual group role.
   * @param bool $individual_admin
   *   Admin flag for the individual group role.
   * @param array $insider_permissions
   *   Permissions for the insider group role.
   * @param bool $insider_admin
   *   Admin flag for the insider group role.
   * @param array $expected_permissions
   *   The expected permissions.
   * @param bool $expected_admin
   *   The expected admin flag.
   *
   * @covers ::alterPermissions
   * @dataProvider alterPermissionsProvider
   */
  public function testAlterPermissions(
    array $individual_permissions,
    bool $individual_admin,
    array $insider_permissions,
    bool $insider_admin,
    array $expected_permissions,
    bool $expected_admin,
  ) {
    $this->config('group_sites.settings')
      ->set('context_provider', '@group_sites_test.hardcoded_group_context:group')
      ->set('no_site_access_policy', 'group_sites.no_site_access_policy.do_nothing')
      ->set('site_access_policy', 'group_sites.site_access_policy.single')
      ->save();

    $this->createGroupType(['id' => 'foo', 'creator_membership' => FALSE]);

    $expected_scopes = [];
    $expected_total_count = 0;
    $expected_individual_count = 0;
    $expected_insider_count = 0;

    if ($individual_permissions || $individual_admin) {
      $this->createGroupRole([
        'id' => 'foo-editor',
        'group_type' => 'foo',
        'scope' => PermissionScopeInterface::INDIVIDUAL_ID,
        'permissions' => $individual_permissions,
        'admin' => $individual_admin,
      ]);

      $expected_scopes[] = PermissionScopeInterface::INDIVIDUAL_ID;
      $expected_total_count += 2;
      $expected_individual_count = 2;
    }

    if ($insider_permissions || $insider_admin) {
      $this->createGroupRole([
        'id' => 'foo-member',
        'group_type' => 'foo',
        'scope' => PermissionScopeInterface::INSIDER_ID,
        'global_role' => RoleInterface::AUTHENTICATED_ID,
        'permissions' => $insider_permissions,
        'admin' => $insider_admin,
      ]);

      $expected_scopes[] = PermissionScopeInterface::INSIDER_ID;
      $expected_total_count += 1;
      $expected_insider_count = 1;
    }

    $account = User::load($this->getCurrentUser()->id());

    $group = $this->createGroup(['type' => 'foo']);
    $group->addMember($account, ['group_roles' => ['foo-editor']]);
    $this->assertSame('1', $group->id(), 'Verified that first group has ID 1 for the context provider to work.');

    $group = $this->createGroup(['type' => 'foo']);
    $group->addMember($account, ['group_roles' => ['foo-editor']]);
    $this->assertNotSame('1', $group->id(), 'Verified that second group has ID other than 1.');

    $this->groupSitesAdminMode->setAdminModeOverride(TRUE);
    $original_calculated_permissions = $this->groupPermissionCalculator->calculateFullPermissions($account);
    $this->assertCount($expected_total_count, $original_calculated_permissions->getItems());
    $this->assertCount($expected_individual_count, $original_calculated_permissions->getItemsByScope(PermissionScopeInterface::INDIVIDUAL_ID));
    $this->assertCount($expected_insider_count, $original_calculated_permissions->getItemsByScope(PermissionScopeInterface::INSIDER_ID));
    $this->assertEqualsCanonicalizing($expected_scopes, $original_calculated_permissions->getScopes());
    $this->groupSitesAdminMode->setAdminModeOverride(FALSE);

    $expected_cache_contexts = $original_calculated_permissions->getCacheContexts();
    $expected_cache_tags = array_merge(
      $original_calculated_permissions->getCacheTags(),
      ['group:1', 'config:group_sites.settings']
    );

    $altered_calculated_permissions = $this->groupPermissionCalculator->calculateFullPermissions($account);
    $this->assertEqualsCanonicalizing($expected_cache_contexts, $altered_calculated_permissions->getCacheContexts());
    $this->assertEqualsCanonicalizing($expected_cache_tags, $altered_calculated_permissions->getCacheTags());

    $this->assertCount($expected_total_count === 0 ? 0 : 1, $altered_calculated_permissions->getItems(), 'Only one permissions item remains if anything was set for either scope.');
    if ($expected_total_count !== 0) {
      $item = $altered_calculated_permissions->getItem(PermissionScopeInterface::INDIVIDUAL_ID, 1);
      $this->assertNotFalse($item, 'The expected permissions item was found in the individual scope.');
      $this->assertEqualsCanonicalizing($expected_permissions, $item->getPermissions());
      $this->assertSame($expected_admin, $item->isAdmin());
    }
  }

  /**
   * Data provider for testAlterPermissions().
   *
   * @return array
   *   A list of testAlterPermissions method arguments.
   */
  public function alterPermissionsProvider(): array {
    $cases['individual-none-insider-none'] = [
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'expected_permissions' => [],
      'expected_admin' => FALSE,
    ];

    $cases['individual-none-insider-admin'] = [
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => TRUE,
      'expected_permissions' => [],
      'expected_admin' => TRUE,
    ];

    $cases['individual-none-insider-permissions'] = [
      'individual_permissions' => [],
      'individual_admin' => FALSE,
      'insider_permissions' => ['view group'],
      'insider_admin' => FALSE,
      'expected_permissions' => ['view group'],
      'expected_admin' => FALSE,
    ];

    $cases['individual-admin-insider-none'] = [
      'individual_permissions' => [],
      'individual_admin' => TRUE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'expected_permissions' => [],
      'expected_admin' => TRUE,
    ];

    $cases['individual-admin-insider-admin'] = [
      'individual_permissions' => [],
      'individual_admin' => TRUE,
      'insider_permissions' => [],
      'insider_admin' => TRUE,
      'expected_permissions' => [],
      'expected_admin' => TRUE,
    ];

    $cases['individual-admin-insider-permissions'] = [
      'individual_permissions' => [],
      'individual_admin' => TRUE,
      'insider_permissions' => ['view group'],
      'insider_admin' => FALSE,
      'expected_permissions' => [],
      'expected_admin' => TRUE,
    ];

    $cases['individual-permissions-insider-none'] = [
      'individual_permissions' => ['edit group'],
      'individual_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => FALSE,
      'expected_permissions' => ['edit group'],
      'expected_admin' => FALSE,
    ];

    $cases['individual-permissions-insider-admin'] = [
      'individual_permissions' => ['edit group'],
      'individual_admin' => FALSE,
      'insider_permissions' => [],
      'insider_admin' => TRUE,
      'expected_permissions' => [],
      'expected_admin' => TRUE,
    ];

    $cases['individual-permissions-insider-permissions'] = [
      'individual_permissions' => ['edit group'],
      'individual_admin' => FALSE,
      'insider_permissions' => ['view group'],
      'insider_admin' => FALSE,
      'expected_permissions' => ['edit group', 'view group'],
      'expected_admin' => FALSE,
    ];

    return $cases;
  }

}
