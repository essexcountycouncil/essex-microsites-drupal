<?php

namespace Drupal\Tests\multiple_select\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test module.
 *
 * @group multiple_select
 */
class CrudFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'multiple_select',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

  /**
   * Test access to site.
   */
  public function testConfigurationForm() {
    // Going to the config page.
    $this->drupalGet('/admin/config/content/multiple-config');

    // Checking that the page is not accesible for anonymous users.
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->drupalCreateUser([
      'access multiple select config page',
      'administer site configuration',
      'access content',
    ]);

    $this->drupalLogin($account);

    // Going to the config page.
    $this->drupalGet('/admin/config/content/multiple-config');

    // Checking the page title.
    $this->assertSession()->elementTextContains('css', 'h1', 'Multiple Select Helper');

  }

  /**
   * Test access to configuration page.
   */
  public function testCanAccessConfigPage() {
    $account = $this->drupalCreateUser([
      'access multiple select config page',
      'administer site configuration',
      'access content',
    ]);

    $this->drupalLogin($account);
    $this->drupalGet('/admin/config/content/multiple-config');
    $this->assertSession()->pageTextContains('Multiple Select Helper');
  }

}
