<?php

declare(strict_types=1);

namespace Drupal\Tests\group_context_domain\Kernel;

use Drupal\Tests\group\Kernel\GroupKernelTestBase;
use Drupal\Tests\group_context_domain\Traits\GroupContextDomainTestTrait;

/**
 * Defines an abstract test base for group kernel tests.
 */
abstract class GroupContextDomainKernelTestBase extends GroupKernelTestBase {

  use GroupContextDomainTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['domain', 'group_context_domain'];

}
