<?php

namespace Drupal\Tests\group_sites\Functional;

/**
 * Tests the behavior of the group sites admin mode controller.
 *
 * @group group_sites
 */
class GroupSitesAdminModeControllerTest extends GroupSitesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['views', 'node'];

  /**
   * The admin mode service.
   *
   * @var \Drupal\group_sites\GroupSitesAdminMode
   */
  protected $adminMode;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminMode = $this->container->get('group_sites.admin_mode');
    $this->config('system.site')->set('page.front', '/node')->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getGlobalPermissions(): array {
    return [
      'configure group_sites',
      'use group_sites admin mode',
      'access content',
    ];
  }

  /**
   * Tests that the admin mode activate link works and redirects.
   */
  public function testAdminModeActivate() {
    $this->setUpAccount();

    // Check that the switch works and redirect is performed.
    $this->assertFalse($this->adminMode->isActive());
    $this->drupalGet('/admin/group/sites/activate_admin_mode', ['query' => ['destination' => 'admin/group/sites/settings']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/admin/group/sites/settings');
    $this->assertTrue($this->adminMode->isActive());

    // Check the Forbidden result and no redirect.
    $this->drupalGet('/admin/group/sites/activate_admin_mode', ['query' => ['destination' => 'admin/group/sites/settings']]);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->addressEquals('/admin/group/sites/activate_admin_mode');

    // Try again and check the redirect to the front page.
    $this->adminMode->setAdminMode(FALSE);
    $this->drupalGet('/admin/group/sites/activate_admin_mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/');
    $this->assertTrue($this->adminMode->isActive());
  }

  /**
   * Tests that the admin mode deactivate link works and redirects.
   */
  public function testAdminModeDeactivate() {
    $this->setUpAccount();

    $this->adminMode->setAdminMode(TRUE);
    $this->drupalGet('/admin/group/sites/deactivate_admin_mode', ['query' => ['destination' => 'admin/group/sites/settings']]);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/admin/group/sites/settings');
    $this->assertFalse($this->adminMode->isActive());

    $this->drupalGet('/admin/group/sites/deactivate_admin_mode', ['query' => ['destination' => 'admin/group/sites/settings']]);
    $this->assertSession()->statusCodeEquals(403);
    $this->assertSession()->addressEquals('/admin/group/sites/deactivate_admin_mode');

    // Try again and check the redirect to the front page.
    $this->adminMode->setAdminMode(TRUE);
    $this->drupalGet('/admin/group/sites/deactivate_admin_mode');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('/');
    $this->assertFalse($this->adminMode->isActive());
  }

}
