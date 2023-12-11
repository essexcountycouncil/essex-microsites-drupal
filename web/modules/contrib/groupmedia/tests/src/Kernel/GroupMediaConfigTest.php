<?php

namespace Drupal\Tests\groupmedia\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests that all config provided by this module passes validation.
 *
 * @group groupmedia
 */
class GroupMediaConfigTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'group',
    'options',
    'entity',
    'image',
    'variationcache',
    'media',
    'groupmedia',
    'views'
  ];

  /**
   * Tests that the module's config installs properly.
   */
  public function testConfig() {
    $this->installConfig(['groupmedia']);
  }

}
