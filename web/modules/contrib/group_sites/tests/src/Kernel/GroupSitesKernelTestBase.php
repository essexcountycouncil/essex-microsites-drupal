<?php

namespace Drupal\Tests\group_sites\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;

/**
 * Provides a base class for Group Sites kernel tests.
 */
abstract class GroupSitesKernelTestBase extends GroupKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_sites'];

}
