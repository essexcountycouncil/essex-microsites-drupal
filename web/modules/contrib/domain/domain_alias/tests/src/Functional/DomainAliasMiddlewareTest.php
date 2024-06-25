<?php

namespace Drupal\Tests\domain_alias\Functional;

/**
 * Tests domain alias request negotiation with a middleware module.
 *
 * @see https://www.drupal.org/project/domain/issues/3199032
 *
 * @group domain_alias
 */
class DomainAliasMiddlewareTest extends DomainAliasNegotiatorTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'domain',
    'domain_alias',
    'user',
    'block',
    'domain_config',
    'domain_config_middleware_test'
  ];

}
