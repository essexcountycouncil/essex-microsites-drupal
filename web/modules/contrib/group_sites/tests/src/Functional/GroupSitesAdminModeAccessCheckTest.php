<?php

namespace Drupal\Tests\group_sites\Functional;

/**
 * Tests the behavior of the group sites admin mode access check.
 *
 * @group group_sites
 */
class GroupSitesAdminModeAccessCheckTest extends GroupSitesBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_sites_test'];

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
  }

  /**
   * {@inheritdoc}
   */
  protected function getGlobalPermissions() {
    return ['use group_sites admin mode'];
  }

  /**
   * Tests that the access check works when admin mode is required.
   */
  public function testAdminModeRequired() {
    $this->setUpAccount();

    $this->adminMode->setAdminMode(TRUE);
    $this->drupalGet('/admin/group/sites/test/admin_mode_required');
    $this->assertSession()->statusCodeEquals(200);

    $this->adminMode->setAdminMode(FALSE);
    $this->drupalGet('/admin/group/sites/test/admin_mode_required');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests that the access check works when admin mode is forbidden.
   */
  public function testAdminModeForbidden() {
    $this->setUpAccount();

    $this->adminMode->setAdminMode(TRUE);
    $this->drupalGet('/admin/group/sites/test/admin_mode_forbidden');
    $this->assertSession()->statusCodeEquals(403);

    $this->adminMode->setAdminMode(FALSE);
    $this->drupalGet('/admin/group/sites/test/admin_mode_forbidden');
    $this->assertSession()->statusCodeEquals(200);
  }

}
