<?php

namespace Drupal\Tests\group_sites\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;

/**
 * Provides a base class for Group Sites functional tests.
 */
abstract class GroupSitesBrowserTestBase extends GroupBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['group_sites'];

}
