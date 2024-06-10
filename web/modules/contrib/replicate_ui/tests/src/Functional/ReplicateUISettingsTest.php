<?php

namespace Drupal\Tests\replicate_ui\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the replicate settings UI.
 *
 * @group replicate_ui
 */
class ReplicateUISettingsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['replicate', 'replicate_ui', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  public function testSettings() {
    $this->drupalGet('/admin/config/content/replicate');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/config/content/replicate');
    $this->assertSession()->statusCodeEquals(200);

    $this->submitForm(['entity_types[node]' => 'node'], 'Save configuration');
    $this->assertEquals(['node'], \Drupal::configFactory()->get('replicate_ui.settings')->get('entity_types'));
  }

}
