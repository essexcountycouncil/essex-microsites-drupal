<?php

namespace Drupal\Tests\domain\Functional;

use Drupal\Tests\block\Traits\BlockCreationTrait;

/**
 * Tests the domain block visibility condition.
 *
 * @group domain
 */
class DomainContextTest extends DomainTestBase {

  use BlockCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'block'];

  /**
   * Test block context when no domains exist.
   *
   * See https://www.drupal.org/project/domain/issues/3201514.
   */
  public function testDomainBlockConfiguration() {
    $admin = $this->drupalCreateUser([
      'access administration pages',
      'administer blocks',
      'administer domains',
    ]);
    $this->drupalLogin($admin);

    // Try to configure a block.
    $url = 'admin/structure/block/manage/olivero_branding';
    $this->drupalGet($url);

    // Create one domain programmatically.
    $this->domainCreateTestDomains(1);

    // Try to configure a block.
    $url = 'admin/structure/block/manage/olivero_branding';
    $this->drupalGet($url);

  }

}
