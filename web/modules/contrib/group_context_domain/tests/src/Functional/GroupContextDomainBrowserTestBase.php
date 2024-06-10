<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Functional;

use Drupal\Tests\group\Functional\GroupBrowserTestBase;
use Drupal\Tests\group_context_domain\Traits\GroupContextDomainTestTrait;

/**
 * Provides a base class for Group Context: Domain functional tests.
 */
abstract class GroupContextDomainBrowserTestBase extends GroupBrowserTestBase {

  use GroupContextDomainTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'group_context_domain'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
