<?php

namespace Drupal\Tests\domain\Functional;

/**
 * Tests the domain access plugin for Views.
 *
 * @group domain
 */
class DomainViewsAccessTest extends DomainTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'node', 'views', 'block'];

  /**
   * Test inactive domain.
   */
  public function testInactiveDomain() {
    // Create five new domains programmatically.
    $this->domainCreateTestDomains(5);
    $domains = \Drupal::entityTypeManager()->getStorage('domain')->loadMultiple();
    // Enable the views.
    $this->enableViewsTestModule();
    // Create a user. To test the area output was more difficult, so we just
    // configured two views. The page shows the first, admin, user, and the
    // block will show this new user name.
    $perms = ['administer domains', 'create domains'];
    $admin_user = $this->drupalCreateUser($perms);
    // Place the view block.
    $this->drupalPlaceBlock('views_block:domain_views_access-block_1');

    // The block and page should be visible on example_com and one_example_com.
    $allowed = ['example_com', 'one_example_com'];

    foreach ($domains as $domain) {
      $path = $domain->getPath() . 'domain-views-access';
      $this->DrupalGet($path);
      if (in_array($domain->id(), $allowed, TRUE)) {
        $this->assertSession()->statusCodeEquals(200);
        $this->assertSession()->pageTextContains('admin');
        $this->assertSession()->pageTextContains($admin_user->getAccountName());
      }
      else {
        $this->assertSession()->statusCodeEquals(403);
        $this->assertSession()->pageTextNotContains('admin');
        $this->assertSession()->pageTextNotContains($admin_user->getAccountName());
      }
      // Test the block on another page.
      $this->drupalGet($domain->getPath());
      if (in_array($domain->id(), $allowed, TRUE)) {
        $this->assertSession()->pageTextContains($admin_user->getAccountName());
      }
      else {
        $this->assertSession()->pageTextNotContains($admin_user->getAccountName());
      }
    }
  }

  /**
   * Sets up the domain_test module.
   *
   * Because the schema of domain_test.module is dependent on the test
   * using it, it cannot be enabled normally.
   */
  protected function enableViewsTestModule() {
    \Drupal::service('module_installer')->install(['domain_test']);
    $this->resetAll();
    $this->rebuildContainer();
    $this->container->get('module_handler')->reload();
  }

}
